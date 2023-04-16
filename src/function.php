<?php

function get_namespace($class){
	$last_slash = strrpos($class, '\\');
	if($last_slash>=0){
		return substr($class, 0, $last_slash);
	}
	return $class;
}

function get_class_without_ns($class){
	$last_slash = strrpos($class, '\\');
	if($last_slash>=0){
		return substr($class, $last_slash+1);
	}
	return $class;
}
