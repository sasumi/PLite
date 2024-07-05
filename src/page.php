<?php
namespace LFPhp\PLite;

use LFPhp\PLite\Exception\PLiteException;
use function LFPhp\Func\event_fire;
use function LFPhp\Func\file_in_dir;
use function LFPhp\Func\html_tag;
use function LFPhp\Func\html_tag_css;
use function LFPhp\Func\html_tag_js;
use function LFPhp\Func\is_url;
use function LFPhp\Func\static_version_patch;
use function LFPhp\Func\static_version_set;

/**
 * 引入页面模板
 * @param string $page_file
 * @param array $params
 * @param bool $as_return
 * @return false|string|null
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function include_page($page_file, $params = [], $as_return = false){
	event_fire(EVENT_BEFORE_INCLUDE_PAGE, $page_file, $params);
	if(!page_exists($page_file)){
		throw new PLiteException("Template no found($page_file)");
	}
	if(!$as_return){
		$GLOBALS['__plite_page_include_map__'][$page_file] = true;
	}
	if($as_return){
		ob_start();
	}
	if($params && is_array($params)){
		extract($params, EXTR_OVERWRITE);
	}
	$f = PLITE_PAGE_PATH."/$page_file";
	include $f;
	event_fire(EVENT_AFTER_INCLUDE_PAGE, $f, $params);
	if($as_return){
		$html = ob_get_contents();
		ob_clean();
		return $html;
	}
	return null;
}

/**
 * 只引入页面模板一次
 * @param string $page_file
 * @param array $params
 * @return false|string|null
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function include_page_once($page_file, $params = []){
	if($GLOBALS['__plite_page_include_map__'][$page_file]){
		return false;
	}
	return include_page($page_file, $params);
}

/**
 * 检测视图文件是否存在
 * @param string $page_file
 * @return bool
 */
function page_exists($page_file){
	$f = PLITE_PAGE_PATH.'/'.$page_file;
	return is_file($f) && file_in_dir($f, PLITE_PAGE_PATH);
}

/**
 * 引入js（同时打上版本号）
 * @param string $js_src
 * @param array $attr
 * @throws \Exception
 */
function include_js($js_src, $attr = []){
	echo html_tag_js(patch_resource_version(patch_site_path($js_src)), $attr);
}

/**
 * 引入css（同时打上版本号）
 * @param string $css_href
 * @param array $attr
 * @throws \Exception
 */
function include_css($css_href, $attr = []){
	echo html_tag_css(patch_resource_version(patch_site_path($css_href)), $attr);
}

/**
 * 引入图片（同时打上版本号）
 * @param string $src
 * @param array $attr
 * @throws \Exception
 */
function include_img($src, $attr = []){
	$attr['src'] = patch_resource_version(patch_site_path($src));
	echo html_tag('img', $attr);
}

/**
 * 补充网站访问目录相对路径
 * @param string $url_or_path
 * @return string
 */
function patch_site_path($url_or_path){
	if(!PLITE_SITE_ROOT || $url_or_path[0] === '/' || is_url($url_or_path)){
		return $url_or_path;
	}
	//trim ./ prefix
	if(strpos($url_or_path, './') === 0){
		return PLITE_SITE_ROOT.substr($url_or_path, 2);
	}
	return PLITE_SITE_ROOT.$url_or_path;
}

/**
 * 前端静态资源打版本号
 * @param string $resource_file
 * @return string
 * @throws \Exception
 */
function patch_resource_version($resource_file){
	static $init_result = null;
	if(!isset($init_result)){
		$configs = get_config(PLITE_STATIC_VERSION_CONFIG_FILE, true);
		if($configs){
			$init_result = true;
			static_version_set($configs);
		}else{
			$init_result = false;
		}
	}
	if($init_result){
		$resource_file = static_version_patch($resource_file, $matched);
	}
	return $resource_file;
}
