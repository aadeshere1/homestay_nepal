<?php

function authenticate(\Slim\Route $route){
	$headers = apache_request_headers();
	$response = array();
	$app = \Slim\Slim::getInstance();

	//verifying Authorization header
	if (isset($headers['Authorization'])){
		$db = new DbHandler();

		//get the api key
		$api_key = $headers['Authorization'];
		//validating api key
		if ($db->isValidOwnerApiKey($api_key)){
			global $owner_id;
			//get owner primary key id
			$owner_id = $db->getOwnerId($api_key);
		} elseif(!$db->isValidTouristApiKey($api_key)) {
			global $tourist_id;
			//get tourist primary key id
			$tourist_id = $db->getTouristId($api_key);
		} else {
			$response["error"] = true;
			$response["message"] = "Access Denied. Invalid Api Key";
			echoRespnse(401, $response);
			$app->stop();
		}
	} else {
		// api key is missing in header
		$response["error"] = true;
		$response["message"] = "Api key is missing";
		echoRespnse(400, $response);
		$app->stop();
	}
}