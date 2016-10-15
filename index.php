<?php

namespace FKolonial;

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

try {
	$response = $guzzle->post($bankUrl, [
		'json' => [
			'WEB-API-key' => $apiKey,
            'Authorization' => 'Bearer ' . $accessToken,
		],
	]);
} catch (ServerException $e) {
	return $fail($e->getMessage(), 500);
}

$transactions = \json_decode($response->getBody()->getContents())->transactions;

foreach ($transactions as $transaction) {
	echo \json_encode($transaction->description, JSON_UNESCAPED_UNICODE);
}
return;
