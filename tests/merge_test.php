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
use local_imisusermerge\imisusermerge_exception;


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

    protected function write_csv_file($path, $data) {
        $d = (array)$data;
        $handle = null;

        try {
            $handle = fopen($path, "w");
            foreach($d as $row) {
                fputcsv($handle, (array)$row);
            }
        } catch (\Exception $ex) {
            throw $ex;
        } finally {
            if ($handle) fclose($handle);
        }

        return $path;
    }

    protected function write_request_file($datetime, $data = null) {
        $path = "{$this->indir}/user_merge_request_{$datetime}.csv";
        return $this->write_csv_file($path, (array)$data);
    }


    public function test_get_next_file_no_files() {
        $this->resetAfterTest(false);

        $this->assertNull(imisusermerge::get_next_file());
    }

    public function test_get_next_file_multiple_files() {
        $this->resetAfterTest(false);

        for ($num = 3; $num >= 1; $num--) {
            touch("{$this->indir}/user_merge_request_20190101-00000{$num}.csv");
        }
        for ($num = 1; $num <= 3; $num++) {
            $this->assertEquals("user_merge_request_20190101-00000{$num}.csv", imisusermerge::get_next_file());
            unlink("{$this->indir}/user_merge_request_20190101-00000{$num}.csv");
        }
    }

//    public function test_get_user_by_imisid() {
//        global $DB;
//
//        $this->resetAfterTest(true);
//
//        $imisid = "zzzzzzzz"; //  Should not exist
//        $this->getDataGenerator()->create_user((object)[
//            'username' => $imisid,
//            'mnethostid' => 1
//            ]);
//        $mu = new imisusermerge();
//
//        // Finds it
//        $user = $mu->get_user_by_imisid($imisid);
//        $this->assertEquals($imisid, $user->username);
//
//        // Does not
//        $DB->delete_records("user", ['id' => $user->id]);
//        $this->assertTrue($mu->get_user_by_imisid($imisid) === false);
//    }

//    public function test_get_header() {
//        $this->resetAfterTest(false);
//
//        $file = "{$this->indir}/user_merge_request_20190101-000001.csv";
//        $contents = "";
//        file_put_contents($file, $contents);
//        $handle = fopen($file, "r");
//        $mu = new imisusermerge();
//
//        for ($num = 1; $num <= 3; $num++) {
//            $this->assertEquals("user_merge_request_20190101-00000{$num}.csv", $mu->get_next_file());
//            unlink("{$this->indir}/user_merge_request_20190101-00000{$num}.csv");
//        }
//    }

    public function test_load_missing_file() {
        $this->resetAfterTest(false);

        $m = new imisusermerge();

        $this->expectException(imisusermerge_exception::class);
        $f = $m->load_file('');

        $this->expectException(imisusermerge_exception::class);
        $f = $m->load_file('abc.zzz');
    }

    public function test_load_empty_file() {
        $this->resetAfterTest(false);

        $path = $this->write_request_file("20190101-000000", []);
        $m = new imisusermerge();
        $f = $m->load_file($path);
        $this->assertEquals($path, $f["filepath"]);
        $this->assertTrue(is_array($f['headers']) && empty($f['headers']));
        $this->assertTrue(is_array($f['fldpos_map']) && empty($f['fldpos_map']));
        $this->assertTrue(is_array($f['merges']) && empty($f['merges']));
    }

    public function test_load_missing_headers() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();
    }

    public function test_load_header_only() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();

    }

    public function test_load_non_matching_lines() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();
    }

//    public function test_load_bad_data() {
//        $this->resetAfterTest(false);
//        $this->markTestIncomplete();
//    }

    public function test_load_with_blank_lines() {
        $this->resetAfterTest(false);

        $path = $this->write_request_file("20190101-000000", [
            ['duplicateid', 'mergetoid', 'dateofmerge', 'full_name', 'email'],
            ['a','a1','','',''],
            [],
            ['b','b1','','',''],
            ]);

        $m = new imisusermerge();
        $f = $m->load_file($path);
        $this->assertEquals($path, $f["filepath"]);
        $this->assertEqual(2, count($f['merges']));
    }

    public function test_load_sorting() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();
    }

    public function test_merge_missing_users() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();

    }

    public function test_merge_same_users() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();
    }

    public function test_merge_already_merged() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();

    }

    public function test_merge_fails() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();

    }

    public function test_merge_suceeds() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();

    }

    public function test_process_fail() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();

    }

    public function test_process_skips() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();

        $time = time();
        $this->createcsvfile($path, [
            $this->headers,
            ['a', 'a1', $time, '', ''],
            ['b', 'b1', $time, '', '']
        ]);

        $this->mock->process();

    }

    public function test_process_suceeds() {
        $this->resetAfterTest(false);
        $this->markTestIncomplete();

    }


}
