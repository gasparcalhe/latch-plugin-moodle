<?php

class Error {
	private $code;
	private $message;
	
	
	/**
	 * 
	 * @param string $json a Json representation of an error with "code" and "message" elements
	 */
	function __construct($json) {
		$json = is_string($json)? json_decode($json) : $json;
		if(array_key_exists("code", $json) && array_key_exists("message", $json)) {
			$this->code = $json->{"code"};
			$this->message = $json->{"message"};
		} else {
			error_log("Error creating error object from string " . $json);
		}
	}
	
	public function getCode() {
		return $this->code;
	}
	
	public function getMessage() {
		return $this->message;
	}
	
	/**
	 *
	 * @return JsonObject a Json object with the code and message of the error
	 */
	public function toJson() {
		$error = new JsonObject();
		$error.addProperty("code", $this->code);
		$error.addProperty("message", $this->message);
		return $error;
	}
}