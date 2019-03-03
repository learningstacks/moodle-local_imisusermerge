<?php

//class foo {
//    private $dir;
//    private $fnregexp;
//
//    function __construct() {
//        // parent::__construct();
//        $this->dir = "c:/tmp";
//        $this->fnregexp = "/^user_merge_request_[0-9]{8}-[0-9]{6}\.csv$/i";
//    }
//
//    public function get_next_file() {
//        $firstfile = null;
//
//        foreach (scandir($this->dir) as $item) {
//            if (preg_match($this->fnregexp, $item)) {
//                if ($firstfile === null || strcmp(strtolower($item), strtolower($firstfile)) < 0) {
//                    $firstfile = $item;
//                }
//            }
//        }
//
//        return $firstfile;
//    }
//}
//
//$c = new foo();
//$f = $c->get_next_file();
//if ($f) {
//    echo $f;
//} else {
//    echo "Noting to process";
//}

$a = strtotime('1/1/2019 00:00:00');
echo $a;