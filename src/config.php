<?php
use Lfphp\Plite\Exception\PLiteException;

/************************************
 * 程序运行基础配置
 ***********************************/
//应用根目录
if(!defined('APP_ROOT')){
	throw new PLiteException('APP_ROOT require to define');
}

//站点根路径，缺省使用 [/] 作为根路径
//在实际项目中，建议配置指定host
!defined('SITE_ROOT') && define('SITE_ROOT', '/');

//配置目录，提供给 get_config 函数使用
!defined('CONFIG_PATH') && define('CONFIG_PATH', APP_ROOT.'/config');

//页面模板目录，提供给 include_page 函数使用
!defined('TEMPLATE_PATH') && define('TEMPLATE_PATH', 'src/page');