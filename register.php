<?php

/*
*
* this is where registration and update on external app is done
*
*/

session_start();

class Register {

	public $this_site_name;
	public $url;
	public $cats;

	public function __construct() {
		$this->setJSONSettings();
	}

	public function setJSONSettings() {

		$file = 'config.json';
		$json = json_decode(file_get_contents($file));
		$success_type = 'update_';

		if (!isset($json)) {
			$json = new stdClass();
			$json->Name = false;
			$json->URL = false;
			$json->cats = new stdClass();
			$success_type = 'registration_';
		}

		$name = $json->Name;
		$url = $json->URL;
		$tmp_cats = (array)$json->cats;

		
		

		
		if (isset($_POST['wp_site_name']) && $json->Name != $_POST['wp_site_name']) {
			
			$json->Name = $_POST['wp_site_name'];
		}
		

		if (isset($_POST['url']) && $json->URL != $_POST['url']) {

			$json->URL = $_POST['url'];
		}

		if (isset($_POST['categories'])) {
			$cats = $_POST['categories'];
			// add new categories
			foreach ($cats as $key => $cat) {
				if (!isset($json->cats) || (!in_array($cat, (array)$json->cats))) {
					$json->cats->$key = $cat;
				}
			}
			// unsets categories that user have unchecked
			if ($json->cats) {
				foreach ($json->cats as $key => $json_cat) {
					if (!in_array($json_cat, $cats)) {
						unset($json->cats->$key);
					}
				}
			}
		}

		//send options to external web app
		if ($name != $_POST['wp_site_name'] || $url != $_POST['url'] || $tmp_cats != $_POST['categories']) {

			$registration = $this->registerOnWebApp($_POST['url'], $json);

			$_SESSION['chasing_bug'] = $registration;

			$_SESSION['status'] = $success_type . json_decode($registration)->message;

		} else {

			$_SESSION['status'] = 'no_change';
		}

		file_put_contents($file, json_encode($json, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));

		
		header("Location: " . $_POST['_wp_http_referer']);
	}

	public function registerOnWebApp($web_app_url, $json) {
		
		$web_app_url = rtrim($web_app_url, "/");

		$url = $this->setURL();

		$username = $_POST['auth_username'];
		$password = $_POST['auth_password'];

		$token = $this->getUserToken($username, password_hash($password, PASSWORD_DEFAULT), $web_app_url, $url);


		// if token is not valid redirect to settings page and show message
		if (property_exists(json_decode($token), 'message') && json_decode($token)->message == 'auth failed') {

			$_SESSION['status'] = 'auth_failed';


			header("Location: " . $_POST['_wp_http_referer']);
			exit;
		}


		$data_array =  array(
			"url"		  => $url  . '/wp-json/posts-stats/v2/all_posts',
			"json_data"	  => $json,
		);

	   	return $this->setcURL($web_app_url, $data_array, '/api/options', $token);
	}

	public function getUserToken($username, $password, $web_app_url) {

		$data_array =  array(
			"username"		  => $username,
			"password"		  => $password,
		);

		return $this->setcURL($web_app_url, $data_array, '/authenticate');
	}
 
	public function setcURL($web_app_url, $data_array, $path, $token = false) {

		if ($token != false) {
			$token = json_decode($token)->token;
		}

		$data_array = json_encode($data_array);

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_POST, 1);

		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_array);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);

		curl_setopt($curl, CURLOPT_URL, $web_app_url . $path);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/json',
		    'Content-Length: ' . strlen($data_array),
			'Authorization: Bearer ' . $token,
		));

		$result = curl_exec($curl);
		var_dump($result);die;
	   	if(!$result){die("Connection Failure");}
	   	curl_close($curl);
	   	return $result;
	}

	public function setURL() {

		return sprintf(
		    "%s://%s",
		    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
		    $_SERVER['SERVER_NAME']
		);
	}
}

$register = new Register();
