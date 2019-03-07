<?php

namespace local_imisusermerge\task;

use local_imisusermerge\merge_file;
use local_imisusermerge\merge_exception;

/**
 * Class merge_task
 * @package local_imisusermerge\task
 */
class merge_task extends \core\task\adhoc_task {


    /**
     * @var merge_file|null
     */
    private $file = null;

    /**
     * @return bool
     * @throws \dml_exception
     * @throws merge_exception
     * @throws merge_exception
     */
    public function execute() {
        try {
            if ($path = merge_file::get_next_file()) {
                $this->file = new merge_file($path);
                $this->file->process();
            }
            return true; // Task completed

        } catch (merge_exception $ex) {
            if (!$this->file) {
                // We found a file, but failed to create merge_file
                // Throw an exception
                // This will fail the task and queue it for retry
                throw $ex;

            } else {
                // File eas created, now we check status
                switch ($this->file->getStatus()) {
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

        return true; // Causes task to be completed and removed
    }

    /**
     *
     */
    protected function notify() {

    }
}