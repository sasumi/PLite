<?php
namespace LFPhp\PLite;

//Framework ID
define('PLITE_ID', 'PLITE');

if(!defined('PLITE_APP_ROOT')){
	//If the project does not start the PLite framework, you do not need to define the following constants, but the program cannot use the following constants normally
	//throw new PLiteException('PLITE_APP_ROOT require to define');
	return;
}

//Site root path, [/] is used as the root path by default
//In actual projects, it is recommended to configure a specified host
!defined('PLITE_SITE_ROOT') && define('PLITE_SITE_ROOT', '');

//Configuration directory, provided for use by the get_config function
!defined('PLITE_CONFIG_PATH') && define('PLITE_CONFIG_PATH', PLITE_APP_ROOT.'/config');

//Route parameters key
!defined('PLITE_ROUTER_KEY') && define('PLITE_ROUTER_KEY', 'r');

//Route parameters key
!defined('PLITE_ROUTER_CONFIG_FILE') && define('PLITE_ROUTER_CONFIG_FILE', 'routes');

//Static resource version configuration file
//For static resource configuration rules, please refer to README.md
!defined('PLITE_STATIC_VERSION_CONFIG_FILE') && define('PLITE_STATIC_VERSION_CONFIG_FILE', 'static_version');

//Page template directory, provided for include_page function
!defined('PLITE_PAGE_PATH') && define('PLITE_PAGE_PATH', PLITE_APP_ROOT.'/src/page');

//ENV KEY
!defined('PLITE_SERVER_APP_ENV_KEY') && define('PLITE_SERVER_APP_ENV_KEY', 'APP_ENV');

//Message error page (pass in exception variable)
!defined('PLITE_PAGE_MESSAGE') && define('PLITE_PAGE_MESSAGE', 'message.php');

//404 page (pass in exception variable)
!defined('PLITE_PAGE_NO_FOUND') && define('PLITE_PAGE_NO_FOUND', '404.php');

//Error page (pass in exception variable)
!defined('PLITE_PAGE_ERROR') && define('PLITE_PAGE_ERROR', '5xx.php');

//Framework built-in event
const EVENT_APP_START = __NAMESPACE__.'EVENT_APP_START';
const EVENT_APP_BEFORE_EXEC = __NAMESPACE__.'EVENT_APP_BEFORE_EXEC';
const EVENT_APP_EXECUTED = __NAMESPACE__.'EVENT_APP_EXECUTED';
const EVENT_APP_FINISHED = __NAMESPACE__.'EVENT_APP_FINISHED';
const EVENT_APP_EXCEPTION = __NAMESPACE__.'EVENT_APP_EXCEPTION';
const EVENT_BEFORE_INCLUDE_PAGE = __NAMESPACE__.'EVENT_BEFORE_INCLUDE_PAGE';
const EVENT_AFTER_INCLUDE_PAGE = __NAMESPACE__.'EVENT_AFTER_INCLUDE_PAGE';
const EVENT_ROUTER_HIT = __NAMESPACE__.'EVENT_ROUTER_HIT';
const EVENT_ROUTER_URL = __NAMESPACE__.'EVENT_ROUTER_URL';

//Framework event list
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

//Framework built-in environment definition (expandable)
const ENV_LOCAL = 'local';//Local environment
const ENV_DEVELOPMENT = 'development';//Development environment
const ENV_TEST = 'test'; //Test environment
const ENV_PRODUCTION = 'production'; //Production environment
