<?php

// load files
require_once '../vendor/autoload.php';

use thom855j\PHPScrud\DB;

// instantiate object via singleton
DB::load('mysql', '127.0.0.1', 'php-scrud', 'root', '');

// select with where statements
DB::load()->select(array('ID,User, Password'), 'test', null, null, array('ORDER BY' => 'ID DESC'));

var_dump(DB::load()->results());
