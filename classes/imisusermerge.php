<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 3/5/2019
 * Time: 10:36 AM
 */

namespace local_imisusermerge;
defined('MOODLE_INTERNAL') || die();

/**
 * Class imisusermerge
 * @package local_imisusermerge
 */
abstract class imisusermerge {
    /**
     *
     */
    const COMPONENT_NAME = "local_imisusermerge";

    /* @var config */
    private static $config;
    /**
     * @var
     */
    private static $mock_merge_tool; // Used for unit testing only

    /**
     * @return config
     * @throws merge_exception
     * @throws \coding_exception
     */
    public static function get_config() {
        if (!static::$config) {
            static::$config = new config();
        }

        return static::$config;
    }

    /**
     * @param null $config
     */
    public static function set_config($config = null) {
        static::$config = $config;
    }

    /**
     * @param string $subject
     * @param string $body
     * @param array $files
     * @param \Exception|null $exception
     * @return bool
     *
     * @throws merge_exception
     */
    public static function send_notification($subject, $body, $files = [], $exception = null) {
        global $CFG;

        if (defined('BEHAT_SITE_RUNNING')) {
            // Fake email sending in behat.
            return true;
        }

        if (!empty($CFG->noemailever)) {
            // Hidden setting for development sites, set in config.php if needed.
            debugging('Not sending email due to $CFG->noemailever config setting', DEBUG_NORMAL);
            return true;
        }

        if (is_array($files)) {
            foreach ($files as $file) {
                if (!is_file($file)) {
                    throw new merge_exception('missing_email_attachment', $file);
                }
            }
        }

        try {
            $recipients = self::get_config()->notification_email_addresses;
            if (!empty($recipients)) {

                $mail = get_mailer();

                $mailbody = $body;
                if (!empty($exception)) {
                    $mailbody = $mailbody . PHP_EOL . PHP_EOL . $exception->getMessage();
                }

                foreach ($recipients as $email) {
                    $mail->addAddress($email);
                }
                $noreply_user = \core_user::get_noreply_user();
                $mail->Subject = $subject;
                $mail->From = $noreply_user->email;
                $mail->FromName = fullname($noreply_user);
                $mail->WordWrap = 60;
                $mail->isHTML(false);
                $mail->Encoding = 'quoted-printable';
                $mail->Body = $mailbody;

                if (is_array($files)) {
                    foreach ($files as $file) {
                        $mail->addAttachment($file);
                    }
                }

                // Autogenerate a MessageID if it's missing.
                if (empty($mail->MessageID)) {
                    $mail->MessageID = generate_email_messageid();
                }

                if (!$mail->send()) {

                    // ERROR
                    // Trigger event for failing to send email.
                    $event = \core\event\email_failed::create(array(
                        'context' => \context_system::instance(),
                        'userid' => $noreply_user->id,
                        'relateduserid' => null,
                        'other' => array(
                            'subject' => $subject,
                            'message' => $mailbody,
                            'errorinfo' => $mail->ErrorInfo
                        )
                    ));

                    $event->trigger();

                    if (CLI_SCRIPT) {
                        mtrace('Error: local_imisusermerge::send_notification(): ' . $mail->ErrorInfo);
                    }

                    throw new merge_exception('send_notification_failed', $mail->ErrorInfo);
                }
            }

        } catch (merge_exception $ex) {
            throw $ex;

        } catch (\Exception $ex) {
            throw new merge_exception('send_notification_failed', $ex->getMessage());
        }

        return true;
    }

    /**
     * @param $mock
     */
    public static function set_mock_merge_tool($mock) {
        self::$mock_merge_tool = $mock;
    }

    /**
     * @return \MergeUserTool
     */
    public static function get_merge_tool() {
        return (self::$mock_merge_tool) ? self::$mock_merge_tool : new \MergeUserTool();
    }

    /**
     * @param $id
     * @param null $a
     * @param bool $lazyload
     * @return string
     * @throws \coding_exception
     */
    public function get_string($id, $a = null, $lazyload = false) {
        return get_string($id, self::COMPONENT_NAME, $a, $lazyload);
    }

}
