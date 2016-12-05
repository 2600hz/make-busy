<?php
require_once 'vendor/autoload.php';
$token = $_ENV['TOKEN'];

$client = new \Github\Client();
$client->authenticate($token, null,  Github\Client::AUTH_HTTP_TOKEN);
