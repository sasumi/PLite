<?php
namespace LFPhp\Plite;

use function LFPhp\Func\guid;

//ev1 => [[$id, payload,break_after], ...]
class __EV_CACHE__ {
	static $event_map = [];
}

/**
 * 触发事件
 * @param string $event
 * @param mixed ...$args
 * @return bool|null 返回 true:命中处理逻辑，false:命中处理逻辑，且有中断行为，null:未命中
 */
function fire_event($event, ...$args){
	$hit = null;
	foreach(__EV_CACHE__::$event_map as $ev => $handle_list){
		if($ev === $event){
			if(!$hit && $handle_list){
				$hit = true;
			}
			foreach($handle_list as list($id, $payload, $break_after)){
				if(call_user_func_array($payload, $args) === false && $break_after){
					return false;
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
 * @param bool $break_after 是否终端后续事件的执行
 * @return string
 */
function register_event($event, $payload, $break_after = false){
	$id = __NAMESPACE__.'-event-'.guid();
	if(!isset(__EV_CACHE__::$event_map[$event])){
		__EV_CACHE__::$event_map[$event] = [];
	}
	__EV_CACHE__::$event_map[$event][] = [$id, $payload, $break_after];
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
		foreach($handle_list as list($id, $payload, $break_after)){
			if($id !== $reg_id){
				$tmp[] = [$id, $payload, $break_after];
			}
		}
		__EV_CACHE__::$event_map[$ev] = $tmp;
	}
}