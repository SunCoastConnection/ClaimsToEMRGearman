<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Worker;

use \Kicken\Gearman\Job\WorkerJob;
use \SunCoastConnection\ClaimsToEMRGearman\Worker;

class RegisterRemoteConnection extends Worker {

	/**
	 * Run the RegisterRemoteConnection Worker
	 *
	 * @param  \Kicken\Gearman\Job\WorkerJob  $job  Job request to perform run on
	 *
	 * @return integer  Return code (0 = registration successful, 1 = failed to create credentials directory, 2 = failed to write configuration out to file)
	 */
	public function run(WorkerJob $job, &$log) {
		$credentialsPath = $this->options()->get('Credentials.path');

		if(!file_exists($credentialsPath)) {
			if(!mkdir($credentialsPath, 0700, true)) {
				// Crecdentials directory does not exist and creation failed
				return 1;
			}
		} elseif(!is_writable($credentialsPath)) {
			// Crecdentials directory is not writable
			return 2;
		}

		$workload = json_decode($job->getWorkload(), true);

		// $workload = [
		// 	'client' => 'tokenName',
		// 	'ssh' => [
		// 		'host' => '1.2.3.4',
		// 		'site' => 'sitesDirectoryPath',
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
			// Configuration file exists but is not writable
			return 3;
		}

		$fileWritten = file_put_contents(
			$remoteConfigurationPath,
			json_encode(
				$workload,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			).PHP_EOL
		);

		// Registration successful
		return 0;
	}
}