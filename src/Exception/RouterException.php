<?php
namespace LFPhp\PLite\Exception;

use Throwable;

class RouterException extends PLiteException {
	public function __construct($message = "", $code = PLITE_RSP_CODE_UNKNOWN_ERROR, Throwable $previous = null){
		parent::__construct($message, $code, $previous);
	}
}