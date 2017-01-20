<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests\Worker\Claims;

use \Exception;
use \Kicken\Gearman\Job\WorkerJob;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;
use \SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process;
use \SunCoastConnection\ClaimsToEMR\Cache;
use \SunCoastConnection\ClaimsToEMR\Models\PqrsImportFiles;
use \SunCoastConnection\ClaimsToEMR\Store\Database;
use \SunCoastConnection\ClaimsToEMR\X12N837;
use \SunCoastConnection\ParseX12\Raw;
use \org\bovigo\vfs\vfsStream;

class ProcessTest extends BaseTestCase {

	protected $process;

	public function setUp() {
		parent::setUp();

		$this->process = $this->getMockery(
			Process::class
		)->makePartial();
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process::run()
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

		$this->process->shouldAllowMockingProtectedMethods()
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
			$this->process->run($job, $log),
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
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process::run()
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

		$this->process->shouldAllowMockingProtectedMethods()
			->shouldReceive('loadConfigurations')
			->once()
			->with($workload);

		$this->process->shouldAllowMockingProtectedMethods()
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
			$this->process->run($job, $log),
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
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process::run()
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

		$this->process->shouldAllowMockingProtectedMethods()
			->shouldReceive('loadConfigurations')
			->once()
			->with($workload);

		$this->process->shouldAllowMockingProtectedMethods()
			->shouldReceive('processClaim')
			->once()
			->with($workload);

		$this->assertNull(
			$this->process->run($job, $log),
			'Should not have returned a value'
		);

		$this->assertEquals(
			[],
			$log,
			'Log should have been empty'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process::loadConfigurations()
	 */
	public function testLoadConfigurationsWithFailedJob() {
		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$this->process->shouldAllowMockingProtectedMethods();

		$this->process->shouldReceive('options->get')
			->once()
			->with('Workers.Credentials.Lookup')
			->andReturn('WorkerName');

		$this->process->shouldReceive('gearmanJob')
			->once()
			->with(
				'WorkerName',
				json_encode(['client' => $workload['client']])
			)
			->andReturn(2);

		$this->setExpectedException(
			'Exception',
			$workload['client'],
			1
		);

		$this->callProtectedMethod(
			$this->process,
			'loadConfigurations',
			[
				$workload
			]
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process::loadConfigurations()
	 */
	public function testLoadConfigurationsWithSuccessfulJob() {
		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$this->process->shouldAllowMockingProtectedMethods();

		$this->process->shouldReceive('options->get')
			->once()
			->with('Workers.Credentials.Lookup')
			->andReturn('WorkerName');

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

		$this->process->shouldReceive('gearmanJob')
			->once()
			->with(
				'WorkerName',
				json_encode(['client' => $workload['client']])
			)
			->andReturn(json_encode($configuration));

		$this->process->shouldReceive('options->set')
			->once()
			->with('Claims', $configuration);

		$this->callProtectedMethod(
			$this->process,
			'loadConfigurations',
			[
				$workload
			]
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process::processClaim()
	 */
	public function testProcessClaimWithFailureFindingRecord() {
		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$this->process->shouldAllowMockingProtectedMethods();

		$this->process->shouldReceive('setupDatabaseConnection');

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
			$this->process,
			'processClaim',
			[
				$workload
			]
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process::processClaim()
	 */
	public function testProcessClaimWithFailureProcessing() {
		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$this->process->shouldAllowMockingProtectedMethods();

		$this->process->shouldReceive('setupDatabaseConnection');

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

		$this->process->shouldReceive('options->getSubset')
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

		$this->process->shouldReceive('getClaimsFile')
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
			$this->process,
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
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process::processClaim()
	 */
	public function testProcessClaimWithSuccessProcessing() {
		$workload = [
			'client' => 'tokenName',
			'fileId' => 123
		];

		$this->process->shouldAllowMockingProtectedMethods();

		$this->process->shouldReceive('setupDatabaseConnection');

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

		$this->process->shouldReceive('options->getSubset')
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

		$this->process->shouldReceive('getClaimsFile')
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

		$this->process->shouldReceive('options->get')
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
			$this->process,
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
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process::setupDatabaseConnection()
	 */
	public function testSetupDatabaseConnectionWithFailedConnection() {
		$this->process->shouldAllowMockingProtectedMethods();

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

		$this->process->shouldReceive('options->get')
			->once()
			->with('Claims.Store.connections.mysql')
			->andReturn($options['claims']);

		$this->process->shouldReceive('options->get')
			->once()
			->with('Remote.mysql')
			->andReturn($options['remote']);

		$this->process->shouldReceive('options->set')
			->once()
			->with(
				'Claims.Store.connections.mysql',
				array_merge($options['claims'], $options['remote'])
			);

		$database = $this->getMockery(
			'alias:'.Database::class
		)->makePartial();

		$claimsOptions = 'Claims Options Object';

		$this->process->shouldReceive('options->getSubset')
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
			$this->process,
			'setupDatabaseConnection'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process::setupDatabaseConnection()
	 */
	public function testSetupDatabaseConnectionWithSuccessfulConnection() {
		$this->process->shouldAllowMockingProtectedMethods();

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

		$this->process->shouldReceive('options->get')
			->once()
			->with('Claims.Store.connections.mysql')
			->andReturn($options['claims']);

		$this->process->shouldReceive('options->get')
			->once()
			->with('Remote.mysql')
			->andReturn($options['remote']);

		$this->process->shouldReceive('options->set')
			->once()
			->with(
				'Claims.Store.connections.mysql',
				array_merge($options['claims'], $options['remote'])
			);

		$database = $this->getMockery(
			'alias:'.Database::class
		)->makePartial();

		$claimsOptions = 'Claims Options Object';

		$this->process->shouldReceive('options->getSubset')
			->once()
			->with('Claims')
			->andReturn($claimsOptions);

		$database->shouldReceive('getInstance')
			->once()
			->with($claimsOptions)
			->andReturnSelf();

		$database->shouldReceive('getManager->getConnection->getPdo')
			->once();

		$this->process->shouldReceive('options->set')
			->once()
			->with('Claims.App.store', $database);

		$this->callProtectedMethod(
			$this->process,
			'setupDatabaseConnection'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker\Claims\Process::getClaimsFile()
	 */
	public function testGetClaimsFile() {
		$this->process->shouldAllowMockingProtectedMethods();

		$this->process->shouldReceive('options->get')
			->once()
			->with('Workers.Claims.Retrieve')
			->andReturn('WorkerName');

		$ssh = [
			'host' => 'host',
			'port' => 'port',
			'site' => 'site',
		];

		$this->process->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.host')
			->andReturn($ssh['host']);

		$this->process->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.port')
			->andReturn($ssh['port']);

		$this->process->shouldReceive('options->get')
			->once()
			->with('Remote.ssh.site')
			->andReturn($ssh['site']);

		$relativePath = 'relative/path.ext';
		$claim = 'Claim file';

		$this->process->shouldReceive('gearmanJob')
			->once()
			->with(
				'WorkerName',
				json_encode([
					'host' => $ssh['host'],
					'port' => $ssh['port'],
					'path' => $ssh['site'].'/'.$relativePath,
				])
			)
			->andReturn($claim);

		$this->assertEquals(
			$claim,
			$this->callProtectedMethod(
				$this->process,
				'getClaimsFile',
				[
					$relativePath
				]
			),
			'Claim file not returned'
		);
	}

}