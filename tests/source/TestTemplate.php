<?php

namespace SunCoastConnection\ClaimsToEMRGearman\Tests;

use \SunCoastConnection\ClaimsToEMRGearman\Template;
use \SunCoastConnection\ClaimsToEMRGearman\Tests\BaseTestCase;

class TemplateTest extends BaseTestCase {

	protected $Template;

	public function setUp() {
		parent::setUp();

		$this->Template = $this->getMockery(
			Template::class
		)->makePartial();

	}

	public function tearDown() {
		parent::tearDown();

	}

	/**
	 * @covers SunCoastConnection\ClaimsToEMR\Template::()
	 */
	public function test() {
		$this->markTestIncomplete('Not yet implemented');
	}

}