<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 3/3/2019
 * Time: 9:21 AM
 */

namespace local_imisusermerge;

require_once(__DIR__ . "/../../../admin/tool/mergeusers/lib/mergeusertool.php");
require_once(__DIR__ . "/../../../user/lib.php");

/**
 * Class merge_action
 * @package local_imisusermerge
 *  * @property-read string $file_base
 * @property-read string $from_imisid The imisid of the user to be merged
 * @property-read string $to_imisid
 * @property-read string $merge_time
 * @property-read string $status
 * @property-read string $message
 * @property-read string $line
 */
class merge_action implements \JsonSerializable {

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
    /**
     * @var
     */
    private $merge_tool_message;

    /**
     *
     */
    const STATUS_TODO = 'STATUS_TODO';
    /**
     *
     */
    const STATUS_MERGED = 'STATUS_MERGED';
    const STATUS_UPDATED = 'STATUS_UPDATED';
    /**
     *
     */
    const STATUS_SKIPPED = 'STATUS_SKIPPED';
    /**
     *
     */
    const STATUS_FAILED = 'STATUS_FAILED';
    /**
     *
     */
    const STATUS_ERROR = 'STATUS_ERROR';
    /**
     *
     */
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
                throw new merge_exception('record_has_no_values', $this->as_string_params());
            }

            if (count($vals) != count($map)) {
                $this->status = self::STATUS_INVALID;
                throw new merge_exception('invalid_record', $this->as_string_params());
            }

            $this->from_imisid = trim($vals[$map['from_imisid']]);
            $this->to_imisid = trim($vals[$map['to_imisid']]);
            $this->merge_time = strtotime(substr($vals[$map['merge_time']], 0, 23));

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
     * @throws merge_exception
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function merge() {

        try {
            $from_imisid = $this->getFromImisid();
            $to_imisid = $this->getToImisid();

            if ($from_imisid == $to_imisid) {
                $this->status = self::STATUS_SKIPPED;
                $this->message = get_string('same_user', imisusermerge::COMPONENT_NAME,  $this->as_string_params());
                return;
//                throw new merge_exception('same_user', $this->as_string_params());
            }

            $this->from_user = $this->get_user_by_imisid($from_imisid);
            $this->to_user = $this->get_user_by_imisid($to_imisid);

            if (!$this->from_user) {
                $this->status = self::STATUS_SKIPPED;
                $this->message = get_string('missing_from_user', imisusermerge::COMPONENT_NAME,  $from_imisid);
                return;
//                throw new merge_exception('missing_from_user', $from_imisid);
            }

            /** @noinspection PhpUndefinedFieldInspection */
            if ($this->user_has_been_merged($this->from_user->id)) {
                $this->status = self::STATUS_SKIPPED;
                $this->message = get_string('already_merged', imisusermerge::COMPONENT_NAME,  $from_imisid);
                return;
//                throw new merge_exception('already_merged', $this->as_string_params());
            }

            if ($this->to_user) {
                // Do the merge
                $result = imisusermerge::get_merge_tool()->merge((int)$this->to_user->id, (int)$this->from_user->id);
                list($success, $log) = $result;
                if ($success) {
                    $this->status = self::STATUS_MERGED;
                } else {
                    $this->status = self::STATUS_FAILED;
                    $this->merge_tool_message = join("\n", (array)$log);
                    throw new merge_exception('merge_tool_failed', $this->as_string_params());
                }
            } else {
                $mods = (object)[
                    "id" => $this->from_user->id,
                    "username" => $to_imisid,
                    "idnumber" => $to_imisid
                ];
                try {
                    user_update_user($mods, false, true);
                    $this->status = self::STATUS_UPDATED;
                } catch (\Exception $ex) {
                    $this->status = self::STATUS_FAILED;
                    $args = [
                        'from_imisid' => $this->from_imisid,
                        'to_imisid' => $this->to_imisid,
                        'message' => $ex->getMessage()
                    ];
                    throw new merge_exception('user_update_failed', $args);
                }
            }


        } catch (merge_exception $ex) {
            $this->message = $ex->getMessage();
            throw $ex;
        }
    }

    /**
     * @param $userid
     * @return bool
     * @throws merge_exception
     */
    protected function user_has_been_merged($userid) {
        global $DB;

        try {
            return $DB->record_exists('tool_mergeusers', [
                'fromuserid' => $userid,
                'success' => 1
            ]);
        } catch (\dml_exception $ex) {
            throw new merge_exception('error', $ex->getMessage());
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
     * @return string
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
     * @return Object
     */
    public function as_string_params() {
        $vars = get_object_vars($this);
        return (object)(array_filter($vars, function ($val) {
            return !is_array($val) && !is_object($val);
        }));
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize() {
        $vars = get_object_vars($this);
        return $vars;
    }


}