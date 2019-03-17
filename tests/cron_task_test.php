<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

use local_imisusermerge\imisusermerge;
use local_imisusermerge\task\cron_task;
use local_imisusermerge\task\merge_task;

/**
 * Class cron_task_testcase
 * @package local_imisusermerge\tests
 */
class cron_task_testcase extends base {

    /**
     * @throws \coding_exception
     * @throws \local_imisusermerge\merge_exception
     */
    public function setUp() {
        parent::setup();
    }

    /**
     *
     */
    public function tearDown() {
        parent::tearDown();
    }

    /**
     * @return array
     * @throws \dml_exception
     */
    protected function get_merge_tasks() {
        global $DB;
        $comp = imisusermerge::COMPONENT_NAME;
        $class = '\\' . merge_task::class;
        return $DB->get_records('task_adhoc', [
            'component' => $comp,
            'classname' => $class
        ]);
    }

    /**
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \local_imisusermerge\merge_exception
     */
    public function test_cron_no_task() {
        $this->resetAfterTest(true);

        $this->assertEmpty($this->get_merge_tasks());
        (new cron_task())->execute();
        $this->assertEquals(1, count($this->get_merge_tasks()));

        // Execute again and verify no additioonal task is created
        (new cron_task())->execute();
        $this->assertEquals(1, count($this->get_merge_tasks()));
    }

}