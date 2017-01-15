<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Console;

use \Kicken\Gearman\Worker;
use \Kicken\Gearman\Job\WorkerJob;
use \SunCoastConnection\ClaimsToEMRGearman\Console\Command;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

class Register extends Command {

	protected $workers = [];

	protected function configure() {
		$this->setName('register')
			->setDescription('Run workers')
			->setHelp('This command allows you to register Gearman workers')
			->addArgument('workers', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Specify workers to spawn');

		parent::configure();
	}

	protected function initialize(InputInterface $input, OutputInterface $output) {
		parent::initialize($input, $output);

		if($input->hasArgument('workers')) {
			$workers = $input->getArgument('workers');

			$configuredWorkers = $this->configuration->get('workers');

			foreach($workers as $worker) {
				if(array_key_exists($worker, $configuredWorkers)) {
					$this->workers[] = $worker;
				}
			}

			if(count($workers) > count($this->workers)) {
				$output->writeln('Invalid workers specified:');

				foreach(array_diff($workers, $this->workers) as $worker) {
					$output->writeln("\t- ".$worker);
				}
			}
		} else {
			$output->writeln('Worker name not provided');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if(!$this->configuration || !count($this->workers)) {
			return;
		}

		$gearmanWorker = new Worker(
			$this->configuration->get('servers', '127.0.0.1:4730')
		);

		$configuration = $this->configuration;

		foreach($this->workers as $workerName) {
			$gearmanWorker->registerFunction(
				$workerName,
				function(WorkerJob $job) use ($configuration, $workerName) {
					$workerClass = $configuration->get('workers.'.$workerName.'.class');

					$worker = $workerClass::getInstance(
						$configuration->getSubset('workers.'.$workerName.'.options')
					);

					$worker->setGearmanServers($configuration->get('servers', '127.0.0.1:4730'));

					$log = [];

					$returnValue = $worker->run($job, $log);

					if(count($log)) {
						echo "Error:\t".implode(PHP_EOL."Error:\t", $log).PHP_EOL;
					}

					return $returnValue;
				}
			);
		}

		$gearmanWorker->work();
	}
}