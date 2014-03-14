<?php
// if already signed in, bypass
if(isset($_COOKIE['iff-id'])) header('Location: http://www.ifantasyfitness.com/home');

require_once __DIR__.'/vendor/autoload.php';
session_start();

// Google based Login

$client = new Google_Client();
$client->setApplicationName('iFantasyFitness');
$client->setClientId('7336321947-cl9s8dmrq9nakagma9bo4o9n7snaegtq.apps.googleusercontent.com');
$client->setClientSecret('QXSk5mtGkQJ2M7JMgq-LOYLC');
$client->setRedirectUri('http://www.ifantasyfitness.com/login');
$client->setDeveloperKey('AIzaSyAXtEAqGuaitrHrsHpYzvYft3gpB14bzeA');
$plus = new Google_PlusService($client);

if(isset($_GET['code'])) {
	$client->authenticate();
	$me = $plus->people->get('me');
	setcookie('g-uuid',$me['id'],0,'/','.ifantasyfitness.com');
	setcookie('g-first',$me['name']['givenName'],0,'/','.ifantasyfitness.com');
	setcookie('g-last',$me['name']['familyName'],0,'/','.ifantasyfitness.com');
	header('Location: http://www.ifantasyfitness.com/setup/google');
}

// Facebook based Login

$fb_config = array(
	'appId' => '447329098726179',
	'secret' => '31df64870a628279ea578979e74383ae',
);
$facebook = new Facebook($fb_config);
$user_id = $facebook->getUser();
if($user_id) {
	try {
		$user_profile = $facebook->api('/me','GET');
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
		<p><a href="<?=$login_url?>" class="btn btn-default btn-block" style="background:#3b5998;border:1px solid #3b5998;"><i class="fa fa-facebook"></i> Sign in with Facebook - coming soon, hang tight!</a></p>
		<!-- <p><a href="#" class="btn btn-default btn-block" style="background:#55acee;border:1px solid #55acee;"><i class="fa fa-twitter"></i> Sign in with Twitter</a></p> Coming Soon! -->
		<p><a href="https://accounts.google.com/o/oauth2/auth?scope=profile&redirect_uri=http%3A%2F%2Fwww.ifantasyfitness.com%2Flogin&response_type=code&client_id=7336321947-cl9s8dmrq9nakagma9bo4o9n7snaegtq.apps.googleusercontent.com" class="btn btn-default btn-block" style="background:#dd4b39;border:1px solid #dd4b39;"><i class="fa fa-google-plus"></i> Sign in with Google</a></p>
	</div>
</div>
<?php
include('../php/foot.php');
?>