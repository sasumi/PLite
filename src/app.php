<?php
namespace LFPhp\PLite;

use LFPhp\PLite\Exception\MessageException;
use LFPhp\PLite\Exception\PLiteException as Exception;
use LFPhp\PLite\Exception\RouterException;
use function LFPhp\Func\http_from_json_request;
use function LFPhp\Func\underscores_to_pascalcase;

/**
 * @throws \ReflectionException
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function start_web(){
	try{
		$req_route = $_GET[PLITE_ROUTER_KEY];
		$wildcard = '*';
		$routes = get_config(PLITE_ROUTER_CONFIG_FILE);

		fire_event(EVENT_APP_START);

		//fix json
		if(http_from_json_request()){
			$req_str = file_get_contents('php://input');
			if($req_str){
				$obj = @json_decode($req_str, true);
				if(!json_last_error()){
					foreach($obj as $k => $val){
						$_POST[$k] = $val;
						$_REQUEST[$k] = $val;
					}
				}
			}
		}

		$matched_route_item = $routes[$req_route];
		if(isset($matched_route_item)){
			call_route($matched_route_item);
			return;
		}

		//存在通配符规则
		list($req_ctrl, $req_act) = explode('/', $req_route);
		if($routes["$req_ctrl/$wildcard"]){
			$matched_route_item = $routes["$req_ctrl/$wildcard"];
			//命中规则存在通配符，则使用请求中的action
			if(strpos($matched_route_item, $wildcard) !== false){
				call_route(str_replace($wildcard, $req_act, $matched_route_item));
				return;
			}
			call_route($matched_route_item);
		}
		throw new RouterException("Router no found");
	}catch(\Exception $e){
		auto_print_exception($e);
	}finally{
		fire_event(EVENT_APP_FINISHED);
	}
}

function auto_print_exception(\Exception $e){
	if(!http_from_json_request()){
		if($e instanceof MessageException){
			echo $e->getMessage();
		}else if($e instanceof RouterException){
			fire_event(EVENT_ROUTER_EXCEPTION, $e);
			include_page(PLITE_PAGE_NO_FOUND, ['exception' => $e]);
		}else{
			include_page(PLITE_PAGE_ERROR, ['exception' => $e]);
			fire_event(EVENT_APP_EXCEPTION, $e);
		}
		return;
	}
	$rsp = $e instanceof MessageException ? $e->toArray() : pack_json_response($e->getMessage(), $e->getCode() ?: PLITE_RSP_CODE_UNKNOWN_ERROR);
	fire_event(EVENT_APP_BEFORE_JSON_RESPONSE, $rsp);
	$json_str = json_encode($rsp, JSON_UNESCAPED_UNICODE);
	fire_event(EVENT_APP_AFTER_JSON_RESPONSE, $rsp, $json_str);
	echo $json_str;
}

/**
 * 设置应用环境标志
 * @param $app_env
 */
function set_app_env($app_env){
	$_SERVER[PLITE_SERVER_APP_ENV_KEY] = $app_env;
}

/**
 * 获取应用环境标识
 * @return mixed
 */
function get_app_env(){
	return $_SERVER[PLITE_SERVER_APP_ENV_KEY];
}

/**
 * 获取应用变量命名
 * @return string
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function get_app_var_name(){
	$name = get_app_name();
	$var_name = str_replace('/', '_', $name);
	return underscores_to_pascalcase($var_name);
}

/**
 * 获取应用命名空间
 * @return string
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function get_app_namespace(){
	$ns = get_app_name();
	$ns = explode('/', $ns);
	foreach($ns as $k => $v){
		$ns[$k] = ucfirst($v);
	}
	return join('\\', $ns);
}

/**
 * 获取应用名称
 * @return string
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function get_app_name(){
	$data = get_app_composer_config();
	return $data['name'];
}

/**
 * 获取应用 composer 配置
 * @return array
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function get_app_composer_config(){
	$composer_json_file = PLITE_APP_ROOT.'/composer.json';
	if(!is_file($composer_json_file)){
		throw new Exception('composer json file no exists:'.$composer_json_file);
	}
	return json_decode(file_get_contents($composer_json_file), true);
}
