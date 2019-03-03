<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 3/3/2019
 * Time: 9:21 AM
 */

namespace local_imisusermerge;


class merge {

    private $from_imisid;
    private $to_imisid;
    private $merge_time;
    private $fullname;
    private $email;
    private $status;
    private $message;
    private $linenum;
    private $line;
    private $from_user;
    private $to_user;

    const STATUS_TODO = 0;
    const STATUS_MERGED = 1;
    const STATUS_SKIPPED = 2;
    const STATUS_FAILED = 3;
    const STATUS_INVALID = 4;

    function __construct($linenum, $line, $map) {

        $this->linenum = $linenum;
        $this->line = $line;

        try {
            // Parse the line as csv
            $vals = str_getcsv($line);

            if (empty($vals)) {
                throw new imisusermerge_exception('record_has_no_values', $line);
            }

            if (count($vals) != count($map)) {
                throw new imisusermerge_exception('invalid_record', $line);
            }

            $errors = [];
            $merge = [];

            $this->from_imisid = trim($vals[$map['from_imisid']['pos']]);
            $this->to_imisid = trim($vals[$map['to_imisid']['pos']]);
            $this->merge_time = strtotime($vals[$map['merge_time']['pos']]);
            $this->full_name = trim($vals[$map['full_name']['pos']]);
            $this->email = trim($vals[$map['email']['pos']]);

            if (
                empty($this->getFromImisid())
                || empty($this->getToImisid())
                || $this->getMergeTime() === false
            ) {
                throw new imisusermerge_exception('invalid_record', $line);
            }

            $merge['status'] = self::STATUS_MERGE_TODO;
            $merge['message'] = '';

        } catch (imisusermerge_exception $ex) {
            $this->status = self::STATUS_INVALID;
            $this->message = $ex->getMessage();
        }

    }

    public function merge() {

        $from_imisid = $this->getFromImisid();
        $to_imisid = $this->getToImisid();

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

    protected function get_merge_tool() {
        require_once("../../../admin/tool/mergeusers/lib/mergeusertool.php");
        if (!$this->merge_tool) {
            $this->merge_tool = new MergeUserTool();
        }

        return $this->merge_tool;
    }


    public function call_admin_merge($to_userid, $from_userid) {
//        return $this->get_merge_tool()->merge($to_userid, $from_userid);
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
     * @return mixed
     */
    public function getFromImisid() {
        return $this->from_imisid;
    }

    /**
     * @return mixed
     */
    public function getToImisid() {
        return $this->to_imisid;
    }

    /**
     * @return mixed
     */
    public function getMergeTime() {
        return $this->merge_time;
    }

    /**
     * @return mixed
     */
    public function getFullname() {
        return $this->fullname;
    }

    /**
     * @return mixed
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @return int
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getLinenum() {
        return $this->linenum;
    }

    /**
     * @return mixed
     */
    public function getLine() {
        return $this->line;
    }

}