<?php
$auth_url = "http://chat.blackphantom.de/api/authentication.php";
$loadMessage_url = "http://chat.blackphantom.de/api/loadLastMessages.php?limit=200";
define('SEND_MESSAGE_URL', "http://chat.blackphantom.de/api/sendMessage.php");
define('COOKIE_FILE', realpath('secure/cookiefile.txt'));

$sentences_bot = array('Gehts um mich?', 'Was ist mit mir?', 'Redet ihr von mir?');

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

function sendMessage($message)
{
	global $conn;
	post($conn, SEND_MESSAGE_URL, 'body='. urlencode(html_entity_decode($message)));
}

function nerdpoints($params)
{
	global $conn, $processedIds, $nerdpoints;
	if(count($params) < 2) return;
	$action = $params[0];
	$username = $params[1];
	if($action == 'add')
	{
		if(count($params) < 5) return;
		$points = intval($params[2]);
		$points = abs($points);
		
		$sender = $params[4];
		
		if(!isset($nerdpoints->{$username}))
		{
			sendMessage('Der User `' .$username . '\' existiert nicht.');
			return;
		}
		if($sender == $username)
		{
			sendMessage('Du kannst dir nicht selbst Nerdpoints geben!');
			return;
		}
		
		
		sendMessage($username . ' bekommt ' . $points . ' Nerdpunkte');
		$nerdpoints->{$username} += $points;
	}
	else if($action == 'show')
	{
		if(isset($nerdpoints->{$username}))
		{
			sendMessage($username . ' hat bereits ' . $nerdpoints->{$username} . ' Nerdpoints angesammelt');
		}
		else 
		{
			sendMessage('Der User `' .$username . '\' existiert nicht.');
		}
	}
}

function afk($params)
{
	if(count($params) != 2)
		return;
	
	$id = $params[0];
	$sender = $params[1];
	
	sendMessage($sender . ' ist jetzt Away from Keyboard.');
}

function joke($params)
{
	if(count($params) > 2)
	{
		unset($params[1], $params[2]);
		$joke = json_decode(file_get_contents('http://api.icndb.com/jokes/random?limitTo=['. implode(',', array_values($params)) .']'));
	}
	else
	{
		$joke = json_decode(file_get_contents('http://api.icndb.com/jokes/random'));
	}
	
	if($joke->type != 'success')
	{
		sendMessage('Tut mir leid, mir fällt gerade keiner ein...');
	}
	else
	{
		sendMessage($joke->value->joke);		
	}
}

$cmds = array('nerdpoints' => '', 'afk' => '', 'joke' => '');
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
			$cmdArr[] = $message[0];
			call_user_func($cmdName, array_values($cmdArr));
			$processedIds['ids'][] = $id;
		}
	}
	else if(stristr($message[2], 'bot') !== false)
	{
		sendMessage($sentences_bot[rand(0, count($sentences_bot) - 1)]);
		$processedIds['ids'][] = $id;
	}
}

file_put_contents('secure/processed_ids.json', json_encode($processedIds));
file_put_contents('secure/nerdpoints.json', json_encode($nerdpoints));
