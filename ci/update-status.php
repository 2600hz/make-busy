<?php
require_once 'vendor/autoload.php';
$token = '';

$client = new \Github\Client();
$client->authenticate($token, null,  Github\Client::AUTH_HTTP_TOKEN);
$client->api('repo')->statuses()->create('jamhed', 'test', '7f0be1e18c48fbbab426d4d5f26f032fd469bc4b', [
		  'target_url'=>'http://docker.2600hz.com:8080/status',
		  'state' => 'success', 
		  'context' => 'MakeBusy']);
