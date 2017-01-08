<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests\Worker;

use \Kicken\Gearman\Job\WorkerJob;
use \org\bovigo\vfs\vfsStream;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;
use \SunCoastConnection\ClaimsToEMRGearman\Worker\ReturnRemoteConnection;

class ReturnRemoteConnectionTest extends BaseTestCase {

	protected $returnRemoteConnection;

	public function setUp() {
		parent::setUp();

		$this->returnRemoteConnection = $this->getMockery(
			ReturnRemoteConnection::class
		)->makePartial();
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ReturnRemoteConnection::run()
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

		$this->returnRemoteConnection->shouldAllowMockingProtectedMethods()
			->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($credentialsDirectory->url());

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->assertEquals(
			json_encode($connection),
			$this->returnRemoteConnection->run($job, $log),
			'Successful response not recieved'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ReturnRemoteConnection::run()
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

		$this->returnRemoteConnection->shouldAllowMockingProtectedMethods()
			->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($credentialsDirectory->url());

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->assertEquals(
			1,
			$this->returnRemoteConnection->run($job, $log),
			'Failed response not recieved'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ReturnRemoteConnection::run()
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

		$this->returnRemoteConnection->shouldAllowMockingProtectedMethods()
			->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($credentialsDirectory->url());

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->assertEquals(
			2,
			$this->returnRemoteConnection->run($job, $log),
			'Failed response not recieved'
		);
	}

}