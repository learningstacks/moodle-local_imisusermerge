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
use core\task\manager as task_manager;

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
     * Run cron.
     */
    public function execute() {
        $current_task = $this->get_current_task();
        $fail_delay_threshold = 100;

        // If no task: create one
        if (!$current_task) {
            $next_task = new merge_task();
            task_manager::queue_adhoc_task($next_task);
        }

    }

    /**
     * If a merge_task currently exists, fetch it
     * @return merge_task|null
     * @throws \dml_exception
     */
    protected function get_current_task() {
        global $DB;

        $current_task = null;

        $record = $DB->get_record('task_adhoc', [
            'component' => imisusermerge::COMPONENT_NAME,
            'classname' => merge_task::class
        ]);

        if ($record) {
            $current_task = self::adhoc_task_from_record($record);
        }

        return $current_task;
    }

}
