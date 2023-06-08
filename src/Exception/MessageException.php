<?php

namespace LFPhp\PLite\Exception;

use JsonSerializable;
use Throwable;

class MessageException extends PLiteException implements JsonSerializable {
	public $data;
	public $forward_url;
	public static $CODE_SUCCESS = 0;
	public static $CODE_ERROR = -1;

	public function __construct($message = "", $code = null, $data = null, $forward_url = '', Throwable $previous = null){
		$code = $code ?? self::$CODE_ERROR;
		parent::__construct($message, $code, $previous);
		$this->data = $data;
		$this->forward_url = $forward_url;
	}

	public static function successData($data, $message = 'success'){
		return new self($message, self::$CODE_SUCCESS, $data);
	}

	public static function errorMessage($message, $code = null, $data = null){
		$code = $code ?? self::$CODE_ERROR;
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
	 * @return mixed
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
