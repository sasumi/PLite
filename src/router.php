<?php
namespace LFPhp\PLite;

use LFPhp\PLite\Exception\PLiteException as Exception;
use LFPhp\PLite\Exception\RouterException;
use ReflectionClass;
use function LFPhp\Func\array_clear_null;
use function LFPhp\Func\get_class_without_namespace;
use function LFPhp\Func\html_tag_hidden;
use function LFPhp\Func\http_from_json_request;
use function LFPhp\Func\http_redirect;

/**
 * @param string $uri
 * @param array $params
 * @param false $force_exists
 * @return string
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function url($uri = '', $params = [], $force_exists = false){
	$routes = get_config(PLITE_ROUTER_CONFIG_FILE);
	if(!isset($routes[$uri]) && $force_exists){
		throw new Exception('Router no found:'.$uri);
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
 * @return bool|mixed|void
 * @throws \ReflectionException|\LFPhp\PLite\Exception\RouterException|\LFPhp\PLite\Exception\PLiteException
 */
function call_route($route_item){
	if(is_callable($route_item)){
		return call_user_func($route_item);
	}

	if(is_url($route_item)){
		fire_event(EVENT_ROUTER_REDIRECT, $route_item);
		http_redirect($route_item);
		return;
	}

	if(is_string($route_item) && strpos($route_item, '@')){
		list($controller_class, $action) = explode('@', $route_item);
		if(!class_exists($controller_class)){
			throw new RouterException("Router no found PageID:$route_item");
		}
		if(!method_exists($controller_class, $action)){
			throw new RouterException('Action no found PageID:'.$route_item);
		}
		$rc = new ReflectionClass($controller_class);
		$method = $rc->getMethod($action);
		if($method->isStatic() || !$method->isPublic()){
			throw new RouterException('Method no accessible:'.$action);
		}
		fire_event(EVENT_APP_BEFORE_ACTION, $controller_class, $action);
		$controller = new $controller_class;
		$ret = call_user_func([$controller, $action]);
		fire_event(EVENT_APP_AFTER_ACTION, $controller_class, $action);
		if(http_from_json_request()){
			echo json_encode(pack_response_success($ret), JSON_UNESCAPED_UNICODE);
			fire_event(EVENT_APP_JSON_RESPONSE, $ret);
		}else{
			$ctrl = get_class_without_namespace($controller_class);
			$tpl = strtolower("$ctrl/$action.php");
			include_page($tpl, $ret);
		}
		return true;
	}
	throw new Exception('Router call fail:'.$route_item);
}

/**
 * 成功
 * @param $data
 * @param string $msg
 * @return array
 */
function pack_response_success($data, $msg = 'success'){
	return [
		'code' => 0,
		'msg'  => $msg,
		'data' => $data,
	];
}

/**
 * 错误格式
 * @param string $msg
 * @param int $code
 * @param null $data
 * @return array
 */
function pack_response_error($msg = 'failed', $code = -1, $data = null){
	return [
		'code' => $code,
		'msg'  => $msg,
		'data' => $data,
	];
}
