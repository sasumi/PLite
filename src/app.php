<?php
namespace LFPhp\PLite;

use Exception;
use LFPhp\PLite\Exception\MessageException;
use LFPhp\PLite\Exception\PLiteException;
use LFPhp\PLite\Exception\RouterException;
use function LFPhp\Func\get_class_without_namespace;
use function LFPhp\Func\http_from_json_request;
use function LFPhp\Func\http_json_response;
use function LFPhp\Func\http_redirect;
use function LFPhp\Func\http_request_accept_json;
use function LFPhp\Func\underscores_to_pascalcase;

/**
 * 开始运行web服务
 */
function start_web(){
	try{
		for(; ;){
			$match_controller = null;
			$match_action = null;
			$req_route = $_GET[PLITE_ROUTER_KEY];
			$wildcard = '*';
			$routes = get_config(PLITE_ROUTER_CONFIG_FILE);

			fire_event(EVENT_APP_START);

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
			list($req_ctrl, $req_act) = explode('/', $req_route);
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
		fire_event(EVENT_APP_EXECUTED, $rsp_data, $match_controller, $match_action);
	}catch(Exception $e){
		$r = fire_event(EVENT_APP_EXCEPTION, $e, $match_controller, $match_action);
		if($r !== EVENT_PAYLOAD_BREAK_NEXT){
			throw $e; //未中断异常处理，直接继续往上抛
		}
	}finally{
		fire_event(EVENT_APP_FINISHED);
	}
}

/**
 * 系统内置默认响应处理器
 * 处理逻辑：
 * 1、json请求，以json返回，
 * 2、其他请求如果在路由配置里面是 Controller@Action 方式的话，尝试加载视图模板
 * @param mixed|null $data
 * @param string|null $controller
 * @param string|null $action
 * @return bool 是否命中处理逻辑
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function default_response_handle($data = null, $controller = null, $action = null){
	if(http_request_accept_json()){
		http_json_response([
			'data'    => $data,
			'code'    => MessageException::$CODE_DEFAULT_SUCCESS,
			'message' => '成功',
		]);
		return true;
	}

	//自动模板
	if($controller && $action){
		$ctrl = get_class_without_namespace($controller);
		$tpl = strtolower("$ctrl/$action.php");
		if(page_exists($tpl)){
			include_page($tpl, $data);
			return true;
		}
	}
	return false;
}

/**
 * 系统内置异常处理器
 * 处理逻辑：
 * 1、不支持 json 响应格式，会自动检测模板
 * 2、只有 MessageException 才输出data，其他 Exception 均只输出 message 和 code
 * 3、RouterException
 * 注意：不中断其他异常事件处理，如果系统有其他异常记录函数，需要自行区分 MessageException 的情况。
 * @param \Exception $e
 * @return true
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function default_exception_handle(Exception $e){
	//不支持JSON响应的访问
	if(!http_request_accept_json()){
		if($e instanceof MessageException){
			if(page_exists(PLITE_PAGE_MESSAGE)){
				include_page(PLITE_PAGE_MESSAGE, ['exception' => $e]);
				return true;
			}
			if($forward_url = $e->getForwardUrl()){
				http_redirect($forward_url);
			}
			echo $e->getMessage();
			return true;
		}
		if($e instanceof RouterException && page_exists(PLITE_PAGE_NO_FOUND)){
			include_page(PLITE_PAGE_NO_FOUND, ['exception' => $e]);
			return true;
		}
		if(page_exists(PLITE_PAGE_ERROR)){
			include_page(PLITE_PAGE_ERROR, ['exception' => $e]);
			return true;
		}
		echo $e->getMessage();
		return true;
	}
	//避免一般exception code = 0 情况
	$msg_code = $e->getCode();
	if(!$msg_code && !($e instanceof MessageException)){
		$msg_code = MessageException::$CODE_DEFAULT_ERROR;
	}

	//支持JSON响应
	http_json_response([
		'code'        => $msg_code,
		'message'     => $e->getMessage(),
		'forward_url' => $e instanceof MessageException ? $e->getForwardUrl() : '',
		'data'        => $e instanceof MessageException ? $e->getData() : null,
	]);
	return true;
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
 * 获取应用变量命名
 * @return string
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function get_app_var_name(){
	$name = get_app_name();
	$var_name = str_replace('/', '_', $name);
	return ucfirst(underscores_to_pascalcase($var_name));
}

/**
 * 获取应用命名空间
 * 从应用名称中按照首字母大写方式转换，如项目：jack/project 会产生 Jack\Project 命名空间
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
 * 从composer.json 中获取应用名称，规范命令例如 jack/project，具体命名规则可以参考Composer官方说明
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
	if(!defined('PLITE_APP_ROOT')){
		throw new Exception('You are no in plite env');
	}
	$composer_json_file = PLITE_APP_ROOT.'/composer.json';
	if(!is_file($composer_json_file)){
		throw new PLiteException('Composer json file no exists:'.$composer_json_file);
	}
	return json_decode(file_get_contents($composer_json_file), true);
}
