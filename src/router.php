<?php
namespace LFPhp\PLite;

use LFPhp\PLite\Exception\RouterException;
use ReflectionClass;
use function LFPhp\Func\array_clean_null;
use function LFPhp\Func\event_fire;
use function LFPhp\Func\html_tag_hidden;
use function LFPhp\Func\http_build_query_recursive;
use function LFPhp\Func\http_redirect;
use function LFPhp\Func\is_url;

/**
 * URL routing function
 * @param string $uri URI string, usually in ctrl/act format. If the system is complex, it can also be a multi-segment namespace, etc.
 * @param array $params
 * @param false $force_exists
 * @return string
 * @example
 * url('article/index') // indicates the generation of a url indexing to the article controller, index method
 * @throws \LFPhp\PLite\Exception\PLiteException
 * @throws \LFPhp\PLite\Exception\RouterException
 */
function url($uri = '', $params = [], $force_exists = false){
	if($force_exists){
		//todo There is no wildcard comparison here
		$routes = get_config(PLITE_ROUTER_CONFIG_FILE);
		if(!isset($routes[$uri])){
			throw new RouterException('Router no found:'.$uri);
		}
	}
	$params = array_clean_null($params);
	$ps = $params ? '&'.http_build_query_recursive($params) : '';
	$url = PLITE_SITE_ROOT."?".PLITE_ROUTER_KEY."=$uri".$ps;
	event_fire(EVENT_ROUTER_URL, $url, $uri, $params);
	return $url;
}

/**
 * Generate html hidden form fields based on uri and params
 * Generally, in a GET type form, you need to submit an additional input:hidden form item to pass the uri information
 * @param string $uri
 * @param array $params
 * @return string
 */
function url_input($uri, $params = []){
	$html = html_tag_hidden(PLITE_ROUTER_KEY, $uri);
	$params = array_clean_null($params);
	foreach($params as $k => $v){
		$html .= html_tag_hidden($k, $v);
	}
	return $html;
}

/**
 * Replace the specified URI with new parameters
 * @param string $uri
 * @param array $replace_map replace variable group mapping [variable name => new variable value,...] When the new variable value is null, delete it
 * @return string
 */
function url_replace($uri, $replace_map = []){
	$ps = $_GET;
	foreach($replace_map as $k => $v){
		if(is_null($v)){
			unset($ps[$k]);
		} else {
			$ps[$k] = $v;
		}
	}
	return url($uri, $ps);
}

/**
 * Replace the current uri parameter part with the new parameter
 * @param array $replace_map replace variable group mapping [variable name => new variable value,...] When the new variable value is null, delete it
 * @return string
 */
function url_replace_current($replace_map = []){
	$uri = get_router();
	return url_replace($uri, $replace_map);
}

/**
 * Set override routing information (including override $_GET, $_REQUEST)
 * @param string $uri
 * @param array $params
 * @return void
 */
function set_router($uri, $params = []){
	$_GET[PLITE_ROUTER_KEY] = $uri;
	$_REQUEST[PLITE_ROUTER_KEY] = $uri;
	foreach($params as $k => $v){
		$_GET[$k] = $v;
		$_REQUEST[$k] = $v;
	}
}

/**
 * Get the current route URI
 * @return string
 */
function get_router(){
	return $_GET[PLITE_ROUTER_KEY];
}

/**
 * Checks whether the current route matches the specified URI
 * @param string $uri
 * @return bool
 */
function match_router($uri = ''){
	$current_uri = get_router();
	if(strcasecmp($uri, $current_uri) === 0){
		return true;
	}
	if($uri xor $current_uri){
		return false;
	}

	[$c, $a] = explode('/', $current_uri);
	[$ctrl, $act] = explode('/', $uri);
	if(strcasecmp($ctrl, $c) != 0){
		return false;
	}
	if($act && strcasecmp($act, $a) === 0){
		return true;
	}
	return false;
}

/**
 * @param string|callable $route_item Routing rules, supported formats: 1. Function; 2. Class method format string; 3. URL jump string
 * @param null $match_controller
 * @param null $match_action
 * @return bool|mixed|void
 * @throws \LFPhp\PLite\Exception\PLiteException
 * @throws \LFPhp\PLite\Exception\RouterException
 */
function call_route($route_item, &$match_controller = null, &$match_action = null){
	event_fire(EVENT_ROUTER_HIT, $route_item);
	if(is_callable($route_item)){
		return call_user_func($route_item, $_REQUEST);
	}
	if(is_url($route_item)){
		http_redirect($route_item);
		return;
	}
	if(is_string($route_item) && strpos($route_item, '@')){
		[$match_controller, $match_action] = explode('@', $route_item);
		if(!class_exists($match_controller)){
			throw new RouterException("Router no found PageID:$route_item");
		}
		//是否存在 __call 方法
		$call_method_exists = method_exists($match_controller, '__call');
		if(!method_exists($match_controller, $match_action) && !$call_method_exists){
			throw new RouterException("Action no found(r:$route_item)");
		}
		$rc = new ReflectionClass($match_controller);

		if(!$call_method_exists){
			if(!$rc->hasMethod($match_action)){
				throw new RouterException('Router no found');
			}
			$method = $rc->getMethod($match_action);
			if($method->isStatic() || !$method->isPublic()){
				throw new RouterException('Method no accessible:'.$match_action);
			}
		}
		event_fire(EVENT_APP_BEFORE_EXEC, $match_controller, $match_action);
		$controller = new $match_controller;
		return call_user_func([$controller, $match_action], $_REQUEST);
	}
	throw new RouterException('Router call fail:'.$route_item);
}
