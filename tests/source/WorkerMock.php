<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests;

use \Kicken\Gearman\Job\WorkerJob;
use \SunCoastConnection\ClaimsToEMRGearman\Worker;

class WorkerMock extends Worker {

	public function run(WorkerJob $job, &$log) {}

}