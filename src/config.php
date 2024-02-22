<?php
namespace LFPhp\PLite;

use LFPhp\PLite\Exception\PLiteException as Exception;
use function LFPhp\Func\array_get;

/**
 * 获取配置文件配置值
 * 配置文件路径由 PLITE_CONFIG_PATH 指定，配置文件格式为 file.inc.php，文件返回关联数组
 * @param string $config_key_uri 配置名称/路径
 * @param bool $ignore_on_file_no_exists 是否忽略文件不存在情况（缺省为必须强制文件存在）
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
