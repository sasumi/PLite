<?php
namespace LFPhp\PLite;

use function LFPhp\Func\array_orderby;

/**
 * @param string $accept_language_str example: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6,ca;q=0.5,zh-TW;q=0.4,fr;q=0.3,pl;q=0.2,mt;q=0.1,de;q=0.1,vi;q=0.1
 * @return string[] language list sort by priority
 */
function lang_parse_accepts($accept_language_str = null){
	$accept_language_str = $accept_language_str ?: $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	if(!$accept_language_str){
		return [];
	}
	$languages = [];
	$ranges = explode(',', $accept_language_str);
	foreach($ranges as $lang_rng){
		if(preg_match('/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/', trim($lang_rng), $match)){
			if(!isset($match[2])){
				$match[2] = '1.0';
			}else{
				$match[2] = (string)floatval($match[2]);
			}
			$languages[] = [
				'priority' => $match[2],
				'language' => strtolower($match[1])
			];
		}
	}
	array_orderby($languages, 'priority', SORT_DESC);
	return array_column($languages, 'language');
}

function lang_find_matches($accepted, $available){
	$matches = [];
	$any = false;
	foreach($accepted as $acceptedQuality => $acceptedValues){
		$acceptedQuality = floatval($acceptedQuality);
		if($acceptedQuality === 0.0){
			continue;
		}
		foreach($available as $availableQuality => $availableValues){
			$availableQuality = floatval($availableQuality);
			if($availableQuality === 0.0)
				continue;
			foreach($acceptedValues as $acceptedValue){
				if($acceptedValue === '*'){
					$any = true;
				}
				foreach($availableValues as $availableValue){
					$matchingGrade = lang_match($acceptedValue, $availableValue);
					if($matchingGrade > 0){
						$q = (string)($acceptedQuality*$availableQuality*$matchingGrade);
						if(!isset($matches[$q])){
							$matches[$q] = [];
						}
						if(!in_array($availableValue, $matches[$q])){
							$matches[$q][] = $availableValue;
						}
					}
				}
			}
		}
	}
	if(count($matches) === 0 && $any){
		$matches = $available;
	}
	krsort($matches);
	return $matches;
}

/**
 * compare two language tags and distinguish the degree of matching
 * @param string $a
 * @param string $b
 * @return float|int
 */
function lang_match($a, $b){
	$a = explode('-', $a);
	$b = explode('-', $b);
	for($i = 0, $n = min(count($a), count($b)); $i < $n; $i++){
		if($a[$i] !== $b[$i]){
			break;
		}
	}
	return $i === 0 ? 0 : (float)$i/count($a);
}

function register_text_domain($domain, $path, $code_set = 'UTF-8'){
	bindtextdomain($domain, $path);
	if($code_set){
		bind_textdomain_codeset($domain, $code_set);
	}
}
