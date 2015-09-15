<?php

// load files
require_once '../vendor/autoload.php';

use thom855j\PHPScrud\DB;

// instantiate object via singleton
DB::load('mysql', '127.0.0.1', 'php-scrud', 'root', '');

DB::load()->insert('test', array(
    'User'     => 'Hans',
    'Password' => '2342%lkjkls',
    'Email'    => 'test@mail.com'
));

var_dump(DB::load());
