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

defined('MOODLE_INTERNAL') || die;

$plugin->component = 'local_imisusermerge'; // Full name of the plugin (used for diagnostics)
$plugin->version   = 2019031001;       // The current plugin version (Date: YYYYMMDDXX)
$plugin->release = 'v1.0.0';
$plugin->requires  = 2016120502;       // Requires this Moodle version
$plugin->dependencies = array(
    'tool_mergeusers' => 2018030900
);