<?php
// Copyright (C) 2017 Learning Stacks LLC https://learningstacks.com/
//
// This file is a part of the IMIS Integration Components developed by
// Learning Stacks LLC - https://learningstacks.com/
//
// This file cannot be copied or distributed without the express permission
// of Learning Stacks LLC.

/**
 * @package   local_imisbridge
 * @copyright 2017 onwards Learning Stacks LLC {@link https://learningstacks.com/}
 * @license   All Rights Reserved
 */


namespace local_imisusermerge;
defined('MOODLE_INTERNAL') || die();

/**
 * Class service_proxy
 * Interface to IMIS Bridge services
 * @package local_imisbridge
 * @property-read config $config
 * @property-read string $filepath
 * @property-read string[] $lines
 * @property-read string[] $headers
 * @property-read string[] $missing_fields
 * @property-read array $fldpos_map
 * @property-read merge_action[][] $merges
 * @property-read string $status
 * @property-read string $message
 * @property-read array $ambiguous_merges
 * @property-read int $failed
 * @property-read int $skipped
 * @property-read int $completed
 * @property-read int $current_line_num
 * @property-read string $current_line
 */
class merge_file implements \JsonSerializable {

    /**
     *
     */
    const STATUS_TODO = 'STATUS_TODO';
    /**
     *
     */
    const STATUS_LOADED = 'STATUS_LOADED';
    /**
     *
     */
    const STATUS_COMPLETE = 'STATUS_COMPLETE';
    /**
     *
     */
    const STATUS_FAILED = 'STATUS_FAILED';
    /**
     *
     */
    const STATUS_INVALID_FILE = 'STATUS_INVALID_FILE';
    /**
     *
     */
    const STATUS_EMPTY_FILE = 'STATUS_EMPTY_FILE';
    /**
     *
     */
    const STATUS_ERROR = 'STATUS_ERROR';

    /**
     * @var config
     */
    private $config;
    /**
     * @var string
     */
    private $filepath;
    /**
     * @var string[]
     */
    private $lines = [];
    /**
     * @var string[]
     */
    private $headers;
    /**
     * @var string[]
     */
    private $missing_fields;
    /**
     * @var array
     */
    private $fldpos_map;
    /**
     * @var array
     */
    private $merges = [];
    /**
     * @var string
     */
    private $status;
    /**
     * @var string
     */
    private $message;
    /** @var @var array */
    private $ambiguous_merges;

    /**
     * @var
     */
    private $failed;
    /**
     * @var
     */
    private $skipped;
    /**
     * @var
     */
    private $completed;

    /**
     * @var
     */
    private $current_line_num;
    /**
     * @var
     */
    private $current_line;

    /**
     * imisusermerge constructor.
     * @param $filepath
     * @throws merge_exception
     * @throws \coding_exception
     */
    public function __construct($filepath) {

        $this->config = imisusermerge::get_config();
        $this->status = self::STATUS_TODO;
        $this->filepath = $filepath;

        if (!file_exists($this->filepath)) {
            $this->status = self::STATUS_INVALID_FILE;
            $this->message = get_string('no_file', imisusermerge::COMPONENT_NAME, $this->as_string_params());
            throw new merge_exception('invalid_file', $this->message);
        }


    }

    /**
     * @return merge_file|null
     * @throws merge_exception
     * @throws \coding_exception
     */
    public static function get_next_file() {
        /* @var config */
        $config = imisusermerge::get_config();
        $regex = $config->file_name_regex;
        $filenames = [];

        foreach (scandir($config->in_dir) as $filename) {
            if (preg_match($regex, $filename)) {
                $filenames[] = $filename;
            }
        }

        $fn = function($a, $b) {
            return strcmp(pathinfo($a, PATHINFO_FILENAME), pathinfo($b, PATHINFO_FILENAME));
        };

        usort($filenames, $fn);
        return ($filenames) ? $filenames[0] : null;
    }


    /**
     * Returns the headers and their position
     *
     * @param $line
     * @throws merge_exception
     * @throws \coding_exception
     */
    protected function parse_header($line) {
        $map = $this->config->file_field_map;
        $this->fldpos_map = [];
        $this->headers = str_getcsv(strtolower(trim($line)));
        $this->missing_fields = array_diff(array_values($map), $this->headers);

        if (!empty($this->missing_fields)) {
            $this->status = self::STATUS_INVALID_FILE;
            $this->message = get_string('file_missing_fields', imisusermerge::COMPONENT_NAME, $this->as_string_params());
            throw new merge_exception('invalid_file', $this->message);
        }

        foreach ($map as $outname => $inname) {
            $this->fldpos_map[$outname] = array_search($inname, $this->headers);
        }
    }


    /**
     * @return int
     * @throws merge_exception
     * @throws \coding_exception
     */
    public function load() {
        $mbsub = ini_get('mbstring.substitute_character');
        ini_set('mbstring.substitute_character', "none");
     
        try {

            if (!file_exists($this->filepath)) {
                $this->status = self::STATUS_INVALID_FILE;
                $this->message = get_string('no_file', imisusermerge::COMPONENT_NAME, $this->as_string_params());
                throw new merge_exception('invalid_file', $this->message);
            }

            // Load entire file into array so we ensure we process each line
            $this->lines = file($this->filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($this->lines === false) {
                $this->status = self::STATUS_INVALID_FILE;
                $this->message = get_string('read_failed', imisusermerge::COMPONENT_NAME, $this->as_string_params());
                throw new merge_exception('invalid_file', $this->message);
            }

            if (count($this->lines) < 2) {
                $this->status = self::STATUS_EMPTY_FILE;
                $this->message = get_string('empty_file', imisusermerge::COMPONENT_NAME, $this->as_string_params());
                throw new merge_exception('invalid_file', $this->message);
            }

            $from = [];
            $this->ambiguous_merges = [];
            $this->parse_header($this->lines[0]);

            // Extract the merge_actions
            for ($linenum = 1; $linenum < count($this->lines); $linenum++) {
                $this->current_line = $str = $this->lines[$linenum];
                $this->current_line_num = $linenum;
                if (!empty($str)) {

                    try {
                        $this->merges[] = $merge = new merge_action($linenum, $str, $this->fldpos_map);
                    } catch (merge_exception $ex) {
                        $this->status = self::STATUS_INVALID_FILE;
                        throw $ex;
                    }


                    $from_imisid = $merge->getFromImisid();

                    // See if this user is being merged more than once
                    if (array_key_exists($from_imisid, $from)) {
                        if (!array_key_exists($from_imisid, $this->ambiguous_merges)) {
                            $this->ambiguous_merges[$from_imisid] = true;
                        }
                    } else {
                        $from[$from_imisid] = true;
                    }
                }
            }

            // Check for ambiguous merges
            if (!empty($this->ambiguous_merges)) {
                $this->status = self::STATUS_INVALID_FILE;
                $this->message = get_string('ambiguous_merges', imisusermerge::COMPONENT_NAME, implode(", ", $this->as_string_params()));
                throw new merge_exception('invalid_file', $this->message);
            }

            // Sort by mergetime to ensure we process in the same order as originally merged
            $success = usort($this->merges, function (merge_action $m1, merge_action $m2) {
                if ($m1->getMergeTime() < $m2->getMergeTime()) {
                    return -1;
                } else if ($m1->getMergeTime() > $m2->getMergeTime()) {
                    return 1;
                } else {
                    return 0;
                }
            });

            if (!$success) {
                $this->status = self::STATUS_ERROR;
                $this->message = get_string('sort_failed', imisusermerge::COMPONENT_NAME, $this->as_string_params());
                throw new merge_exception('error', $this->message);
            }

            $this->status = self::STATUS_LOADED;

        } catch (merge_exception $ex) {
            $this->message = $ex->getMessage();
            throw $ex;

        } catch (\Exception $ex) {
            $this->status = self::STATUS_ERROR;
            $this->message = get_string('sort_failed', imisusermerge::COMPONENT_NAME, $this->as_string_params())
                . "\n\n" . $ex->getMessage();
            throw new merge_exception('error', $this->message);
        } finally {
            ini_set('mbstring.substitute_character', $mbsub);
        }

        return $this->status;
    }

    /**
     * Process a merge request file
     *
     * @return void
     * @throws merge_exception
     * @throws \coding_exception
     */
    public function process() {
        $this->failed = 0;
        $this->skipped = 0;
        $this->completed = 0;

        try {

            if ($this->status == self::STATUS_TODO) {
                $this->load();
            }

            /* @var $merge merge_action */
            foreach ($this->merges as $merge) {
                try {
                    $merge->merge(); // Succeeds or throws
                    $this->completed++;

                } catch (merge_exception $ex) {
                    switch ($merge->getStatus()) {
                        case merge_action::STATUS_FAILED:
                            $this->status = self::STATUS_FAILED;
                            $this->failed++;
                            throw $ex;
                            break;
                        case merge_action::STATUS_ERROR:
                            $this->status = self::STATUS_ERROR;
                            $this->failed++;
                            throw $ex;
                            break;
                        case merge_action::STATUS_INVALID:
                            $this->status = self::STATUS_INVALID_FILE;
                            $this->failed++;
                            throw $ex;
                            break;
                        case merge_action::STATUS_SKIPPED:
                            $this->skipped++;
                            break;
                        case merge_action::STATUS_MERGED:
                            $this->completed++;
                            break;
                        default:
                            throw new \coding_exception("Invalid merge_action status {$merge->getStatus()}");
                    }
                }
            }

            $this->log_success();

        } catch (merge_exception $ex) {
            $this->message = $ex->getMessage();
            if ($this->status == self::STATUS_EMPTY_FILE) {
                $this->log_success(); // Then continue
            } else {
                $this->log_failure();
                throw $ex;
            }

        } catch (\Exception $ex) {
            $this->status = self::STATUS_ERROR;
            $this->log_failure();
            $this->status = self::STATUS_ERROR;
            $this->message = get_string('sort_failed', imisusermerge::COMPONENT_NAME, $this->as_string_params())
                . "\n\n" . $ex->getMessage();
            throw new merge_exception('error', $this->message);
        }
    }

    /**
     *
     */
    protected function log_success() {
        $completed_file_path = $this->get_completed_file_path();
        $completed_log_path = $this->get_completed_log_path();
        $failed_log_path = $this->get_failed_log_path();

        rename($this->filepath, $completed_file_path);
        $this->write_log($completed_log_path, $this->get_data_to_log());
        if (is_file($failed_log_path)) {
            unlink($failed_log_path);
        }
    }

    /**
     *
     */
    protected function log_failure() {
        $this->write_log($this->get_failed_log_path(), $this->get_data_to_log());
    }

    /**
     * @return array|mixed
     */
    protected function get_data_to_log() {
        return $this->jsonSerialize();
//        $data = new \stdClass();
//        $map = [];
//        $data->summary = $this;
//
//        $data->merges = [
//
//        ];
//        foreach($this->config->file_field_map as $var_name => $file_field) {
//            $map[$file_field] = $var_name;
//        }
//        $map['status'] = 'status';
//        $map['message'] = 'message';
//
//        $data->merges[] = array_values($map);
//
//        foreach($this->merges as $merge) {
//            $line_fields = [];
//            foreach ($map as $file_name => $var_name) {
//                $line_fields[] = $merge->$var_name;
//            }
//            $data->merges[] = $line_fields;
//        }
//
//        return $data;
    }

    /**
     * @param $path
     * @param $data
     */
    public function write_log($path, $data) {
        file_put_contents($path, json_encode($data));
    }

    /**
     * @return Object
     */
    public function as_string_params() {
        $vars = get_object_vars($this);
        return (object)(array_filter($vars, function ($val) {
            return (!is_array($val) && !is_object($val));
        }));
    }

    /**
     * @return string
     */
    public function get_completed_file_path() {
        $filename = pathinfo($this->filepath, PATHINFO_FILENAME);
        return "{$this->config->completed_dir}/{$filename}.{$this->config->file_ext}";
    }

    /**
     * @return string
     */
    public function get_completed_log_path() {
        $filename = pathinfo($this->filepath, PATHINFO_FILENAME);
        return "{$this->config->completed_dir}/{$filename}_log.json";
    }

    /**
     * @return string
     */
    public function get_failed_log_path() {
        $filename = pathinfo($this->filepath, PATHINFO_FILENAME);
        return "{$this->config->in_dir}/{$filename}_log.json";
    }

    /**
     * @return mixed
     */
    public function getFilepath() {
        return $this->filepath;
    }

    /**
     * @return array
     */
    public function getLines() {
        return $this->lines;
    }

    /**
     * @return mixed
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function getMerges() {
        return $this->merges;
    }

    /**
     * @return mixed
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize() {
        $vars = get_object_vars($this);
        return $vars;
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

}
