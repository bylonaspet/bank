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

if (php_sapi_name() !== "cli") {
	$requiredQueryArgs = [
		'bank_url',
		'api_key',
		'access_token',
	];

	$args = \json_decode(file_get_contents('php://input'), true);

	foreach ($requiredQueryArgs as $arg) {
		if (!array_key_exists($arg, $args)) {
			return $fail(sprintf('Missing parameter %s', $arg), 404);
		}
	}
}


$guzzle = new Client(['verify' => false]);

if (php_sapi_name() !== "cli") {
	$bankUrl = $args['bank_url'];
	$apiKey = $args['api_key'];
	$accessToken = $args['access_token'];
} else {
	$bankUrl = 'https://www.csas.cz/webapi/api/v1/netbanking/my/accounts/CZ5608000000002326573123/transactions?dateStart=2016-09-15T00:00:00Z&dateEnd=2016-10-15T00:00:00Z';
	$apiKey = '0bca73a4-0ebb-4837-a841-7dcb189e9c02';
	$accessToken = 'demo_b8d3fb54a86b63641727eba34fd638ef';

}


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

$i = 0;
foreach ($body->transactions as &$transaction) {
	$transaction->enhanced = null;

	if ($i === 0) {
		// Hack transaction description
		$transaction->description = 'Výhra v Hackathonu, Viktor';
		$transaction->amount->value = -5000;
	}

	$i++;
}

$body->transactions = array_values($body->transactions);

// Add kolonial transaction
$knTransaction = clone $transaction;
$knTransaction->description = 'Nákup na Koloniál.cz';
$knTransaction->amount->value = 135;
$knTransaction->variableSymbol = 59597;
$knTransaction->enhanced = [
	'type' => 'kolonial',
	'server' => 'http://bylonaspetkolonial03.azurewebsites.net',
	'method' => 'get',
	'parameters' => [
		'client_id',
		'client_secret',
		'username',
		'password'
	]
];
array_unshift($body->transactions, $knTransaction);

// Add uber transaction
$uberTransaction = clone $transaction;
$uberTransaction->description = 'Uber ride';
$uberTransaction->amount->value = 109.35;
$uberTransaction->variableSymbol = 12345;
$uberTransaction->enhanced = [
	'type' => 'uber',
	'server' => 'http://bylonaspatuberapi02.azurewebsites.net',
	'method' => 'get',
	'parameters' => [
		'client_id',
		'client_secret',
		'username',
		'password'
	]
];
array_unshift($body->transactions, $uberTransaction);

$body->transactions = array_values($body->transactions);

echo \json_encode($body, JSON_UNESCAPED_UNICODE);

return;
