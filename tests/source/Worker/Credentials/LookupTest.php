<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests\Worker\Credentials;

use \Kicken\Gearman\Job\WorkerJob;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;
use \SunCoastConnection\ClaimsToEMRGearman\Worker\Credentials\Lookup;
use \org\bovigo\vfs\vfsStream;

class LookupTest extends BaseTestCase {

	protected $lookup;

	public function setUp() {
		parent::setUp();

		$this->lookup = $this->getMockery(
			Lookup::class
		)->makePartial();
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Credentials\Lookup::run()
	 */
	public function testRunWithSuccessfulRead() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$workload = [
			'client' => 'tokenName'
		];

		$connection = [
			'client' => 'tokenName',
			'ssh' => [
				'host' => '1.2.3.4',
				'site' => 'sitesDirectoryPath',
			],
			'mysql' => [
				'host' => '1.2.3.4',
				'port' => '3306',
				'database' => 'clientDatabase',
				'username' => 'username',
				'password' => 'password'
			]
		];

		$parentDirectory = vfsStream::setup('parent', 0700, [
			'credentials' => [
				$workload['client'].'.json' => json_encode($connection)
			]
		]);

		$credentialsDirectory = $parentDirectory->getChild('credentials');

		$this->lookup->shouldAllowMockingProtectedMethods()
			->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($credentialsDirectory->url());

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->assertEquals(
			json_encode($connection),
			$this->lookup->run($job, $log),
			'Successful response not recieved'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Credentials\Lookup::run()
	 */
	public function testRunWithFileMissing() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$workload = [
			'client' => 'tokenName'
		];

		$parentDirectory = vfsStream::setup('parent', 0700, [
			'credentials' => []
		]);

		$credentialsDirectory = $parentDirectory->getChild('credentials');

		$this->lookup->shouldAllowMockingProtectedMethods()
			->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($credentialsDirectory->url());

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->assertEquals(
			1,
			$this->lookup->run($job, $log),
			'Failed response not recieved'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Credentials\Lookup::run()
	 */
	public function testRunWithFileOwnedByRoot() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$workload = [
			'client' => 'tokenName'
		];

		$connection = [
			'client' => 'tokenName',
			'ssh' => [
				'host' => '1.2.3.4',
				'site' => 'sitesDirectoryPath',
			],
			'mysql' => [
				'host' => '1.2.3.4',
				'port' => '3306',
				'database' => 'clientDatabase',
				'username' => 'username',
				'password' => 'password'
			]
		];

		$parentDirectory = vfsStream::setup('parent', 0700, [
			'credentials' => [
				$workload['client'].'.json' => json_encode($connection)
			]
		]);

		$credentialsDirectory = $parentDirectory->getChild('credentials');

		$credentialsFile = $credentialsDirectory->getChild($workload['client'].'.json');
		$credentialsFile->chmod(0700);
		$credentialsFile->chown(vfsStream::OWNER_ROOT);

		$this->lookup->shouldAllowMockingProtectedMethods()
			->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($credentialsDirectory->url());

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->assertEquals(
			2,
			$this->lookup->run($job, $log),
			'Failed response not recieved'
		);
	}

}