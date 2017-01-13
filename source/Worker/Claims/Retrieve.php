<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Worker\Claims;

use \Exception;
use \Kicken\Gearman\Job\WorkerJob;
use \phpseclib\Crypt\RSA;
use \phpseclib\Net\SFTP;
use \SunCoastConnection\ClaimsToEMRGearman\Worker;

class Retrieve extends Worker {

	/**
	 * Run the Retrieve Worker
	 *
	 * @param  \Kicken\Gearman\Job\WorkerJob  $job  Job request to perform run on
	 *
	 * @return integer  Return code:
	 *     1 = Failed to connect to remote filesystem
	 *     2 = Failed to connect to remote directory
	 *     3 = Failed to open remote file
	 */
	public function run(WorkerJob $job, &$log) {
		$workload = json_decode($job->getWorkload(), true);

		// $workload = [
		// 	'host' => '1.2.3.4',
		// 	'port' => 22,
		// 	'path' => 'pathToFile'
		// ];

		$this->options()->set('SSH', $workload);

		try {
			$sftp = $this->getSFTPconnection();
		} catch (Exception $e) {
			return 1;
		}

		if(!$sftp->chdir(dirname($this->options()->get('SSH.path')))) {
			return 2;
		}

		$claim = $sftp->get(basename($this->options()->get('SSH.path')));

		if(!$claim) {
			return 3;
		}

		return $claim;
	}

	protected function getSFTPconnection() {
		$sftp = new SFTP(
			$this->options()->get('SSH.host', '127.0.0.1'),
			$this->options()->get('SSH.port', '22')
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