<?php
namespace Lfphp\Plite;

use Lfphp\Plite\Exception\PLiteException;
use function LFPhp\Func\assert_file_in_dir;

/**
 * @throws \Lfphp\Plite\Exception\PLiteException
 */
function include_page($page_file, $params = [], $as_return = false){
	$f = PAGE_PATH."/$page_file";
	if(!is_file($f)){
		throw new PLiteException("Template no found($f)");
	}
	assert_file_in_dir($f, PAGE_PATH);
	if($as_return){
		ob_start();
	}
	if($params && is_array($params)){
		extract($params, EXTR_OVERWRITE);
	}
	include $f;
	if($as_return){
		$html = ob_get_contents();
		ob_clean();
		return $html;
	}
	return null;
}