<?php

namespace LFPhp\PLite\Exception;

use JsonSerializable;
use Throwable;

class MessageException extends PLiteException implements JsonSerializable {
	public $forward_url;
	public static $CODE_DEFAULT_SUCCESS = 0;
	public static $CODE_DEFAULT_ERROR = -1;

	/**
	 * @param string $message
	 * @param null $code
	 * @param null $data
	 * @param string $forward_url
	 * @param \Throwable|null $previous
	 */
	public function __construct($message = "", $code = null, $data = null, $forward_url = '', Throwable $previous = null){
		$code = $code ?? self::$CODE_DEFAULT_ERROR;
		parent::__construct($message, $code, $previous);
		$this->data = $data;
		$this->forward_url = $forward_url;
	}

	/**
	 * make success message
	 * @param $data
	 * @param string $message
	 * @param string $forward_url
	 * @return \LFPhp\PLite\Exception\MessageException
	 */
	public static function successData($data, $message = 'success', $forward_url = ''){
		return new self($message, self::$CODE_DEFAULT_SUCCESS, $data, $forward_url);
	}

	/**
	 * make error message
	 * @param $message
	 * @param null $code
	 * @param null $data
	 * @return \LFPhp\PLite\Exception\MessageException
	 */
	public static function errorMessage($message, $code = null, $data = null){
		$code = $code ?? self::$CODE_DEFAULT_ERROR;
		return new self($message, $code, $data);
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
	 * @return string
	 */
	public function getForwardUrl(){
		return $this->forward_url;
	}

	/**
	 * @param mixed $forward_url
	 */
	public function setForwardUrl($forward_url): void{
		$this->forward_url = $forward_url;
	}
}
