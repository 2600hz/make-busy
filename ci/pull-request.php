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
	return $_ENV['TOKEN'];
}

function client() {
	$client = new \Github\Client();
	$client->authenticate(get_token(), null,  Github\Client::AUTH_HTTP_TOKEN);
	return $client;
}

function process_pr($pr) {
	$client = client();
	$owner = $pr->base->repo->owner->login;
	$repo = $pr->base->repo->name;
	$commit = $pr->head->sha;
	error_log(sprintf("owner:%s repo:%s commit:%s", $owner, $repo, $commit));
	$client->api('repo')->statuses()->create(
		$owner, $repo, $commit,
		[
			'state' => 'pending',
			'context' => 'MakeBusy'
		]);
	error_log("builder: $commit $owner:$repo:$commit");
	if(pcntl_fork() > 0) {
		passthru("./build.sh $commit $owner:$repo:$commit");
		exit();
	}
}
