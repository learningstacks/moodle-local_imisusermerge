<?php

/**
 *
 *
 * @package   local_imisusermerge
 * @copyright 2019 International Association of Fire Fighters {@link https://client.prod.iaff.org//}
 * @license   All Rights Reserved
 */

defined('MOODLE_INTERNAL') || die;

$plugin->component = 'local_imisusermerge'; // Full name of the plugin (used for diagnostics)
$plugin->version   = 2019040903;       // The current plugin version (Date: YYYYMMDDXX)
$plugin->release = 'v1.0.0';
$plugin->requires  = 2016120502;       // Requires this Moodle version
$plugin->dependencies = array(
    'tool_mergeusers' => 2018030900
);