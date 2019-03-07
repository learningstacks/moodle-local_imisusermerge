<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

class settings_testcase extends base {

    public function setUp() {
        parent::setup();
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function test_get_config_values() {
        $this->resetAfterTest(true);
        $this->markTestIncomplete();
    }

}