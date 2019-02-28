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

use local_imisusermerge\imisusermerge;


/**
 * Test class for adhoc tasks.
 *
 * @package auth_imisbridge
 * @category auth
 * @copyright 2017 Learning Stacks LLC
 * @license   All Rights Reserved
 */
class merge_testcase extends \advanced_testcase {

    private $indir;
    private $fnregexp;

    public function setUp() {

        $this->indir = sys_get_temp_dir();
        $this->fnregexp = "/^user_merge_request_[0-9]{8}-[0-9]{6}\.csv$/i";

        set_config('indir', $this->indir, 'local_imisusermerge');
        set_config('fnregexp', $this->fnregexp, 'local_imisusermerge');

        // Create indir if it does not exist
    }

    public function tearDown() {
        while ($file = imisusermerge::get_next_file()) {
            unlink(realpath($this->indir . "/" . $file));
        }
    }

    public function test_get_next_file_no_files() {
        $this->resetAfterTest(false);

        $mu = new imisusermerge();
        $this->assertNull($mu->get_next_file());
    }

    public function test_foo() {
        $this->resetAfterTest(false);
        $a = (object)[
            'p1' => 'p1',
            'p2' => (object)[
                'a' => 'p2.a'
            ]
        ];
        $str = get_string('foo',imisusermerge::COMPONENT_NAME, $a);
        echo $str;
    }

    public function test_get_next_file_multiple_files() {
        $this->resetAfterTest(false);

        for ($num = 3; $num >= 1; $num--) {
            touch("{$this->indir}/user_merge_request_20190101-00000{$num}.csv");
        }
        $mu = new imisusermerge();
        for ($num = 1; $num <= 3; $num++) {
            $this->assertEquals("user_merge_request_20190101-00000{$num}.csv", $mu->get_next_file());
            unlink("{$this->indir}/user_merge_request_20190101-00000{$num}.csv");
        }
    }

    public function test_get_user_by_imisid() {
        global $DB;

        $this->resetAfterTest(true);

        $imisid = "zzzzzzzz"; //  Should not exist
        $this->getDataGenerator()->create_user((object)[
            'username' => $imisid,
            'mnethostid' => 1
            ]);
        $mu = new imisusermerge();

        // Finds it
        $user = $mu->get_user_by_imisid($imisid);
        $this->assertEquals($imisid, $user->username);

        // Does not
        $DB->delete_records("user", ['id' => $user->id]);
        $this->assertTrue($mu->get_user_by_imisid($imisid) === false);
    }

    public function test_get_header() {
        $this->resetAfterTest(false);

        $file = "{$this->indir}/user_merge_request_20190101-000001.csv";
        $contents = "";
        file_put_contents($file, $contents);
        $handle = fopen($file, "r");
        $mu = new imisusermerge();

        for ($num = 1; $num <= 3; $num++) {
            $this->assertEquals("user_merge_request_20190101-00000{$num}.csv", $mu->get_next_file());
            unlink("{$this->indir}/user_merge_request_20190101-00000{$num}.csv");
        }
    }
}
