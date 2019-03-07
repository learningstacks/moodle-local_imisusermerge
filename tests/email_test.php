<?php
namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

//use local_imisusermerge\merge_file;
//use local_imisusermerge\merge_action;
//use local_imisusermerge\merge_exception;


class email_testcase extends base {

    public function setUp() {
        parent::setup();
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function test_send_email() {
        $this->resetAfterTest(true);

        $this->markTestIncomplete();
    }

}