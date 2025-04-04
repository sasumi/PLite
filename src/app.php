<?php
namespace LFPhp\PLite;

use Composer\InstalledVersions;
use Exception;
use LFPhp\PLite\Exception\MessageException;
use LFPhp\PLite\Exception\PLiteException;
use LFPhp\PLite\Exception\RouterException;
use function LFPhp\Func\event_fire;
use function LFPhp\Func\get_class_without_namespace;
use function LFPhp\Func\h;
use function LFPhp\Func\html_redirect_with_message;
use function LFPhp\Func\http_from_json_request;
use function LFPhp\Func\http_get_current_page_url;
use function LFPhp\Func\http_header_json_response;
use function LFPhp\Func\http_redirect;
use function LFPhp\Func\http_request_accept_json;
use function LFPhp\Func\register_error2exception;
use function LFPhp\Func\underscores_to_pascalcase;
use const LFPhp\Func\EVENT_PAYLOAD_BREAK_NEXT;

$GLOBALS[__NAMESPACE__.'/JSON_RESPONSE_HANDLE'] = function($data){
	return $data;
};

/**
 * Start web server
 * @param callable|null $pre_handler pre-handler
 */
function start_web($pre_handler = null){
	$match_controller = null;
	$match_action = null;

	/**
	 * System built-in default response processor
	 * Processing logic:
	 * 1. json request, returned as json,
	 * 2. If other requests are in Controller@Action mode in the routing configuration, try to load the view template
	 * @param array $response
	 * @param string|null $controller
	 * @param string|null $action
	 * @throws \LFPhp\PLite\Exception\PLiteException
	 */
	$response_handle = function($response = null, $controller = null, $action = null){
		//[1].json request, convert anything to json response
		if(http_request_accept_json()){
			http_header_json_response();
			echo json_encode($GLOBALS[__NAMESPACE__.'/JSON_RESPONSE_HANDLE']($response), JSON_UNESCAPED_UNICODE);
			return;
		}

		//[2] text/html request
		//for auto template
		if($controller && $action){
			$ctrl = get_class_without_namespace($controller);
			$tpl = strtolower("$ctrl/$action.php");
			if(page_exists($tpl)){
				include_page($tpl, $response);
				return;
			}
		}
		if(!$response){
			return;
		}
		//for text output(or no ctrl/act template)
		if(is_string($response)){
			echo h($response);
		}else{
			var_export($response);
		}
	};

	/**
	 * System built-in exception handler
	 * Processing logic:
	 * 1. Does not support json response format, automatically detects template
	 * 2. Only MessageException outputs data, other Exceptions only output message and code
	 * 3. RouterException
	 * Note: Do not interrupt other exception event processing. If the system has other exception recording functions, you need to distinguish the MessageException situation yourself.
	 * @param \Exception $exception
	 * @return bool|void
	 * @throws \LFPhp\PLite\Exception\PLiteException
	 */
	$exception_handle = function(Exception $exception){
		//json exceptions
		if(http_request_accept_json()){
			http_header_json_response();
			echo json_encode($GLOBALS[__NAMESPACE__.'/JSON_RESPONSE_HANDLE']($exception), JSON_UNESCAPED_UNICODE);
			return;
		}

		//router exception page
		if($exception instanceof RouterException && page_exists(PLITE_PAGE_NO_FOUND)){
			include_page(PLITE_PAGE_NO_FOUND, ['exception' => $exception]);
			return true;
		}

		if(page_exists(PLITE_PAGE_ERROR)){
			include_page(PLITE_PAGE_ERROR, ['exception' => $exception]);
			return true;
		}

		//handle un-caught exception
		$msg_ex = MessageException::fromException($exception, false);
		$msg = $msg_ex->getMessage();
		$forward_url = $msg_ex->getForwardUrl();
		if($forward_url){
			if($msg){
				echo html_redirect_with_message($msg, $forward_url);
			}else{
				http_redirect($forward_url);
			}
			return true;
		}

		echo h($msg || MessageException::$MESSAGE_DEFAULT_ERROR);
		return true;
	};

	try{
		register_error2exception(E_ALL^E_NOTICE);
		$pre_handler && call_user_func($pre_handler);
		for(; ;){
			$req_route = $_GET[PLITE_ROUTER_KEY];
			$wildcard = '*';
			$routes = get_config(PLITE_ROUTER_CONFIG_FILE);

			$url = http_get_current_page_url();
			event_fire(EVENT_APP_START, $url);

			//fix json
			if(http_from_json_request()){
				$req_str = file_get_contents('php://input');
				if($req_str){
					$obj = @json_decode($req_str, true);
					if(!json_last_error() && is_array($obj)){
						$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
						$is_get = $_SERVER['REQUEST_METHOD'] === 'GET';
						foreach($obj as $k => $val){
							if($is_post){
								$_POST[$k] = $val;
							}else if($is_get){
								$_GET[$k] = $val;
							}
							$_REQUEST[$k] = $val;
						}
					}
				}
			}

			$matched_route_item = $routes[$req_route];
			if(isset($matched_route_item)){
				$rsp_data = call_route($matched_route_item, $match_controller, $match_action);
				break;
			}

			//router match
			[$req_ctrl, $req_act] = explode('/', $req_route);
			if($routes["$req_ctrl/$wildcard"]){
				$matched_route_item = $routes["$req_ctrl/$wildcard"];
				//use wild character match
				if(strpos($matched_route_item, $wildcard) !== false){
					$rsp_data = call_route(str_replace($wildcard, $req_act, $matched_route_item), $match_controller, $match_action);
					break;
				}
				$rsp_data = call_route($matched_route_item, $match_controller, $match_action);
				break;
			}
			throw new RouterException("Router no found");
		}

		event_fire(EVENT_APP_EXECUTED, $rsp_data, $match_controller, $match_action);
		$response_handle($rsp_data, $match_controller, $match_action);
		event_fire(EVENT_APP_FINISHED);
	}catch(Exception $e){
		try{
			$r = event_fire(EVENT_APP_EXCEPTION, $e, $match_controller, $match_action);
			if($r === EVENT_PAYLOAD_BREAK_NEXT){
				return;
			}
			$exception_handle($e);
		}catch(Exception $e){
			echo $e->getMessage();
			error_log($e->getMessage());
		}
	}
}

/**
 * bind JSON response handle
 * @param $handler
 * @return void
 */
function bind_json_response_handler($handler){
	$GLOBALS[__NAMESPACE__.'/JSON_RESPONSE_HANDLE'] = $handler;
}

/**
 * set application environment
 * @param $app_env
 */
function set_app_env($app_env){
	$_SERVER[PLITE_SERVER_APP_ENV_KEY] = $app_env;
}

/**
 * get application environment
 * @return mixed
 * @throws \Exception
 */
function get_app_env(){
	$env = $_SERVER[PLITE_SERVER_APP_ENV_KEY];
	if(!$env){
		throw new PLiteException('no env detected:'.PLITE_SERVER_APP_ENV_KEY);
	}
	return $env;
}

/**
 * get application name as variable name
 * @return string
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function get_app_var_name(){
	$name = get_app_name();
	$var_name = str_replace('/', '_', $name);
	return ucfirst(underscores_to_pascalcase($var_name));
}

/**
 * Get the application namespace
 * Convert the first letter of the application name to uppercase, such as project: jack/project will generate Jack\Project namespace
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
 * Get the application name from composer.json, standard command such as jack/project, for specific naming rules, please refer to the official description of Composer
 * @return string
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function get_app_name(){
	$data = get_app_composer_config();
	return $data['name'];
}

/**
 * Get the application composer configuration
 * The environment must be installed through composer packagist to call this method
 * @return array
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function get_app_composer_config(){
	if(class_exists('\Composer\InstalledVersions')){
		$r = InstalledVersions::getRootPackage();
		$root = realpath($r['install_path']);
	}else{
		throw new Exception('No composer class [Composer\InstalledVersions] detected');
	}
	$composer_json_file = $root.'/composer.json';
	if(!is_file($composer_json_file)){
		throw new PLiteException('Composer json file no exists:'.$composer_json_file);
	}
	return json_decode(file_get_contents($composer_json_file), true);
}
