<?php
/**
 * Created by PhpStorm.
 * User: terry
 * Date: 2/27/2019
 * Time: 9:25 PM
 */

namespace local_imisusermerge;


class csvfile {

    private $filepath = '';
    private $handle = null;

    function __construct($filepath, $hdrs) {
        $this->filepath = $filepath;
        if (!$this->handle = fopen($filepath, "w")) {
            throw new log_exception($filepath);
        }
        $this->write($hdrs);
    }

    /**
     *
     */
    public function write(Array $data) {
        fputcsv($this->handle, join(",", $data));
    }

}