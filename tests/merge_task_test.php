<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

use local_imisusermerge\task\merge_task;
use local_imisusermerge\merge_file;
use core\task\manager as task_manager;

class merge_task_testcase extends base {

    public function setUp() {
        parent::setup();
    }

    public function tearDown() {
        parent::tearDown();
    }

    /**
     * @throws \moodle_exception
     */
    protected function do_task() {
        // Create and queue a merge_task.
        $task = new merge_task();
        \core\task\manager::queue_adhoc_task($task);

        // Get it from the scheduler and execute it
        $now = time();
        $task = \core\task\manager::get_next_adhoc_task($now);
        $this->assertInstanceOf('\\local_imisusermerge\\task\\merge_task', $task);

        try {
            $task->execute(); // No exceptions expected
            \core\task\manager::adhoc_task_complete($task);
        } catch (\Exception $ex) {
            \core\task\manager::adhoc_task_failed($task);
        }
    }

    /**
     * @throws \moodle_exception
     */
    public function test_empty_file() {
        $this->resetAfterTest(true);

        $timestamp = "20190101-000000";
        $path = $this->write_request_file("20190101-000000", [
            'duplicateid,mergetoid,dateofmerge,full_name,email'
        ]);

        $this->assertFileExists($path);
        $this->do_task();
        $this->assertFileNotExists($path, "File has been deleted");
        $this->assertNull(\core\task\manager::get_next_adhoc_task(time()+3600), "Task is gone");
    }

    /**
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_no_file() {
        $this->resetAfterTest(true);

        $this->resetAfterTest(true);
        $this->assertNull(merge_file::get_next_file());
        $this->do_task();
        $this->assertNull(\core\task\manager::get_next_adhoc_task(time()+3600), "Task is gone");
    }

}