<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

use local_imisusermerge\config;
use local_imisusermerge\imisusermerge;
use local_imisusermerge\merge_exception;

class config_testcase extends base {

    private $base_dir = 'C:/dev/IAFF/merge_requests';

    public function setUp() {
        parent::setup();
    }

    public function tearDown() {
        parent::tearDown();
    }


    public function test_valid_config() {
        $this->resetAfterTest(true);

        $config = new config();
        $this->assertEquals("{$this->base_dir}/todo", $config->in_dir);
        $this->assertEquals("{$this->base_dir}/completed", $config->completed_dir);
        $this->assertEquals("/^{$this->file_base}[0-9]{8}-[0-9]{6}\.csv$/i", $config->file_name_regex);
        $this->assertEquals('from_imisid', $config->file_field_map['duplicateid']);
        $this->assertEquals('to_imisid', $config->file_field_map['mergetoid']);
        $this->assertEquals('merge_time', $config->file_field_map['dateofmerge']);
        $this->assertEquals(2, count($config->notification_email_addresses));
        $this->assertEquals('someone@somewhere.com', $config->notification_email_addresses[0]);
        $this->assertEquals('someone2@somewhere.com', $config->notification_email_addresses[0]);
    }

    // public function test_valid_config_variations() {

    // }

    public function invalid_config_dataset() {
        return [
            "merge_in_dir not set" => ["unset(\$CFG->merge_in_dir);"],
            "merge_in_dir = null" => ["\$CFG->merge_in_dir = null;"],
            "merge_in_dir = empty string" => ["\$CFG->merge_in_dir = '';"],
            "merge_completed_dir not set" => ["unset(\$CFG->merge_completed_dir);"],
            "merge_completed_dir = null" => ["\$CFG->merge_completed_dir = null;"],
            "merge_completed_dir = empty string" => ["\$CFG->merge_completed_dir = '';"],
            "merge_file_name_regex not set" => ["unset(\$CFG->merge_file_name_regex);"],
            "merge_file_name_regex = null" => ["\$CFG->merge_file_name_regex = null;"],
            "merge_file_name_regex = empty string" => ["\$CFG->merge_file_name_regex = '';"],

            "merge_file_field_map not set" => ["unset(\$CFG->merge_file_field_map);"],
            "merge_file_field_map = null" => ["\$CFG->merge_file_field_map = null;"],
            "merge_file_field_map = empty array" => ["\$CFG->merge_file_field_map = [];"],

            "merge_file_field_map[from_imisid] is not set" => ["unset(\$CFG->merge_file_field_map['from_imisid']);"],
            "merge_file_field_map[from_imisid] is null" => ["\$CFG->merge_file_field_map['from_imisid'] = null;"],
            "merge_file_field_map[from_imisid] is empty string" => ["\$CFG->merge_file_field_map['from_imisid'] = 'aa';"],

        ];
    }

    /**
     * @dataProvider invalid_config_dataset
     * @param $action
     * @throws \dml_exception
     * @throws merge_exception
     */
    public function test_invalid_config($action) {
        $this->resetAfterTest(true);
        global $CFG;

        eval($action);
        $this->expectException(merge_exception::class);
        $config = new config();
    }
}