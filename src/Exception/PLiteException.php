<?php

namespace LFPhp\PLite\Exception;

use Exception;
use JsonSerializable;

/**
 * PLite Exception
 * extends with data store
 */
class PLiteException extends Exception implements JsonSerializable {
	public $data;

	public function toArray(){
		return [
			'code'    => $this->getCode(),
			'message' => $this->getMessage(),
			'data'    => $this->getData(),
		];
	}

	public function jsonSerialize(){
		return $this->toArray();
	}

	/**
	 * @return mixed
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * @param mixed $data
	 */
	public function setData($data){
		$this->data = $data;
	}
}
