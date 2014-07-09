<?php
$auth_url = "http://chat.blackphantom.de/api/authentication.php";
$loadMessage_url = "http://chat.blackphantom.de/api/loadLastMessages.php?limit=200";
define('SEND_MESSAGE_URL', "http://chat.blackphantom.de/api/sendMessage.php");
define('COOKIE_FILE', realpath('secure/cookiefile.txt'));

$conn = curl_init();

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

function nerdpoints($params)
{
	global $conn, $processedIds, $nerdpoints;
	if(count($params) < 2) return;
	$action = $params[0];
	$username = $params[1];
	if($action == 'add')
	{
		if(count($params) < 4) return;
		$points = intval($params[2]);
		$points = abs($points);
		
		$messageid = $params[3];
		
		post($conn, SEND_MESSAGE_URL, 'body='. urlencode($username . ' bekommt ' . $points . ' Nerdpunkte'));
		$nerdpoints->{$username} += $points;
	}
	else if($action == 'show')
	{
		post($conn, SEND_MESSAGE_URL, 'body='. urlencode($username . ' hat bereits ' . $nerdpoints->{$username} . ' Nerdpoints angesammelt'));
	}
}

$cmds = array('nerdpoints' => array('param_length' => 3));
$processedIds = json_decode(file_get_contents('secure/processed_ids.json'), true);
$nerdpoints = json_decode(file_get_contents('secure/nerdpoints.json'));

$config = json_decode(file_get_contents('secure/config.json'));


post($conn, $auth_url, 'username='. $config->username .'&password='. $config->password);
$messages = get($conn, $loadMessage_url);
foreach($messages->payload as $id=>$message)
{
	if(in_array($id, $processedIds['ids']))
	{
		break;
	}
	
	if(startsWith($message[2], '/'))
	{
		// Command issued
		$cmdArr = explode(' ', substr($message[2], 1));
		if(isset($cmds[$cmdArr[0]]))
		{
			$cmdName = $cmdArr[0];
			unset($cmdArr[0]);

			$cmdArr[] = $id;
			call_user_func($cmdName, array_values($cmdArr));
			$processedIds['ids'][] = $id;
		}
	}
}

file_put_contents('secure/processed_ids.json', json_encode($processedIds));
file_put_contents('secure/nerdpoints.json', json_encode($nerdpoints));
