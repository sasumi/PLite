<?php
namespace LFPhp\PLite;

use LFPhp\PLite\Exception\RouterException;
use ReflectionClass;
use function LFPhp\Func\array_clear_null;
use function LFPhp\Func\event_fire;
use function LFPhp\Func\html_tag_hidden;
use function LFPhp\Func\http_redirect;
use function LFPhp\Func\is_url;

/**
 * URL 路由函数
 * @param string $uri URI 字符串，一般为 ctrl/act 格式。如果系统复杂，也可以是多段命名空间等。
 * @param array $params
 * @param false $force_exists
 * @return string
 * @example
 * url('article/index') //表示生成一条索引到 article controller, index方法的url
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
	event_fire(EVENT_ROUTER_URL, $url, $uri, $params);
	return $url;
}

/**
 * 根据uri、params 生成 html hidden 表单字段
 * 一般在 GET 类型的form中，需要额外提交 input:hidden 表单项来传递uri信息
 * @param string $uri
 * @param array $params
 * @return string
 */
function url_input($uri, $params = []){
	$html = html_tag_hidden(PLITE_ROUTER_KEY, $uri);
	$params = array_clear_null($params);
	foreach($params as $k => $v){
		$html .= html_tag_hidden($k, $v);
	}
	return $html;
}

/**
 * 使用新的参数替换指定URI
 * @param string $uri
 * @param array $replace_map 替换变量组映射 [变量名 => 新变量值,...]  当新变量值为 null 时，做删除处理
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
 * 使用新的参数替换当前uri参数部分
 * @param array $replace_map 替换变量组映射 [变量名 => 新变量值,...]  当新变量值为 null 时，做删除处理
 * @return string
 */
function url_replace_current($replace_map = []){
	$uri = get_router();
	return url_replace($uri, $replace_map);
}

/**
 * 设置覆盖路由信息（包括覆盖 $_GET, $_REQUEST
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
 * 获取当前路由URI
 * @return string
 */
function get_router(){
	return $_GET[PLITE_ROUTER_KEY];
}

/**
 * 检测当前路由是否匹配指定URI
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
 * @param string|callable $route_item 路由规则，支持格式：1、函数；2、Class@method \格式字符串；3、URL跳转字符串
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
		event_fire(EVENT_APP_BEFORE_EXEC, $match_controller, $match_action);
		$controller = new $match_controller;
		return call_user_func([$controller, $match_action], $_REQUEST);
	}
	throw new RouterException('Router call fail:'.$route_item);
}
