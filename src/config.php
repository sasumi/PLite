<?php
namespace LFPhp\PLite;

use LFPhp\PLite\Exception\PLiteException as Exception;
use function LFPhp\Func\array_get;
use function LFPhp\Func\array_push_by_path;

// Shared cache for config values
$GLOBALS['_PLITE_CONFIG_CACHE'] = [];

/**
 * Get configuration value of configuration file
 * The configuration file path is specified by PLITE_CONFIG_PATH, the configuration file format is file.inc.php, and the file returns an associative array
 * @param string $config_key_uri configuration name/path
 * @param bool $ignore_on_file_no_exists whether to ignore the situation where the file does not exist (the default is to force the file to exist)
 * @param bool $nocache
 * @return array|mixed
 * @throws \Exception
 */
function get_config($config_key_uri, $ignore_on_file_no_exists = false, $nocache = false){
	if(!$nocache && isset($GLOBALS['_PLITE_CONFIG_CACHE'][$config_key_uri])){
		return $GLOBALS['_PLITE_CONFIG_CACHE'][$config_key_uri];
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
	$GLOBALS['_PLITE_CONFIG_CACHE'][$config_key_uri] = array_get($config, join('/', $path), null, '/');
	return $GLOBALS['_PLITE_CONFIG_CACHE'][$config_key_uri];
}

/**
 * Set configuration value to configuration file
 * @param string $config_key_uri configuration name/path (e.g., "hello/world/yes")
 * @param mixed $data value to set
 * @return bool true on success
 * @throws \Exception
 */
function set_config($config_key_uri, $data){
	$path = explode('/', $config_key_uri);
	$file = array_shift($path);

	$config_file = PLITE_CONFIG_PATH."/$file.inc.php";
	
	// Load existing config or create empty array
	if(is_file($config_file)){
		$config = include $config_file;
		if(!is_array($config)){
			$config = [];
		}
	}else{
		// Create config directory if not exists
		$config_dir = dirname($config_file);
		if(!is_dir($config_dir)){
			if(!mkdir($config_dir, 0755, true)){
				throw new Exception("Failed to create config directory: {$config_dir}");
			}
		}
		$config = [];
	}
	
	// Set value using path
	if(empty($path)){
		// If no path, replace entire config
		$config = $data;
	}else{
		// Set nested value using array_push_by_path
		array_push_by_path($config, join('/', $path), $data, '/');
	}
	
	// Write config back to file
	$export = var_export($config, true);
	$content = "<?php\nreturn {$export};\n";
	
	if(file_put_contents($config_file, $content) === false){
		throw new Exception("Failed to write config file: {$config_file}");
	}
	
	// Clear all cache entries for this config file
	foreach(array_keys($GLOBALS['_PLITE_CONFIG_CACHE']) as $cache_key){
		if(strpos($cache_key, $file.'/') === 0 || $cache_key === $file){
			unset($GLOBALS['_PLITE_CONFIG_CACHE'][$cache_key]);
		}
	}
	
	return true;
}
