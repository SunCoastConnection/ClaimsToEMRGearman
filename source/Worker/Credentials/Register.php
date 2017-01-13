<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Worker\Credentials;

use \Kicken\Gearman\Job\WorkerJob;
use \SunCoastConnection\ClaimsToEMRGearman\Worker;

class Register extends Worker {

	/**
	 * Run the Register Worker
	 *
	 * @param  \Kicken\Gearman\Job\WorkerJob  $job  Job request to perform run on
	 *
	 * @return integer  Return code:
	 *     0 = Registration written
	 *     1 = Failed creating credentials directory
	 *     2 = Failed writing to credentials directory
	 *     3 = Failed writing to credentials file
	 */
	public function run(WorkerJob $job, &$log) {
		$credentialsPath = $this->options()->get('Credentials.path');

		if(!file_exists($credentialsPath)) {
			if(!mkdir($credentialsPath, 0700, true)) {
				return 1;
			}
		} elseif(!is_writable($credentialsPath)) {
			return 2;
		}

		$workload = json_decode($job->getWorkload(), true);

		// $workload = [
		// 	'client' => 'tokenName',
		// 	'ssh' => [
		// 		'host' => '1.2.3.4',
		// 		'port' => 22,
		// 		'site' => 'sitesDirectoryPath'
		// 	],
		// 	'mysql' => [
		// 		'host' => '1.2.3.4',
		// 		'port' => '3306',
		// 		'database' => 'clientDatabase',
		// 		'username' => 'username',
		// 		'password' => 'password'
		// 	]
		// ];

		$remoteConfigurationPath = $credentialsPath.'/'.$workload['client'].'.json';

		if(file_exists($remoteConfigurationPath) && !is_writable($remoteConfigurationPath)) {
			return 3;
		}

		$fileWritten = file_put_contents(
			$remoteConfigurationPath,
			json_encode(
				$workload,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			).PHP_EOL
		);

		return 0;
	}
}