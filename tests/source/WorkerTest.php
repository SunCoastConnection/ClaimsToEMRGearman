<?php

namespace SunCoastConnection\ClaimsToEMR\Tests;

use \Kicken\Gearman\Client;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\WorkerMock;
use \SunCoastConnection\ClaimsToEMRGearman\Worker;
use \SunCoastConnection\ParseX12\Options;

class WorkerTest extends BaseTestCase {

	protected $worker;

	public function setUp() {
		parent::setUp();

		$this->worker = $this->getMockery(
			Worker::class
		)->makePartial();
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker::getInstance()
	 */
	public function testGetInstance() {
		$options = $this->getMockery(
			Options::class
		);

		$worker = WorkerMock::getInstance($options);

		$this->assertInstanceOf(
			Worker::class,
			$worker,
			'Expected new instance of '.Worker::class.'.'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker::__construct()
	 */
	public function testConstruct() {
		$options = $this->getMockery(
			Options::class
		);

		$this->worker->shouldAllowMockingProtectedMethods();

		$this->worker->shouldReceive('options')
			->once()
			->with($options);

		$this->worker->__construct($options);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker::options()
	 */
	public function testOptions() {
		$this->assertNull(
			$this->worker->options(),
			'Options should return null when empty.'
		);

		$options = $this->getMockery(
			Options::class
		);

		$this->assertSame(
			$options,
			$this->worker->options($options),
			'Options should return set option object when setting value.'
		);

		$this->assertSame(
			$options,
			$this->worker->options(),
			'Options should return set option object after setting value.'
		);
	}


	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker::setGearmanServers()
	 */
	public function testSetGearmanServers() {
		$servers = [
			'127.0.0.1:4730'
		];

		$this->worker->setGearmanServers($servers);

		$this->assertEquals(
			$servers,
			$this->getProtectedProperty(
				$this->worker,
				'servers'
			),
			'Servers not set correctly'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker::getGearmanClient()
	 */
	public function testGetGearmanClientWithNoServersSet() {
		$this->assertNull(
			$this->worker->getGearmanClient(),
			'Gearman Client should not have been returned'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker::getGearmanClient()
	 */
	public function testGetGearmanClient() {
		$servers = [
			'127.0.0.1:4730'
		];

		$this->setProtectedProperty(
			$this->worker,
			'servers',
			$servers
		);

		$client = $this->worker->getGearmanClient();

		$this->assertInstanceOf(
			Client::class,
			$client,
			'Gearman Client not returned'
		);

		$connection = $this->getProtectedProperty(
			$client,
			'connection'
		);

		$this->assertEquals(
			$servers,
			$this->getProtectedProperty(
				$this->getProtectedProperty(
					$client,
					'connection'
				),
				'serverList'
			),
			'Gearman servers not set'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker::gearmanJob()
	 */
	public function testGearmanJobWithNoClient() {
		$jobArguments = [
			'function' => 'functionName',
			'workload' => json_encode([ 'work', 'load' ]),
			'priority' => 0,
			'unique' => 'abc123'
		];

		$this->worker->shouldReceive('getGearmanClient')
			->once();

		$this->assertNull(
			$this->worker->gearmanJob(
				$jobArguments['function'],
				$jobArguments['workload'],
				$jobArguments['priority'],
				$jobArguments['unique']
			),
			'Job should have returned null'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker::gearmanJob()
	 */
	public function testGearmanJob() {
		$jobArguments = [
			'function' => 'functionName',
			'workload' => json_encode([ 'work', 'load' ]),
			'priority' => 0,
			'unique' => 'abc123'
		];

		$client = $this->getMockery(
			Client::class
		);

		$this->worker->shouldReceive('getGearmanClient')
			->once()
			->andReturn($client);

		$client->shouldReceive('submitJob')
			->once()
			->with(
				$jobArguments['function'],
				$jobArguments['workload'],
				$jobArguments['priority'],
				$jobArguments['unique']
			)
			->andReturnSelf();

		$client->shouldReceive('wait')
			->once();

		$client->shouldReceive('getResult')
			->once()
			->andReturnSelf();

		$this->assertSame(
			$client,
			$this->worker->gearmanJob(
				$jobArguments['function'],
				$jobArguments['workload'],
				$jobArguments['priority'],
				$jobArguments['unique']
			),
			'Job should have returned job results'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker::gearmanBackgroundJob()
	 */
	public function testGearmanBackgroundJobWithNoClient() {
		$jobArguments = [
			'function' => 'functionName',
			'workload' => json_encode([ 'work', 'load' ]),
			'priority' => 0,
			'unique' => 'abc123'
		];

		$this->worker->shouldReceive('getGearmanClient')
			->once();

		$this->assertNull(
			$this->worker->gearmanBackgroundJob(
				$jobArguments['function'],
				$jobArguments['workload'],
				$jobArguments['priority'],
				$jobArguments['unique']
			),
			'Job should have returned null'
		);
	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMRGearman\Worker::gearmanBackgroundJob()
	 */
	public function testGearmanBackgroundJob() {
		$jobArguments = [
			'function' => 'functionName',
			'workload' => json_encode([ 'work', 'load' ]),
			'priority' => 0,
			'unique' => 'abc123'
		];

		$client = $this->getMockery(
			Client::class
		);

		$this->worker->shouldReceive('getGearmanClient')
			->once()
			->andReturn($client);

		$client->shouldReceive('submitBackgroundJob')
			->once()
			->with(
				$jobArguments['function'],
				$jobArguments['workload'],
				$jobArguments['priority'],
				$jobArguments['unique']
			)
			->andReturnSelf();

		$this->assertSame(
			$client,
			$this->worker->gearmanBackgroundJob(
				$jobArguments['function'],
				$jobArguments['workload'],
				$jobArguments['priority'],
				$jobArguments['unique']
			),
			'Job should have returned job results'
		);
	}

}