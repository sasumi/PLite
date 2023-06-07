<?php
namespace LFPhp\PLite;

use LFPhp\PLite\Exception\PLiteException;
use function LFPhp\Func\guid;

//ev1 => [[$id, payload], ...]
class __EV_CACHE__ {
	static $event_map = [];
}

const EVENT_PAYLOAD_HIT = 1; //事件命中
const EVENT_PAYLOAD_BREAK_NEXT = 2; //事件命中，且中断后续执行
const EVENT_PAYLOAD_NULL = 3; //未命中事件

/**
 * 触发事件（事件触发参数采用引用方式传参，支持修改）
 * @param string $event
 * @return int 返回状态标记：EVENT_PAYLOAD_
 * @throws \LFPhp\PLite\Exception\PLiteException
 */
function fire_event($event, &$p1 = null, &$p2 = null, &$p3 = null, &$p4 = null, &$p5 = null, &$p6 = null){
	$hit = EVENT_PAYLOAD_NULL;
	$arg_limit = 7;
	$arg_count = func_num_args();
	if($arg_count > $arg_limit){
		throw new PLiteException("fire event arguments overload:$arg_count (limitation: $arg_limit)");
	}
	foreach(__EV_CACHE__::$event_map as $ev => $handle_list){
		if($ev === $event){
			foreach($handle_list as list($id, $payload)){
				$hit = EVENT_PAYLOAD_HIT;
				if($payload($p1, $p2, $p3, $p4, $p5, $p6) === false){
					return EVENT_PAYLOAD_BREAK_NEXT;
				}
			}
		}
	}
	return $hit;
}

/**
 * 注册事件
 * @param string $event
 * @param callable $payload
 * @return string
 */
function register_event($event, $payload){
	$id = __NAMESPACE__.'-event-'.guid();
	if(!isset(__EV_CACHE__::$event_map[$event])){
		__EV_CACHE__::$event_map[$event] = [];
	}
	__EV_CACHE__::$event_map[$event][] = [$id, $payload];
	return $id;
}

/**
 * 根据事件类型反注册事件
 * @param string $event
 */
function unregister_event_by_type($event){
	unset(__EV_CACHE__::$event_map[$event]);
}

/**
 * 根据id反注册事件
 * @param string $reg_id
 */
function unregister_event_by_id($reg_id){
	foreach(__EV_CACHE__::$event_map as $ev => $handle_list){
		$tmp = [];
		foreach($handle_list as list($id, $payload)){
			if($id !== $reg_id){
				$tmp[] = [$id, $payload];
			}
		}
		__EV_CACHE__::$event_map[$ev] = $tmp;
	}
}
