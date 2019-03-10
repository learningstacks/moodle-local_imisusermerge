<?php

// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * Provides some custom settings for the certificate module
 *
 * @package    mod_certificate
 * @copyright  Michael Avelar <michaela@moodlerooms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $ADMIN;

/* @throws \dml_exception; */


if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_configtext(
            'local_imisusermerge/notification_emails',
            new lang_string('notification_emails_label', local_imisusermerge\imisusermerge::COMPONENT_NAME),
            new lang_string('notification_emails_desc', local_imisusermerge\imisusermerge::COMPONENT_NAME),
            '',
            PARAM_TEXT));
}
