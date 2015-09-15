<?php

// load files
require_once '../vendor/autoload.php';

use thom855j\PHPScrud\DB;

// instantiate object via singleton
DB::load('mysql', '127.0.0.1', 'php-scrud', 'root', '');

// normal query, where params is passed as array in order of "?" 
DB::load()->query(
        // query
        'SELECT * FROM test WHERE ID < ?', 
        // params in order
        array(3,'test'));

var_dump(DB::load());
