<?php

namespace local_imisusermerge\task;
use local_imisusermerge\imisusermerge;

use Exception;
use core_text;

class process_merge_file extends \core\task\adhoc_task
{

    public function execute()
    {
        global $CFG, $PAGE, $SITE, $DB;

        $data = $this->get_custom_data();
        $mu = new imisusermerge();
        $file = $mu->get_next_file();
        if (!$file) {
           return true; // Success, task will be deleted
        }

        $result = $mu->process_file($file);


    }


}