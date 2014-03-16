<?php
# Sign out of iFF
$cookies = array('id','facebook','twitter','google','exp');
foreach($cookies as $type) {
	setcookie("iff-".$type, 0, 1, '/', '.ifantasyfitness.com');
}

# sign out of Facebook first
require_once __DIR__.'/vendor/autoload.php';
require_once 'tokens.php';

$fb_config = array("appId" => $fb_app_id, "secret" => $fb_app_secret);
$facebook = new Facebook($fb_config);
$fb_logout = $facebook->getLogoutUrl(array("next" => "http://www.ifantasyfitness.com/login"));
header("Location: $fb_logout");
?>