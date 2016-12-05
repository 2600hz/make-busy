<?php
require_once 'vendor/autoload.php';

$content = file_get_contents("php://input");
$req = json_decode($content);
if ($_SERVER['HTTP_X_GITHUB_EVENT'] == 'pull_request') {
	if ($req->action == 'opened') {
		process_pr($req->pull_request);
	}
}

function get_token() {
	return "";
}

function client() {
	$client = new \Github\Client();
	$client->authenticate(get_token(), null,  Github\Client::AUTH_HTTP_TOKEN);
	return $client;
}

function process_pr($pr) {
	$client = client();
	error_log(sprintf("name:%s sha:%s", $pr->base->repo->full_name, $pr->head->sha));
	$client->api('repo')->statuses()->create($pr->base->repo->owner->login, $pr->base->repo->name, $pr->head->sha, ['state' => 'pending', 'context' => 'MakeBusy']);
}
