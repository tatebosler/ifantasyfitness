<?php
require __DIR__.'/vendor/autoload.php';
require 'tokens.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$client = new Google_Client();
$client->setApplicationName($google_app_name);
$client->setClientId($google_client_id);
$client->setClientSecret($google_app_secret);
$client->setRedirectUri('postmessage');

$plus = new Google_PlusService($client);

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__,
));
$app->register(new Silex\Provider\SessionServiceProvider());

// Initialize a session for the current user, and render index.html.
$app->get('/', function () use ($app) {
	# Facebook login
	require __DIR__.'/vendor/autoload.php';
	require 'tokens.php';
	$fb_config = array('appId' => $fb_app_id,'secret' => $fb_app_secret,'allowSignedRequest' => false );
	$facebook = new Facebook($fb_config);
	$fb_user = $facebook->getUser();
	if($fb_user) {
		try {
			$fb_profile = $facebook->api("/me","GET");
			header("Location: http://www.ifantasyfitness.com/setup?provider=facebook&uid=".$fb_profile['id']."&first=".$fb_profile['first_name']."&last=".$fb_profile['last_name']."&rq=".time());
		} catch (FacebookApiException $fb_execption) {
			define("FB_LOGIN_URL", $facebook->getLoginUrl());
		}
	} else {
		define("FB_LOGIN_URL", $facebook->getLoginUrl());
	}
    $state = md5(rand());
    $app['session']->set('state', $state);
    return $app['twig']->render('index.html', array(
        'CLIENT_ID' => '7336321947-ublsm7i9aa19ae7bn9fsvjeia3qudj3k.apps.googleusercontent.com',
        'STATE' => $state,
        'APPLICATION_NAME' => 'Sign in to iFantasyFitness',
        'FACEBOOK_LOGIN' => FB_LOGIN_URL
    ));
});

// Upgrade given auth code to token, and store it in the session.
// POST body of request should be the authorization code.
// Example URI: /connect?state=...&gplus_id=...
$app->post('/connect', function (Request $request) use ($app, $client) {
    $token = $app['session']->get('token');

    if (empty($token)) {
        // Ensure that this is no request forgery going on, and that the user
        // sending us this connect request is the user that was supposed to.
        if ($request->get('state') != ($app['session']->get('state'))) {
            return new Response('Invalid state parameter', 401);
        }

        // Normally the state would be a one-time use token, however in our
        // simple case, we want a user to be able to connect and disconnect
        // without reloading the page.  Thus, for demonstration, we don't
        // implement this best practice.
        $app['session']->set('state', '');

        $code = $request->getContent();
        // Exchange the OAuth 2.0 authorization code for user credentials.
        $client->authenticate($code);
        $token = json_decode($client->getAccessToken());

        // You can read the Google user ID in the ID token.
        // "sub" represents the ID token subscriber which in our case
        // is the user ID. This sample does not use the user ID.
        $attributes = $client->verifyIdToken($token->id_token, $google_client_id)
            ->getAttributes();
        $gplus_id = $attributes["payload"]["sub"];

        // Store the token in the session for later use.
        $app['session']->set('token', json_encode($token));
        $response = 'Successfully connected with token: ' . print_r($token, true);
    }

    return new Response($response, 200);
});

// Revoke current user's token and reset their session.
$app->post('/disconnect', function () use ($app, $client) {
    $token = json_decode($app['session']->get('token'))->access_token;
    $client->revokeToken($token);
    // Remove the credentials from the user's session.
    $app['session']->set('token', '');
    return new Response('Successfully disconnected', 200);
});

$app->run();
?>