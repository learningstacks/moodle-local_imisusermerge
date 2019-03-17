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
 * @property-read string $file_base
 * @property-read string $in_dir the full path to the in_dir
 * @property-read string $completed_dir
 * @property-read string $file_name_regex
 * @property-read array $file_field_map
 * @property-read string[] $notification_email_addresses
 */
class config extends imisusermerge implements \JsonSerializable {

    /**
     * @var
     */
    private $file_base;
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
     * @throws \coding_exception
     */
    function __construct() {
        global $CFG;

        // hard-coded settings
        $this->file_base = 'user_merge_request_';
        $this->notification_email_addresses = [];
        $this->file_name_regex = "/^{$this->file_base}[0-9]{8}-[0-9]{6}\.csv$/i";
        $this->file_field_map = [
            'from_imisid' => 'duplicateid',
            'to_imisid' => 'mergetoid',
            'merge_time' => 'dateofmerge'
        ];

        $errors = [];
        try {
            $c = get_config(imisusermerge::COMPONENT_NAME);
        } catch (\Exception $ex) {
            throw new merge_exception('error', $ex->getMessage());
        }

        // from config_plugins
        if (!empty($c->notification_email_addresses)) {
            $this->notification_email_addresses = explode(';', $c->notification_email_addresses);
            $bademails = [];
            foreach ($this->notification_email_addresses as $email) {
                if (!validate_email($email)) {
                    $bademails[] = $email;
                }
            }

            if (!empty($bademails)) {
                $errors[] = $this->get_string("invalid_notification_email_addresses", join(', ', $bademails));
            }
        }

        // merge_in_dir
        if (empty($CFG->merge_in_dir)) {
            $errors[] = $this->get_string("invalid_merge_in_dir", "not set");
        } else if (!is_dir($CFG->merge_in_dir)) {
            $errors[] = $this->get_string("invalid_merge_in_dir", $CFG->merge_in_dir);
        } else {
            $this->in_dir = $CFG->merge_in_dir;
        }

        // merge_completed_dir
        if (empty($CFG->merge_completed_dir)) {
            $errors[] = $this->get_string("invalid_merge_completed_dir", "not set");
        } else if (!is_dir($CFG->merge_completed_dir)) {
            $errors[] = $this->get_string("invalid_merge_completed_dir", $CFG->merge_completed_dir);
        } else {
            $this->completed_dir = $CFG->merge_completed_dir;
        }

//        // merge_file_name_regex
//        if (empty($CFG->merge_file_name_regex)) {
//            $errors[] = $this->get_string("invalid_merge_file_name_regex","not set");
//        } else {
//            $this->file_name_regex = $CFG->merge_file_name_regex;
//        }
//
//        // merge_file_field_map
//        if (empty($CFG->merge_file_field_map)) {
//            $errors[] = "merge_file_field_map is not set";
//        } else if (!is_array($CFG->merge_file_field_map)) {
//            $errors[] = "merge_file_field_map is not an array";
//        } else {
//            $required_fields = ['from_imisid', 'to_imisid', 'merge_time'];
//            $missing_fields = [];
//            foreach ($required_fields as $fld) {
//                if (!array_key_exists($fld, $CFG->merge_file_field_map) || empty($CFG->merge_file_field_map[$fld])) {
//                    $missing_fields[] = $fld;
//                }
//            }
//            if (!empty($missing_fields)) {
//                $errors[] = "Missing merge_file_field_map entries: " . join(', ', $missing_fields);
//            } else {
//                $this->file_field_map = $CFG->merge_file_field_map;
//            }
//        }

        if (!empty($errors)) {
            throw new merge_exception('invalid_config', join("\n", $errors));
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws \coding_exception
     */
    public function __get($name) {
        if (property_exists(self::class, $name)) {
            return $this->$name;
        } else {
            throw new \coding_exception("Attempt to access invalid config property $name");
        }
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize() {
        $vars = get_object_vars($this);
        return $vars;
    }


}