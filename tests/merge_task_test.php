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

    protected function run_task() {
        $task = null;
        $exception = null;

        if ($task = \core\task\manager::get_next_adhoc_task(time())) {
            $this->assertInstanceOf('\\local_imisusermerge\\task\\merge_task', $task);

            try {
                $task->execute();
                \core\task\manager::adhoc_task_complete($task);

            } catch (\Exception $ex) {
                $exception = $ex;
                \core\task\manager::adhoc_task_failed($task);
            }
        }

        return $exception;

    }

    public function test_task_with_file_dataset() {
        return [
            [
                'No File' => [
                    0,
                    [
                    ],
                    false
                ],

                'No Data' => [
                    0,
                    [
                        'duplicateid,mergetoid,dateofmerge,full_name,email'
                    ],
                    false
                ],

                'Non Matching Lines' => [
                    0,
                    [
                        'duplicateid,mergetoid,dateofmerge,full_name,email',
                        'a,a1,1/1/2019,name,email@email.com',
                        'a,a1,1/1/2019,name', // missing email
                    ],
                    true
                ]

            ]

        ];
    }

    /**
     * @dataprovider test_task_with_file_dataset
     */
    public function test_task_with_file($data, $expect_exception) {
        $this->resetAfterTest(true);

        $path = null;
        if (!empty($data)) {
            $path = $this->write_request_file('20190101-000001', $data);
        }

        \core\task\manager::queue_adhoc_task(new merge_task());

        $exception = $this->run_task();

        if ($expect_exception) {
            $this->assertNotNull($exception, "Exception expected");
            $this->assertSuccessFiles($path);
        } else {
            $this->assertNull($exception, "Exception not expected");
            $this->asserFailedFiles($path);
        }
    }

    /**
     * @throws \moodle_exception
     */
    public function test_all_files_processed() {
        $this->resetAfterTest(true);

        $timestamp = "20190101-000000";
        $path = $this->write_request_file("20190101-000000", [
            'duplicateid,mergetoid,dateofmerge,full_name,email'
        ]); // Headers, no data

        $this->run_task();
        $this->assertFileNotExists($path, "File has been deleted");
        $this->assertNull(\core\task\manager::get_next_adhoc_task(time() + 3600), "Task is gone");
    }

}