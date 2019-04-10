<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A scheduled task for creating and delivering certificates.
 *
 *
 * @package    mod_forum
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_imisusermerge\task;

use local_imisusermerge\imisusermerge;
use local_imisusermerge\merge_exception;
use core\task\manager as task_manager;

/**
 * Class cron_task
 * @package local_imisusermerge\task
 */
class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('crontask', 'local_imisusermerge');
    }

    /**
     * @return array
     * @throws \dml_exception
     */
    public function get_merge_tasks() {
        global $DB;
        $comp = imisusermerge::COMPONENT_NAME;
        $class = '\\' . merge_task::class;
        return $DB->get_records('task_adhoc', [
            'component' => $comp,
            'classname' => $class
        ]);
    }

    /**
     * @throws merge_exception
     * @throws \coding_exception
     * @throws merge_exception
     */
    public function execute() {

        try {
            mtrace("Ensure merge ad-hoc tasks exists");
            $tasks = $this->get_merge_tasks();

            if (count($tasks) > 1) {
                throw new \coding_exception("More than one merge_tasks exist");

            } else if (empty($tasks)) {
                mtrace("Creating merge ad-hoc task");
                task_manager::queue_adhoc_task(new merge_task());
            } else {
                mtrace("Merge ad-hoc tasks already exists");
            }

        } catch (\Exception $ex) {
            mtrace($ex->getMessage());
            imisusermerge::send_notification(
                get_string('failed_to_create_merge_task_email_subject', imisusermerge::COMPONENT_NAME),
                get_string('failed_to_create_merge_task_email_body', imisusermerge::COMPONENT_NAME),
                null,
                $ex
            );
        }

    }
}
