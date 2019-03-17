<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

use local_imisusermerge\imisusermerge;
use local_imisusermerge\task\merge_task;
use core\task\manager as task_manager;

/**
 * Class merge_task_testcase
 * @package local_imisusermerge\tests
 */
class merge_task_testcase extends base {

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
     */
    public function task_with_file_dataset() {
        return [
            'No File' => [
                [
                ],
                true
            ],

            'No Data' => [
                [
                    'duplicateid,mergetoid,dateofmerge'
                ],
                true
            ],

            'Non Matching Lines' => [
                [
                    'duplicateid,mergetoid,dateofmerge',
                    'a,a1,1/1/2019',
                    'a,a1', // missing email
                ],
                false
            ]
        ];
    }

    /**
     * @dataProvider task_with_file_dataset
     * @param $data
     * @param $expect_success
     * @throws \moodle_exception
     */
    public function test_task_with_file($data, $expect_success) {
        $this->resetAfterTest(true);

        $path = null;
        if (!empty($data)) {
            $path = $this->write_request_file('20190101-000001', $data);
        }

        task_manager::queue_adhoc_task(new merge_task());

        $results = $this->do_adhoc_tasks();
        if ($expect_success) {
            $this->assertTrue($results[0][1], "Fail: Task did not succeed");
            if ($path) {
                $this->assertSuccessFiles($path);
            }
        } else {
            $this->assertFalse($results[0][1], "Fail: Task did not fail");
            if ($path) {
                $this->assertFailedFiles($path);
            }
        }
    }

    /**
     * Verify that when a task completes successfully, it creates another task
     * In so doing, all files will be processed
     * @throws \moodle_exception
     */
    public function test_all_files_processed_in_correct_order() {
        $this->resetAfterTest(true);

        $users = $this->create_users(range(1, 4));

        $path1 = $this->write_request_file("20190101-000002", [
            'duplicateid,mergetoid,dateofmerge',
            'user1,user2,1/1/2019'
        ]);

        $path2 = $this->write_request_file("20190101-000001", [
            'duplicateid,mergetoid,dateofmerge',
            'user3,user4,1/1/2019'
        ]);

        $merge_tool_mock = $this->getMergeToolMock();
        $merge_tool_mock
            ->expects($this->exactly(2))
            ->method('merge')
            ->withConsecutive(
                [$users[4]->id, $users[3]->id],
                [$users[2]->id, $users[1]->id]
            )
            ->willReturnOnConsecutiveCalls(
                [true, [], 0],
                [true, [], 0]
            );
        imisusermerge::set_mock_merge_tool($merge_tool_mock);

        task_manager::queue_adhoc_task(new merge_task());
        $results = $this->do_adhoc_tasks();

        $this->assertEquals(3, count($results), "Fail: there should be 3 results");
        $this->assertEquals($path2, ($results[0][0]->get_path()));
        $this->assertEquals($path1, ($results[1][0]->get_path()));
        $this->assertNull(($results[2][0]->get_path()), "Fail: last task should have no path");
        $this->assertSuccessFiles($path1);
        $this->assertSuccessFiles($path2);
        $this->assertNull(task_manager::get_next_adhoc_task(time() + 3600), "Tasks not completed");
    }

}