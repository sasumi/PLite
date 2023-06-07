<?php
namespace LFPhp\PLite\Exception;

use Throwable;

class RouterException extends PLiteException {
	public function __construct($message = "", $code = null, Throwable $previous = null){
		parent::__construct($message, $code, $previous);
	}
}