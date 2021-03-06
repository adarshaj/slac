<?php
	include_once 'jymengine.class.php';
	include_once 'config.php';
	include_once 'cachemanager.php';
	include_once 'utils/geo.php';
	session_start();
	if(isset($_REQUEST['action'])){
		if($debug) echo "I m in action.<br />";
		$action = $_REQUEST['action'];
		if(strcmp($_REQUEST['action'], "login")==0){
		
			if($debug) echo "I m in action login";
			$userdomain = explode("@",$_REQUEST['user']);
			$user = $userdomain[0];
			$password = $_REQUEST['password'];
			if(isset($_REQUEST['lat']))	{
				$lat = $_REQUEST['lat'];
			}
			else {
				$lat = -1;	
			}
			if(isset($_REQUEST['lon']))	{
				$lon = $_REQUEST['lon'];
			}
			else {
				$lon = -1;	
			}
			$city = reverseGeocodeCity($lat,$lon);
			$engine = new JYMEngine(CONSUMER_KEY, SECRET_KEY, $user, $password);
			if (!$engine->fetch_request_token()) die('Fetching request token failed');
			if (!$engine->fetch_access_token()) die('Fetching access token failed');
			if (!$engine->signon('Me on slac.in! Yay! :)')) die('Signon failed');
			$fh = fopen(".tmp/$user", "wb");
			fwrite($fh, serialize($engine));
			if(sizeof($engine)>10000)
				error_log("Damn! So big object got written check $user");
			$_SESSION['loggedIn']=1;
			$_SESSION['user']=$user;
			$_SESSION['city']=$city;
			if($debug) echo "Logged in!";
			if($debug) var_dump($_REQUEST);
			
			
			header('Location: /do.php?action=confirmLocation');
		}
		if(strcmp($_REQUEST['action'], "confirmLocation")==0){
			if(!isset($_SESSION['loggedIn'])||$_SESSION['loggedIn']!=1){
				exit();
			}	
			$city=$_SESSION['city'];
			echo<<<HTML
<!DOCTYPE html>
<html>
<head>
<title>Confirm Location - $city</title>
</head>
<body>
	<form action="do.php?action=locationConfirmed" method="post">
		<fieldset>
			<label for="city">Detected City:</label><input type="text" name="city" value="$city" />
			<br />
			<input type="submit" name="continue" value="Confirm" />
		</fieldset>
	</form>
</body>
</html>
HTML;
		}
		if(strcmp($_REQUEST['action'], "locationConfirmed")==0){
			if(!isset($_SESSION['loggedIn'])||$_SESSION['loggedIn']!=1){
				exit();
			}
			$user = $_SESSION['user'];
			$city = $_REQUEST['city'];
			postLoginAction($user, $city);
			header('Location: /');
		}
		if(strcmp($_REQUEST['action'], "getContacts")==0){
			if(!isset($_SESSION['loggedIn'])||$_SESSION['loggedIn']!=1){
				exit();
			}
			$user = $_SESSION['user'];
			$fh = fopen(".tmp/$user", "rb");
			$serialized_data= fread($fh, 10000);
			$engine=unserialize($serialized_data);
			echo $engine->fetch_contact_list();
		}
		if(strcmp($_REQUEST['action'], "showInfo")==0){
			if(!isset($_SESSION['loggedIn'])||$_SESSION['loggedIn']!=1){
				exit();
			}
			$user = $_REQUEST['user'];
			echo getUserInfo($user);
		}
		if(strcmp($_REQUEST['action'], "send")==0){
			if(!isset($_SESSION['loggedIn'])||$_SESSION['loggedIn']!=1){
				exit();
			}
			$user = $_SESSION['user'];
			$fh = fopen(".tmp/$user", "rb");
			$serialized_data= fread($fh, 10000);
			if($debug) var_dump($serialized_data);
			$engine=unserialize($serialized_data);
			if($debug) var_dump($engine);
			$msg = $_REQUEST['msg'];
			$to = $_REQUEST['to'];
			if($debug) echo "Sending $msg to $to";
			$engine->send_message($to, json_encode($msg));
			fclose($fh);
			$fh = fopen(".tmp/$user", "wb");
			fwrite($fh, serialize($engine));
		}
		if(strcmp($_REQUEST['action'], "logout")==0){
			if(!isset($_SESSION['loggedIn'])||$_SESSION['loggedIn']!=1){
				exit();
			}

			$user = $_SESSION['user'];
			$fh = fopen(".tmp/$user", "rb");
			$serialized_data= fread($fh, 10000);
			$engine=unserialize($serialized_data);
			$engine->signoff();
			unlink(".tmp/$user");
			session_destroy();
			postLogoutAction($user);
			header('Location: /');
		}
	}
?>
