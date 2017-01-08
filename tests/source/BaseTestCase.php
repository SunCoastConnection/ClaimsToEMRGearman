<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests;

use \PHPUnit_Framework_TestCase;
use \Mockery;
use \ReflectionClass;

class BaseTestCase extends PHPUnit_Framework_TestCase {

	/**
	 * Closes Mockery if active
	 */
	public function tearDown() {
		parent::tearDown();

		Mockery::close();
	}

	/**
	 * Creates a mock class, registers it with the application, and returns it.
	 */
	protected function getMockery() {
		return call_user_func_array([Mockery::class, 'mock'], func_get_args());
	}

	/**
	 * Creates a mock class, registers it with the application, and returns it.
	 */
	protected function getMockeryNamed() {
		return call_user_func_array([Mockery::class, 'namedMock'], func_get_args());
	}

	protected function getMockeryOn() {
		return call_user_func_array([Mockery::class, 'on'], func_get_args());
	}

	protected function getProtectedProperty($object, $property) {
		$reflection = new ReflectionClass(get_class($object));

		$property = $reflection->getProperty($property);
		$property->setAccessible(true);
		return $property->getValue($object);
	}

	protected function setProtectedProperty($object, $property, $value) {
		$reflection = new ReflectionClass(get_class($object));

		$property = $reflection->getProperty($property);
		$property->setAccessible(true);
		$property->setValue($object, $value);
	}

	protected function callProtectedMethod($object, $method, array $arguments = []) {
		$reflection = new ReflectionClass(get_class($object));

		$method = $reflection->getMethod($method);
		$method->setAccessible(true);
		return $method->invokeArgs($object, $arguments);
	}

	/**
	 * Output a message to the command line during testing
	 *
	 * @param  string $message Message to display
	 *
	 * @example
	 *
	 * $this->outputMessage('Trigger A');
	 *   -OR-
	 * $this->outputMessage('-', true);
	 */
	protected function outputMessage($message, $raw = false) {
		if(!$raw) {
			$message = "\n$message\n";
		}

		fwrite(STDOUT, $message);
	}

	/**
	 * Output a time-test message to the command line during testing
	 *
	 * @param  string  $testName   Name of test
	 * @param  integer $iterations Number of iterations
	 * @param  float   $startTime  Unix time-stamp with microseconds when test started
	 * @param  float   $endTime    Unix time-stamp with microseconds when test ended
	 *
	 * @example
	 *
	 * $before = microtime(true);
	 * for($iteration = 0; $iteration < 100000; $iteration++) {
	 *     ClassName::$functionName();
	 * }
	 * $after = microtime(true);
	 * $this->outputTimeTestMessage($functionName, $iteration, $after - $before);
	 */
	protected function outputTimeTestMessage($testName, $iteration, $duration) {
		$instance = $duration / $iterations;
		$this->outputMessage("$testName\t[$duration seconds / $iterations times]\t$instance seconds / iteration");
	}

}