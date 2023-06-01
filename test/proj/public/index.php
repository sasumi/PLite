<?php

use function LFPhp\Func\dump;
use function LFPhp\PLite\register_event;
use function LFPhp\PLite\start_web;
use const LFPhp\PLite\EVENT_BEFORE_INCLUDE_PAGE;
use const LFPhp\PLite\EVENT_ROUTER_URL;

define("PLITE_APP_ROOT", dirname(__DIR__));
include "../../../vendor/autoload.php";

register_event(EVENT_ROUTER_URL, function(&$url, &$uri, &$params){
	echo 'change link ';
	$url .= "#hello";
});

register_event(EVENT_BEFORE_INCLUDE_PAGE, function(&$tpl, $param){
	echo "change page to hello";
	$tpl = 'hello.php';
	dump($tpl, $param);
});

start_web();
