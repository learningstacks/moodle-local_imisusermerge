<?php

namespace local_imisusermerge\tests;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/base.php");

use local_imisusermerge\imisusermerge;
use local_imisusermerge\merge_exception;

/**
 * Class email_testcase
 * @package local_imisusermerge\tests
 */
class email_testcase extends base {

    /**
     * @throws \coding_exception
     * @throws merge_exception
     */
    public function setUp() {
        parent::setup();
    }

    /**
     *
     */
    public function tearDown() {
        parent::tearDown();
    }

    /**
     * @throws merge_exception
     */
    public function test_send_email_no_email_addresses() {
        $this->resetAfterTest(true);

        set_config('notification_email_addresses', '', imisusermerge::COMPONENT_NAME);

        unset_config('noemailever');
        $sink = $this->redirectEmails();

        $result = imisusermerge::send_notification('test subject', 'test body', null, null);
        $this->assertTrue($result === true);

        $messages = $sink->get_messages();
        $this->assertEquals(0, count($messages));

    }

    /**
     * @throws merge_exception
     */
    public function test_send_email_with_attachment_and_exception() {
        $this->resetAfterTest(true);

        set_config('notification_email_addresses', 'a@a.com', imisusermerge::COMPONENT_NAME);
        $path = $this->write_request_file('20190101-000000',[
            'duplicateid,mergetoid,dateofmerge,full_name,email',
            'user1,user2,1/1/2019 00:00:02,,',
        ]);
        $exception = new merge_exception('error_code');
        unset_config('noemailever');
        $sink = $this->redirectEmails();

        $result = imisusermerge::send_notification('test subject', 'test body', $path, $exception);
        $this->assertTrue($result === true);

        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));

    }

}