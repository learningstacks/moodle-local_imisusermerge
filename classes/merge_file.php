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
 */
class merge_file {

    const STATUS_TODO = 'STATUS_TODO';
    const STATUS_LOADED = 'STATUS_LOADED';
    const STATUS_COMPLETE = 'STATUS_COMPLETE';
    const STATUS_FAILED = 'STATUS_FAILED';
    const STATUS_INVALID_FILE = 'STATUS_INVALID_FILE';
    const STATUS_EMPTY_FILE = 'STATUS_EMPTY_FILE';
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
     */
    public function __construct($filepath) {

        $this->config = imisusermerge::get_config();
        $this->status = self::STATUS_TODO;
        $this->filepath = $filepath;

        if (!file_exists($this->filepath)) {
            $this->status = self::STATUS_INVALID_FILE;
            throw new merge_exception('no_file');
        }


    }

    /**
     * @return merge_file|null
     * @throws merge_exception
     */
    public static function get_next_file() {
        $firstfile = null;

        /* @var config */
        $config = imisusermerge::get_config();
        $regex = $config->file_name_regex;

        foreach (scandir($config->in_dir) as $filename) {
            if (preg_match($regex, $filename)) {
                if ($firstfile === null || strcmp(strtolower($filename), strtolower($firstfile)) < 0) {
                    $firstfile = $filename;
                }
            }
        }

        return $firstfile;
    }


    /**
     * Returns the headers and their position
     *
     * @param $line
     * @throws merge_exception
     */
    protected function parse_header($line) {
        $map = $this->config->file_field_map;
        $this->fldpos_map = [];
        $this->headers = str_getcsv(strtolower(trim($line)));
        $this->missing_fields = array_diff(array_keys($map), $this->headers);

        if (!empty($this->missing_fields)) {
            $this->status = self::STATUS_INVALID_FILE;
            throw new merge_exception('file_missing_fields', $this->as_string_params());
        }

        foreach ($map as $inname => $outname) {
            $this->fldpos_map[$map[$inname]] = array_search($inname, $this->headers);
        }
    }


    /**
     * @return int
     * @throws merge_exception
     */
    public function load() {

        try {

            if (!file_exists($this->filepath)) {
                $this->status = self::STATUS_INVALID_FILE;
                throw new merge_exception("no_file", $this->as_string_params());
            }

            // Load entire file into array so we ensure we process each line
            $this->lines = file($this->filepath, FILE_IGNORE_NEW_LINES);
            if ($this->lines === false) {
                $this->status = self::STATUS_INVALID_FILE;
                throw new merge_exception("read_failed", $this->as_string_params());
            }

            if (count($this->lines) < 2) {
                $this->status = self::STATUS_EMPTY_FILE;
                throw new merge_exception("empty_file", $this->as_string_params());
            }

            $from = [];
            $dups = [];

            $this->parse_header($this->lines[0]);

            // Extract the merge_actions
            for ($linenum = 1; $linenum < count($this->lines); $linenum++) {
                $this->current_line = $str = trim($this->lines[$linenum]);
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
                        if (!array_key_exists($from_imisid, $dups)) {
                            $dups[$from_imisid] = true;
                        }
                    } else {
                        $from[$from_imisid] = true;
                    }
                }
            }

            // Check for ambiguous merges
            if (!empty($dups)) {
                $this->status = self::STATUS_INVALID_FILE;
                throw new merge_exception('ambiguous_merges', $this->as_string_params());
            }

            // Sert by mergetime to ensure we process in the same order as originally merged
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
                $this->status = self::STATUS_INVALID_FILE;
                throw new merge_exception('sort_failed', $this->as_string_params());
            }

            $this->status = self::STATUS_LOADED;

        } catch (merge_exception $ex) {
            $this->message = $ex->getMessage();
            throw $ex;

        } catch (\Exception $ex) {
            $this->status = self::STATUS_ERROR;
            $this->message = $ex->getMessage();
            throw new merge_exception('error_encountered', $this->as_string_params());
        }

        return $this->status;
    }

    /**
     * Process a merge request file
     *
     * @return void
     * @throws merge_exception
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
            $this->message = $ex->getMessage();
            $this->log_failure();
            throw new merge_exception('error_encountered', $this->as_string_params());
        }
    }

    /**
     *
     */
    protected function log_success() {
        $completed_file_path = $this->get_completed_file_path();
        $completed_log_path = $this->get_completed_log_path();
        $failed_log_path = $this->get_failed_log_path();

        // TODO: file with same name, other failure
        rename($this->filepath, $completed_file_path);
        $this->write_log($completed_log_path);
        if (is_file($failed_log_path)) {
            unlink($failed_log_path);
        }
    }

    /**
     *
     */
    protected function log_failure() {
        $this->write_log($this->get_failed_log_path());
    }

    protected function write_log($path) {
        $handle = null;

        try {
            $handle = fopen($path, "w");

        } catch (\Exception $ex) {
            // TODO: Write the file
        } finally {
            if ($handle) {
                fclose($handle);
            }
        }

    }

    /**
     * @return Object
     */
    public function as_string_params() {
        $vars = get_object_vars($this);
        return (object) (array_filter($vars, function ($val) {
            return !is_array($val) && !is_object($val);
        }));
    }

    public function get_completed_file_path() {
        $filename = pathinfo($this->filepath, PATHINFO_FILENAME);
        return "{$this->config->completed_dir}/{$filename}.csv";
    }

    public function get_completed_log_path() {
        $filename = pathinfo($this->filepath, PATHINFO_FILENAME);
        return "{$this->config->completed_dir}/{$filename}_log.csv";
    }

    public function get_failed_log_path() {
        $filename = pathinfo($this->filepath, PATHINFO_FILENAME);
        return "{$this->config->in_dir}/{$filename}_log.csv";
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

}
