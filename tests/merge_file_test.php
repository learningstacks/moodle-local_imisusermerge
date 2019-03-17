<?php
// Copyright (C) 2017 Learning Stacks LLC https://learningstacks.com/
//
// This file is a part of the IMIS Integration Components developed by
// Learning Stacks LLC - https://learningstacks.com/
//
// This file cannot be copied or distributed without the express permission
// of Learning Stacks LLC.

/**
 *
 *
 * @package   local_imisbridge
 * @copyright 2017 onwards Learning Stacks LLC {@link https://learningstacks.com/}
 * @license   All Rights Reserved
 */

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

use local_imisusermerge\imisusermerge;
use local_imisusermerge\merge_file;
use local_imisusermerge\merge_exception;


/**
 * Class merge_file_testcase
 * @package local_imisusermerge\tests
 */
class merge_file_testcase extends base {


    /**
     * @throws \coding_exception
     * @throws merge_exception
     */
    public function setUp() {
        parent::setup();
    }

    /**
     */
    public function tearDown() {
        parent::tearDown();
    }

//    protected function assert_merged($from_userid, $to_userid) {
//        global $DB;
//
//        $params = [$from_userid, $to_userid];
//        $this->assertTrue($DB->record_exists())
//    }

    /**
     *
     * @throws merge_exception
     * @throws \coding_exception
     */
    public function test_get_next_file_no_files() {
        $this->resetAfterTest(true);

        $this->assertNull(merge_file::get_next_file());
    }

    /**
     *
     * @throws merge_exception
     * @throws \coding_exception
     */
    public function test_get_next_file_multiple_files() {
        $this->resetAfterTest(true);

        for ($num = 3; $num >= 1; $num--) {
            touch("{$this->config->in_dir}/user_merge_request_20190101-00000{$num}.csv");
        }
        for ($num = 1; $num <= 3; $num++) {
            $this->assertEquals("user_merge_request_20190101-00000{$num}.csv", merge_file::get_next_file());
            unlink("{$this->config->in_dir}/user_merge_request_20190101-00000{$num}.csv");
        }
    }

    /**
     *
     * @throws merge_exception
     * @throws merge_exception
     * @throws \coding_exception
     * @throws \coding_exception
     */
    public function test_process_missing_headers() {
        $this->resetAfterTest(true);

        $path = $this->write_request_file("20190101-000000", [
            'duplicateid,mergetoid',
            'a,a1'
        ]);

        $merge_tool_mock = $this->getMergeToolMock();
        $merge_tool_mock->expects($this->never())->method('merge');
        imisusermerge::set_mock_merge_tool($merge_tool_mock);
        $f = new merge_file($path);

        try {
            $f->process();
            throw new \PHPUnit_Framework_AssertionFailedError("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertFailedFiles($path);
            $this->assert_log_file_contents($this->get_failed_log_path($path), $f);
        }
    }

    /**
     * @throws merge_exception
     * @throws \coding_exception
     */
    public function test_process_non_matching_lines() {
        $this->resetAfterTest(true);

        $path = $this->write_request_file("20190101-000000", [
            'duplicateid,mergetoid,dateofmerge',
            'a,a1,1/1/2019,',
            'a,a1,1/1/2019,a' // missing email
        ]);

        $merge_tool_mock = $this->getMergeToolMock();
        $merge_tool_mock->expects($this->never())->method('merge');
        imisusermerge::set_mock_merge_tool($merge_tool_mock);
        $f = new merge_file($path);

        try {
            $f->process();
            throw new \PHPUnit_Framework_AssertionFailedError("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertFailedFiles($path);
            $this->assert_log_file_contents($this->get_failed_log_path($path), $f);
        }
    }

    /**
     * @throws merge_exception
     * @throws \coding_exception
     * @throws \coding_exception
     */
    public function test_process_sort_blank_lines_skips() {
        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2']);
        $user3 = $this->getDataGenerator()->create_user(['username' => 'user3']);
        $user4 = $this->getDataGenerator()->create_user(['username' => 'user4']);
        $user5 = $this->getDataGenerator()->create_user(['username' => 'user5']);
        $user6 = $this->getDataGenerator()->create_user(['username' => 'user6']);

        // Pre merge user5 -> 6
        $m = new \MergeUserTool();
        list($success) = $m->merge($user6->id, $user5->id);
        $this->assertTrue($success);

        $path = $this->write_request_file("20190101-000000", [
            'duplicateid,mergetoid,dateofmerge',
            'user1,user2,1/1/2019 00:00:02',
            '',
            'user5,user6,1/1/2019 00:00:03', // skip: already merged
            'user3,user4,1/1/2019 00:00:01',
            'user7,user7,1/1/2019 00:00:01', // skip: from = to
        ]);

        $merge_tool_mock = $this->getMergeToolMock();
        $merge_tool_mock->expects($this->exactly(2))
            ->method('merge')
            ->withConsecutive(
                [$this->equalTo($user4->id), $this->equalTo($user3->id)],
                [$this->equalTo($user2->id), $this->equalTo($user1->id)]
            )
            ->will($this->returnValue([true, [], 0]));
        imisusermerge::set_mock_merge_tool($merge_tool_mock);

        $f = new merge_file($path);
        $f->process();

        $this->assertEquals(0, $f->failed, 'failed count');
        $this->assertEquals(2, $f->completed, 'completed count');
        $this->assertEquals(2, $f->skipped, 'skipped count');
        $this->assertSuccessFiles($path);
        $this->assert_log_file_contents($this->get_completed_log_path($path), $f);
    }

    /**
     * @return array
     */
    public function missing_user_dataset() {
        return [
            [
                [
                    'duplicateid,mergetoid,dateofmerge',
                    'user1,user2,1/1/2019 00:00:01', // Should work
                    'user5,user2,1/1/2019 00:00:02', // SMissing from, should cause error
                    'user3,user4,1/1/2019 00:00:03', // Should not be attempted
                ],
                [
                    'failed' => 1,
                    'skipped' => 0,
                    'completed' => 1
                ]
            ],
            [
                [
                    'duplicateid,mergetoid,dateofmerge',
                    'user1,user2,1/1/2019 00:00:01', // Should work
                    'user2,user5,1/1/2019 00:00:02', // Missing to, hould cause error
                    'user3,user4,1/1/2019 00:00:03', // Should not be attempted
                ],
                [
                    'failed' => 1,
                    'skipped' => 0,
                    'completed' => 1
                ]
            ]
        ];
    }

    /**
     * @dataProvider missing_user_dataset
     * @param $data
     * @throws merge_exception
     * @throws \coding_exception
     * @throws \coding_exception
     */
    public function test_process_error_missing_user($data, $expected) {
        $this->resetAfterTest(true);
        $users = $this->create_users(range(1, 4));

        $path = $this->write_request_file("20190101-000000", $data);

        $merge_tool_mock = $this->getMergeToolMock();
        $merge_tool_mock->expects($this->once())
            ->method('merge')
            ->with($this->equalTo($users[2]->id), $this->equalTo($users[1]->id))
            ->will($this->returnValue([true, [], 1]));
        imisusermerge::set_mock_merge_tool($merge_tool_mock);

        $f = new merge_file($path);

        try {
            $f->process();
            throw new \PHPUnit_Framework_AssertionFailedError("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertEquals($expected['failed'], $f->failed, 'failed count');
            $this->assertEquals($expected['completed'], $f->completed, 'completed count');
            $this->assertEquals($expected['skipped'], $f->skipped, 'skipped count');

            $this->assertEquals('STATUS_ERROR', $f->getStatus());
            $this->assertFailedFiles($path);
            $this->assert_log_file_contents($this->get_failed_log_path($path), $f);

        }
    }


    /**
     * @throws merge_exception
     * @throws \coding_exception
     */
    public function test_process_ambiguous_merges() {
        $this->resetAfterTest(true);

        $path = $this->write_request_file("20190101-000000", [
            'duplicateid,mergetoid,dateofmerge',
            'user1,user2,1/1/2019 00:00:02', // ok
            'user3,user4,1/1/2019 00:00:03', // fail
            'user5,user6,1/1/2019 00:00:01', // not attempted
        ]);

        $merge_tool_mock = $this->getMergeToolMock();
        $merge_tool_mock->expects($this->never())
            ->method('merge');
        imisusermerge::set_mock_merge_tool($merge_tool_mock);
        $f = new merge_file($path);

        try {
            $f->process();
            $this->fail("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertEquals('STATUS_ERROR', $f->getStatus());
            $this->assertFailedFiles($path);
            $this->assert_log_file_contents($this->get_failed_log_path($path), $f);
        }
    }

    /**
     * @throws merge_exception
     * @throws \coding_exception
     */
    public function test_process_fail_error_from_merge_tool() {
        $this->resetAfterTest(true);

        $users = $this->create_users(range(1, 6));

        $path = $this->write_request_file("20190101-000000", [
            'duplicateid,mergetoid,dateofmerge',
            'user1,user2,1/1/2019 00:00:01', // ok
            'user3,user4,1/1/2019 00:00:02', // will fail this
            'user5,user6,1/1/2019 00:00:03', // should not be attempted
        ]);

        $merge_tool_mock = $this->getMergeToolMock();
        $merge_tool_mock->expects($this->exactly(2))
            ->method('merge')
            ->withConsecutive(
                [$this->equalTo($users[2]->id), $this->equalTo($users[1]->id)],
                [$this->equalTo($users[4]->id), $this->equalTo($users[3]->id)]
            )
            ->willReturnOnConsecutiveCalls(
                [true, [], 0],
                [false, [], 0]
            );
        imisusermerge::set_mock_merge_tool($merge_tool_mock);

        $f = new merge_file($path);

        try {
            $f->process();
            $this->fail("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertEquals('STATUS_FAILED', $f->getStatus());
            $this->assertFailedFiles($path);
            $this->assert_log_file_contents($this->get_failed_log_path($path), $f);
        }

    }

    /**
     * @return array
     */
    public function header_order_dataset() {
        return [
            [
                [
                    'duplicateid,mergetoid,dateofmerge',
                    'user1,user2,1/1/2019 00:00:01'
                ]
            ],
            [
                [
                    'mergetoid,duplicateid,dateofmerge',
                    'user2,user1,1/1/2019 00:00:01'
                ]
            ],
            [
                [
                    'dateofmerge,mergetoid,duplicateid',
                    '1/1/2019 00:00:01,user2,user1'
                ]
            ],

        ];
    }


    /**
     * @dataProvider header_order_dataset
     * @param $data
     * @throws merge_exception
     * @throws \coding_exception
     */
    public function test_header_order($data) {
        $this->resetAfterTest(true);

        $users = $this->create_users(range(1, 2));
        $path = $this->write_request_file('20190101-000001', $data);

        $merge_tool_mock = $this->getMergeToolMock();
        $merge_tool_mock->expects($this->once())
            ->method('merge')
            ->with($users[2]->id, $users[1]->id)
            ->willReturn([true, [], 0]);
        imisusermerge::set_mock_merge_tool($merge_tool_mock);

        $f = new merge_file($path);
        $f->process();
        $this->assertSuccessFiles($path);
        $this->assert_log_file_contents($this->get_completed_log_path($path), $f);

    }
}
