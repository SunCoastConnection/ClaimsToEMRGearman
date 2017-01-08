<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Worker;

use \Carbon\Carbon;
use \Exception;
use \Kicken\Gearman\Job\WorkerJob;
use \phpseclib\Crypt\RSA;
use \phpseclib\Net\SFTP;
use \SunCoastConnection\ClaimsToEMR\Document\Raw;
use \SunCoastConnection\ClaimsToEMR\Models\PqrsImportFiles;
use \SunCoastConnection\ClaimsToEMR\Store\Database;
use \SunCoastConnection\ClaimsToEMR\X12N837;
use \SunCoastConnection\ClaimsToEMR\X12N837\Cache;
use \SunCoastConnection\ClaimsToEMRGearman\Worker;

class ImportClaims extends Worker {

	/**
	 * Run the ImportClaims Worker
	 *
	 * @param  \Kicken\Gearman\Job\WorkerJob  $job  Job request to perform run on
	 *
	 * @return integer  Return code ()
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
		$remoteConfigurationPath = $this->options()->get('Credentials.path').
			'/'.$workload['client'].'.json';

		if(!file_exists($remoteConfigurationPath)) {
			// Failed to read configuration
			throw new Exception('Remote configuration faild to load: '.$remoteConfigurationPath, 1);
		}

		// Get remote configuration
		$this->options()->set(
			'Remote',
			json_decode(
				file_get_contents($remoteConfigurationPath),
				true
			)
		);

		$claimsConfigurationPath = $this->options()->get('ClaimsConfig');

		if(!is_readable($claimsConfigurationPath)) {
			throw new Exception('Claims configuration faild to load: '.$claimsConfigurationPath, 2);
		}

		//** GET CLAIMS CONFIGURATION OPTIONS **//
		$this->options()->set('Claims', include($claimsConfigurationPath));
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
		try {
			$sftp = $this->getSFTPconnection();
		} catch (Exception $e) {
			throw new Exception('Failed to connect to remote filesystem', 5, $e);
		}

		$path = dirname($this->options()->get('Remote.ssh.site').'/PQRS/X12N837/'.$relativeFilePath);

		if(!$sftp->chdir($path)) {
			throw new Exception('Failed connecting to remote site claims directory: '.$path, 6);
		}

		$claim = $sftp->get(basename($relativeFilePath));

		if(!$claim) {
			throw new Exception('Failed to open claim file', 7);
		}

		return $claim;
	}

	protected function getSFTPconnection() {
		$sftp = new SFTP(
			$this->options()->get('Remote.ssh.host', '127.0.0.1'),
			$this->options()->get('Remote.ssh.port', '22')
		);

		if($this->options()->get('SFTP.privateKey.path')) {
			// Load private key if path provided
			$secret = new RSA();

			if($this->options()->get('SFTP.privateKey.passphrase') != '') {
				// If the private key is encrypted, set a passphrase
				$secret->setPassword($this->options()->get('SFTP.privateKey.passphrase'));
			}

			// Load the private key
			$secret->loadKey(file_get_contents($this->options()->get('SFTP.privateKey.path')));
		} else {
			$secret = $this->options()->get('SFTP.password');
		}

		if(!$sftp->login($this->options()->get('SFTP.username'), $secret)) {
			throw new Exception('Login failed');
		}

		return $sftp;
	}
}