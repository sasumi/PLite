<?php
namespace Lfphp\Plite;

/************************************
 * 程序运行基础配置
 ***********************************/

//应用根目录
use function LFPhp\Func\array_get;
use Lfphp\Plite\Exception\PLiteException as Exception;

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