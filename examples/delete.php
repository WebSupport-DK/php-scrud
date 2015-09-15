<?php

// load files
require_once '../vendor/autoload.php';

use thom855j\PHPScrud\DB;

// instantiate object via singleton
DB::load('mysql', '127.0.0.1', 'php-scrud', 'root', '');

// select all records
DB::load()->delete('test', array(array(
    'ID', '=', 1
)));

var_dump(DB::load());
