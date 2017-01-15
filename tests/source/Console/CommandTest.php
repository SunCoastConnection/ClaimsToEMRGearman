<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests\Console {

	use \org\bovigo\vfs\vfsStream;
	use \SunCoastConnection\ClaimsToEMR\Document\Options;
	use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;
	use \SunCoastConnection\ClaimsToEMRGearman\Console\Command;
	use \Symfony\Component\Console\Input\InputInterface;
	use \Symfony\Component\Console\Output\OutputInterface;

	class CommandTest extends BaseTestCase {

		protected $command;

		public function setUp() {
			parent::setUp();

			$this->command = $this->getMockery(
				Command::class
			)
			->makePartial()
			->shouldAllowMockingProtectedMethods();
		}

		/**
		 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Command::configure()
		 */
		public function testConfigure() {
			$this->command->shouldReceive('addOption');

			$this->command->configure();
		}

		/**
		 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Command::initialize()
		 */
		public function testInitializeWithConfig() {
			$input = $this->getMockery(
				'stdClass, '.InputInterface::class
			);

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

			$output = $this->getMockery(
				'stdClass, '.OutputInterface::class
			);

			$output->shouldNotReceive('writeln');

			$this->command->initialize($input, $output);

			$this->assertEquals(
				$options,
				$this->getProtectedProperty(
					$this->command,
					'configuration'
				),
				'Configuration not set'
			);
		}

		/**
		 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Command::initialize()
		 */
		public function testInitializeWithConfigNotReadable() {
			$input = $this->getMockery(
				'stdClass, '.InputInterface::class
			);

			$configContents = [ 'test' => '123' ];

			$parentDirectory = vfsStream::setup('parent', 0777, [
				'config' => [
					'config.php' => '<?php return '.var_export($configContents, true).';'
				]
			]);

			$configFile = $parentDirectory->getChild('config')
				->getChild('config.php');

			$configFile->chmod(0700);
			$configFile->chown(vfsStream::OWNER_ROOT);

			$input->shouldReceive('getOption')
				->once()
				->with('config')
				->andReturn($configFile->url());

			$output = $this->getMockery(
				'stdClass, '.OutputInterface::class
			);

			$output->shouldReceive('writeln')
				->once();

			$this->command->initialize($input, $output);
		}

		/**
		 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Command::initialize()
		 */
		public function testInitializeWithoutConfigAndWithGetCwd() {
			$input = $this->getMockery(
				'stdClass, '.InputInterface::class
			);

			$input->shouldReceive('getOption')
				->once()
				->with('config')
				->andReturn(false);

			$configContents = [ 'test' => '123' ];

			$parentDirectory = vfsStream::setup('parent', 0777, [
				'app' => [
					'application' => ''
				],
				'config' => [
					'application.php' => '<?php return '.var_export($configContents, true).';'
				]
			]);

			$GLOBALS['argv'][0] = $parentDirectory->getChild('app')
				->getChild('application')->url();

			$options = $this->getMockery(
				'overload:'.Options::class
			);

			$options->shouldReceive('getInstance')
				->once()
				->with($configContents)
				->andReturnSelf();

			$output = $this->getMockery(
				'stdClass, '.OutputInterface::class
			);

			$output->shouldNotReceive('writeln');

			$this->command->initialize($input, $output);

			$this->assertEquals(
				$options,
				$this->getProtectedProperty(
					$this->command,
					'configuration'
				),
				'Configuration not set'
			);
		}

		/**
		 * @covers SunCoastConnection\ClaimsToEMRGearman\Console\Command::initialize()
		 */
		public function testInitializeWithoutConfigAndWithAppDir() {
			$input = $this->getMockery(
				'stdClass, '.InputInterface::class
			);

			$input->shouldReceive('getOption')
				->once()
				->with('config')
				->andReturn(false);

			$configContents = [ 'test' => '123' ];

			$parentDirectory = vfsStream::setup('parent', 0777, [
				'app' => [
					'application' => '',
					'config' => [
						'application.php' => '<?php return '.var_export($configContents, true).';'
					]
				],
			]);

			$GLOBALS['argv'][0] = $parentDirectory->getChild('app')
				->getChild('application')->url();

			$options = $this->getMockery(
				'overload:'.Options::class
			);

			$options->shouldReceive('getInstance')
				->once()
				->with($configContents)
				->andReturnSelf();

			$output = $this->getMockery(
				'stdClass, '.OutputInterface::class
			);

			$output->shouldNotReceive('writeln');

			$this->command->initialize($input, $output);

			$this->assertEquals(
				$options,
				$this->getProtectedProperty(
					$this->command,
					'configuration'
				),
				'Configuration not set'
			);
		}

	}

}

namespace SunCoastConnection\ClaimsToEMRGearman\Console {

	function getcwd() {
		return 'vfs://parent';
	}

	function realpath($path) {
		return $path;
	}

}