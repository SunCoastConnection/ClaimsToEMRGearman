<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Console;

use \SunCoastConnection\ClaimsToEMRGearman\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

class Available extends Command {

	protected function configure() {
		$this->setName('available')
			->setDescription('List available workers')
			->setHelp('This command allows you to list the available Gearman workers');

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if(!$this->configuration) {
			return;
		}

		$output->writeln('Workers:');

		foreach($this->configuration->get('workers') as $name => $worker) {
			$output->writeln("\t".$name);
		}

		$output->writeln('');
	}
}