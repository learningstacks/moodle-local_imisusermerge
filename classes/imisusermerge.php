<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 3/5/2019
 * Time: 10:36 AM
 */

namespace local_imisusermerge;
defined('MOODLE_INTERNAL') || die();

abstract class imisusermerge {
    const COMPONENT_NAME = "local_imisusermerge";

    /* @var config */
    private static $config;

    /**
     * @return config
     * @throws merge_exception
     */
    public static function get_config() {
        if (!static::$config) {
            static::$config = new config();
        }
        return static::$config;
    }

}