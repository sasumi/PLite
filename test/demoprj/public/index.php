<?php

use function Lfphp\Plite\start_web;

define('APP_ROOT', basename(__DIR__));
define('SITE_ROOT', 'http://localhost/PLite/test/demoprj/public/index.php');
include "../../../vendor/autoload.php";

start_web();