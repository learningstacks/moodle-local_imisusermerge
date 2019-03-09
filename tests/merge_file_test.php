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

use local_imisusermerge\merge_file;
use local_imisusermerge\merge_action;
use local_imisusermerge\merge_exception;


class merge_file_testcase extends base {

    /**
     *
     * @throws \dml_exception
     */
    public function setUp() {
        parent::setup();
    }

    public function tearDown() {
        parent::tearDown();
    }

    /**
     *
     * @throws \dml_exception
     */
    public function test_get_next_file_no_files() {
        $this->resetAfterTest(true);

        $this->assertNull(merge_file::get_next_file());
    }

    /**
     *
     * @throws \dml_exception
     */
    public function test_get_next_file_multiple_files() {
        $this->resetAfterTest(true);

        for ($num = 3; $num >= 1; $num--) {
            touch("{$this->in_dir}/user_merge_request_20190101-00000{$num}.csv");
        }
        for ($num = 1; $num <= 3; $num++) {
            $this->assertEquals("user_merge_request_20190101-00000{$num}.csv", merge_file::get_next_file());
            unlink("{$this->in_dir}/user_merge_request_20190101-00000{$num}.csv");
        }
    }

    /**
     *
     * @throws merge_exception
     * @throws merge_exception
     */
    public function test_process_missing_headers() {
        $this->resetAfterTest(true);

        $path = $this->write_request_file("20190101-000000", [
            'duplicateid,mergetoid,full_name,email',
            'a,a1,1/1/2019,name,email@email.com'
        ]);

        $mt = $this->getMergeToolMock();
        $f = new merge_file($path, $mt);
        $mt->expects($this->never())->method('merge');

        try {
            $f->process();
            throw new \PHPUnit_Framework_AssertionFailedError("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertFileExists($path);
            $this->assertFileNotExists($this->get_completed_file_path($path));
            $this->assertFileExists($this->get_failed_log_path($path));
            $this->assertFileNotExists($this->get_completed_log_path($path));
        }
    }

    /**
     * @throws merge_exception
     */
    public function test_process_non_matching_lines() {
        $this->resetAfterTest(true);

        $path = $this->write_request_file("20190101-000000", [
            'duplicateid,mergetoid,dateofmerge,full_name,email',
            'a,a1,1/1/2019,name,email@email.com',
            'a,a1,1/1/2019,name', // missing email
        ]);

        $mt = $this->getMergeToolMock();
        $f = new merge_file($path, $mt);
        $mt->expects($this->never())->method('merge');

        try {
            $f->process();
            throw new \PHPUnit_Framework_AssertionFailedError("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertFileExists($path);
            $this->assertFileNotExists($this->get_completed_file_path($path));
            $this->assertFileExists($this->get_failed_log_path($path));
            $this->assertFileNotExists($this->get_completed_log_path($path));
        }
    }

    /**
     * @throws merge_exception
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
        list($success, $log, $id) = $m->merge($user6->id, $user5->id);
        $this->assertTrue($success);

        $path = $this->write_request_file("20190101-000000", [
            'duplicateid,mergetoid,dateofmerge,full_name,email',
            'user1,user2,1/1/2019 00:00:02,,',
            '',
            'user5,user6,1/1/2019 00:00:03,,', // skip: already merged
            'user3,user4,1/1/2019 00:00:01,,',
            'user7,user7,1/1/2019 00:00:01,,', // skip: from = to
        ]);

        $mt = $this->getMergeToolMock();
        $f = new merge_file($path, $mt);
        $mt->expects($this->exactly(2))
            ->method('merge')
            ->withConsecutive(
                [$this->equalTo($user4->id), $this->equalTo($user3->id)],
                [$this->equalTo($user2->id), $this->equalTo($user1->id)]
            );

        $f->process();

        $this->assertFileNotExists($path);
        $this->assertFileExists($this->get_completed_file_path($path));
        $this->assertFileNotExists($this->get_failed_log_path($path));
        $this->assertFileExists($this->get_completed_log_path($path));
    }

    public function missing_user_dataset() {
        return [
            [
                [
                    'duplicateid,mergetoid,dateofmerge,full_name,email',
                    'user1,user2,1/1/2019 00:00:01,,', // Should work
                    'user5,user2,1/1/2019 00:00:02,,', // SMissing from, hould cause error
                    'user3,user4,1/1/2019 00:00:03,,', // Should not be attempted
                ]
            ],
            [
                [
                    'duplicateid,mergetoid,dateofmerge,full_name,email',
                    'user1,user2,1/1/2019 00:00:01,,', // Should work
                    'user2,user5,1/1/2019 00:00:02,,', // Missing to, hould cause error
                    'user3,user4,1/1/2019 00:00:03,,', // Should not be attempted
                ]
            ]
        ];
    }

    /**
     * @dataProvider missing_user_dataset
     */
    public function test_process_error_missing_user($data) {
        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2']);
        $user3 = $this->getDataGenerator()->create_user(['username' => 'user3']);
        $user4 = $this->getDataGenerator()->create_user(['username' => 'user4']);

        $path = $this->write_request_file("20190101-000000", $data);

        $mt = $this->getMergeToolMock();
        $f = new merge_file($path, $mt);
        $mt->expects($this->once())
            ->method('merge')
            ->with([$this->equalTo($user2->id), $this->equalTo($user1->id)])
            ->will([true, [], 1]);

        try {
            $f->process();
            throw new \PHPUnit_Framework_AssertionFailedError("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertEquals('STATUS_ERROR', $f->getStatus());
            $this->assertFileExists($path);
            $this->assertFileNotExists($this->get_completed_file_path($path));
            $this->assertFileExists($this->get_failed_log_path($path));
            $this->assertFileNotExists($this->get_completed_log_path($path));
        }
    }

    public function test_process_ambiguous_merges() {
        $this->resetAfterTest(true);
        $this->markTestIncomplete();

    }

    public function test_process_fail_error_from_merge_tool() {
        $this->resetAfterTest(true);
        $this->markTestIncomplete();

    }


}
