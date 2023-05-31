<?php

namespace LFPhp\Plite;

use LFPhp\Plite\Exception\PLiteException;

//框架ID
define('PLITE_ID', 'PLite');

if(!defined('APP_ROOT')){
	throw new PLiteException('APP_ROOT require to define');
}

//站点根路径，缺省使用 [/] 作为根路径
//在实际项目中，建议配置指定host
!defined('SITE_ROOT') && define('SITE_ROOT', '/');

//配置目录，提供给 get_config 函数使用
!defined('CONFIG_PATH') && define('CONFIG_PATH', APP_ROOT.'/config');

//路由参数 key
!defined('ROUTER_KEY') && define('ROUTER_KEY', 'r');

//页面模板目录，提供给 include_page 函数使用
!defined('PAGE_PATH') && define('PAGE_PATH', APP_ROOT.'/src/page');

//ENV KEY
!defined('SERVER_APP_ENV_KEY') && define('SERVER_APP_ENV_KEY', 'APP_ENV');

//404页面（传入 exception 变量）
!defined('PAGE_NO_FOUND') && define('PAGE_NO_FOUND', '404.php');;

//错误页面（传入 exception 变量）
!defined('PAGE_ERROR') && define('PAGE_ERROR', '5xx.php');;

//框架内置事件（可拓展）
const EVENT_APP_START = 'EVENT_APP_START';
const EVENT_APP_BEFORE_ACTION = 'EVENT_APP_BEFORE_ACTION';
const EVENT_APP_AFTER_ACTION = 'EVENT_APP_AFTER_ACTION';
const EVENT_APP_FINISHED = 'EVENT_APP_FINISHED';
const EVENT_APP_EXCEPTION = 'EVENT_APP_EXCEPTION';
const EVENT_ROUTER_REDIRECT = 'EVENT_ROUTER_REDIRECT';
const EVENT_ROUTER_EXCEPTION = 'EVENT_ROUTER_EXCEPTION';
const EVENT_APP_JSON_RESPONSE = 'EVENT_APP_JSON_RESPONSE';
const EVENT_APP_BEFORE_INCLUDE_PAGE = 'EVENT_APP_BEFORE_INCLUDE_PAGE';
const EVENT_APP_AFTER_INCLUDE_PAGE = 'EVENT_APP_AFTER_INCLUDE_PAGE';

//框架事件列表
const FRAMEWORK_EVENT_LIST = [
	EVENT_APP_START,
	EVENT_APP_BEFORE_ACTION,
	EVENT_APP_AFTER_ACTION,
	EVENT_APP_FINISHED,
	EVENT_APP_EXCEPTION,
	EVENT_ROUTER_REDIRECT,
	EVENT_ROUTER_EXCEPTION,
	EVENT_APP_JSON_RESPONSE,
	EVENT_APP_BEFORE_INCLUDE_PAGE,
	EVENT_APP_AFTER_INCLUDE_PAGE,
];

//框架内置环境定义（可拓展）
const ENV_LOCAL = 'local';//本地环境
const ENV_DEVELOPMENT = 'development';//开发环境
const ENV_TEST = 'development';//测试环境
const ENV_PRODUCTION = 'production';//生产环境