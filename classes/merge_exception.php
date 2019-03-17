<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 2/27/2019
 * Time: 9:04 PM
 */

namespace local_imisusermerge;


/**
 * Class merge_exception
 * @package local_imisusermerge
 */
class merge_exception extends \moodle_exception{
    /**
     * @var
     */
    protected $merge_status;

    /**
     * merge_exception constructor.
     * @param null $errorcode
     * @param string $a
     * @param null $debuginfo
     */
    function __construct($errorcode=null, $a = '', $debuginfo = null) {
        parent::__construct($errorcode, imisusermerge::COMPONENT_NAME, '', $a, $debuginfo);
    }
}