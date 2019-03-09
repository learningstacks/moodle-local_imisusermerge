<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . "/../../../admin/tool/mergeusers/lib/mergeusertool.php");

use local_imisusermerge\merge_file;
use \MergeUserTool;

/**
 * Class base
 * @package local_imisusermerge\tests
 */
abstract class base extends \advanced_testcase {

    /**
     * @var
     */
    protected $file_base ;
    protected $in_dir;
    protected $completed_dir;
    protected $file_name_regex;
    protected $file_field_map;

    /**
     *
     * @throws \dml_exception
     */
    public function setup() {

        global $CFG;

        $this->file_base = "user_merge_request_";

        $CFG->merge_in_dir = $this->in_dir = "C:/dev/IAFF/merge_requests/todo";
        $CFG->merge_completed_dir = $this->completed_dir = "C:/dev/IAFF/merge_requests/completed";
        $CFG->merge_file_name_regex = $this->file_name_regex = "/^{$this->file_base}[0-9]{8}-[0-9]{6}\.csv$/i";
        $CFG->merge_file_field_map = $this->file_field_map = [
            'duplicateid' => 'from_imisid',
            'mergetoid' => 'to_imisid',
            'dateofmerge' => 'merge_time',
            'full_name' => 'full_name',
            'email' => 'email'
        ];

        $this->delete_all_files();
    }

    /**
     * @throws \dml_exception
     */
    public function tearDown() {
        $this->delete_all_files();
    }

    /**
     * @throws \dml_exception
     */
    public function delete_all_files() {
        $files = glob("{$this->in_dir}/*"); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }

        $files = glob("{$this->completed_dir}/*"); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }
    }

    /**
     * @param $datetime
     * @param array $data
     * @return string
     */
    protected function write_request_file($datetime, $data = []) {
        $path = "{$this->in_dir}/user_merge_request_{$datetime}.csv";
        file_put_contents($path, join("\n", $data));
        return $path;
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

    protected function get_completed_file_path($path) {
        $filename =  pathinfo($path, PATHINFO_FILENAME);
        return "{$this->completed_dir}/{$filename}.csv";
    }

    protected function get_completed_log_path($path) {
        $completed_dir = $this->completed_dir;
        $filename =  pathinfo($path, PATHINFO_FILENAME);
        return "{$this->completed_dir}/{$filename}_log.csv";
    }

    protected function get_failed_log_path($path) {
        $filename =  pathinfo($path, PATHINFO_FILENAME);
        return "{$this->in_dir}/{$filename}_log.csv";
    }

    protected function assertSuccessFiles($path) {
        $this->assertFileNotExists($path, "Data file no longer in indir");
        $this->assertFileExists($this->get_completed_file_path($path), "File has been moved to completed dir");
        $this->assertFileExists($this->get_completed_log_path($path), "Log file has been created in completed dir");
        $this->assertFileNotExists($this->get_failed_log_path($path), "Any log file in the indir has been deleted");
    }

    protected function asserFailedFiles($path) {
        $this->assertFileExists($path, "Data file remains in indir");
        $this->assertFileNotExists($this->get_completed_file_path($path), "File has not been moved to completed dir");
        $this->assertFileNotExists($this->get_completed_log_path($path), "No log file in completed dir");
        $this->assertFileExists($this->get_failed_log_path($path), "Log file created in indir");
    }

}