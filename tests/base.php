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
    protected $indir;
    protected $completed_dir;
    /**
     * @var
     */
    protected $fnregexp;
    protected $file_base = "user_merge_request_";

    /**
     *
     * @throws \dml_exception
     */
    public function setup() {

        $this->indir = "C:/dev/IAFF/merge_requests/todo";
        $this->completed_dir = "C:/dev/IAFF/merge_requests/completed";
        $this->fnregexp = "/^{$this->file_base}[0-9]{8}-[0-9]{6}\.csv$/i";

        set_config('indir', $this->indir, 'local_imisusermerge');
        set_config('completed_dir', $this->completed_dir, 'local_imisusermerge');
        set_config('fnregexp', $this->fnregexp, 'local_imisusermerge');

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
        $files = glob("{$this->indir}/*"); // get all file names
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
        $path = "{$this->indir}/user_merge_request_{$datetime}.csv";
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
        return "{$this->indir}/{$filename}_log.csv";
    }

}