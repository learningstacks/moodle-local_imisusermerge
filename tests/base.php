<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . "/../../../admin/tool/mergeusers/lib/mergeusertool.php");

use local_imisusermerge\imisusermerge;
use local_imisusermerge\task\merge_task;
use \MergeUserTool;

/**
 * Class base
 * @package local_imisusermerge\tests
 */
abstract class base extends \advanced_testcase {

    /**
     * @var
     */
    protected $file_base;
    protected $in_dir;
    /**
     * @var
     */
    protected $completed_dir;
    /**
     * @var
     */
    protected $file_name_regex;
    /**
     * @var
     */
    protected $file_field_map;

    protected $notification_email_addresses;

    /**
     *
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

        set_config('notification_email_addresses', 'a@a.com,b@b.com', imisusermerge::COMPONENT_NAME);

        $this->delete_all_files();
        imisusermerge::set_mock_merge_tool(null);
        imisusermerge::set_config(null);
    }

    /**
     */
    public function tearDown() {
        $this->delete_all_files();
    }

    /**
     */
    public function delete_all_files() {
        $files = glob("{$this->in_dir}/*"); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
                unlink($file); // delete file
        }

        $files = glob("{$this->completed_dir}/*"); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
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

//    /**
//     * @return \PHPUnit_Framework_MockObject_MockObject
//     */
//    protected function getMergeToolMock($expected_params = [], $returns = []) {
//
//        $mock = $this->getMockBuilder(MergeUserTool::class)
//            ->setMethods([
//                'merge'
//            ])
//            ->getMock();
//
//        if (empty($expected_params)) {
//            $mock->expects($this->never())->method('merge');
//
//        } else {
//            $mock->expects($this->exactly(count($expected_params)))
//                ->method('merge')
//                ->withConsecutive($expected_params)
//                ->willReturnOnConsecutiveCalls($returns);
//        };
//
//        return $mock;
//    }

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
     * @param $path
     * @return string
     */
    protected function get_completed_file_path($path) {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        return "{$this->completed_dir}/{$filename}.csv";
    }

    /**
     * @param $path
     * @return string
     */
    protected function get_completed_log_path($path) {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        return "{$this->completed_dir}/{$filename}_log.csv";
    }

    /**
     * @param $path
     * @return string
     */
    protected function get_failed_log_path($path) {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        return "{$this->in_dir}/{$filename}_log.csv";
    }

    /**
     * @param $path
     */
    protected function assertSuccessFiles($path) {
        $this->assertFileNotExists($path, "Fail ($path): Data file still in in_dir");
        $this->assertFileExists($this->get_completed_file_path($path), "Fail ($path): File has not been moved to completed_dir");
        $this->assertFileExists($this->get_completed_log_path($path), "Fail ($path): Log file has not been created in completed_dir");
        $this->assertFileNotExists($this->get_failed_log_path($path), "Fail ($path): Log file exists in in_dir");
    }

    /**
     * @param $path
     */
    protected function assertFailedFiles($path) {
        $this->assertFileExists($path, "Fail ($path): Data file no longer in in_dir");
        $this->assertFileNotExists($this->get_completed_file_path($path), "Fail ($path): File has been moved to completed_dir");
        $this->assertFileNotExists($this->get_completed_log_path($path), "Fail ($path): Log file in completed_dir");
        $this->assertFileExists($this->get_failed_log_path($path), "Fail ($path): Log file not created in in_dir");
    }

    /**
     * @param $username
     * @return \stdClass
     */
    protected function create_user($username) {
        return $this->getDataGenerator()->create_user([
            'username' => $username
        ]);
    }

    /**
     * @param $range
     * @return array
     */
    protected function create_users($range) {
        $users = [];

        foreach($range as $num) {
            $users[$num] = $this->getDataGenerator()->create_user([
                'username' => "user{$num}"
            ]);
        }

        return $users;
    }

    /**
     * @return array
     * @throws \moodle_exception
     */
    protected function do_adhoc_tasks() {
        $tasks = [];

        while($task = \core\task\manager::get_next_adhoc_task(time())) {
            $this->assertInstanceOf(merge_task::class, $task);

            try {
                $task->execute();
                \core\task\manager::adhoc_task_complete($task);
                $tasks[] = [$task, true, null];

            } catch (\Exception $ex) {
                \core\task\manager::adhoc_task_failed($task);
                $tasks[] = [$task, false, $ex];
            }
        }

        return $tasks;

    }

}