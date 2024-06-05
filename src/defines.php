<?php
namespace LFPhp\PLite;

//框架ID
define('PLITE_ID', 'PLite');

if(!defined('PLITE_APP_ROOT')){
	//如果项目没有启动PLite框架，可以不要求定义以下常量，但程序无法正常使用以下常量
	//throw new PLiteException('PLITE_APP_ROOT require to define');
	return;
}

//站点根路径，缺省使用 [/] 作为根路径
//在实际项目中，建议配置指定host
!defined('PLITE_SITE_ROOT') && define('PLITE_SITE_ROOT', '');

//配置目录，提供给 get_config 函数使用
!defined('PLITE_CONFIG_PATH') && define('PLITE_CONFIG_PATH', PLITE_APP_ROOT.'/config');

//路由参数 key
!defined('PLITE_ROUTER_KEY') && define('PLITE_ROUTER_KEY', 'r');

//路由参数 key
!defined('PLITE_ROUTER_CONFIG_FILE') && define('PLITE_ROUTER_CONFIG_FILE', 'routes');

//静态资源版本配置文件
//静态资源配置规则请参考 README.md
!defined('PLITE_STATIC_VERSION_CONFIG_FILE') && define('PLITE_STATIC_VERSION_CONFIG_FILE', 'static_version');

//页面模板目录，提供给 include_page 函数使用
!defined('PLITE_PAGE_PATH') && define('PLITE_PAGE_PATH', PLITE_APP_ROOT.'/src/page');

//ENV KEY
!defined('PLITE_SERVER_APP_ENV_KEY') && define('PLITE_SERVER_APP_ENV_KEY', 'APP_ENV');

//消息错误页面（传入 exception 变量）
!defined('PLITE_PAGE_MESSAGE') && define('PLITE_PAGE_MESSAGE', 'message.php');

//404页面（传入 exception 变量）
!defined('PLITE_PAGE_NO_FOUND') && define('PLITE_PAGE_NO_FOUND', '404.php');

//错误页面（传入 exception 变量）
!defined('PLITE_PAGE_ERROR') && define('PLITE_PAGE_ERROR', '5xx.php');

//框架内置事件
const EVENT_APP_START = __NAMESPACE__.'EVENT_APP_START';
const EVENT_APP_BEFORE_EXEC = __NAMESPACE__.'EVENT_APP_BEFORE_EXEC';
const EVENT_APP_EXECUTED = __NAMESPACE__.'EVENT_APP_EXECUTED';
const EVENT_APP_FINISHED = __NAMESPACE__.'EVENT_APP_FINISHED';
const EVENT_APP_EXCEPTION = __NAMESPACE__.'EVENT_APP_EXCEPTION';

const EVENT_BEFORE_INCLUDE_PAGE = __NAMESPACE__.'EVENT_BEFORE_INCLUDE_PAGE';
const EVENT_AFTER_INCLUDE_PAGE = __NAMESPACE__.'EVENT_AFTER_INCLUDE_PAGE';

const EVENT_ROUTER_HIT = __NAMESPACE__.'EVENT_ROUTER_HIT';
const EVENT_ROUTER_URL = __NAMESPACE__.'EVENT_ROUTER_URL';

//框架事件列表
const FRAMEWORK_EVENT_LIST = [
	EVENT_APP_START,
	EVENT_APP_BEFORE_EXEC,
	EVENT_APP_EXECUTED,
	EVENT_APP_FINISHED,
	EVENT_APP_EXCEPTION,
	EVENT_ROUTER_HIT,
	EVENT_ROUTER_URL,
	EVENT_BEFORE_INCLUDE_PAGE,
	EVENT_AFTER_INCLUDE_PAGE,
];

//框架内置环境定义（可拓展）
const ENV_LOCAL = 'local';//本地环境
const ENV_DEVELOPMENT = 'development';//开发环境
const ENV_TEST = 'test';//测试环境
const ENV_PRODUCTION = 'production';//生产环境
