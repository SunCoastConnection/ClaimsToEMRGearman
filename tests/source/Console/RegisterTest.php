<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests\Console;

use \Kicken\Gearman\Worker;
use \SunCoastConnection\ClaimsToEMRGearman\Console\Register;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;
use \SunCoastConnection\ParseX12\Options;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \org\bovigo\vfs\vfsStream;

class RegisterTest extends BaseTestCase {

	protected $register;

	public function setUp() {
		parent::setUp();

		$this->register = $this->getMockery(
			Register::class
		)
		->makePartial()
		->shouldAllowMockingProtectedMethods();
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Register::configure()
	 */
	public function testConfigure() {
		$this->register->shouldReceive('setName')->andReturnSelf();
		$this->register->shouldReceive('setDescription')->andReturnSelf();
		$this->register->shouldReceive('setHelp')->andReturnSelf();
		$this->register->shouldReceive('addArgument')->andReturnSelf();

		$this->register->shouldReceive('addOption');

		$this->register->configure();
	}

	public function commandInitialize($input, $output) {
		$configContents = [ 'test' => '123' ];

		$parentDirectory = vfsStream::setup('parent', 0777, [
			'config' => [
				'config.php' => '<?php return '.var_export($configContents, true).';'
			]
		]);

		$configFile = $parentDirectory->getChild('config')
			->getChild('config.php')->url();

		$input->shouldReceive('getOption')
			->once()
			->with('config')
			->andReturn($configFile);

		$options = $this->getMockery(
			'overload:'.Options::class
		);

		$options->shouldReceive('getInstance')
			->once()
			->with($configContents)
			->andReturnSelf();

		return $options;
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Register::initialize()
	 */
	public function testInitializeWithWorkers() {
		$input = $this->getMockery(
			'stdClass, '.InputInterface::class
		);

		$output = $this->getMockery(
			'stdClass, '.OutputInterface::class
		);

		$options = $this->commandInitialize($input, $output);

		$input->shouldReceive('hasArgument')
			->once()
			->with('workers')
			->andReturn(true);

		$workers = [
			'worker1' => [],
			'worker2' => [],
			'worker3' => [],
		];

		$input->shouldReceive('getArgument')
			->once()
			->with('workers')
			->andReturn(array_keys($workers));

		$options->shouldReceive('get')
			->once()
			->with('workers')
			->andReturn($workers);

		$output->shouldNotReceive('writeln');

		$this->register->initialize($input, $output);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Register::initialize()
	 */
	public function testInitializeWithBadWorkers() {
		$input = $this->getMockery(
			'stdClass, '.InputInterface::class
		);

		$output = $this->getMockery(
			'stdClass, '.OutputInterface::class
		);

		$options = $this->commandInitialize($input, $output);

		$input->shouldReceive('hasArgument')
			->once()
			->with('workers')
			->andReturn(true);

		$workers = [
			'worker1' => [],
			'worker2' => [],
			'worker3' => [],
		];

		$input->shouldReceive('getArgument')
			->once()
			->with('workers')
			->andReturn(array_merge(array_keys($workers), [ 'worker4' ]));

		$options->shouldReceive('get')
			->once()
			->with('workers')
			->andReturn($workers);

		$output->shouldReceive('writeln')
			->twice();

		$this->register->initialize($input, $output);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Register::initialize()
	 */
	public function testInitializeWithNoWorkers() {
		$input = $this->getMockery(
			'stdClass, '.InputInterface::class
		);

		$output = $this->getMockery(
			'stdClass, '.OutputInterface::class
		);

		$this->commandInitialize($input, $output);

		$input->shouldReceive('hasArgument')
			->once()
			->with('workers')
			->andReturn(false);

		$output->shouldReceive('writeln');

		$this->register->initialize($input, $output);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Register::execute()
	 */
	public function testExecuteWithNoConfiguration() {
		$input = $this->getMockery(
			'stdClass, '.InputInterface::class
		);

		$output = $this->getMockery(
			'stdClass, '.OutputInterface::class
		);

		$this->setProtectedProperty(
			$this->register,
			'workers',
			[ 'worker1' ]
		);

		$this->register->execute($input, $output);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Register::execute()
	 */
	public function testExecuteWithNoWorkers() {
		$input = $this->getMockery(
			'stdClass, '.InputInterface::class
		);

		$output = $this->getMockery(
			'stdClass, '.OutputInterface::class
		);

		$options = $this->getMockery(
			Options::class
		);

		$this->setProtectedProperty(
			$this->register,
			'configuration',
			$options
		);

		$this->register->execute($input, $output);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Register::execute()
	 */
	public function testExecuteWithRegister() {
		$input = $this->getMockery(
			'stdClass, '.InputInterface::class
		);

		$output = $this->getMockery(
			'stdClass, '.OutputInterface::class
		);

		$options = $this->getMockery(
			Options::class
		);

		$this->setProtectedProperty(
			$this->register,
			'configuration',
			$options
		);

		$this->setProtectedProperty(
			$this->register,
			'workers',
			[ 'worker1' ]
		);

		$workerConfiguration = '1.2.3.4:4730';

		$options->shouldReceive('get')
			->once()
			->with('servers', '127.0.0.1:4730')
			->andReturn($workerConfiguration);

		$worker = $this->getMockery(
			'overload:'.Worker::class
		);

		$worker->shouldReceive('registerFunction')
			->once();

		$worker->shouldReceive('work');

		$this->register->execute($input, $output);
	}

}