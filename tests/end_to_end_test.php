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
//use local_imisusermerge\merge_action;
//use local_imisusermerge\merge_exception;


/**
 * Test class for adhoc tasks.
 *
 * @package auth_imisbridge
 * @category auth
 * @copyright 2017 Learning Stacks LLC
 * @license   All Rights Reserved
 */
class end_to_end_testcase extends base {

    /**
     * @var
     */
    private $indir;
    /**
     * @var
     */
    private $fnregexp;

    /**
     * @throws \dml_exception
     */
    public function setUp() {
        parent::setup();
    }

    public function tearDown() {
        parent::tearDown();
    }

    /**
     * @param $datetime
     * @param array $data
     * @return string
     */
    protected function write_request_file($datetime, $data = []) {
        $path = "{$this->indir}/user_merge_request_{$datetime}.csv";
        file_put_contents($path, join("\n", $data));
        return $path;
    }

    /**
     *
     */
    public function test_valid_file_all_cases() {
        $this->resetAfterTest(true);
        $this->markTestIncomplete();


    }

    /**
     *
     */
    public function test_invalid_file_all_cases() {
        $this->resetAfterTest(true);
        $this->markTestIncomplete();

    }




}
