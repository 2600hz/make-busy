<?php
require_once 'vendor/autoload.php';
// ENV? ARG?
$token = '';
$owner = '';
$repo = '';
$commit = '';
$short_sha = substr($commit, 0, 10);

$client = new \Github\Client();
$client->authenticate($token, null, Github\Client::AUTH_HTTP_TOKEN);
$client->api('repo')->statuses()->create($owner, $repo, $commit, [
		  'target_url' => "http://docker.2600hz.com:8080/status.php?run=$short_sha",
		  'state' => 'success', 
		  'context' => 'MakeBusy']);
