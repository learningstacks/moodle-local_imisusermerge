<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

use local_imisusermerge\config;
use local_imisusermerge\imisusermerge;
use local_imisusermerge\merge_exception;

/**
 * Class config_testcase
 * @package local_imisusermerge\tests
 */
class config_testcase extends base
{


    /**
     * @throws \coding_exception
     * @throws merge_exception
     */
    public function setUp()
    {
        parent::setup();
    }

    /**
     *
     */
    public function tearDown()
    {
        parent::tearDown();
    }


    /**
     * @throws merge_exception
     * @throws \coding_exception
     */
    public function test_valid_config()
    {
        global $CFG;

        $this->resetAfterTest(true);

        $this->set_test_config([
            'merge_in_dir' => $CFG->merge_in_dir,
            'merge_completed_dir' => $CFG->merge_completed_dir,
            'notification_email_addresses' => 'someone@somewhere.com;someone2@somewhere.com'
        ]);

        $config = new config();
        $this->assertEquals($CFG->merge_in_dir, $config->in_dir);
        $this->assertEquals($CFG->merge_completed_dir, $config->completed_dir);
        $this->assertEquals('duplicateid', $config->file_field_map['from_imisid']);
        $this->assertEquals('mergetoid', $config->file_field_map['to_imisid']);
        $this->assertEquals('dateofmerge', $config->file_field_map['merge_time']);
        $this->assertEquals(2, count($config->notification_email_addresses));
        $this->assertEquals('someone@somewhere.com', $config->notification_email_addresses[0]);
        $this->assertEquals('someone2@somewhere.com', $config->notification_email_addresses[1]);
    }

    /**
     * @return array
     * @throws \coding_exception
     */
    public function invalid_config_dataset()
    {
        global $CFG;
        return [
            'no settings' => [
                [],
                [
                    get_string('invalid_merge_in_dir', imisusermerge::COMPONENT_NAME, 'not set'),
                    get_string('invalid_merge_completed_dir', imisusermerge::COMPONENT_NAME, 'not set'),
                ]
            ],
            'invalid directories' => [
                [
                    'merge_in_dir' => "qq:/a",
                    'merge_completed_dir' => "qq:/b"
                ],
                [
                    get_string('invalid_merge_in_dir', imisusermerge::COMPONENT_NAME, 'qq:/a'),
                    get_string('invalid_merge_completed_dir', imisusermerge::COMPONENT_NAME, 'qq:/b'),
                ]
            ],
            'invalid emails' => [
                [
                    'merge_in_dir' => $CFG->merge_in_dir,
                    'merge_completed_dir' => $CFG->merge_completed_dir,
                    'notification_email_addresses' => "goodemail@dest.com;bademail1;bademail2@z"
                ],
                [
                    get_string('invalid_notification_email_addresses', imisusermerge::COMPONENT_NAME, 'bademail1, bademail2@z'),
                ]
            ],

        ];
    }

    /**
     * @dataProvider invalid_config_dataset
     * @param $data
     * @throws \coding_exception
     * @throws merge_exception
     */
    public function test_invalid_config($data)
    {
        $this->resetAfterTest(true);
        $this->set_test_config($data);
        $this->expectException(merge_exception::class);
        new config();
    }
}