<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Console;

use \SunCoastConnection\ParseX12\Options;
use \Symfony\Component\Console\Command\Command as SymfonyCommand;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyCommand {

	protected $configuration;

	protected function configure() {
		$this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Specify configuration file to use');
	}

	protected function initialize(InputInterface $input, OutputInterface $output) {
		$configPath = $input->getOption('config');

		if($configPath) {
			$configPath = [
				realpath($configPath)
			];
		} else {
			$appPath = realpath($GLOBALS['argv'][0]);
			$confFilename = basename($appPath).'.php';
			$appDir = dirname($appPath);

			$configPath = [
				getcwd().'/'.$confFilename,
				getcwd().'/config/'.$confFilename,
				$appDir.'/'.$confFilename,
				$appDir.'/config/'.$confFilename,
			];
		}

		foreach($configPath as $config) {
			if(is_readable($config) && is_file($config)) {
				$this->configuration = Options::getInstance(
					require_once($config)
				);

				break;
			}
		}

		if(!$this->configuration) {
			$output->writeln('Configuration could not be loaded');
		}
	}
}