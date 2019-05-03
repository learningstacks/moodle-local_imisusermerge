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
    public function test_get_next_file_sorting() {
        $this->resetAfterTest(true);

        $filenames = [
            "{$this->config->file_base}_20190115.txt",
            "{$this->config->file_base}_20190114.csv",
            "{$this->config->file_base}_20190113-100001.txt",
            "{$this->config->file_base}_20190113-100000.csv",
        ];

        // Create files
        foreach ($filenames as $filename) {
            touch("{$this->config->in_dir}/{$filename}");
        }

        // Verify accessed in sorted order
        $filenames_sorted = $filenames;
        $this->assertTrue(sort($filenames_sorted), "sort the filenames");
        $this->assertTrue($filenames_sorted !== $filenames, "filenames were actually sorted");
        foreach ($filenames_sorted as $filename) {
            $this->assertEquals($filename, merge_file::get_next_file());
            unlink("{$this->config->in_dir}/{$filename}");
        }
    }

    /**
     * @throws \coding_exception
     * @throws merge_exception
     */
    public function test_load_datetime_patterns() {
        $this->resetAfterTest(true);

        $path = $this->write_request_file("20190101-000000", [
            'duplicateid,mergetoid,dateofmerge',
            'a1,b1,1/1/2019 00:00:00',
            'a2,b2,1/1/2019 00:00:00.0000',
            'a3,b3,2019-01-01 00:00:00',
            'a4,b4,2019-01-01 00:00:00.970000000',
        ]);
        $f = new merge_file($path);
        $this->assertEquals(merge_file::STATUS_LOADED, $f->load());

    }

    /**
     * @return array
     */
    public function empty_file_dataset() {
        return [
            [
                [] // No data
            ],
            [
                ['duplicateid,mergetoid,dateofmerge'] // headers only
            ]
        ];
    }

    /**
     * @dataProvider empty_file_dataset
     * @param $data
     * @throws \coding_exception
     * @throws merge_exception
     */
    public function test_process_empty_file($data) {
        $this->resetAfterTest(true);

        $path = $this->write_request_file("20190101-000000", $data);
        $f = new merge_file($path);
        $f->process();
        $this->assertEquals(merge_file::STATUS_EMPTY_FILE, $f->status);
        $this->assertSuccessFiles($path);
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
            'a,a1,1/1/2019,a' // extra element
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
     * @param $data
     * @throws merge_exception
     * @throws \coding_exception
     * @throws \coding_exception
     */
    public function test_process_error_missing_user() {
        $this->resetAfterTest(true);
        $users = $this->create_users(range(1, 4));

        $path = $this->write_request_file(
            "20190101-000000",
            [
                'duplicateid,mergetoid,dateofmerge',
                'user1,user2,1/1/2019 00:00:01', // Should work
                'missing,user2,1/1/2019 00:00:02', // SMissing from, error
                'user3,user4,1/1/2019 00:00:03', // Should not be attempted
            ]
        );

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
            $this->assertEquals(1, $f->failed, 'failed count');
            $this->assertEquals(1, $f->completed, 'completed count');
            $this->assertEquals(0, $f->skipped, 'skipped count');
            $this->assertEquals('STATUS_ERROR', $f->status);
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

    /**
     * @throws \coding_exception
     * @throws merge_exception
     */
    public function test_load_sample_file_from_iaff() {
        $this->resetAfterTest(true);

        $path = $this->write_request_file("20190101-000000", [
            "DuplicateID,MergeToID,DateOfMerge",
            "1258561,1311348,2015-02-02 09:56:28.960000000",
            "1279860,1311348,2015-02-02 09:56:49.827000000",
            "1324916,1318106,2016-03-08 12:10:08.463000000",
            "1300843,1315331,2016-06-14 09:36:32.953000000",
            "1343670,0479005,2017-01-10 10:18:26.597000000",
            "1325678,1315331,2017-02-16 11:45:11.633000000",
            "1259764,1374389,2017-07-05 13:39:59.243000000",
            "1363688,1229960,2019-03-12 15:24:52.107000000",
            "1346730,1263064,2019-03-12 15:29:46.730000000",
            "1356361,1352468,2019-03-12 15:33:08.587000000",
            "1353065,1147407,2019-03-12 15:38:20.490000000",
            "1361048,1167977,2019-03-12 15:41:01.350000000",
            "1360239,1052648,2019-03-13 13:53:06.857000000",
            "1366205,1358037,2019-03-14 09:39:08.123000000",
            "1358037,0513549,2019-03-14 09:39:47.650000000",
            "1371143,1049491,2019-03-14 09:49:56.610000000",
            "1335597,1020118,2019-03-19 15:20:13.700000000",
            "1312053,1254211,2019-03-19 15:53:30.800000000",
            "1303640,1178773,2019-03-19 15:56:08.733000000"
        ]);

        $f = new merge_file($path);
        $this->assertEquals(merge_file::STATUS_LOADED, $f->load());
    }
}
