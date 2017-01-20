<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Worker\Claims;

use \Carbon\Carbon;
use \Exception;
use \Kicken\Gearman\Job\WorkerJob;
use \SunCoastConnection\ClaimsToEMRGearman\Worker;
use \SunCoastConnection\ClaimsToEMR\Cache;
use \SunCoastConnection\ClaimsToEMR\Models\PqrsImportFiles;
use \SunCoastConnection\ClaimsToEMR\Store\Database;
use \SunCoastConnection\ClaimsToEMR\X12N837;
use \SunCoastConnection\ParseX12\Raw;

class Process extends Worker {

	/**
	 * Run the Process Worker
	 *
	 * @param  \Kicken\Gearman\Job\WorkerJob  $job  Job request to perform run on
	 *
	 * @return integer  Return code:
	 *     1 = Failed looking up claims
	 */
	public function run(WorkerJob $job, &$log) {
		$workload = json_decode($job->getWorkload(), true);

		// $workload = [
		// 	'client' => 'tokenName',
		// 	'fileId' => 123,
		// ];

		try {
			$this->loadConfigurations($workload);

			$this->processClaim($workload);
		} catch (Exception $e) {
			// Failed to read configuration
			$log[] = $e->getMessage();

			return $e->getCode();
		}
	}

	protected function loadConfigurations($workload) {
		$configuration = $this->gearmanJob(
			$this->options()->get('Workers.Credentials.Lookup'),
			json_encode([
				'client' => $workload['client'],
			])
		);

		if(is_numeric($configuration) || is_null($configuration)) {
			throw new Exception('Claims configuration failed to load: '.$workload['client'], 1);
		} else {
			$this->options()->set(
				'Claims',
				json_decode($configuration, true)
			);
		}
	}

	protected function processClaim($workload) {
		$this->setupDatabaseConnection();

		$claimsRecord = PqrsImportFiles::where('id', $workload['fileId'])->first();

		if(is_null($claimsRecord)) {
			throw new Exception('Claims file ['.$workload['fileId'].'] could not be found in remote database for client ['.$workload['client'].']', 3);
		}

		$claimsRecord->status = 'Processing';
		$claimsRecord->processing_datetime = Carbon::now();
		$claimsRecord->save();

		$failed = false;

		try {
			// Pull claims file
			$raw = Raw::getInstance($this->options()->getSubset('Claims'));
			$raw->parse(
				$this->getClaimsFile($claimsRecord->relative_path)
			);

			// Process claim
			$document = X12N837::getInstance(
				$this->options()->getSubset('Claims')
			);
			$document->parse($raw);

			// Cache claim to remote MySQL
			$cache = Cache::getInstance(
				$this->options()->get('Claims.App.store')
			);
			$cache->processDocument($document);
		} catch (Exception $e) {
			$failed = true;
			$failedReason = $e->getMessage();
		}

		if($failed) {
			$claimsRecord->status = 'Failed';
			$claimsRecord->failed_datetime = Carbon::now();
			$claimsRecord->failed_reason = $failedReason;
		} else {
			$claimsRecord->status = 'Completed';
			$claimsRecord->completed_datetime = Carbon::now();
		}

		$claimsRecord->save();
	}

	protected function setupDatabaseConnection() {
		$this->options()->set(
			'Claims.Store.connections.mysql',
			array_merge(
				$this->options()->get('Claims.Store.connections.mysql'),
				$this->options()->get('Remote.mysql')

			)
		);

		$database = Database::getInstance($this->options()->getSubset('Claims'));

		try {
			$database->getManager()->getConnection()->getPdo();
		} catch(Exception $e) {
			throw new Exception('Failed to connect to database', 4, $e);
		}

		// Set Database instance
		$this->options()->set(
			'Claims.App.store',
			$database
		);
	}

	protected function getClaimsFile($relativeFilePath) {
		return $this->gearmanJob(
			$this->options()->get('Workers.Claims.Retrieve'),
			json_encode([
				'host' => $this->options()->get('Remote.ssh.host'),
				'port' => $this->options()->get('Remote.ssh.port'),
				'path' => $this->options()->get('Remote.ssh.site').'/'.$relativeFilePath,
			])
		);
	}
}