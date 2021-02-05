<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . "/../../../admin/tool/mergeusers/lib/mergeusertool.php");

use local_imisusermerge\imisusermerge;
use local_imisusermerge\merge_file;
use local_imisusermerge\task\merge_task;
use local_imisusermerge\config;
use \MergeUserTool;
use Symfony\Component\Filesystem\Filesystem;


function mtrace_wrapper_stub($string, $eol)
{
    // Suppress output during unit test
}
/**
 * Class base
 * @package local_imisusermerge\tests
 */
abstract class base extends \advanced_testcase {

    /**
     * @var config
     */
    protected $config;
    protected $fsroot;

    /**
     *
     * @throws \coding_exception
     * @throws \local_imisusermerge\merge_exception
     */
    public function setup() {

        global $CFG;

        $this->fsroot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "local_imisusermerge";
        if (is_dir($this->fsroot)) {
            $fs = new Filesystem();
            $fs->remove($this->fsroot);
        }

        $CFG->merge_in_dir = $this->fsroot . DIRECTORY_SEPARATOR . "in";
        $CFG->merge_completed_dir = $this->fsroot . DIRECTORY_SEPARATOR . "complete";
        mkdir($this->fsroot);
        mkdir($CFG->merge_in_dir);
        mkdir($CFG->merge_completed_dir);

        $this->config = new config();
        imisusermerge::set_mock_merge_tool(null);
        imisusermerge::set_config(null);
    }

    /**
     */
    public function tearDown() {
        $fs = new Filesystem();
        $fs->remove($this->fsroot);
    }

    /**
     * Set $CFG and config_plugins data
     * @param array $data
     */
    public function set_test_config($data = []) {
        global $CFG;

        $data = (array)$data;

        $cfg_fields = [
            'merge_in_dir',
            'merge_completed_dir'
        ];
        foreach ($cfg_fields as $name) {
            if (isset($data[$name])) {
                $CFG->$name = $data[$name];
            } else {
                unset($CFG->$name);
            }
        }

        if (isset($data['notification_email_addresses'])) {
            set_config(
                'notification_email_addresses',
                $data['notification_email_addresses'],
                imisusermerge::COMPONENT_NAME);
        } else {
            unset_config(
                'notification_email_addresses',
                imisusermerge::COMPONENT_NAME);
        }
    }

    /**
     * @param $datetime
     * @param array $data
     * @return string
     */
    protected function write_request_file($datetime, $data = []) {
        $path = "{$this->config->in_dir}/{$this->config->file_base}_{$datetime}.{$this->config->file_ext}";
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

    /**
     * @param $path
     * @return string
     */
    protected function get_completed_file_path($path) {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        return "{$this->config->completed_dir}/{$filename}.{$this->config->file_ext}";
    }

    /**
     * @param $path
     * @return string
     */
    protected function get_completed_log_path($path) {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        return "{$this->config->completed_dir}/{$filename}_log.json";
    }

    /**
     * @param $path
     * @return string
     */
    protected function get_failed_log_path($path) {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        return "{$this->config->in_dir}/{$filename}_log.json";
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

        foreach ($range as $num) {
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

        while ($task = \core\task\manager::get_next_adhoc_task(time())) {
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

    /**
     * @param $path
     * @param merge_file $merge_file
     */
    public function assert_log_file_contents($path, $merge_file) {
        /* @var object */
        $log = json_decode(file_get_contents($path));
        $check_props = [
            'config',
            'status',
            'message',
            'filepath',
            'lines',
            'headers',
            'missing_fields',
            'fldpos_map',
            'merges',
            'ambiguous_merges'];
        foreach ($check_props as $prop) {
            $logval = $log->$prop;
            $expval = $merge_file->$prop;
            if (is_array($expval)) {
                $this->assertEquals(json_encode($expval), json_encode($logval), $prop);
            } else if (is_object($expval)) {
                $this->assertEquals(json_encode($expval), json_encode($logval), $prop);
            } else {
                $this->assertEquals($expval, $logval, $prop);
            }
        }
    }

}