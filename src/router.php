<?php
namespace LFPhp\PLite;

use LFPhp\PLite\Exception\RouterException;
use ReflectionClass;
use function LFPhp\Func\array_clear_null;
use function LFPhp\Func\html_tag_hidden;
use function LFPhp\Func\http_redirect;

/**
 * @param string $uri
 * @param array $params
 * @param false $force_exists
 * @return string
 * @throws \LFPhp\PLite\Exception\PLiteException
 * @throws \LFPhp\PLite\Exception\RouterException
 */
function url($uri = '', $params = [], $force_exists = false){
	if($force_exists){
		//todo 这里缺少通配符比较
		$routes = get_config(PLITE_ROUTER_CONFIG_FILE);
		if(!isset($routes[$uri])){
			throw new RouterException('Router no found:'.$uri);
		}
	}
	$params = array_clear_null($params);
	$ps = $params ? '&'.http_build_query($params) : '';
	$url = PLITE_SITE_ROOT."?".PLITE_ROUTER_KEY."=$uri".$ps;
	fire_event(EVENT_ROUTER_URL, $url, $uri, $params);
	return $url;
}

function url_input($uri, $params = []){
	$html = html_tag_hidden(PLITE_ROUTER_KEY, $uri);
	$params = array_clear_null($params);
	foreach($params as $k => $v){
		$html .= html_tag_hidden($k, $v);
	}
	return $html;
}

function url_replace($path, $params = []){
	$ps = $_GET;
	foreach($params as $k => $v){
		$ps[$k] = $v;
	}
	return url($path, $ps);
}

function url_hit($path){
	$uri = ltrim($_SERVER["PATH_INFO"], '/');
	return $uri == $path;
}

function is_url($url){
	return strpos($url, '//') === 0 || filter_var($url, FILTER_VALIDATE_URL);
}

function get_router(){
	return $_GET[PLITE_ROUTER_KEY];
}

function match_router($uri = ''){
	$current_uri = get_router();
	if(strcasecmp($uri, $current_uri) === 0){
		return true;
	}
	if($uri xor $current_uri){
		return false;
	}

	list($c, $a) = explode('/', $current_uri);
	list($ctrl, $act) = explode('/', $uri);
	if(strcasecmp($ctrl, $c) != 0){
		return false;
	}
	if($act && strcasecmp($act, $a) === 0){
		return true;
	}
	return false;
}

/**
 * @param string|callable $route_item 路由规则，支持格式：1、函数；2、Class@method \格式字符串；3、URL跳转字符串
 * @param null $match_controller
 * @param null $match_action
 * @return bool|mixed|void
 * @throws \LFPhp\PLite\Exception\PLiteException
 * @throws \LFPhp\PLite\Exception\RouterException
 */
function call_route($route_item, &$match_controller = null, &$match_action = null){
	fire_event(EVENT_ROUTER_HIT, $route_item);
	if(is_callable($route_item)){
		return call_user_func($route_item);
	}
	if(is_url($route_item)){
		http_redirect($route_item);
		return;
	}
	if(is_string($route_item) && strpos($route_item, '@')){
		list($match_controller, $match_action) = explode('@', $route_item);
		if(!class_exists($match_controller)){
			throw new RouterException("Router no found PageID:$route_item");
		}
		//是否存在 __call 方法
		$call_method_exists = method_exists($match_controller, '__call');
		if(!method_exists($match_controller, $match_action) && !$call_method_exists){
			throw new RouterException('Action no found PageID:'.$route_item);
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
		fire_event(EVENT_APP_BEFORE_EXEC, $match_controller, $match_action);
		$controller = new $match_controller;
		return call_user_func([$controller, $match_action]);
	}
	throw new RouterException('Router call fail:'.$route_item);
}
