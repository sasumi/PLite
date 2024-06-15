<?php
namespace LFPhp\PLite;

use function LFPhp\Func\event_register;

const PATTERN_HOLDER = 'PATTERN_HOLDER';

/**
 * 将 str 中的 $1, $2 替换成相应 matches 数组中对应下标的数据
 * @param string $str
 * @param string[] $ms
 * @return string
 */
function __reg_var_replace($str, $ms = []){
	foreach($ms as $idx => $v){
		$str = str_replace("\${$idx}", $v, $str);
	}
	return $str;
}

/**
 * 从规则模板 $1/$2 结合给定URI： ctrl/act 生成替换数组：['$1'=>'ctrl', '$2'=> 'act']
 * 规则模板可以是 $1/$2，ctrl/$2， path/$1/act 等格式，如果格式不匹配，返回空数组
 * @param string $uri_pattern
 * @param string $uri
 * @param array $replacement 用来替换占位符的映射数组
 * @return bool 是否匹配
 */
function __rewrite_match_uri($uri_pattern, $uri, &$replacement = []){
	if(strcasecmp($uri_pattern, $uri) === 0){
		return true;
	}
	$reg_pat = preg_replace('/(\$\d+)/', PATTERN_HOLDER, $uri_pattern);
	$reg_pat = str_replace(PATTERN_HOLDER, '(.+)', preg_quote($reg_pat));
	if(preg_match("#$reg_pat#", $uri, $uri_segments)){
		$uri_segments = array_slice($uri_segments, 1);
		preg_replace_callback('/(\$\d+)/', function($ms) use (&$uri_segments, &$replacement){
			$replacement[$ms[1]] = array_shift($uri_segments);
		}, $uri_pattern);
		return true;
	}
	return false;
}

/**
 * 绑定 url() 函数 结合给定重写映射规则生成最终URL
 * @param array $mapping mapping 规则请参考 [/REWRITE.md](REWRITE.md)
 * @return void
 */
function rewrite_bind_url($mapping){
	event_register(EVENT_ROUTER_URL, function(&$url, $uri, $param) use ($mapping){
		foreach($mapping as $url_pattern => list($uri_pattern, $param_pattern)){
			//格式为：
			// '$1' => 'value1'
			// '$2' => 'value2'
			// 可以从 _uri 规则，或者 $_param 规则中获取
			// 从规则 $1/$2 结合给定URI： ctrl/act 生成替换数组：['$1'=>'ctrl', '$2'=> 'act']
			$replacement = [];
			$match_param_keys = []; //已经匹配的param key, 剩余未替换变量，统一补充到尾部成为 query string
			if(__rewrite_match_uri($uri_pattern, $uri, $replacement)){
				foreach($param_pattern ?: [] as $k => $holder){
					if(!isset($param[$k])){
						//参数不满足，匹配下一个规则
						continue 2;
					}
					$replacement[$holder] = $param[$k];
					$match_param_keys[] = $k;
				}

				//表达式转换成占位符模式，
				//如：{W}/{W}.html
				$idx = 0;
				$url = preg_replace_callback('/{\w+}/', function() use (&$idx, $replacement){
					$replace_key = '$'.(++$idx);
					return isset($replacement[$replace_key]) ? urlencode($replacement[$replace_key]) : '';
				}, $url_pattern);

				//剩余参数补充到query string
				$ext_param = $param;
				foreach($match_param_keys as $k){
					unset($ext_param[$k]);
				}
				if($ext_param){
					$url .= (strpos($url, '?') !== false ? '&' : '?').http_build_query($ext_param);
				}
				//清理掉没用的 {}
				if(preg_match('/{\w+}/', $url)){
					$url = preg_replace('/{\w+}/', '', $url);
				}
				$url = PLITE_SITE_ROOT.$url;
				return true;
			}
		}
		//no match
		return false;
	});
}

/**
 * 处理当前请求path info
 * @param array $mapping mapping 规则请参考 [/REWRITE.md](REWRITE.md)
 * @param string|null $path_info
 * @return bool 是否命中规则
 * @throws \Exception
 */
function rewrite_resolve_path($mapping, $path_info = null){
	//解析识别当前访问URL
	$path_info = $path_info === null ? $_SERVER['PATH_INFO'] : $path_info;
	$path_info = trim($path_info, '/');
	foreach($mapping as $url_pattern => list($uri_pattern, $param_pattern)){
		//直接写死URL方式
		if(!preg_match_all('/{(\w+)}/', $url_pattern, $all_matches)){
			//当前页面地址包含规则地址
			if(stripos($path_info, $url_pattern) === 0){
				$ps = array_merge($_GET, $param_pattern ?: []);
				set_router($uri_pattern, $ps);
				return true;
			}else{
				continue;
			}
		}

		//将 {w} {d} 更换成真正的正则表达式
		$idx = 0;
		$url_regexp = preg_replace_callback("/".PATTERN_HOLDER."/", function() use (&$idx, $all_matches){
			$flag = strtolower($all_matches[1][$idx++]);
			switch($flag){
				case 'w':
					return '(\w+)';
				case 'd':
					return '(\d+)';
				default:
					throw new \Exception("Pattern flag no support: ".$flag);
			}
		}, preg_quote(preg_replace('/{\w+}/', PATTERN_HOLDER, $url_pattern)));

		//从开始对比
		$url_regexp = "#^$url_regexp#u";
		if(preg_match($url_regexp, $path_info, $ms)){
			$uri = __reg_var_replace($uri_pattern, $ms);
			$ps = $_GET;
			foreach($param_pattern ?: [] as $k => $v){
				$k = __reg_var_replace($k, $ms);
				$v = __reg_var_replace($v, $ms);
				$ps[$k] = urldecode($v);
			}
			set_router($uri, $ps);
			return true;
		}
	}
	return false;
}

/**
 * 通过 pathinfo 设置路由映射
 * @param array $mapping mapping 规则请参考 [/REWRITE.md](REWRITE.md)
 * @param string|null $path_info pathinfo 信息，缺省由 $_SERVER['PATH_INFO'] 中获取
 * @return void
 * @throws \Exception
 */
function rewrite_setup($mapping, $path_info = null){
	//1. 绑定 url() 函数
	rewrite_bind_url($mapping);

	//2. 处理当前请求
	rewrite_resolve_path($mapping, $path_info);
}
