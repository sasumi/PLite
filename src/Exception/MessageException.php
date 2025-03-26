<?php

namespace LFPhp\PLite\Exception;

use Exception;
use JsonSerializable;
use Throwable;

class MessageException extends PLiteException implements JsonSerializable {
	public $forward_url;

	//default success message
	public static $MESSAGE_DEFAULT_SUCCESS = 'success';

	//default error message
	public static $MESSAGE_DEFAULT_ERROR = 'error';

	//
	public static $CODE_SUCCESS = 0;
	public static $CODE_ERROR = -1;

	//redirect timeout
	public static $REDIRECT_TIMEOUT = 3;

	/**
	 * @param string $message
	 * @param null $code exception code, use CODE_ERROR as default
	 * @param null $data
	 * @param string $forward_url
	 * @param \Throwable|null $previous
	 */
	public function __construct($message = "", $code = null, $data = null, $forward_url = '', Throwable $previous = null){
		$code = $code === null ? self::$CODE_ERROR : $code;
		parent::__construct($message, $code, $previous);
		$this->data = $data;
		$this->forward_url = $forward_url;
	}

	/**
	 * convert other exception to MessageException
	 * @param \Exception $e
	 * @param bool $success
	 * @return \Exception|\LFPhp\PLite\Exception\MessageException
	 */
	public static function fromException(Exception $e, $success){
		if($e instanceof MessageException){
			return $e;
		}
		$data = method_exists($e, 'getData') ? $e->getData() : null;
		$forward_url = method_exists($e, 'getForwardUrl') ? $e->getForwardUrl() : null;
		return $success ?
			self::successData($data, $e->getMessage(), $forward_url) :
			self::errorMessage($e->getMessage(), $e->getCode(), $data, $forward_url);
	}

	/**
	 * make success message exception
	 * @param mixed $data
	 * @param string|null $message if message is null, use default success message
	 * @param string $forward_url
	 * @return \LFPhp\PLite\Exception\MessageException
	 */
	public static function successData($data, $message = null, $forward_url = ''){
		$message = $message ?? self::$MESSAGE_DEFAULT_SUCCESS;
		return new self($message, self::$CODE_SUCCESS, $data, $forward_url);
	}

	/**
	 * make error message exception
	 * @param string|null $message if message is null, use default error message
	 * @param int|null $code error code, if code eq to self::CODE_DEFAULT_SUCCESS, code will convert to CODE_DEFAULT_ERROR
	 * @param mixed|null $data
	 * @param string $forward_url
	 * @return \LFPhp\PLite\Exception\MessageException
	 */
	public static function errorMessage($message = null, $code = null, $data = null, $forward_url = ''){
		//fix error code
		$code = $code == self::$CODE_SUCCESS ? self::$CODE_ERROR : $code;
		$message = $message ?? self::$MESSAGE_DEFAULT_ERROR;
		return new self($message, $code, $data, $forward_url);
	}

	/**
	 * patch forward_url data
	 * @return array
	 */
	public function toArray(){
		$exp = parent::toArray();
		$exp['forward_url'] = $this->getForwardUrl();
		return $exp;
	}

	/**
	 * get forward url
	 * @return string
	 */
	public function getForwardUrl(){
		return $this->forward_url;
	}

	/**
	 * set forward url
	 * @param string $forward_url
	 */
	public function setForwardUrl($forward_url): void{
		$this->forward_url = $forward_url;
	}
}
