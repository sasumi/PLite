<?php
namespace LFPhp\PLite;
$source_dir = __DIR__."/src";
include_once $source_dir."/app.php";
include_once $source_dir."/config.php";
include_once $source_dir."/defines.php";
include_once $source_dir."/page.php";
include_once $source_dir."/rewrite.php";
include_once $source_dir."/router.php";

spl_autoload_register(function($class) use ($source_dir){
	if(strpos($class, __NAMESPACE__) === 0){
		$file = $source_dir.str_replace('\\', DIRECTORY_SEPARATOR, str_replace(__NAMESPACE__, '', $class)).'.php';
		include_once $file;
	}
});

