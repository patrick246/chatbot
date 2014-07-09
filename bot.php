<?php
$auth_url = "http://chat.blackphantom.de/api/authentication.php";
$loadMessage_url = "http://chat.blackphantom.de/api/loadLastMessages.php?limit=50";
$sendMessage_url = "http://chat.blackphantom.de/api/sendMessage.php";
define('COOKIE_FILE', realpath('secure/cookiefile.txt'));

function get($c, $url)
{
	$conn =& $c;
	curl_setopt($conn, CURLOPT_URL, $url);
	curl_setopt($conn, CURLOPT_COOKIEFILE, COOKIE_FILE);
	curl_setopt($conn, CURLOPT_COOKIEJAR, COOKIE_FILE);
	curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($conn, CURLOPT_HEADER, false);
	return json_decode(curl_exec($conn));
}

function post($c, $url, $data)
{
	
	$conn =& $c;
	
	curl_setopt($conn, CURLOPT_URL, $url);
	curl_setopt($conn, CURLOPT_COOKIEFILE, COOKIE_FILE);
	curl_setopt($conn, CURLOPT_COOKIEJAR, COOKIE_FILE);
	curl_setopt($conn, CURLOPT_POSTFIELDS, $data);
	curl_setopt($conn, CURLOPT_POST, 1);
	
	curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($conn, CURLOPT_HEADER, false);
	
	return json_decode(curl_exec($conn));
}

function startsWith($haystack, $needle)
{
	return $needle === "" || strpos($haystack, $needle) === 0;
}

$conn = curl_init();
$config = json_decode(file_get_contents('secure/config.json'));

var_dump($config);

post($conn, $auth_url, 'username='. $config->username .'&password='. $config->password);
$messages = get($conn, $loadMessage_url);
foreach($messages->payload as $message)
{
	if(startsWith($message[2], '/'))
	{
		// Command issued
		
	}
}
