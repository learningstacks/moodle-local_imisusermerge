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

    const STATUS_MERGE_TODO = 0;
    const STATUS_MERGE_COMPLETE = 1;
    const STATUS_MERGE_SKIPPED = 2;
    const STATUS_MERGE_FAILED = 3;

    /**
     * @var mixed|null
     */
    private $config;
    private $merge_tool;

    /**
     * imisusermerge constructor.
     */
    public function __construct() {
        try {
            $this->config = get_config(self::COMPONENT_NAME);
            $this->config->fieldmap = [
                'duplicateid' => 'from_imisid',
                'mergetoid' => 'to_imisid',
                'dateofmerge' => 'merge_time',
                'full_name' => 'full_name',
                'email' => 'email'
            ];
        } catch (\dml_exception $ex) {
            throw new imisusermerge_exception('get_config failed');
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
    public function merge($merge_rec) {

        list($from_imisid, $to_imisid) = $merge_rec;

        if ($from_imisid == $to_imisid) {
            $merge_rec['status'] = self::STATUS_MERGE_SKIPPED;
            $merge_rec['message'] = get_string(
                'same_users',
                self::COMPONENT_NAME,
                "$from_imisid, $to_imisid"
            );

            return $merge_rec;
        }


        $from_user = $this->get_user_by_imisid($from_imisid);
        $to_user = $this->get_user_by_imisid($to_imisid);

        $missing = [];

        if ($from_user === false) {
            $missing[] = $from_imisid;
        }

        if ($to_user === false) {
            $missing[] = $to_imisid;
        }

        if (!empty($missing)) {
            $merge_rec['status'] = self::STATUS_MERGE_SKIPPED;
            $merge_rec['message'] = get_string(
                'missing_users',
                self::COMPONENT_NAME,
                join(', ', $missing)
            );

            return $merge_rec;

        }

        // if db record found in merges with id

        // See if the from user has previously been merged and to whom

        // Do the merge
        list($success, $log) = $this->call_admin_merge($to_user->id, $from_user->id);
        if ($success) {
            $merge_rec['status'] = self::STATUS_MERGE_COMPLETE;
            $merge_rec['message'] = join("\n", $log);
        } else {
            $merge_rec['status'] = self::STATUS_MERGE_FAILED;
            $merge_rec['message'] = join("\n", $log);
        }

        return $merge_rec;
    }

    /**
     * Lookup user by imisid
     *
     * @param string $imisid The IMISID of a user to lookup
     * @return int | null The Moodle userid or null if not found
     */
    protected function get_user_by_imisid($imisid) {
        global $DB;

        return $DB->get_record("user", ['username' => $imisid, 'mnethostid' => 1]);
    }

    /**
     *
     * @param $handle
     * @param $map
     */
//    protected function parse_row($line, $fldpos_map) {
//
//        $vals = str_getcsv($line);
//
//        if (empty($vals)) {
//            throw new imisusermerge_exception('record_has_no_values', $line);
//        }
//
//        if (count($vals) != count($fldpos_map)) {
//            throw new imisusermerge_exception('malformed_record', $line);
//        }
//
//        $errors = [];
//        $merge = [];
//
//        if (!empty($vals['duplicatid']))
//            foreach ($fldpos_map as $varname => $pos) {
//                $merge[$varname] = $vals[$pos];
//            }
//
//
//        $valid = $this->validate_row($merge);
//        if ($valid !== true) {
//            throw new imisusermerge_exception('value_errors', $valid);
//        }
//
//        $merge['status'] = self::STATUS_MERGE_TODO;
//        $merge['message'] = '';
//
//        return $merge;
//
//    }

    /**
     * Assumes the file position is at first record/line.
     * Returns the headers and their position
     *
     * @param $handle
     */
    protected function parse_header($line) {
        $map = $this->config->fieldmap;
        $fldpos_map = [];
        $hdrs = str_getcsv(strtolower(trim($line)));

        if (!empty($hdrs)) {
            if (!empty($diff = array_diff(array_keys($map), $hdrs))) {
                throw new imisusermerge_exception('file_missing_fields', join(', ', $diff));
            }

            foreach ($map as $inname => $outname) {
                $fldpos_map[$map[$inname]] = array_search($inname, $hdrs);
            }
        }

        $r = [
            'headers' => $hdrs,
            'fldpos_map' => $fldpos_map
        ];

        return $r;

    }


    public function load_file($filepath) {

        $result = [
            'filepath' => $filepath,
            'headers' => [],
            'fldpos_map' => [],
            'merges' => []
        ];

        try {

            if (!file_exists($filepath)) {
                throw new imisusermerge_exception("missing_file", $filepath);
            }

            // Load entire file into array so we ensure we process each line
            $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                throw new imisusermerge_exception("open_failed", $filepath);
            }

            if (!empty($lines)) {

                // Extract the header
                $r = $this->parse_header($lines[0]);
                $headers = $r['headers'];
                $fldpos_map = $r['fldpos_map'];

                // Extract the rows
                $rows = [];
                $from = [];
                $dups = [];

                for ($linenum = 1; $linenum < count($linenum); $linenum++) {
                    $pos = $linenum - 1;
                    $str = trim($lines[$linenum]);
                    if (!empty($str)) {
                        $merge = new merge($linenum, $lines[$str], $fldpos_map);
                        $rows[] = $merge;

                        $from_imisid = $merge->getFromImisid();

                        // See if this user is being merged more than once
                        if (array_key_exists($from_imisid, $from)) {
                            if (!array_key_exists($dups[$from_imisid])) {
                                $dups[$from_imisid] = true;
                            }
                        }
                    }
                }

                // Check for ambiguous merges
                if (!empty($dups)) {
                    throw new imisusermerge_exception(
                        'ambiguous_merges',
                        join(',', array_keys($from)));
                }


                // Sert by mergetime to ensure we process in the same order as originally merged
                usort($rows, function (merge $row1, merge $row2) {
                    $t1 = $row1->getMergeTime();
                    $t2 = $row2->getMergeTime();
                    if ($t1 < $t2) {
                        return -1;
                    } else if ($t1 > $t2) {
                        return 1;
                    } else {
                        return 0;
                    }
                });

                $result['headers'] = $headers;
                $result['fldpos_map'] = $fldpos_map;
                $result['merges'] = $rows;
            }

            return $result;

        } catch (imisusermerge_exception $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     *
     */
    public function process($filepath) {
        $merges = null;
        $failed = 0;
        $skipped = 0;
        $completed = 0;

        try {
            $file = $this->load_file($filepath); // ASSUME file is not too large
            foreach ($file['merges'] as $index => $merge) {
                $file['merges'][$index] = $merge_result = $this->merge($merge);

                if ($merge_result['status'] == self::STATUS_MERGE_FAILED) {
                    $failed++;
                    break;
                } else if ($merge_result['status'] == self::STATUS_MERGE_SKIPPED) {
                    $skipped++;
                    continue;
                } else {
                    $completed++;
                    continue;
                }
            }

            $summary = [
                'requested' => count($merges),
                'completed' => $completed,
                'failed' => $failed,
                'skipped' => $skipped,
                'not_processed' => count($merges) - $failed - $completed - $skipped
            ];

//            $this->log($file, $summary);
//            $this->notify($file, $summary);

        } catch (imisusermerge_exception $ex) {
            // log error
            //
        } catch (\Exception $e) {

        }
    }
}
