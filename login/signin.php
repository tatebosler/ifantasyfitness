<?php
require_once __DIR__.'/vendor/autoload.php';
require_once 'tokens.php';
session_start();

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
const CLIENT_ID = $google_client_id;
const CLIENT_SECRET = $google_app_secret;
const APPLICATION_NAME = $google_app_name;

$client = new Google_Client();
$client->setApplicationName(APPLICATION_NAME);
$client->setClientId(CLIENT_ID);
$client->setClientSecret(CLIENT_SECRET);
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
    $state = md5(rand());
    $app['session']->set('state', $state);
    return $app['twig']->render('index.html', array(
        'CLIENT_ID' => CLIENT_ID,
        'STATE' => $state,
        'APPLICATION_NAME' => APPLICATION_NAME
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
        // $app['session']->set('state', '');

        $code = $request->getContent();
        // Exchange the OAuth 2.0 authorization code for user credentials.
        $client->authenticate($code);
        $token = json_decode($client->getAccessToken());

        // You can read the Google user ID in the ID token.
        // "sub" represents the ID token subscriber which in our case
        // is the user ID. This sample does not use the user ID.
        $attributes = $client->verifyIdToken($token->id_token, CLIENT_ID)
            ->getAttributes();
        $gplus_id = $attributes["payload"]["sub"];

        // Store the token in the session for later use.
        $app['session']->set('token', json_encode($token));
        $response = 'Successfully connected with token: ' . print_r($token, true);
    }

    return new Response($response, 200);
});

// Get list of people user has shared with this app.
$app->get('/people', function () use ($app, $client, $plus) {
    $token = $app['session']->get('token');

    if (empty($token)) {
        return new Response('Unauthorized request', 401);
    }

    $client->setAccessToken($token);
    $people = $plus->people->listPeople('me', 'visible', array());
    return $app->json($people);
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