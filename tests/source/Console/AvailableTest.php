<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests\Console;

use \SunCoastConnection\ClaimsToEMR\Document\Options;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;
use \SunCoastConnection\ClaimsToEMRGearman\Console\Available;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

class AvailableTest extends BaseTestCase {

	protected $available;

	public function setUp() {
		parent::setUp();

		$this->available = $this->getMockery(
			Available::class
		)
		->makePartial()
		->shouldAllowMockingProtectedMethods();
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Available::configure()
	 */
	public function testConfigure() {
		$this->available->shouldReceive('setName')->andReturnSelf();
		$this->available->shouldReceive('setDescription')->andReturnSelf();
		$this->available->shouldReceive('setHelp')->andReturnSelf();

		$this->available->shouldReceive('addOption');

		$this->available->configure();
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Available::execute()
	 */
	public function testExecute() {
		$options = $this->getMockery(
			Options::class
		);

		$this->setProtectedProperty(
			$this->available,
			'configuration',
			$options
		);

		$input = $this->getMockery(
			'stdClass, '.InputInterface::class
		);

		$output = $this->getMockery(
			'stdClass, '.OutputInterface::class
		);

		$output->shouldReceive('writeln')
			->times(5);

		$workers = [
			'worker1' => [],
			'worker2' => [],
			'worker3' => [],
		];

		$options->shouldReceive('get')
			->once()
			->with('workers')
			->andReturn($workers);

		$this->available->execute($input, $output);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Available::execute()
	 */
	public function testExecuteWithNoConfiguration() {
		$input = $this->getMockery(
			'stdClass, '.InputInterface::class
		);

		$output = $this->getMockery(
			'stdClass, '.OutputInterface::class
		);

		$output->shouldNotReceive('writeln');

		$this->available->execute($input, $output);
	}

}