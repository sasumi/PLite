<?php
namespace Lfphp\Plite;

use LFPhp\Logger\Logger;
use Lfphp\Plite\Exception\PLiteException as Exception;
use Lfphp\Plite\Exception\RouterException;
use function LFPhp\Func\http_from_json_request;

/**
 * @throws \ReflectionException
 * @throws \Lfphp\Plite\Exception\PLiteException
 */
function start_web(){
	try{
		$req_route = $_GET['r'];
		$wildcard = '*';
		$routes = get_config('routes');

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
	}catch(RouterException $e){
		fire_event(EVENT_ROUTER_EXCEPTION, $e);
		if(http_from_json_request()){
			die(json_encode(pack_response_error($e->getMessage()), JSON_UNESCAPED_UNICODE));
		}
		include_page(PAGE_NO_FOUND, ['exception' => $e]);
	}catch(Exception $e){
		fire_event(EVENT_APP_EXCEPTION, $e);
		if(http_from_json_request()){
			die(json_encode(pack_response_error($e->getMessage()), JSON_UNESCAPED_UNICODE));
		}
		include_page(PAGE_ERROR, ['exception' => $e]);
	}finally{
		fire_event(EVENT_APP_FINISHED);
	}
}

function set_app_env($app_env){
	$_SERVER[SERVER_APP_ENV_KEY] = $app_env;
}

function get_app_env(){
	return $_SERVER[SERVER_APP_ENV_KEY];
}