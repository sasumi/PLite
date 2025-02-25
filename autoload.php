<?php
namespace LFPhp\PLite;

const PLITE_CORE_ROOT = __DIR__."/src";

include_once __DIR__.'/vendor/autoload.php';

include_once PLITE_CORE_ROOT."/app.php";
include_once PLITE_CORE_ROOT."/config.php";
include_once PLITE_CORE_ROOT."/defines.php";
include_once PLITE_CORE_ROOT."/page.php";
include_once PLITE_CORE_ROOT."/rewrite.php";
include_once PLITE_CORE_ROOT."/router.php";

spl_autoload_register(function($class){
	if(strpos($class, __NAMESPACE__) === 0){
		$file = PLITE_CORE_ROOT.str_replace('\\', DIRECTORY_SEPARATOR, str_replace(__NAMESPACE__, '', $class)).'.php';
		include_once $file;
	}
});

