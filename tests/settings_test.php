<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

use local_imisusermerge\imisusermerge;
use local_imisusermerge\merge_exception;

class settings_testcase extends base {

    public function setUp() {
        parent::setup();
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function test_settings() {
        $this->resetAfterTest(true);
        $this->markTestIncomplete();
    }
}