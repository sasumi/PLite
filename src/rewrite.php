<?php
namespace LFPhp\PLite;

use Exception;
use function LFPhp\Func\event_register;

const PATTERN_HOLDER = 'PATTERN_HOLDER';

/**
 * Replace $1, $2 in str with the corresponding index data in the matches array
 * @param string $str
 * @param string[] $ms
 * @return string
 */
function __reg_var_replace($str, $ms = []){
	foreach($ms as $idx => $v){
		$str = str_replace("\${$idx}", $v, $str);
	}
	return $str;
}

/**
 * Generate replacement array from rule template $1/$2 combined with given URI: ctrl/act: ['$1'=>'ctrl', '$2'=> 'act']
 * Rule template can be in the format of $1/$2, ctrl/$2, path/$1/act, etc. If the format does not match, an empty array is returned
 * @param string $uri_pattern
 * @param string $uri
 * @param array $replacement Mapping array used to replace placeholders
 * @return bool whether it matches
 */
function __rewrite_match_uri($uri_pattern, $uri, &$replacement = []){
	if(strcasecmp($uri_pattern, $uri) === 0){
		return true;
	}
	$reg_pat = preg_replace('/(\$\d+)/', PATTERN_HOLDER, $uri_pattern);
	$reg_pat = str_replace(PATTERN_HOLDER, '(.+)', preg_quote($reg_pat));
	if(preg_match("#$reg_pat#", $uri, $uri_segments)){
		$uri_segments = array_slice($uri_segments, 1);
		preg_replace_callback('/(\$\d+)/', function($ms) use (&$uri_segments, &$replacement){
			$replacement[$ms[1]] = array_shift($uri_segments);
		}, $uri_pattern);
		return true;
	}
	return false;
}

/**
 * Bind url() function to generate final URL based on given rewrite mapping rules
 * @param array $mapping For mapping rules, please refer to [/REWRITE.md](REWRITE.md)
 * @return void
 */
function rewrite_bind_url($mapping){
	event_register(EVENT_ROUTER_URL, function(&$url, $uri, $param) use ($mapping){
		foreach($mapping as $url_pattern => [$uri_pattern, $param_pattern]){
			//The format is:
			// '$1' => 'value1'
			// '$2' => 'value2'
			// Can be obtained from the _uri rule or the $_param rule
			// Combine the given URI from the rule $1/$2: ctrl/act to generate a replacement array: ['$1'=>'ctrl', '$2'=> 'act']
			$replacement = [];
			$match_param_keys = []; //The matched param key and the remaining unreplaced variables are added to the end to form the query string.
			if(__rewrite_match_uri($uri_pattern, $uri, $replacement)){
				foreach($param_pattern ?: [] as $k => $holder){
					if(!isset($param[$k])){
						//The parameters are not satisfied, match the next rule
						continue 2;
					}
					$replacement[$holder] = $param[$k];
					$match_param_keys[] = $k;
				}

				//Convert the expression into placeholder mode,
				//For example: {W}/{W}.html
				$idx = 0;
				$url = preg_replace_callback('/{\w+}/', function() use (&$idx, $replacement){
					$replace_key = '$'.(++$idx);
					return isset($replacement[$replace_key]) ? urlencode($replacement[$replace_key]) : '';
				}, $url_pattern);

				//The remaining parameters are added to the query string
				$ext_param = $param;
				unset($ext_param[PLITE_ROUTER_KEY]);
				foreach($match_param_keys as $k){
					unset($ext_param[$k]);
				}
				if($ext_param){
					$url .= (strpos($url, '?') !== false ? '&' : '?').http_build_query($ext_param);
				}
				//Clean up the unused {}
				if(preg_match('/{\w+}/', $url)){
					$url = preg_replace('/{\w+}/', '', $url);
				}
				$url = PLITE_SITE_ROOT.$url;
				return true;
			}
		}
		//no match
		return false;
	});
}

/**
 * Process the current request path info
 * @param array $mapping For mapping rules, please refer to [/REWRITE.md](REWRITE.md)
 * @param string|null $path_info
 * @return bool Whether the rule is hit
 * @throws \Exception
 */
function rewrite_resolve_path($mapping, $path_info = null){
	//Parse and identify the currently accessed URL
	$path_info = $path_info === null ? $_SERVER['PATH_INFO'] : $path_info;
	$path_info = trim($path_info, '/');
	foreach($mapping as $url_pattern => [$uri_pattern, $param_pattern]){
		//hardcode URL
		if(!preg_match_all('/{(\w+)}/', $url_pattern, $all_matches)){
			//The current page address contains the rule address
			if(stripos($path_info, $url_pattern) === 0){
				$ps = array_merge($_GET, $param_pattern ?: []);
				set_router($uri_pattern, $ps);
				return true;
			}else{
				continue;
			}
		}

		//replace {w} {d} to regexp
		$idx = 0;
		$url_regexp = preg_replace_callback("/".PATTERN_HOLDER."/", function() use (&$idx, $all_matches){
			$flag = strtolower($all_matches[1][$idx++]);
			switch($flag){
				case 'w':
					return '(\w+)';
				case 'd':
					return '(\d+)';
				default:
					throw new Exception("Pattern flag no support: ".$flag);
			}
		}, preg_quote(preg_replace('/{\w+}/', PATTERN_HOLDER, $url_pattern)));

		//start compare
		$url_regexp = "#^$url_regexp#u";
		if(preg_match($url_regexp, $path_info, $ms)){
			$uri = __reg_var_replace($uri_pattern, $ms);
			$ps = $_GET;
			foreach($param_pattern ?: [] as $k => $v){
				$k = __reg_var_replace($k, $ms);
				$v = __reg_var_replace($v, $ms);
				$ps[$k] = urldecode($v);
			}
			set_router($uri, $ps);
			return true;
		}
	}
	return false;
}

/**
 * Set route mapping through pathinfo
 * @param array $mapping For mapping rules, please refer to [/REWRITE.md](REWRITE.md)
 * @param string|null $path_info pathinfo information, by default obtained from $_SERVER['PATH_INFO']
 * @return void
 * @throws \Exception
 */
function rewrite_setup($mapping, $path_info = null){
	//1. bind url()
	rewrite_bind_url($mapping);

	//2. process current request
	rewrite_resolve_path($mapping, $path_info);
}
