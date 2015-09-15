<?php

// load files
require_once '../vendor/autoload.php';

use thom855j\PHPScrud\DB;

// instantiate object via singleton
DB::load('mysql', '127.0.0.1', 'php-scrud', 'root', '');

// searh in table
DB::load()->search('test',
        // in these attributes
                   array('ID', 'User', 'Email'),
        // for this (you define the joker)
                   array('%test%', '%3%')
);

var_dump(DB::load());
