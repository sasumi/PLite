<?php
namespace Lfphp\Plite;

use function LFPhp\Func\guid;

const EVENT_APP_START = 'EVENT_APP_START';
const EVENT_APP_TERMINAL = 'EVENT_APP_TERMINAL';

//ev1 => [[$id, payload,break_after], ...]
class __EV_CACHE__ {
	static $event_map = [];
}

function fire_event($event, ...$args){
	foreach(__EV_CACHE__::$event_map as $ev => $handle_list){
		if($ev === $event){
			foreach($handle_list as list($id, $payload, $break_after)){
				if(call_user_func_array($payload, $args) === false && $break_after){
					return;
				}
			}
		}
	}
}

/**
 * 注册事件
 * @param $event
 * @param $payload
 * @param false $break_after
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
 * @param $event
 */
function unregister_event_by_type($event){
	unset(__EV_CACHE__::$event_map[$event]);
}

/**
 * 根据id反注册事件
 * @param $reg_id
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