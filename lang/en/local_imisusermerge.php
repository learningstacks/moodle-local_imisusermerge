<?php
// Copyright (C) 2017 Learning Stacks LLC https://learningstacks.com/
//
// This file is a part of the IMIS Integration Components developed by
// Learning Stacks LLC - https://learningstacks.com/
//
// This file cannot be copied or distributed without the express permission
// of Learning Stacks LLC.

/**
*
*
* @package   local_imisbridge
* @copyright 2017 onwards Learning Stacks LLC {@link https://learningstacks.com/}
* @license   All Rights Reserved
*/

$string['pluginname'] = 'IMIS User Merge';
$string['pluginname_desc'] = 'Merges user accounts based on merged user csv file';

/*
 * Settings
 */
$string['notification_emails_label'] = "Notification Emails";
$string['notification_emails_desc'] = "List of email addresses to which notification emails will be sent.
Enter one or more email addresses, separated by semi-colons";

/*
 * Email strings
 */
$string['user_merge_failed_email_subject'] = 'User merge failed';
$string['user_merge_failed_email_body'] = 'Failure occured while processing a merge request file:
File: {$a->filepath}
Error Message: {$a->message}

See the attached files for additional detail.
';

$string['user_merge_success_email_subject'] = 'Users merged';
$string['user_merge_success_email_body'] = 'Users have been merged
File: {$a->filepath}

See the attached files for additional detail.
';

$string['failed_to_create_merge_task_email_subject'] = 'Failed to create merge_users task';
$string['failed_to_create_merge_task_email_body'] = 'While running the cron, an attempt to create a merge_user task failed';

// Errors
$string['invalid_config'] = 'Invalid configuration settings: 
{$a}';
$string['invalid_merge_in_dir'] = 'Invalid merge_in_dir setting: ({$a})';
$string['invalid_merge_completed_dir'] = 'Invalid merge_completed_dir setting: ({$a})';
$string['invalid_merge_file_field_map'] = 'Invalid merge_ifile_field_map setting: ({$a})';
$string['invalid_merge_file_name_regex'] = 'Invalid merge_ifile_name_regex setting: ({$a})';
$string['invalid_notification_email_addresses'] = 'Invalid notification_email_addresses: ({$a})';
$string['email_send_failed'] = 'Failure occured attempting to send notification email: 
{$a}';
$string['missing_email_attachment'] = 'Failure occured attempting to send notification email: 
{$a}';
$string['file_missing_fields'] = 'File is missing required fields';
$string['empty_file'] = "File contains no data";