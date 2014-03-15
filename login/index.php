<?php
require_once __DIR__.'/vendor/autoload.php';
require_once 'tokens.php';
session_start();

$fb_config = array(
	'appId' => $fb_app_id,
	'secret' => $fb_app_secret,
);
$facebook = new Facebook($fb_config);
$fb_user = $facebook->getUser();

$client = new Google_Client();
$client->setApplicationName($google_app_name);
$client->setClientId($google_client_id);
$client->setClientSecret($google_app_secret);
$client->setRedirectUri($google_app_redirect);
$client->setDeveloperKey($google_app_key);
$plus = new Google_PlusService($client);

if(isset($_GET['logout'])) {
	# sign out of iFF
	setcookie('iff-id',0,1,'/','.ifantasyfitness.com');
	setcookie('iff-google',0,1,'/','.ifantasyfitness.com');
	setcookie('iff-facebook',0,1,'/','.ifantasyfitness.com');
	
	# sign out of Facebook, if the user is signed in
	if($fb_user) {
		$fb_logout = $facebook->getLogoutUrl(array('next', "http://www.ifantasyfitness.com/login?logout_google"));
		header("Location: $fb_logout");
	} else {
		$_GET['input_google'] = true;
	}
}

if(isset($_GET['logout_google'])) {
	# sign out of Google
	echo '<div onload="gapi.auth.signOut();"></div>
	<div onload="window.location.assign("http://www.ifantasyfitness.com/logout?ok");"></div>';
}

// if already signed in, bypass
if(isset($_COOKIE['iff-id'])) header('Location: http://www.ifantasyfitness.com/home');

// Google based Login
if(isset($_GET['code'])) {
	$code = $_GET['code'];
	echo $code;
	echo "Trying to authenticate.";
	/* $client->authenticate($code);
	$me = $plus->people->get('me');
	setcookie('g-uuid',$me['id'],0,'/','.ifantasyfitness.com');
	setcookie('g-first',$me['name']['givenName'],0,'/','.ifantasyfitness.com');
	setcookie('g-last',$me['name']['familyName'],0,'/','.ifantasyfitness.com');
	header('Location: http://www.ifantasyfitness.com/setup/google'); */
}

// Facebook based Login
if($fb_user) {
	try {
		$user_profile = $facebook->api('/me','GET');
		setcookie('f-uuid',$user_profile['id'],0,'/','.ifantasyfitness.com');
		setcookie('f-first',$user_profile['first_name'],0,'/','.ifantasyfitness.com');
		setcookie('f-last',$user_profile['last_name'],0,'/','ifantasyfitness.com');
		header('Location: http://www.ifantasyfitness.com/setup/facebook');
	} catch(FacebookApiException $e) {
		$login_url = $facebook->getLoginUrl();
	}   
} else {
	$login_url = $facebook->getLoginUrl();
}

$title = 'Sign in';
include('../php/head.php');
?>
<div class="row">
	<div class="col-xs-12">
		<h1>Sign in</h1>
	</div>
</div>
<div class="row">
	<div class="col-md-8">
		<p class="lead">Please choose a social network to sign in with. You can add or remove additional networks at any time.</p>
	</div>
	<div class="col-md-4">
		<p><a href="<?=$login_url?>" class="btn btn-default btn-block" style="background:#3b5998;border:1px solid #3b5998;"><i class="fa fa-facebook"></i> Sign in with Facebook</a></p>
		<!-- <p><a href="#" class="btn btn-default btn-block" style="background:#55acee;border:1px solid #55acee;"><i class="fa fa-twitter"></i> Sign in with Twitter</a></p> Coming Soon! -->
		<p><a href="https://accounts.google.com/o/oauth2/auth?scope=profile&redirect_uri=http%3A%2F%2Fwww.ifantasyfitness.com%2Flogin&response_type=code&client_id=7336321947-ublsm7i9aa19ae7bn9fsvjeia3qudj3k.apps.googleusercontent.com" class="btn btn-default btn-block" style="background:#dd4b39;border:1px solid #dd4b39;"><i class="fa fa-google-plus"></i> Sign in with Google</a></p>
	</div>
</div>
<?php
include('../php/foot.php');
?>