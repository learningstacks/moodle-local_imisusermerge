<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 3/5/2019
 * Time: 10:36 AM
 */

namespace local_imisusermerge;
defined('MOODLE_INTERNAL') || die();

class config {

    private $in_dir;
    private $completed_dir;
    private $file_field_map;
    private $file_name_regex;

    /**
     * config constructor.
     * @throws merge_exception
     */
    function __construct() {
        global $CFG;

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

    public function get_in_dir() {
        return $this->in_dir;
    }

    public function get_completed_dir() {
    return $this->completed_dir;
    }

    public function get_file_name_regex() {
        return $this->file_name_regex;
    }

    public function get_file_field_map() {
        return $this->file_field_map;
    }

}