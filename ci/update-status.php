<?php
require_once 'vendor/autoload.php';
// ENV?
$token = '';
$owner = '';
$repo = '';
$commit = '';

$client = new \Github\Client();
$client->authenticate($token, null,  Github\Client::AUTH_HTTP_TOKEN);
$client->api('repo')->statuses()->create($owner, $repo, $commit, [
		  'target_url' => "http://docker.2600hz.com:8080/status?$commit",
		  'state' => 'success', 
		  'context' => 'MakeBusy']);
