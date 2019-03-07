<?php
namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

//use local_imisusermerge\merge_file;
//use local_imisusermerge\merge_action;
//use local_imisusermerge\merge_exception;


class cron_task_testcase extends base {

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