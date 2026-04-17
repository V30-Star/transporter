<?php
require 'vendor/autoload.php';

$client = new Google\Client();
$client->setAuthConfig(__DIR__ . '/storage/app/google-oauth-credentials.json');
$client->setScopes([Google\Service\Drive::DRIVE]);
$client->setAccessType('offline');
$client->setPrompt('consent');

$httpClient = new \GuzzleHttp\Client(['verify' => false]);
$client->setHttpClient($httpClient);

$authUrl = $client->createAuthUrl();
echo "Buka URL ini di browser:\n" . $authUrl . "\n\n";
echo "Masukkan code dari URL redirect: ";
$code = trim(fgets(STDIN));

$token = $client->fetchAccessTokenWithAuthCode($code);
file_put_contents(__DIR__ . '/storage/app/google-token.json', json_encode($token));
echo "Token tersimpan!\n";