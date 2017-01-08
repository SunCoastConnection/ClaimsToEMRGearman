<?php

namespace SunCoastConnection\ClaimsToEMR\Tests;

use \SunCoastConnection\ClaimsToEMR\Document\Options;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\WorkerMock;
use \SunCoastConnection\ClaimsToEMRGearman\Worker;

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
	public function testConstructWith() {
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

}