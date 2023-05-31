<?php
global $a;
$a = 3234234;

function aa(){
	global $a;
	echo "aa A:$a";
}

function setA($v){
	global $a;
	$a = $v;
}