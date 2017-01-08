<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests\Worker;

use \Exception;
use \Kicken\Gearman\Job\WorkerJob;
use \org\bovigo\vfs\vfsStream;
use \phpseclib\Crypt\RSA;
use \phpseclib\Net\SFTP;
use \SunCoastConnection\ClaimsToEMR\Document\Raw;
use \SunCoastConnection\ClaimsToEMR\Models\PqrsImportFiles;
use \SunCoastConnection\ClaimsToEMR\Store\Database;
use \SunCoastConnection\ClaimsToEMR\X12N837;
use \SunCoastConnection\ClaimsToEMR\X12N837\Cache;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;
use \SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims;

class ImportClaimsTest extends BaseTestCase {

	protected $importClaims;

	public function setUp() {
		parent::setUp();

		$this->importClaims = $this->getMockery(
			ImportClaims::class
		)->makePartial();
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::run()
	 */
	public function testRunWithLoadConfigurationsException() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$exception = [
			'class' => Exception::class,
			'message' => 'Exception Text',
			'code' => 333
		];

		$this->importClaims->shouldAllowMockingProtectedMethods()
			->shouldReceive('loadConfigurations')
			->once()
			->with($workload)
			->andThrow(
				$exception['class'],
				$exception['message'],
				$exception['code']
			);

		$this->assertEquals(
			$exception['code'],
			$this->importClaims->run($job, $log),
			'Exception code should have been returned'
		);

		$this->assertEquals(
			[
				$exception['message']
			],
			$log,
			'Log should have returned exception message'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::run()
	 */
	public function testRunWithProcessClaimException() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$exception = [
			'class' => Exception::class,
			'message' => 'Exception Text',
			'code' => 333
		];

		$this->importClaims->shouldAllowMockingProtectedMethods()
			->shouldReceive('loadConfigurations')
			->once()
			->with($workload);

		$this->importClaims->shouldAllowMockingProtectedMethods()
			->shouldReceive('processClaim')
			->once()
			->with($workload)
			->andThrow(
				$exception['class'],
				$exception['message'],
				$exception['code']
			);

		$this->assertEquals(
			$exception['code'],
			$this->importClaims->run($job, $log),
			'Exception code should have been returned'
		);

		$this->assertEquals(
			[
				$exception['message']
			],
			$log,
			'Log should have returned exception message'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::run()
	 */
	public function testRunWithoutException() {
		$job = $this->getMockery(
			WorkerJob::class
		);

		$log = [];

		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$job->shouldReceive('getWorkload')
			->once()
			->andReturn(json_encode($workload));

		$this->importClaims->shouldAllowMockingProtectedMethods()
			->shouldReceive('loadConfigurations')
			->once()
			->with($workload);

		$this->importClaims->shouldAllowMockingProtectedMethods()
			->shouldReceive('processClaim')
			->once()
			->with($workload);

		$this->assertNull(
			$this->importClaims->run($job, $log),
			'Should not have returned a value'
		);

		$this->assertEquals(
			[],
			$log,
			'Log should have been empty'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::loadConfigurations()
	 */
	public function testLoadConfigurationsWithMissingCredentialsConfiguration() {
		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$parentDirectory = vfsStream::setup(
			'cache',
			0700,
			[
				'credentials' => [],
			]
		);

		$this->importClaims->shouldAllowMockingProtectedMethods()
			->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($parentDirectory->url().'/credentials');

		$this->setExpectedException(
			'Exception',
			'Remote configuration faild to load',
			1
		);

		$this->callProtectedMethod(
			$this->importClaims,
			'loadConfigurations',
			[
				$workload
			]
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::loadConfigurations()
	 */
	public function testLoadConfigurationsWithMissingClaimsConfiguration() {
		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$configuration = [
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

		$parentDirectory = vfsStream::setup(
			'cache',
			0700,
			[
				'credentials' => [
					$workload['client'].'.json' => json_encode($configuration)
				],
			]
		);

		$this->importClaims->shouldAllowMockingProtectedMethods();

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($parentDirectory->getChild('credentials')->url());

		$this->importClaims->shouldReceive('options->set')
			->once()
			->with('Remote', $configuration);

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('ClaimsConfig')
			->andReturn($parentDirectory->url().'/app.php');

		$this->setExpectedException(
			'Exception',
			'Claims configuration faild to load',
			2
		);

		$this->callProtectedMethod(
			$this->importClaims,
			'loadConfigurations',
			[
				$workload
			]
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::loadConfigurations()
	 */
	public function testLoadConfigurationsWithoutMissingConfigurations() {
		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$configuration = [
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

		$claimsConfigurationPath = __DIR__.'/../../../config/app.php';

		$parentDirectory = vfsStream::setup(
			'cache',
			0700,
			[
				'credentials' => [
					$workload['client'].'.json' => json_encode($configuration)
				],
				'app.php' => file_get_contents($claimsConfigurationPath)
			]
		);

		$this->importClaims->shouldAllowMockingProtectedMethods();

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Credentials.path')
			->andReturn($parentDirectory->getChild('credentials')->url());

		$this->importClaims->shouldReceive('options->set')
			->once()
			->with('Remote', $configuration);

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('ClaimsConfig')
			->andReturn($parentDirectory->getChild('app.php')->url());

		$this->importClaims->shouldReceive('options->set')
			->once()
			->with('Claims', include($parentDirectory->getChild('app.php')->url()));

		$this->callProtectedMethod(
			$this->importClaims,
			'loadConfigurations',
			[
				$workload
			]
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::processClaim()
	 */
	public function testProcessClaimWithFailureFindingRecord() {
		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$this->importClaims->shouldAllowMockingProtectedMethods();

		$this->importClaims->shouldReceive('setupDatabaseConnection');

		$claimsRecord = $this->getMockery(
			'alias:'.PqrsImportFiles::class
		)->makePartial();

		$claimsRecord->shouldReceive('where')
			->with('id', $workload['fileId'])
			->andReturnSelf();

		$claimsRecord->shouldReceive('first')
			->andReturn(null);

		$this->setExpectedException(
			'Exception',
			'Claims file ['.$workload['fileId'].'] could not be found in remote database for client ['.$workload['client'].']',
			3
		);

		$this->callProtectedMethod(
			$this->importClaims,
			'processClaim',
			[
				$workload
			]
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::processClaim()
	 */
	public function testProcessClaimWithFailureProcessing() {
		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$this->importClaims->shouldAllowMockingProtectedMethods();

		$this->importClaims->shouldReceive('setupDatabaseConnection');

		$claimsRecord = $this->getMockery(
			'alias:'.PqrsImportFiles::class
		)->makePartial();

		$claimsRecord->shouldReceive('where')
			->with('id', $workload['fileId'])
			->andReturnSelf();

		$claimsRecord->shouldReceive('first')
			->andReturnSelf();

		$claimsRecord->shouldReceive('save')
			->twice();

		$claimsOptions = 'Claims Options Object';

		$this->importClaims->shouldReceive('options->getSubset')
			->once()
			->with('Claims')
			->andReturn($claimsOptions);

		$raw = $this->getMockery(
			'alias:'.Raw::class
		)->makePartial();

		$raw->shouldReceive('getInstance')
			->once()
			->with($claimsOptions)
			->andReturnSelf();

		$claimsRecord->relative_path = '/root/path/claims/file';

		$claimsFile = 'segments for claims file';

		$this->importClaims->shouldReceive('getClaimsFile')
			->once()
			->with($claimsRecord->relative_path)
			->andReturn($claimsFile);

		$exception = [
			'class' => Exception::class,
			'message' => 'Failed Raw Parse',
			'code' => 333
		];

		$raw->shouldReceive('parse')
			->once()
			->andThrow(
				$exception['class'],
				$exception['message'],
				$exception['code']
			);

		$this->callProtectedMethod(
			$this->importClaims,
			'processClaim',
			[
				$workload
			]
		);

		$this->assertEquals(
			'Failed',
			$claimsRecord->status,
			'Claims record should have been set to Failed'
		);

		$this->assertEquals(
			$exception['message'],
			$claimsRecord->failed_reason,
			'Claims record should have stored failed message'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::processClaim()
	 */
	public function testProcessClaimWithSuccessProcessing() {
		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$this->importClaims->shouldAllowMockingProtectedMethods();

		$this->importClaims->shouldReceive('setupDatabaseConnection');

		$claimsRecord = $this->getMockery(
			'alias:'.PqrsImportFiles::class
		)->makePartial();

		$claimsRecord->shouldReceive('where')
			->with('id', $workload['fileId'])
			->andReturnSelf();

		$claimsRecord->shouldReceive('first')
			->andReturnSelf();

		$claimsRecord->shouldReceive('save')
			->twice();

		$claimsOptions = 'Claims Options Object';

		$this->importClaims->shouldReceive('options->getSubset')
			->twice()
			->with('Claims')
			->andReturn($claimsOptions);

		$raw = $this->getMockery(
			'alias:'.Raw::class
		)->makePartial();

		$raw->shouldReceive('getInstance')
			->once()
			->with($claimsOptions)
			->andReturnSelf();

		$claimsRecord->relative_path = '/root/path/claims/file';

		$claimsFile = 'segments for claims file';

		$this->importClaims->shouldReceive('getClaimsFile')
			->once()
			->with($claimsRecord->relative_path)
			->andReturn($claimsFile);

		$raw->shouldReceive('parse')
			->once()
			->with($claimsFile);

		$document = $this->getMockery(
			'alias:'.X12N837::class
		)->makePartial();

		$document->shouldReceive('getInstance')
			->once()
			->with($claimsOptions)
			->andReturnSelf();

		$document->shouldReceive('parse')
			->once()
			->with($raw);

		$store = 'Claims Store Object';

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Claims.App.store')
			->andReturn($store);

		$cache = $this->getMockery(
			'alias:'.Cache::class
		)->makePartial();

		$cache->shouldReceive('getInstance')
			->once()
			->with($store)
			->andReturnSelf();

		$cache->shouldReceive('processDocument')
			->once()
			->with($document);

		$this->callProtectedMethod(
			$this->importClaims,
			'processClaim',
			[
				$workload
			]
		);

		$this->assertEquals(
			'Completed',
			$claimsRecord->status,
			'Claims record should have been set to completed'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::setupDatabaseConnection()
	 */
	public function testSetupDatabaseConnectionWithFailedConnection() {
		$this->importClaims->shouldAllowMockingProtectedMethods();

		$options = [
			'claims' => [
				'host' => 'host',
				'database' => 'db'
			],
			'remote' => [
				'host' => 'host2',
				'database' => 'db2'
			]
		];

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Claims.Store.connections.mysql')
			->andReturn($options['claims']);

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.mysql')
			->andReturn($options['remote']);

		$this->importClaims->shouldReceive('options->set')
			->once()
			->with(
				'Claims.Store.connections.mysql',
				array_merge($options['claims'], $options['remote'])
			);

		$database = $this->getMockery(
			'alias:'.Database::class
		)->makePartial();

		$claimsOptions = 'Claims Options Object';

		$this->importClaims->shouldReceive('options->getSubset')
			->once()
			->with('Claims')
			->andReturn($claimsOptions);

		$database->shouldReceive('getInstance')
			->once()
			->with($claimsOptions)
			->andReturnSelf();

		$exception = [
			'class' => Exception::class,
			'message' => 'Failed to return PDO',
			'code' => 333
		];

		$database->shouldReceive('getManager->getConnection->getPdo')
			->once()
			->andThrow(
				$exception['class'],
				$exception['message'],
				$exception['code']
			);

		$this->setExpectedException(
			'Exception',
			'Failed to connect to database',
			4
		);

		$this->callProtectedMethod(
			$this->importClaims,
			'setupDatabaseConnection'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::setupDatabaseConnection()
	 */
	public function testSetupDatabaseConnectionWithSuccessfulConnection() {
		$this->importClaims->shouldAllowMockingProtectedMethods();

		$options = [
			'claims' => [
				'host' => 'host',
				'database' => 'db'
			],
			'remote' => [
				'host' => 'host2',
				'database' => 'db2'
			]
		];

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Claims.Store.connections.mysql')
			->andReturn($options['claims']);

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.mysql')
			->andReturn($options['remote']);

		$this->importClaims->shouldReceive('options->set')
			->once()
			->with(
				'Claims.Store.connections.mysql',
				array_merge($options['claims'], $options['remote'])
			);

		$database = $this->getMockery(
			'alias:'.Database::class
		)->makePartial();

		$claimsOptions = 'Claims Options Object';

		$this->importClaims->shouldReceive('options->getSubset')
			->once()
			->with('Claims')
			->andReturn($claimsOptions);

		$database->shouldReceive('getInstance')
			->once()
			->with($claimsOptions)
			->andReturnSelf();

		$database->shouldReceive('getManager->getConnection->getPdo')
			->once();

		$this->importClaims->shouldReceive('options->set')
			->once()
			->with('Claims.App.store', $database);

		$this->callProtectedMethod(
			$this->importClaims,
			'setupDatabaseConnection'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::getClaimsFile()
	 */
	public function testGetClaimsFileWithFailedSftpConnection() {
		$exception = [
			'class' => Exception::class,
			'message' => 'Failed connecting to SFTP',
			'code' => 333
		];

		$this->importClaims->shouldAllowMockingProtectedMethods()
			->shouldReceive('getSFTPconnection')
			->andThrow(
				$exception['class'],
				$exception['message'],
				$exception['code']
			);

		$this->setExpectedException(
			'Exception',
			'Failed to connect to remote filesystem',
			5
		);

		$this->callProtectedMethod(
			$this->importClaims,
			'getClaimsFile',
			[
				'/path/to/file'
			]
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::getClaimsFile()
	 */
	public function testGetClaimsFileWithFailedStfpChdir() {
		$sftp = $this->getMockery(
			'alias:'.SFTP::class
		)->makePartial();

		$this->importClaims->shouldAllowMockingProtectedMethods();

		$this->importClaims->shouldReceive('getSFTPconnection')
			->andReturn($sftp);

		$directoryPath = [
			'site' => 'cache',
			'x12' => '/PQRS/X12N837/',
			'file' => 'path/to/filen'
		];

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.site')
			->andReturn($directoryPath['site']);

		$sftp->shouldReceive('chdir')
			->once()
			->with(dirname(implode('', $directoryPath)))
			->andReturn(false);

		$this->setExpectedException(
			'Exception',
			'Failed connecting to remote site claims directory',
			6
		);

		$this->callProtectedMethod(
			$this->importClaims,
			'getClaimsFile',
			[
				$directoryPath['file']
			]
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::getClaimsFile()
	 */
	public function testGetClaimsFileWithFailedRetrieval() {
		$sftp = $this->getMockery(
			'alias:'.SFTP::class
		)->makePartial();

		$this->importClaims->shouldAllowMockingProtectedMethods();

		$this->importClaims->shouldReceive('getSFTPconnection')
			->andReturn($sftp);

		$directoryPath = [
			'site' => 'cache',
			'x12' => '/PQRS/X12N837/',
			'file' => 'path/to/filen'
		];

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.site')
			->andReturn($directoryPath['site']);

		$sftp->shouldReceive('chdir')
			->once()
			->with(dirname(implode('', $directoryPath)))
			->andReturn(true);

		$sftp->shouldReceive('get')
			->once()
			->with(basename($directoryPath['file']))
			->andReturn(false);

		$this->setExpectedException(
			'Exception',
			'Failed to open claim file',
			7
		);

		$this->callProtectedMethod(
			$this->importClaims,
			'getClaimsFile',
			[
				$directoryPath['file']
			]
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::getClaimsFile()
	 */
	public function testGetClaimsFileWithoutFailure() {
		$sftp = $this->getMockery(
			'alias:'.SFTP::class
		)->makePartial();

		$this->importClaims->shouldAllowMockingProtectedMethods();

		$this->importClaims->shouldReceive('getSFTPconnection')
			->andReturn($sftp);

		$directoryPath = [
			'site' => 'cache',
			'x12' => '/PQRS/X12N837/',
			'file' => 'path/to/filen'
		];

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.site')
			->andReturn($directoryPath['site']);

		$sftp->shouldReceive('chdir')
			->once()
			->with(dirname(implode('', $directoryPath)))
			->andReturn(true);

		$claimsFile = 'Claims File Content';

		$sftp->shouldReceive('get')
			->once()
			->with(basename($directoryPath['file']))
			->andReturn($claimsFile);

		$this->assertEquals(
			$claimsFile,
			$this->callProtectedMethod(
				$this->importClaims,
				'getClaimsFile',
				[
					$directoryPath['file']
				]
			),
			'Claims file content not returned as expected'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::getSFTPconnection()
	 */
	public function testGetSFTPconnectionWithPasswordAndLoginFailure() {
		$this->importClaims->shouldAllowMockingProtectedMethods();

		$ssh = [
			'host' => '127.0.0.1',
			'port' => '22',
			'password' => 'abc123',
			'username' => 'user1'
		];

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.host', $ssh['host'])
			->andReturn($ssh['host']);

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.port', $ssh['port'])
			->andReturn($ssh['port']);

		$sftp = $this->getMockery(
			'overload:'.SFTP::class
		)->makePartial();

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('SFTP.privateKey.path')
			->andReturn(null);

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('SFTP.password')
			->andReturn($ssh['password']);

		$this->importClaims->shouldReceive('options->get')
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
			$this->importClaims,
			'getSFTPconnection'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::getSFTPconnection()
	 */
	public function testGetSFTPconnectionWithPassword() {
		$this->importClaims->shouldAllowMockingProtectedMethods();

		$ssh = [
			'host' => '127.0.0.1',
			'port' => '22',
			'password' => 'abc123',
			'username' => 'user1'
		];

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.host', $ssh['host'])
			->andReturn($ssh['host']);

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.port', $ssh['port'])
			->andReturn($ssh['port']);

		$sftp = $this->getMockery(
			'overload:'.SFTP::class
		)->makePartial();

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('SFTP.privateKey.path')
			->andReturn(null);

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('SFTP.password')
			->andReturn($ssh['password']);

		$this->importClaims->shouldReceive('options->get')
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
				$this->importClaims,
				'getSFTPconnection'
			),
			'SFTP object not returned as expected'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::getSFTPconnection()
	 */
	public function testGetSFTPconnectionWithPrivateKey() {
		$this->importClaims->shouldAllowMockingProtectedMethods();

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

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.host', $ssh['host'])
			->andReturn($ssh['host']);

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.port', $ssh['port'])
			->andReturn($ssh['port']);

		$sftp = $this->getMockery(
			'overload:'.SFTP::class
		)->makePartial();

		$this->importClaims->shouldReceive('options->get')
			->twice()
			->with('SFTP.privateKey.path')
			->andReturn($ssh['path']);

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('SFTP.privateKey.passphrase')
			->andReturn($ssh['passphrase']);

		$rsa = $this->getMockery(
			'overload:'.RSA::class
		)->makePartial();

		$rsa->shouldReceive('loadKey')
			->once()
			->with($privateKeyContents);

		$this->importClaims->shouldReceive('options->get')
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
				$this->importClaims,
				'getSFTPconnection'
			),
			'SFTP object not returned as expected'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\ImportClaims::getSFTPconnection()
	 */
	public function testGetSFTPconnectionWithPrivateKeyAndPassphrase() {
		$this->importClaims->shouldAllowMockingProtectedMethods();

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

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.host', $ssh['host'])
			->andReturn($ssh['host']);

		$this->importClaims->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.port', $ssh['port'])
			->andReturn($ssh['port']);

		$sftp = $this->getMockery(
			'overload:'.SFTP::class
		)->makePartial();

		$this->importClaims->shouldReceive('options->get')
			->twice()
			->with('SFTP.privateKey.path')
			->andReturn($ssh['path']);

		$this->importClaims->shouldReceive('options->get')
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

		$this->importClaims->shouldReceive('options->get')
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
				$this->importClaims,
				'getSFTPconnection'
			),
			'SFTP object not returned as expected'
		);
	}

}