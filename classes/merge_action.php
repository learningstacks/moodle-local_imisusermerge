<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 3/3/2019
 * Time: 9:21 AM
 */

namespace local_imisusermerge;

require_once(__DIR__ . "/../../../admin/tool/mergeusers/lib/mergeusertool.php");

use MergeUserTool;

/**
 * Class merge_action
 * @package local_imisusermerge
 */
class merge_action {

    /**
     * @var string
     */
    private $from_imisid;
    /**
     * @var string
     */
    private $to_imisid;
    /**
     * @var false|int
     */
    private $merge_time;
    /**
     * @var string
     */
    private $full_name;
    /**
     * @var string
     */
    private $email;
    /**
     * @var int
     */
    private $status;
    /**
     * @var string
     */
    private $message;
    /**
     * @var
     */
    private $linenum;
    /**
     * @var
     */
    private $line;
    /**
     * @var
     */
    private $from_user;
    /**
     * @var
     */
    private $to_user;
    private $merge_tool_message;

    const STATUS_TODO = 'STATUS_TODO';
    const STATUS_MERGED = 'STATUS_MERGED';
    const STATUS_SKIPPED = 'STATUS_SKIPPED';
    const STATUS_FAILED = 'STATUS_FAILED';
    const STATUS_ERROR = 'STATUS_ERROR';
    const STATUS_INVALID = 'STATUS_INVALID';

    /**
     * merge_action constructor.
     * @param $linenum
     * @param $line
     * @param $map
     * @throws merge_exception
     */
    function __construct($linenum, $line, $map) {

        $this->linenum = $linenum;
        $this->line = $line;
        $this->message = '';

        try {
            // Parse the line as csv
            $vals = str_getcsv($line);

            if (empty($vals)) {
                $this->status = self::STATUS_INVALID;
                throw new merge_exception('record_has_no_values', $this->asArray());
            }

            if (count($vals) != count($map)) {
                $this->status = self::STATUS_INVALID;
                throw new merge_exception('invalid_record', $this->asArray());
            }

            $merge = [];

            $this->from_imisid = trim($vals[$map['from_imisid']]);
            $this->to_imisid = trim($vals[$map['to_imisid']]);
            $this->merge_time = strtotime($vals[$map['merge_time']]);
            $this->full_name = trim($vals[$map['full_name']]);
            $this->email = trim($vals[$map['email']]);

            if (
                empty($this->from_imisid) || empty($this->to_imisid) || $this->merge_time === false
            ) {
                throw new merge_exception('invalid_record', $line);
            }

            $this->status = self::STATUS_TODO;

        } catch (merge_exception $ex) {
            $this->status = self::STATUS_INVALID;
            $this->message = $ex->getMessage();
            throw $ex;
        }

    }

    /**
     * @param $merge_tool
     * @return int
     * @throws \dml_exception
     * @throws merge_exception
     */
    public function merge(MergeUserTool $merge_tool) {

        global $DB;

        try {
            $from_imisid = $this->getFromImisid();
            $to_imisid = $this->getToImisid();

            if ($from_imisid == $to_imisid) {
                $this->status = self::STATUS_SKIPPED;
                throw new merge_exception('same_user', $this->asArray());
            }

            $missing = [];
            if (($this->from_user = $this->get_user_by_imisid($from_imisid)) === false) {
                $missing[] = $from_imisid;
            }
            if (($this->to_user = $this->get_user_by_imisid($to_imisid)) === false) {
                $missing[] = $to_imisid;
            }

            if (!empty($missing)) {
                $this->status = self::STATUS_ERROR;
                throw new merge_exception('missing_user', $this->asArray());
            }

            // See if the from user has previously been merged
            $merged = $DB->record_exists('tool_mergeusers', [
                'fromuserid' => $this->from_user->id,
                'success' => 1
            ]);
            if ($merged) {
                $this->status = self::STATUS_SKIPPED;
                throw new merge_exception('already_merged', $this->asArray());
            }

            // Do the merge
            list($success, $log) = $merge_tool->merge((int)$this->to_user->id, (int)$this->from_user->id);
            if ($success) {
                $this->status = self::STATUS_MERGED;
            } else {
                $this->status = self::STATUS_FAILED;
                $this->merge_tool_message = join("\n", (array)$log);
                throw new merge_exception('merge_tool_failed', $this->asArray());
            }

        } catch (merge_exception $ex) {
            $this->message = $ex->getMessage();
            throw $ex;
        }

    }

    /**
     * Lookup user by imisid
     *
     * @param string $imisid The IMISID of a user to lookup
     * @return int | null The Moodle userid or null if not found
     * @throws \dml_exception
     */
    protected function get_user_by_imisid($imisid) {
        global $DB;

        return $DB->get_record("user", ['username' => $imisid, 'mnethostid' => 1], "id");
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
        return $this->full_name;
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

    /**
     * @return mixed
     */
    public function getFromUser() {
        return $this->from_user;
    }

    /**
     * @return mixed
     */
    public function getToUser() {
        return $this->to_user;
    }

    /**
     * @return mixed
     */
    public function getMergeToolMessage() {
        return $this->merge_tool_message;
    }

    /**
     * @return array
     */
    public function asArray() {
        $vars = get_object_vars($this);
        return array_filter($vars, function ($val) {
            return !is_array($val) && !is_object($val);
        });
    }

}