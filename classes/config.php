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
 * Class config
 * @package local_imisusermerge
 * @property-read string $in_dir the full path to the in_dir
 * @property-read string $completed_dir
 * @property-read string $file_name_regex
 * @property-read array $file_field_map
 * @property-read string[] $notification_email_addresses
 */
class config {

    /**
     * @var
     */
    private $in_dir;
    /**
     * @var
     */
    private $completed_dir;
    /**
     * @var
     */
    private $file_field_map;
    /**
     * @var
     */
    private $file_name_regex;
    /**
     * @var
     */
    private $notification_email_addresses = [];

    /**
     * config constructor.
     * @throws merge_exception
     * @throws \dml_exception
     */
    function __construct() {
        global $CFG;

        $c = get_config(imisusermerge::COMPONENT_NAME);

        if (isset($c->notification_email_addresses) && !empty($c->notification_email_addresses)) {
            $this->notification_email_addresses = explode(',', $c->notification_email_addresses);
            $bademails = [];
            foreach ($this->notification_email_addresses as $email) {
                if (!validate_email($email)) {
                    $bademails[] = $email;
                }
            }

            if (!empty($bademails)) {
                throw new merge_exception('invalid_notification_recipient_emails', join(', ', $bademails));
            }
        }

        if (isset($CFG->merge_in_dir)) {
            $this->in_dir = $CFG->merge_in_dir;
            if (!is_dir($this->in_dir)) {
                throw new merge_exception('missing_directory', $this->in_dir);
            }
        } else {
            throw new merge_exception('missing_config', 'merge_in_dir');
        }

        if (isset($CFG->merge_completed_dir)) {
            $this->completed_dir = $CFG->merge_completed_dir;
            if (!is_dir($this->completed_dir)) {
                throw new merge_exception('missing_directory', $this->completed_dir);
            }
        } else {
            throw new merge_exception('missing_config', 'merge_completed_dir');
        }

        if (isset($CFG->merge_file_field_map)) {
            $this->file_field_map = $CFG->merge_file_field_map;
        } else {
            throw new merge_exception('missing_config', 'merge_file_field_map');
        }

        if (isset($CFG->merge_file_name_regex)) {
            $this->file_name_regex = $CFG->merge_file_name_regex;
        } else {
            throw new merge_exception('missing_config', '$file_name_regex');
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws \coding_exception
     */
    public function __get($name) {
        if (isset($this->$name)) {
            return $this->$name;
        } else {
            throw new \coding_exception("Attempt to access invalid config property $name");
        }
    }

}