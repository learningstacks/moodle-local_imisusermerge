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

// email strings
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

