<?php
namespace Lfphp\Plite;

use LFPhp\Logger\Logger;
use Lfphp\Plite\Exception\PLiteException as Exception;
use Lfphp\Plite\Exception\RouterException;
use ReflectionClass;
use function LFPhp\Func\array_clear_null;
use function LFPhp\Func\html_tag_hidden;
use function LFPhp\Func\http_from_json_request;

function url($path = '', $params = [], $force_exists = false){
	$routes = get_config('routes');
	if(!isset($routes[$path]) && $force_exists){
		throw new Exception('Router no found:'.$path);
	}
	$params = array_clear_null($params);
	$ps = $params ? '&'.http_build_query($params) : '';
	return SITE_ROOT."?r=$path".$ps;
}

function url_input($path, $params = []){
	$html = html_tag_hidden('r', $path);
	$params = array_clear_null($params);
	foreach($params as $k => $v){
		$html .= html_tag_hidden($k, $v);
	}
	return $html;
}

function get_router(){
	return $_GET['r'];
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
 * @throws \ReflectionException|\Lfphp\Plite\Exception\RouterException|\Lfphp\Plite\Exception\PLiteException
 */
function call_route($route_item){
	if(is_callable($route_item)){
		return call_user_func($route_item);
	}

	if(is_url($route_item)){
		Logger::info('Req redirect', $route_item);
		http_redirect($route_item);
		return;
	}

	if(is_string($route_item) && strpos($route_item, '@')){
		list($controller, $action) = explode('@', $route_item);
		if(!class_exists($controller)){
			throw new RouterException("Router no found PageID:$route_item");
		}
		if(!method_exists($controller, $action)){
			throw new RouterException('Action no found PageID:'.$route_item);
		}
		$rc = new ReflectionClass($controller);
		$method = $rc->getMethod($action);
		if($method->isStatic() || !$method->isPublic()){
			throw new RouterException('Method no accessible:'.$action);
		}
		Logger::info("Controller Action called: {$controller}->{$action}()");
		$ctrl_ins = new $controller;
		$ret = call_user_func([$ctrl_ins, $action]);
		if(http_from_json_request()){
			echo json_encode(pack_response_success($ret), JSON_UNESCAPED_UNICODE);
			function_exists('fastcgi_finish_request') && fastcgi_finish_request();
			if(in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])){
				Logger::info(substr('Javascript Post '.str_replace(get_namespace($controller), '', $route_item), 0, 50), substr(json_encode($_POST, JSON_UNESCAPED_UNICODE), 0, 200));
			}
		}else{
			$ctrl = get_class_without_ns($controller);
			$tpl = strtolower("$ctrl/$action.php");
			include_page($tpl, $ret);
		}
		return true;
	}
	throw new Exception('Router call fail:'.$route_item);
}

function pack_response_success($data, $msg = 'success'){
	return [
		'code' => 0,
		'msg'  => $msg,
		'data' => $data,
	];
}

function pack_response_error($msg = 'failed', $code = -1, $data = null){
	return [
		'code' => $code,
		'msg'  => $msg,
		'data' => $data,
	];
}

function url_replace($path, $params = []){
	$ps = $_GET;
	foreach($params as $k => $v){
		$ps[$k] = $v;
	}
	return url($path, $ps);
}

function url_hit($path){
	$uri = ltrim($_SERVER['PATH_INFO'], '/');
	return $uri == $path;
}

function is_url($url){
	return strpos($url, '//') === 0 || filter_var($url, FILTER_VALIDATE_URL);
}

