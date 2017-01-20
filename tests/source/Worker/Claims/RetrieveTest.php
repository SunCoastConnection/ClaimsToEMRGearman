<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests\Worker\Credentials;

use \Exception;
use \Kicken\Gearman\Job\WorkerJob;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;
use \SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Retrieve;
use \org\bovigo\vfs\vfsStream;
use \phpseclib\Crypt\RSA;
use \phpseclib\Net\SFTP;

class RetrieveTest extends BaseTestCase {

	protected $retrieve;

	public function setUp() {
		parent::setUp();

		$this->retrieve = $this->getMockery(
			Retrieve::class
		)->makePartial();
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Retrieve::run()
	 */
	public function testRunWithFailedSftpConnection() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$workload = [
			'host' => '1.2.3.4',
			'port' => 22,
			'path' => 'pathToFile'
		];

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->retrieve->shouldAllowMockingProtectedMethods();

		$this->retrieve->shouldReceive('options->set')
			->once()
			->with('SSH', $workload);

		$exception = [
			'class' => Exception::class,
			'message' => 'Failed connecting to SFTP',
			'code' => 333
		];

		$this->retrieve->shouldReceive('getSFTPconnection')
			->andThrow(
				$exception['class'],
				$exception['message'],
				$exception['code']
			);

		$this->assertEquals(
			1,
			$this->retrieve->run($job, $log),
			'Exception code should have been returned'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Retrieve::run()
	 */
	public function testRunWithFailedStfpChdir() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$workload = [
			'host' => '1.2.3.4',
			'port' => 22,
			'path' => 'pathToFile'
		];

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->retrieve->shouldAllowMockingProtectedMethods();

		$this->retrieve->shouldReceive('options->set')
			->once()
			->with('SSH', $workload);

		$sftp = $this->getMockery(
			'alias:'.SFTP::class
		)->makePartial();

		$this->retrieve->shouldReceive('getSFTPconnection')
			->andReturn($sftp);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SSH.path')
			->andReturn($workload['path']);

		$sftp->shouldReceive('chdir')
			->once()
			->with(dirname($workload['path']))
			->andReturn(false);

		$this->assertEquals(
			2,
			$this->retrieve->run($job, $log),
			'Exception code should have been returned'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Retrieve::run()
	 */
	public function testRunWithFailedRetrieval() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$workload = [
			'host' => '1.2.3.4',
			'port' => 22,
			'path' => 'pathToFile'
		];

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->retrieve->shouldAllowMockingProtectedMethods();

		$this->retrieve->shouldReceive('options->set')
			->once()
			->with('SSH', $workload);

		$sftp = $this->getMockery(
			'alias:'.SFTP::class
		)->makePartial();

		$this->retrieve->shouldReceive('getSFTPconnection')
			->andReturn($sftp);

		$this->retrieve->shouldReceive('options->get')
			->twice()
			->with('SSH.path')
			->andReturn($workload['path']);

		$sftp->shouldReceive('chdir')
			->once()
			->with(dirname($workload['path']))
			->andReturn(true);

		$sftp->shouldReceive('get')
			->once()
			->with(basename($workload['path']))
			->andReturn(false);

		$this->assertEquals(
			3,
			$this->retrieve->run($job, $log),
			'Exception code should have been returned'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Retrieve::run()
	 */
	public function testRunWithoutFailure() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$workload = [
			'host' => '1.2.3.4',
			'port' => 22,
			'path' => 'pathToFile'
		];

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->retrieve->shouldAllowMockingProtectedMethods();

		$this->retrieve->shouldReceive('options->set')
			->once()
			->with('SSH', $workload);

		$sftp = $this->getMockery(
			'alias:'.SFTP::class
		)->makePartial();

		$this->retrieve->shouldReceive('getSFTPconnection')
			->andReturn($sftp);

		$this->retrieve->shouldReceive('options->get')
			->twice()
			->with('SSH.path')
			->andReturn($workload['path']);

		$sftp->shouldReceive('chdir')
			->once()
			->with(dirname($workload['path']))
			->andReturn(true);

		$claimsFile = 'Claims File Content';

		$sftp->shouldReceive('get')
			->once()
			->with(basename($workload['path']))
			->andReturn($claimsFile);

		$this->assertEquals(
			$claimsFile,
			$this->retrieve->run($job, $log),
			'Claims file content not returned as expected'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Retrieve::getSFTPconnection()
	 */
	public function testGetSFTPconnectionWithPasswordAndLoginFailure() {
		$this->retrieve->shouldAllowMockingProtectedMethods();

		$ssh = [
			'host' => '127.0.0.1',
			'port' => '22',
			'password' => 'abc123',
			'username' => 'user1'
		];

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SSH.host', $ssh['host'])
			->andReturn($ssh['host']);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SSH.port', $ssh['port'])
			->andReturn($ssh['port']);

		$sftp = $this->getMockery(
			'overload:'.SFTP::class
		)->makePartial();

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SFTP.privateKey.path')
			->andReturn(null);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SFTP.password')
			->andReturn($ssh['password']);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SFTP.username')
			->andReturn($ssh['username']);

		$sftp->shouldReceive('login')
			->once()
			->with($ssh['username'], $ssh['password'])
			->andReturn(false);

		$this->setExpectedException(
			'Exception',
			'Login failed'
		);

		$this->callProtectedMethod(
			$this->retrieve,
			'getSFTPconnection'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Retrieve::getSFTPconnection()
	 */
	public function testGetSFTPconnectionWithPassword() {
		$this->retrieve->shouldAllowMockingProtectedMethods();

		$ssh = [
			'host' => '127.0.0.1',
			'port' => '22',
			'password' => 'abc123',
			'username' => 'user1'
		];

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SSH.host', $ssh['host'])
			->andReturn($ssh['host']);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SSH.port', $ssh['port'])
			->andReturn($ssh['port']);

		$sftp = $this->getMockery(
			'overload:'.SFTP::class
		)->makePartial();

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SFTP.privateKey.path')
			->andReturn(null);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SFTP.password')
			->andReturn($ssh['password']);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SFTP.username')
			->andReturn($ssh['username']);

		$sftp->shouldReceive('login')
			->once()
			->with($ssh['username'], $ssh['password'])
			->andReturn(true);

		$this->assertInstanceOf(
			get_class($sftp),
			$this->callProtectedMethod(
				$this->retrieve,
				'getSFTPconnection'
			),
			'SFTP object not returned as expected'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Retrieve::getSFTPconnection()
	 */
	public function testGetSFTPconnectionWithPrivateKey() {
		$this->retrieve->shouldAllowMockingProtectedMethods();

		$privateKeyContents = 'Private Key';

		$parentDirectory = vfsStream::setup(
			'cache',
			0700,
			[
				'private.key' => $privateKeyContents
			]
		);

		$ssh = [
			'host' => '127.0.0.1',
			'port' => '22',
			'path' => $parentDirectory->getChild('private.key')->url(),
			'passphrase' => '',
			'username' => 'user1'
		];

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SSH.host', $ssh['host'])
			->andReturn($ssh['host']);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SSH.port', $ssh['port'])
			->andReturn($ssh['port']);

		$sftp = $this->getMockery(
			'overload:'.SFTP::class
		)->makePartial();

		$this->retrieve->shouldReceive('options->get')
			->twice()
			->with('SFTP.privateKey.path')
			->andReturn($ssh['path']);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SFTP.privateKey.passphrase')
			->andReturn($ssh['passphrase']);

		$rsa = $this->getMockery(
			'overload:'.RSA::class
		)->makePartial();

		$rsa->shouldReceive('loadKey')
			->once()
			->with($privateKeyContents);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SFTP.username')
			->andReturn($ssh['username']);

		$sftp->shouldReceive('login')
			->once()
			->with($ssh['username'], get_class($rsa))
			->andReturn(true);

		$this->assertInstanceOf(
			get_class($sftp),
			$this->callProtectedMethod(
				$this->retrieve,
				'getSFTPconnection'
			),
			'SFTP object not returned as expected'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Retrieve::getSFTPconnection()
	 */
	public function testGetSFTPconnectionWithPrivateKeyAndPassphrase() {
		$this->retrieve->shouldAllowMockingProtectedMethods();

		$privateKeyContents = 'Private Key';

		$parentDirectory = vfsStream::setup(
			'cache',
			0700,
			[
				'private.key' => $privateKeyContents
			]
		);

		$ssh = [
			'host' => '127.0.0.1',
			'port' => '22',
			'path' => $parentDirectory->getChild('private.key')->url(),
			'passphrase' => 'Private Key Passphrase',
			'username' => 'user1'
		];

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SSH.host', $ssh['host'])
			->andReturn($ssh['host']);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SSH.port', $ssh['port'])
			->andReturn($ssh['port']);

		$sftp = $this->getMockery(
			'overload:'.SFTP::class
		)->makePartial();

		$this->retrieve->shouldReceive('options->get')
			->twice()
			->with('SFTP.privateKey.path')
			->andReturn($ssh['path']);

		$this->retrieve->shouldReceive('options->get')
			->twice()
			->with('SFTP.privateKey.passphrase')
			->andReturn($ssh['passphrase']);

		$rsa = $this->getMockery(
			'overload:'.RSA::class
		)->makePartial();

		$rsa->shouldReceive('setPassword')
			->once()
			->with($ssh['passphrase']);

		$rsa->shouldReceive('loadKey')
			->once()
			->with($privateKeyContents);

		$this->retrieve->shouldReceive('options->get')
			->once()
			->with('SFTP.username')
			->andReturn($ssh['username']);

		$sftp->shouldReceive('login')
			->once()
			->with($ssh['username'], get_class($rsa))
			->andReturn(true);

		$this->assertInstanceOf(
			get_class($sftp),
			$this->callProtectedMethod(
				$this->retrieve,
				'getSFTPconnection'
			),
			'SFTP object not returned as expected'
		);
	}
}