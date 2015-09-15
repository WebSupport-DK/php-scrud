<?php

// load files
require_once '../vendor/autoload.php';

use thom855j\PHPScrud\DB;

// instantiate object via singleton
DB::load('mysql', '127.0.0.1', 'php-scrud', 'root', '');

$data = DB::load()->select(
        // select these attributes
        array('ID,User, Password'),
        // from table
        'test',
        array(
    'start' => 1
));

var_dump(DB::load());