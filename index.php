<?php

namespace Bank;

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;


header('Content-Type: application/json; charset=utf-8');

$fail = function ($reason, $code) {
	http_response_code((int) $code);
	echo \json_encode((object) ['error' => (string) $reason], JSON_UNESCAPED_UNICODE);
};

$requiredQueryArgs = [
	'bank_url',
	'api_key',
	'access_token',
];

foreach ($requiredQueryArgs as $arg) {
	if (!array_key_exists($arg, $_POST)) {
		return $fail(sprintf('Missing parameter %s', $arg), 404);
	}
}

$guzzle = new Client(['verify' => false]);

$bankUrl = $_POST['bank_url'];
$apiKey = $_POST['api_key'];
$accessToken = $_POST['access_token'];
/*$bankUrl = 'https://www.csas.cz/webapi/api/v1/netbanking/my/accounts/CZ5608000000002326573123/transactions?dateStart=2016-09-15T00:00:00Z&dateEnd=2016-10-15T00:00:00Z';
$apiKey = '0bca73a4-0ebb-4837-a841-7dcb189e9c02';
$accessToken = 'demo_b8d3fb54a86b63641727eba34fd638ef';*/

try {
	$response = $guzzle->get($bankUrl, [
		'headers' => [
			'WEB-API-key' => $apiKey,
            'Authorization' => 'Bearer ' . $accessToken,
		],
	]);
} catch (ServerException $e) {
	return $fail($e->getMessage(), 500);
}

$body = \json_decode($response->getBody()->getContents());

foreach ($body->transactions as &$transaction) {
	$transaction->test = 'test';
}
echo \json_encode($body, JSON_UNESCAPED_UNICODE);
return;
