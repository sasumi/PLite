<?php

namespace LFPhp\PLite\Exception;

use JsonSerializable;
use Throwable;

class MessageException extends PLiteException implements JsonSerializable {
	public $data;
	public $forward_url;
	public static $CODE_DEFAULT_SUCCESS = 0; //默认成功码
	public static $CODE_DEFAULT_ERROR = -1; //默认失败码

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
	 * 成功message
	 * @param $data
	 * @param string $message
	 * @param string $forward_url
	 * @return \LFPhp\PLite\Exception\MessageException
	 */
	public static function successData($data, $message = 'success', $forward_url = ''){
		return new self($message, self::$CODE_DEFAULT_SUCCESS, $data, $forward_url);
	}

	/**
	 * @param $message
	 * @param null $code
	 * @param null $data
	 * @return \LFPhp\PLite\Exception\MessageException
	 */
	public static function errorMessage($message, $code = null, $data = null){
		$code = $code ?? self::$CODE_DEFAULT_ERROR;
		return new self($message, $code, $data);
	}

	public function toArray(){
		return [
			'code'        => $this->getCode(),
			'message'     => $this->getMessage(),
			'data'        => $this->getData(),
			'forward_url' => $this->getData(),
		];
	}

	public function jsonSerialize(){
		return $this->toArray();
	}

	/**
	 * @return mixed|null
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * @param mixed|null $data
	 */
	public function setData($data): void{
		$this->data = $data;
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
