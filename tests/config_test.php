<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

use local_imisusermerge\config;

class config_testcase extends base {

    private $base_dir = 'C:/dev/IAFF/merge_requests';

    public function setUp() {
        parent::setup();
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function config_dataset() {
        return [
            [
                [
                    'merge_in_dir' => "{$this->base_dir}/todo",
                    'merge_completed_dir' => "{$this->base_dir}/completed",
                    'merge_file_field_map' => [
                        'duplicateid' => 'from_imisid',
                        'mergetoid' => 'to_imisid',
                        'dateofmerge' => 'merge_time',
                        'full_name' => 'full_name',
                        'email' => 'email'
                    ],
                    'merge_file_name_regex' => "/^{$this->file_base}[0-9]{8}-[0-9]{6}\.csv$/i"
                ],
                false
            ],
            [
                [
                    'merge_in_dir' => null,
                    'merge_completed_dir' => "{$this->base_dir}/completed",
                    'merge_file_field_map' => [
                        'duplicateid' => 'from_imisid',
                        'mergetoid' => 'to_imisid',
                        'dateofmerge' => 'merge_time',
                        'full_name' => 'full_name',
                        'email' => 'email'
                    ],
                    'merge_file_name_regex' => "/^{$this->file_base}[0-9]{8}-[0-9]{6}\.csv$/i"
                ],
                true
            ],
            [
                [
                    'merge_in_dir' => "{$this->base_dir}/todo",
                    'merge_completed_dir' => null,
                    'merge_file_field_map' => [
                        'duplicateid' => 'from_imisid',
                        'mergetoid' => 'to_imisid',
                        'dateofmerge' => 'merge_time',
                        'full_name' => 'full_name',
                        'email' => 'email'
                    ],
                    'merge_file_name_regex' => "/^{$this->file_base}[0-9]{8}-[0-9]{6}\.csv$/i"
                ],
                true
            ],
            [
                [
                    'merge_in_dir' => "{$this->base_dir}/todo",
                    'merge_completed_dir' => "{$this->base_dir}/completed",
                    'merge_file_field_map' => [],
                    'merge_file_name_regex' => "/^{$this->file_base}[0-9]{8}-[0-9]{6}\.csv$/i"
                ],
                true
            ],
            [
                [
                    'merge_in_dir' => "{$this->base_dir}/todo",
                    'merge_completed_dir' => "{$this->base_dir}/completed",
                    'merge_file_field_map' => [
                        'duplicateid' => 'from_imisid',
                        'mergetoid' => 'to_imisid',
                        'dateofmerge' => 'merge_time',
                        'full_name' => 'full_name',
                        'email' => 'email'
                    ],
                    'merge_file_name_regex' => null
                ],
                true
            ],
        ];
    }

    protected function set_config(Array $vals) {
        global $CFG;

        foreach ($vals as $name => $val) {
            if (!empty($val)) {
                $CFG->$name = $val;
            } else {
                unset($CFG->$name);
            }
        }
    }

    /**
     * @dataProvider config_dataset
     * @param $data
     * @param $expect_exception
     * @throws \Exception
     */
    public function test_get_config_values($data, $expect_exception) {
        $this->resetAfterTest(true);

        $this->set_config($data);
        try {
            $config = new config();
            if ($expect_exception) {
                $this->fail("Exception expected");
            }

            $this->assertEquals($data['merge_in_dir'], $config->in_dir);
            $this->assertEquals($data['merge_completed_dir'], $config->completed_dir);
            $this->assertEquals($data['merge_file_name_regex'], $config->file_name_regex);
            $map = $config->file_field_map;
            foreach($data['merge_file_field_map'] as $name => $val) {
                $this->assertEquals($val, $map[$name], "$name");
            }

        } catch (\Exception $ex) {
            if (!$expect_exception) {
                throw $ex;
            }
        }
    }

}