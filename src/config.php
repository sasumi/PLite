<?php

use Lfphp\Plite\Exception\PLiteException as Exception;
use function LFPhp\Func\array_get;

/**
 * @throws \Lfphp\Plite\Exception\PLiteException
 */
function get_config($config_uri){
	static $cache = [];

	if(isset($cache[$config_uri])){
		return $cache[$config_uri];
	}

	list($file, $path) = explode('/', $config_uri);
	$config_file = CONFIG_ROOT."/$file.inc.php";
	if(!is_file($config_file)){
		throw new Exception('Config file no found:'.$config_file);
	}
	$config = include $config_file;
	if(!isset($config_file)){
		throw new Exception("Config content empty in file:".$config_file);
	}
	$cache[$config_uri] = array_get($config, $path, null, '/');
	return $cache[$config_uri];
}