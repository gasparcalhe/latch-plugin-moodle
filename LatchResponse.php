<?php
require_once("Error.php");

/**
 * This class models a response from any of the endpoints in the Latch API.
 * It consists of a "data" and an "error" elements. Although normally only one of them will be
 * present, they are not mutually exclusive, since errors can be non fatal, and therefore a response
 * could have valid information in the data field and at the same time inform of an error.
 *
 * @author Jose Palazon <jose@11paths.com>
 *
 */
class LatchResponse {

	public $data = null;
	public $error = null;

	/**
	 *
	 * @param $json a json string received from one of the methods of the Latch API
	 */
	public function __construct($jsonString) {
		$json = json_decode($jsonString);
		if(!is_null($json)) {
			if (array_key_exists("data", $json)) {
				$this->data = $json->{"data"};
			}
			if (array_key_exists("error", $json)) {
				$this->error = new Error($json->{"error"});
			} 
		}
	}
	
	/**
	 *
	 * @return JsonObject the data part of the API response
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 *
	 * @param $data the data to include in the API response
	 */
	public function setData($data) {
		$this->data = json_decode($data);
	}

	/**
	 * 
	 * @return Error the error part of the API response, consisting of an error code and an error message
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 *
	 * @param $error an error to include in the API response
	 */
	public function setError($error) {
		$this->error = new Error($error);
	}

	/**
	 *
	 * @return JsonObject a Json object with the data and error parts set if they exist
	 */
	public function toJSON() {
		$response = array();
		if(!empty($this->data)) {
			$response["data"] = $data;
		}
		
		if(!empty($error)) {
			$response["error"] = $error;
		} 
		return json_encode($response);
	}
}