<?php

namespace SunCoastConnection\ClaimsToEMRGearman;

use \Kicken\Gearman\Client;
use \Kicken\Gearman\Job\JobPriority;
use \Kicken\Gearman\Job\WorkerJob;
use \Kicken\Gearman\Protocol\Connection;
use \SunCoastConnection\ParseX12\Options;

abstract class Worker {

	protected $servers = [];

	/**
	 * Get instance of Worker class with provided options
	 *
	 * @param  \SunCoastConnection\ParseX12\Options  $options  Options to create Worker object with
	 *
	 * @return \SunCoastConnection\ClaimsToEMRGearman\Worker  Worker object
	 */
	public static function getInstance(Options $options) {
		return new static($options);
	}

	/**
	 * Create a new Job
	 *
	 * @param \SunCoastConnection\ParseX12\Options  $options  Options to create Worker object with
	 */
	public function __construct(Options $options) {
		$this->options($options);
	}

	/**
	 * Set Job options or retrieve Job options
	 *
	 * @param  \SunCoastConnection\ParseX12\Options|null  $setOptions  Options to set Worker object with
	 *
	 * @return \SunCoastConnection\ParseX12\Options|null  Worker options or null when not set
	 */
	protected function options(Options $setOptions = null) {
		static $options = null;

		if(is_null($options) && !is_null($setOptions)) {
			$options = $setOptions;
		}

		return $options;
	}

	/**
	 * Set host and port to connect to Gearman server
	 *
	 * @param array  $servers  List of Gearman servers to connect to
	 */
	public function setGearmanServers(array $servers) {
		$this->servers = $servers;
	}

	/**
	 * Return Gearman Client object
	 *
	 * @return \Kicken\Gearman\Client  If servers set return Gearman Client object
	 */
	public function getGearmanClient() {
		if(!empty($this->servers)) {
			return new Client($this->servers);
		}
	}

	/**
	 * Submit a new job to the Gearman server.
	 * Results can be retrieved once the job is complete through the returned
	 * ClientJob object.  Use the wait method to wait for the job to complete.
	 *
	 * @param  string  $function  The function to be run.
	 * @param  string  $workload  Data for the function to operate on.
	 * @param  int     $priority  One of the JobPriority constants.
	 * @param  string  $unique    A unique ID for the job.
	 * @return mixed              Job results
	 */
	public function gearmanJob($function, $workload, $priority = JobPriority::NORMAL, $unique = null) {
		$client = $this->getGearmanClient();

		if($client) {
			$job = $client->submitJob($function, $workload, $priority, $unique);

			$client->wait();

			return $job->getResult();
		}
	}

	/**
	 * Submit a new job to the Gearman server for execution as a background task.
	 * Background tasks are unable to pass back any result data, but can provide
	 * status information regarding the progress of the job.  Status information
	 * must be obtained by calling the getJobStatus function
	 *
	 * @param  string  $function  The function to be run.
	 * @param  string  $workload  Data for the function to operate on.
	 * @param  int     $priority  One of the JobPriority constants.
	 * @param  string  $unique    A unique ID for the job.
	 * @return string             The job handle assigned.
	 */
	public function gearmanBackgroundJob($function, $workload, $priority = JobPriority::NORMAL, $unique = null) {
		$client = $this->getGearmanClient();

		if($client) {
			return $client->submitBackgroundJob($function, $workload, $priority, $unique);
		}
	}

	abstract public function run(WorkerJob $job, &$log);
}