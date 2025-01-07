<?php
namespace LFPhp\PLite;

use Composer\InstalledVersions;
use Exception;
use LFPhp\PLite\Exception\MessageException;
use LFPhp\PLite\Exception\PLiteException;
use LFPhp\PLite\Exception\RouterException;
use function LFPhp\Func\event_fire;
use function LFPhp\Func\get_class_without_namespace;
use function LFPhp\Func\http_from_json_request;
use function LFPhp\Func\http_get_current_page_url;
use function LFPhp\Func\http_json_response;
use function LFPhp\Func\http_redirect;
use function LFPhp\Func\http_request_accept_json;
use function LFPhp\Func\underscores_to_pascalcase;
use const LFPhp\Func\EVENT_PAYLOAD_NULL;

/**
 * Start web server
 */
function start_web(){
	try{
		for(; ;){
			$match_controller = null;
			$match_action = null;
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
							} else if($is_get){
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

			//存在通配符规则
			[$req_ctrl, $req_act] = explode('/', $req_route);
			if($routes["$req_ctrl/$wildcard"]){
				$matched_route_item = $routes["$req_ctrl/$wildcard"];
				//命中规则存在通配符，则使用请求中的action
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
	}catch(Exception $e){
		$r = event_fire(EVENT_APP_EXCEPTION, $e, $match_controller, $match_action);
		if($r === EVENT_PAYLOAD_NULL){
			throw $e; //未处理过任何异常，继续往上抛
		}
	}finally{
		event_fire(EVENT_APP_FINISHED);
	}
}

/**
 * System built-in default response processor
 * Processing logic:
 * 1. json request, returned as json,
 * 2. If other requests are in Controller@Action mode in the routing configuration, try to load the view template
 * @param mixed|null $data
 * @param string|null $controller
 * @param string|null $action
 * @return true|null whether the processing logic is hit
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function default_response_handle($data = null, $controller = null, $action = null){
	if(http_request_accept_json()){
		http_json_response([
			'data'    => $data,
			'code'    => MessageException::$CODE_DEFAULT_SUCCESS,
			'message' => 'success',
		]);
		return true;
	}

	//auto template
	if($controller && $action){
		$ctrl = get_class_without_namespace($controller);
		$tpl = strtolower("$ctrl/$action.php");
		if(page_exists($tpl)){
			include_page($tpl, $data);
			return true;
		}
	}
}
/**
 * System built-in exception handler
 * Processing logic:
 * 1. Does not support json response format, automatically detects template
 * 2. Only MessageException outputs data, other Exceptions only output message and code
 * 3. RouterException
 * Note: Do not interrupt other exception event processing. If the system has other exception recording functions, you need to distinguish the MessageException situation yourself.
 * @param \Exception $e
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function default_exception_handle(Exception $e){
	//no support json access
	if(!http_request_accept_json()){
		if($e instanceof MessageException){
			if(page_exists(PLITE_PAGE_MESSAGE)){
				include_page(PLITE_PAGE_MESSAGE, ['exception' => $e]);
				return;
			}
			if($forward_url = $e->getForwardUrl()){
				http_redirect($forward_url);
			}
			echo $e->getMessage();
			return;
		}
		if($e instanceof RouterException && page_exists(PLITE_PAGE_NO_FOUND)){
			include_page(PLITE_PAGE_NO_FOUND, ['exception' => $e]);
			return;
		}
		if(page_exists(PLITE_PAGE_ERROR)){
			include_page(PLITE_PAGE_ERROR, ['exception' => $e]);
			return;
		}
		echo $e->getMessage();
		return;
	}
	//Avoid general exception code = 0 situation
	$msg_code = $e->getCode();
	if(!$msg_code && !($e instanceof MessageException)){
		$msg_code = MessageException::$CODE_DEFAULT_ERROR;
	}

	//json request supported
	http_json_response([
		'code'        => $msg_code,
		'message'     => $e->getMessage(),
		'forward_url' => $e instanceof MessageException ? $e->getForwardUrl() : '',
		'data'        => $e instanceof MessageException ? $e->getData() : null,
	]);
	return;
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
