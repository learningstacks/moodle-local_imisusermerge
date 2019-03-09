<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 2/27/2019
 * Time: 9:04 PM
 */

namespace local_imisusermerge;


class merge_exception extends \moodle_exception{
    protected $merge_status;
    function __construct($errorcode=null, $a = '', $debuginfo = null) {
        parent::__construct($errorcode, imisusermerge::COMPONENT_NAME, '', $a, $debuginfo);
    }
}