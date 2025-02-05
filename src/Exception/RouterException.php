<?php
namespace LFPhp\PLite\Exception;

use Throwable;
use function LFPhp\Func\get_client_ip;
use function LFPhp\Func\http_get_current_page_url;

class RouterException extends PLiteException {
	public function __construct($message = "", $code = null, Throwable $previous = null){
		parent::__construct($message, $code, $previous);
		$this->data = [
			'current_url' => http_get_current_page_url(),
			'client_ip'   => get_client_ip(),
			'user_agent'  => $_SERVER['HTTP_USER_AGENT'],
		];
	}
}
