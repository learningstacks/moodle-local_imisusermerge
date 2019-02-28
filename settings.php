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


// Ensure the configurations for this site are set
if ($hassiteconfig) {

    // Create the new settings page
    // - in a local plugin this is not defined as standard, so normal $settings->methods will throw an error as
    // $settings will be NULL
//    $settings = new admin_settingpage('local_imisusermerge', 'IMIS User Merge Settings');
//
//    // Create
//    $ADMIN->add('localplugins', $settings);

    // Add a setting field to the settings for this page
//    $settings->add(new admin_setting_configtext(
//        'local_imisbridge/base_api_url',
//        'Base API Url',
//        'The base URL where the bridge web services are located',
//        '',
//        PARAM_URL));
//    $settings->add(new admin_setting_configcheckbox(
//        'local_imisbridge/enable_debug_trace',
//        'Enable debug tracing',
//        'If checked, trace messages will be displayed on screen',
//        '0',
//        PARAM_BOOL));
}