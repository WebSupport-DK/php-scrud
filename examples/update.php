<?php

// load files
require_once '../vendor/autoload.php';

use thom855j\PHPScrud\DB;

// instantiate object via singleton
DB::load('mysql', '127.0.0.1', 'php-scrud', 'root', '');

DB::load()->update('test', 'ID', 5, array(
    'User'     => 'Hansi',
    'Password' => '2434LD'
));

var_dump(DB::load());
