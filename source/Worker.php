<?php

namespace SunCoastConnection\ClaimsToEMRGearman;

use \Kicken\Gearman\Job\WorkerJob;
use \SunCoastConnection\ClaimsToEMR\Document\Options;

abstract class Worker {

	/**
	 * Get instance of Worker class with provided options
	 *
	 * @param  \SunCoastConnection\ClaimsToEMR\Document\Options  $options  Options to create Worker object with
	 *
	 * @return \SunCoastConnection\ClaimsToEMRGearman\Worker  Worker object
	 */
	static public function getInstance(Options $options) {
		return new static($options);
	}

	/**
	 * Create a new Job
	 *
	 * @param \SunCoastConnection\ClaimsToEMR\Document\Options  $options  Options to create Worker object with
	 */
	public function __construct(Options $options) {
		$this->options($options);
	}

	/**
	 * Set Job options or retrieve Job options
	 *
	 * @param  \SunCoastConnection\ClaimsToEMR\Document\Options|null  $setOptions  Options to set Worker object with
	 *
	 * @return \SunCoastConnection\ClaimsToEMR\Document\Options|null  Worker options or null when not set
	 */
	protected function options(Options $setOptions = null) {
		static $options = null;

		if(is_null($options) && !is_null($setOptions)) {
			$options = $setOptions;
		}

		return $options;
	}

	abstract public function run(WorkerJob $job, &$log);
}