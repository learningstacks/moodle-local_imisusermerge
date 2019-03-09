<?php

namespace local_imisusermerge\task;

use local_imisusermerge\merge_file;
use local_imisusermerge\merge_exception;
use core\task\manager as task_manager;

/**
 * Class merge_task
 * @package local_imisusermerge\task
 */
class merge_task extends \core\task\adhoc_task {

    private $path = null;

    /**
     * @var merge_file|null
     */
    private $merge_file = null;

    /**
     * @return bool
     * @throws \dml_exception
     * @throws merge_exception
     * @throws merge_exception
     */
    public function execute() {
        try {
            if ($this->path = merge_file::get_next_file()) {
                $this->merge_file = new merge_file($this->path);
                $this->merge_file->process();
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
                        throw $ex; // Task will be retried
                        break;
                    case merge_file::STATUS_EMPTY_FILE:
                        return true; // Complete, nothing to do
                }
            }
        }

    }

    /**
     *
     */
    protected function notify() {

    }

    protected function get_merge_tool() {

    }
}