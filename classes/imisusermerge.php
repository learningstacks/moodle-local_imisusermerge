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

require_once("../../../admin/tool/mergeusers/lib/mergeusertool.php");

/**
 * Class service_proxy
 * Interface to IMIS Bridge services
 * @package local_imisbridge
 */
class imisusermerge {
    /**
     *
     */
    const COMPONENT_NAME = "local_imisusermerge";

    const STATUS_TODO = 1;
    const STATUS_COMPLETE = 2;
    const STATUS_FAILED = 3;

    /**
     * @var mixed|null
     */
    private $config = null;
    private $job;
    private $filepath;
    private $logger;
    private $headers;

    /**
     * imisusermerge constructor.
     * @throws \dml_exception
     */
    public function __construct($filepath) {
        $this->config = get_config(self::COMPONENT_NAME);
        $this->config->fieldmap = [
            'duplicateid' => 'from_imisid',
            'mergetoid' => 'to_imisid'
        ];

        $this->job = (object)[
            'file_path' => $filepath,
            'status' => self::STATUS_TODO,
            'file_headers_raw' => [],
            'file_headers_mapped' => [],
            'file_records_sorted' => [],
            'merges' => [],
        ];

        if (!file_exists($this->job->filepath)) {
            throw new imisusermerge_exception('missing_file', $this->job);
        }
    }

    /**
     * @return |null
     */
    static public function get_next_file() {
        $firstfile = null;

        $config = get_config(self::COMPONENT_NAME);

        foreach (scandir($config->indir) as $item) {
            if (preg_match($config->fnregexp, $item)) {
                if ($firstfile === null || strcmp(strtolower($item), strtolower($firstfile)) < 0) {
                    $firstfile = $item;
                }
            }
        }

        return $firstfile;
    }


    /**
     * @param $fromuser
     * @param $touser
     */
    public function merge_user($fromuser, $touser) {
        /**
         * If already merged, return true
         */

        return false;
    }

    /**
     * Lookup user by imisid
     *
     * @param string $imisid The IMISID of a user to lookup
     * @return int | null The Moodle userid or null if not found
     */
    public function get_user_by_imisid($imisid) {
        global $DB;

        return $DB->get_record("user", ['username' => $imisid, 'mnethostid' => 1]);
    }

    /**
     *
     * @param $handle
     * @param $map
     */
    public function load_record($handle) {
        // File at $handle is at next record to read
        if ($rec = fgetcsv($handle, 1000, ",") !== false) {
            $mapped = [];
            foreach ($map as $fld => $pos) {
                $mapped[$fld] = $result->record->raw[$pos];
            }
        }

        return $result;
    }

    /**
     * Assumes the file position is at first record/line.
     * Returns the headers and their position
     *
     * @param $handle
     */
    public function get_header($handle) {
        $map = [];
        $hdrs = [];

        if ($hdrs = fgetcsv($handle, 1000, ",") !== false) {
            $this->job->file_headers_raw = $hdrs;
            $maphdr = function ($fldname) {
                return array_key_exists($fldname, $this->config->fieldmap) ? $this->config->fieldmap[$fldname] : $fldname;
            };
            $this->job->file_headers_mapped = array_map($maphdr, $hdrs);

            // Verify required headers are present
            if (!empty(array_diff(array_keys($this->config->fieldmap), array_keys($map)))) {
                throw new \moodle_exception(
                    'missing_file_fields',
                    [
                        'required_fields' => join(',', array_values($this->config->fieldmap)),
                        'found_fields' => join(',', $hdrs)
                    ]
                );
            }

        } else {
            throw new imisusermerge_exception('get_header_failed', $this->job);
        }
    }

    public function load_file() {

        $handle = null;

        try {

            if (!file_exists($this->job->filepath)) {
                throw new imisusermerge_exception("missing_file", $this->job);
            } else if (($handle = fopen($this->job->filepath, "r")) === false) {
            }

            // Load entire file into array so we ensure we process each line
            $lines = file($this->file_path);
            // throw new imisusermerge_exception("open_failed", $this->job);
            $this->set_header($lines[0]);
            for ($line = 1; $line < count($lines); $line++) {
                $this->add_row($lines[$line]);
            }

            while ($rec = $this->load_record($handle, $map)) {
                $job = (object)[
                    'record' => $rec,
                    'from_userid' => null,
                    'to_userid' => null,
                    'status' => TO_BE_PROCESSED,
                    'log' => []
                ];
                list($from_imisid, $to_imisid) = $rec->mapped;
                if ($from_imisid == $to_imisid) {
                    // log skip
                    continue;
                }

                $from_userid = $this->get_user_by_imisid($from_imisid);
                $to_userid = $this->get_user_by_imisid($to_imisid);

                if (empty($from_userid) || empty($to_userid = $this->get_user_by_imisid($to_imisid))) {

                }
                $this->merge_user($from_userid, $to_userid);
            }


        } catch (imisusermerge_exception $ex) {
            // log error
            //
        } catch (\Exception $e) {
        } finally {
            if ($handle) {
                fclose($handle);
            }
        }
    }

    /**
     *
     */
    public function process() {
        $handle = null;
        $from_userid = null;
        $to_userid = null;

        try {
            if (!file_exists($this->job->filepath)) {
                throw new imisusermerge_exception("missing_file",
                    (object)[
                        "filepath" => $file
                    ]
                );
            } else if (($handle = fopen($this->job->filepath, "r")) === false) {
                throw new imisusermerge_exception("open_failed",
                    (object)[
                        "filepath" => $file
                    ]);
            }

            $map = $this->get_headers($handle);

            while ($rec = $this->load_record($handle, $map)) {
                $job = (object)[
                    'record' => $rec,
                    'from_userid' => null,
                    'to_userid' => null,
                    'status' => TO_BE_PROCESSED,
                    'log' => []
                ];
                list($from_imisid, $to_imisid) = $rec->mapped;
                if ($from_imisid == $to_imisid) {
                    // log skip
                    continue;
                }

                $from_userid = $this->get_user_by_imisid($from_imisid);
                $to_userid = $this->get_user_by_imisid($to_imisid);

                if (empty($from_userid) || empty($to_userid = $this->get_user_by_imisid($to_imisid))) {

                }
                $this->merge_user($from_userid, $to_userid);
            }


        } catch (imisusermerge_exception $ex) {
            // log error
            //
        } catch (\Exception $e) {
        } finally {
            if ($handle) {
                fclose($handle);
            }
        }
    }

    public function process2() {
        try {
            $this->load_file();
            foreach ($this->job->actions as $action) {
                $action->merge();
            }

        } catch (imisusermerge_exception $ex) {
            // log error
            //
        } catch (\Exception $e) {
        } finally {
            if ($handle) {
                fclose($handle);
            }
        }
    }

}
