<?php
namespace LFPhp\PLite;

use LFPhp\PLite\Exception\PLiteException;
use function LFPhp\Func\assert_file_in_dir;

/**
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function include_page($page_file, $params = [], $as_return = false){
	fire_event(EVENT_BEFORE_INCLUDE_PAGE, $page_file, $params);
	$f = PLITE_PAGE_PATH."/$page_file";
	if(!is_file($f)){
		throw new PLiteException("Template no found($f)");
	}
	assert_file_in_dir($f, PLITE_PAGE_PATH);
	if($as_return){
		ob_start();
	}
	if($params && is_array($params)){
		extract($params, EXTR_OVERWRITE);
	}
	include $f;
	fire_event(EVENT_AFTER_INCLUDE_PAGE, $f, $params);
	if($as_return){
		$html = ob_get_contents();
		ob_clean();
		return $html;
	}
	return null;
}