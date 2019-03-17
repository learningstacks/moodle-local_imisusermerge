<?php

namespace local_imisusermerge\task;

use local_imisusermerge\imisusermerge;
use local_imisusermerge\merge_file;
use local_imisusermerge\merge_exception;
use core\task\manager as task_manager;

/**
 * Class merge_task
 * @package local_imisusermerge\task
 */
class merge_task extends \core\task\adhoc_task {

    /**
     * @var null
     */
    private $path = null;

    /**
     * @var merge_file|null
     */
    private $merge_file = null;

    /**
     * merge_task constructor.
     */
    function __construct() {
        $this->set_component(imisusermerge::COMPONENT_NAME);
    }

    /**
     * @return void
     * @throws merge_exception
     * @throws \coding_exception
     */
    public function execute() {
        $config = imisusermerge::get_config();

        try {
            if ($filename = merge_file::get_next_file()) {
                $this->path = "{$config->in_dir}/$filename";
                $this->merge_file = new merge_file($this->path);
                $this->merge_file->process();

                $params = $this->merge_file->as_string_params();
                $comp = imisusermerge::COMPONENT_NAME;
                imisusermerge::send_notification(
                    get_string('user_merge_success_email_subject', $comp, $params),
                    get_string('user_merge_success_email_body', $comp, $params),
                    [
                        $this->merge_file->get_completed_file_path(),
                        $this->merge_file->get_completed_log_path()
                    ],
                    null
                );

                $next_task = new merge_task();
                task_manager::queue_adhoc_task($next_task);
            }

        } catch (merge_exception $ex) {
            if (!$this->merge_file) {
                // We found a file, but failed to create merge_file
                // Throw an exception
                // This will fail the task and queue it for retry
                throw $ex;

            } else {
                // File eas created, now we check status
                switch ($this->merge_file->getStatus()) {
                    case merge_file::STATUS_FAILED:
                    case merge_file::STATUS_ERROR:
                    case merge_file::STATUS_INVALID_FILE:
                        $params = $this->merge_file->as_string_params();
                        $comp = imisusermerge::COMPONENT_NAME;
                        $subj = get_string('user_merge_failed_email_subject', $comp, $params);
                        $body = get_string('user_merge_failed_email_body', $comp, $params);
                        imisusermerge::send_notification($subj, $body, $this->merge_file->getFilepath(), $ex);
                        throw $ex; // Task will be retried
                        break;
                    case merge_file::STATUS_EMPTY_FILE:
                        break; // Complete, nothing to do
                    default:
                        throw new \coding_exception("Unrecognized status {$this->merge_file->getStatus()}");
                }
            }
        } catch (\Exception $ex) {
            throw new merge_exception('exception_occured', $ex->getMessage());
        }
    }

    /**
     * @return string|null
     */
    public function get_path() {
        return $this->path;
    }

}