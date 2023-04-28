<?php

use function LFPhp\Func\array_get;

function get_namespace($class){
	$last_slash = strrpos($class, '\\');
	if($last_slash>=0){
		return substr($class, 0, $last_slash);
	}
	return $class;
}

function get_class_without_namespace($class){
	$last_slash = strrpos($class, '\\');
	if($last_slash>=0){
		return substr($class, $last_slash+1);
	}
	return $class;
}

/**
 * 获取配置值
 * @param string $config_key_uri 配置名称/路径
 * @return array|mixed
 * @throws \Exception
 */
function get_config($config_key_uri){
	static $cache = [];
	if(isset($cache[$config_key_uri])){
		return $cache[$config_key_uri];
	}
	list($file, $path) = explode('/', $config_key_uri);
	$config_file = CONFIG_PATH."/$file.php";
	if(!is_file($config_file)){
		throw new Exception('Config file no found:'.$config_file);
	}
	$config = include $config_file;
	if(!isset($config_file)){
		throw new Exception("Config content empty in file:".$config_file);
	}
	$cache[$config_key_uri] = array_get($config, $path, null, '/');
	return $cache[$config_key_uri];
}