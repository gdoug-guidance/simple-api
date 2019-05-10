<?php

require '../vendor/autoload.php';

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient as Client;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/hello', function () use ($router) {
	return response()->json([
		"message" => "Hello from the Public Endpoint."
	]);
});

$router->get('/private', function () use ($router) {
	// if ($user = checkUserToken()) {
	// 	return "<pre>" . print_r($user, true) . "</pre>";
	// }

	if ($user = checkUserToken()) {
		return response()->json([
			"message" => "Hello User: ${user['username']} who registered with Email: ${user['email']}"
		]);
	}
	
	return response()->json(['error' => 'Unauthorized'], 401, ['X-Header-One' => 'Header Value']);
});


function checkUserToken() {
	$client = new Client([ 
		'region' => env('COGNITO_REGION', true),
		'version' => 'latest',
		'app_client_id' => env('COGNITO_APP_CLIENT_ID', true),
		'identityPool' => env('COGNITO_IDENTITY_POOL', true),
		'user_pool_id' => env('COGNITO_USERPOOL_ID', true)
	]);

	$accessToken = getBearerToken();

	try {
		$userResponse = $client->getUser(['AccessToken' => $accessToken]);	
	} catch (Exception $e) {
		return false;
	}
	
	$user = [];
	$user['username'] = $userResponse->get('Username');
	$userAttributes = $userResponse->get('UserAttributes');
	
	foreach ($userAttributes as $value) {
		$user[$value['Name']] = $value['Value'];
	}

    return $user;
}


/** 
 * Get header Authorization
 * */
function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
/**
 * get access token from header
 * */
function getBearerToken() {
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}