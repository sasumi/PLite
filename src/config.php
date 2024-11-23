<?php
namespace LFPhp\PLite;

use LFPhp\PLite\Exception\PLiteException as Exception;
use function LFPhp\Func\array_get;
/**
 * Get configuration value of configuration file
 * The configuration file path is specified by PLITE_CONFIG_PATH, the configuration file format is file.inc.php, and the file returns an associative array
 * @param string $config_key_uri configuration name/path
 * @param bool $ignore_on_file_no_exists whether to ignore the situation where the file does not exist (the default is to force the file to exist)
 * @return array|mixed
 * @throws \Exception
 */
function get_config($config_key_uri, $ignore_on_file_no_exists = false){
	static $cache = [];
	if(isset($cache[$config_key_uri])){
		return $cache[$config_key_uri];
	}
	$path = explode('/', $config_key_uri);
	$file = array_shift($path);

	$config_file = PLITE_CONFIG_PATH."/$file.inc.php";
	if(!is_file($config_file)){
		if(!$ignore_on_file_no_exists){
			throw new Exception('Config file no found:'.$config_file);
		}else{
			return null;
		}
	}
	$config = include $config_file;
	if(!isset($config_file)){
		throw new Exception("Config content empty in file:".$config_file);
	}
	$cache[$config_key_uri] = array_get($config, join('/', $path), null, '/');
	return $cache[$config_key_uri];
}
