<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests\Worker\Credentials;

use \Kicken\Gearman\Job\WorkerJob;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;
use \SunCoastConnection\ClaimsToEMRGearman\Worker\Credentials\Register;
use \org\bovigo\vfs\vfsStream;

class RegisterTest extends BaseTestCase {

	protected $register;

	public function setUp() {
		parent::setUp();

		$this->register = $this->getMockery(
			Register::class
		)->makePartial();
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Credentials\Register::run()
	 */
	public function testRunWithSuccessfulWrite() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$parentDirectory = vfsStream::setup('parent', 0700);

		$this->register->shouldAllowMockingProtectedMethods()
			->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($parentDirectory->url().'/credentials');

		$workload = [
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

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->assertEquals(
			0,
			$this->register->run($job, $log),
			'Successfule response not recieved'
		);

		$this->assertTrue(
			$parentDirectory->hasChild('credentials'),
			'Credentials directory was not created'
		);

		$credentialsDirectory = $parentDirectory->getChild('credentials');

		$this->assertEquals(
			0700,
			$credentialsDirectory->getPermissions(),
			'Permissions on credentials directory not set correctly'
		);

		$this->assertTrue(
			$credentialsDirectory->hasChild($workload['client'].'.json'),
			'Client credentials file not created'
		);

		$this->assertEquals(
			$workload,
			json_decode(
				$credentialsDirectory->getChild($workload['client'].'.json')->getContent(),
				true
			),
			'Client credentials contents does not match expected'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Credentials\Register::run()
	 */
	public function testRunWithParentDirectoryOwnedByRoot() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$parentDirectory = vfsStream::setup('parent', 0700);
		$parentDirectory->chown(vfsStream::OWNER_ROOT);

		$this->register->shouldAllowMockingProtectedMethods()
			->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($parentDirectory->url().'/credentials');

		$workload = [
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

		$job->shouldReceive('getWorkload')
			->andReturn(json_encode($workload));

		$this->assertEquals(
			1,
			$this->register->run($job, $log),
			'Failed credentials directory creation response not recieved'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Credentials\Register::run()
	 */
	public function testRunWithCredentialsDirectoryOwnedByRoot() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$parentDirectory = vfsStream::setup('parent', 0700, [ 'credentials' => [] ]);
		$parentDirectory->chown(vfsStream::OWNER_ROOT);

		$credentialsDirectory = $parentDirectory->getChild('credentials');
		$credentialsDirectory->chmod(0700);
		$credentialsDirectory->chown(vfsStream::OWNER_ROOT);

		$this->register->shouldAllowMockingProtectedMethods()
			->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($credentialsDirectory->url());

		$this->assertEquals(
			2,
			$this->register->run($job, $log),
			'Failed '
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Credentials\Register::run()
	 */
	public function testRunWithCredentialsFileOwnedByRoot() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$workload = [
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
				$workload['client'].'.json' => json_encode($workload)
			]
		]);

		$credentialsDirectory = $parentDirectory->getChild('credentials');

		$credentialsFile = $credentialsDirectory->getChild($workload['client'].'.json');
		$credentialsFile->chmod(0700);
		$credentialsFile->chown(vfsStream::OWNER_ROOT);

		$this->register->shouldAllowMockingProtectedMethods()
			->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($credentialsDirectory->url());

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		clearstatcache();

		$this->assertEquals(
			3,
			$this->register->run($job, $log),
			'Failed '
		);
	}

}