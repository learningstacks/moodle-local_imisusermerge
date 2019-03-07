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

require_once(__DIR__ . "/../../../admin/tool/mergeusers/lib/mergeusertool.php");
require_once(__DIR__ . "/base.php");

use MergeUserTool;
use local_imisusermerge\merge_action;
use local_imisusermerge\merge_exception;


/**
 * Test class for adhoc tasks.
 *
 * @package auth_imisbridge
 * @category auth
 * @copyright 2017 Learning Stacks LLC
 * @license   All Rights Reserved
 */
class merge_action_testcase extends base {

    /**
     * @var array
     */
    private $map = [
        'from_imisid' => 0,
        'to_imisid' => 1,
        'merge_time' => 2,
        'full_name' => 3,
        'email' => 4
    ];

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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMergeToolMock() {
        $mock = $this->getMockBuilder(MergeUserTool::class)
            ->setMethods([
                'merge'
            ])
            ->getMock();

        return $mock;
    }

    /**
     * @return array
     */
    public function merge_create_invalid_line_dataset() {
        return [
            'blank line' => ['', merge_action::STATUS_INVALID],
            'missing from' => [',a1,1/1/2019,name,email@foo.com', merge_action::STATUS_INVALID],
            'missing to' => ['a,,1/1/2019,name,email@foo.com', merge_action::STATUS_INVALID],
            ['missing date' => 'a,a1,,name,email@foo.com', merge_action::STATUS_INVALID],
            'bad date' => ['a,a1,notadate,name,email@foo.com', merge_action::STATUS_INVALID],
            'too many field' => ['a,a1,1/1/2019,name,email@foo.com, z', merge_action::STATUS_INVALID],
            'not enough fields' => ['a,a1,1/1/2019,name', merge_action::STATUS_INVALID],
        ];
    }

    /**
     * @dataProvider merge_create_invalid_line_dataset
     * @param $line
     * @param $expected_status
     * @throws merge_exception
     */
    public function test_merge_create_invalid_line($line, $expected_status) {
        $this->resetAfterTest(true);

        $this->expectException(merge_exception::class);
        new merge_action(1, $line, $this->map);
    }

    /**
     * @return array
     */
    public function merge_missing_users_dataset() {
        return [
            'missing from' => ['exists,missing,1/1/2019,name,email@foo.com', merge_action::STATUS_ERROR],
            'missing to' => ['missing,exists,1/1/2019,name,email@foo.com', merge_action::STATUS_ERROR],
            'missing both' => ['missing1,missing2,1/1/2019,name,email@foo.com', merge_action::STATUS_ERROR],
        ];
    }

    /**
     * @dataProvider merge_missing_users_dataset
     * @param $line
     * @param $expected_status
     */
    public function test_merge_missing_users($line, $expected_status) {
        $this->resetAfterTest(true);

        $this->getDataGenerator()->create_user(['username' => 'exists']);

        $m = new merge_action(1, $line, $this->map);
        $mock = $this->getMergeToolMock();
        $mock->expects($this->never())->method('merge');

        try {
            $m->merge($mock);
            throw new \PHPUnit_Framework_AssertionFailedError("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertEquals($expected_status, $m->getStatus());
        }

    }

    /**
     * @return array
     */
    public function create_valid_line_dataset() {
        return [
            'no spaces' => ['a,a1,1/1/2019,name,email@foo.com', merge_action::STATUS_TODO, 'a', 'a1', strtotime('1/1/2019')],
            'spaces' => [' a , a1 , 1/1/2019 , name , email@foo.com ', merge_action::STATUS_TODO, 'a', 'a1', strtotime('1/1/2019')],
        ];
    }

    /**
     * @dataProvider create_valid_line_dataset
     * @param $line
     * @param $expected_status
     * @param $expected_from
     * @param $expected_to
     * @param $expected_merge_time
     * @throws merge_exception
     */
    public function test_create_valid_line($line, $expected_status, $expected_from, $expected_to, $expected_merge_time) {
        $this->resetAfterTest(true);

        $m = new merge_action(1, $line, $this->map);
        $this->assertEquals($expected_status, $m->getStatus(), 'status');
        $this->assertEquals($expected_from, $m->getFromImisid(), 'from_imisid');
        $this->assertEquals($expected_to, $m->getToImisid(), 'to_imisid');
        $this->assertEquals($expected_merge_time, $m->getMergeTime(), 'merge_time');
    }


    /**
     * @return array
     */
    public function merge_skips_dataset() {
        return [
            'same users' => ['user1,user1,1/1/2019,name,email@foo.com', merge_action::STATUS_SKIPPED],
        ];
    }

    /**
     * @dataProvider merge_skips_dataset
     * @param $line
     * @param $expected_status
     * @throws \dml_exception
     * @throws merge_exception
     */
    public function test_merge_skips($line, $expected_status) {
        $this->resetAfterTest(true);

        $merge_tool_mock = $this->getMergeToolMock();
        $merge_tool_mock->expects($this->never())->method('merge');

        $m = new merge_action(1, $line, $this->map);

        $mock = $this->getMergeToolMock();
        $mock->expects($this->never())->method('merge');

        try {
            $m->merge($merge_tool_mock);
            throw new \PHPUnit_Framework_AssertionFailedError("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertEquals($expected_status, $m->getStatus());
        }
    }

    /**
     * @throws \dml_exception
     * @throws merge_exception
     */
    public function test_user_merged_only_once() {
        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2']);

        $line = 'user1,user2,1/1/2019,,';

        $m = new merge_action(1, $line, $this->map);
        $m->merge(new MergeUserTool());
        $this->assertEquals(merge_action::STATUS_MERGED, $m->getStatus(), 'status');


        // Try again, should skip because already merged
        $m = new merge_action(1, $line, $this->map);
        $merge_tool_mock = $this->getMergeToolMock();
        $merge_tool_mock->expects($this->never())->method('merge');
        try {
            $m->merge($merge_tool_mock);
            throw new \PHPUnit_Framework_AssertionFailedError("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertEquals(merge_action::STATUS_SKIPPED, $m->getStatus());
        }
    }

    /**
     * @throws \dml_exception
     * @throws merge_exception
     */
    public function test_merge_fails() {
        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2']);

        $merge_tool_mock = $this->getMergeToolMock();
        $line = 'user1,user2,1/1/2019,name,email@foo.com';
        $m = new merge_action(1, $line, $this->map);
        $merge_tool_mock->method('merge')->willReturn([false, ['errors']]);

        try {
            $m->merge($merge_tool_mock);
            throw new \PHPUnit_Framework_AssertionFailedError("Did not receive expected merge_exception");

        } catch (merge_exception $ex) {
            $this->assertEquals(merge_action::STATUS_FAILED, $m->getStatus());
            $this->assertEquals('errors', $m->getMergeToolMessage(), 'status');
        }
    }

}
