<?php
require_once 'vendor/autoload.php';

$content = file_get_contents("php://input");
$req = json_decode($content);
if ($_SERVER['HTTP_X_GITHUB_EVENT'] == 'pull_request') {
	process_pr($req->action, $req->pull_request);
}

function get_token() {
	return $_ENV['TOKEN'];
}

function client() {
	$client = new \Github\Client();
	$client->authenticate(get_token(), null,  Github\Client::AUTH_HTTP_TOKEN);
	return $client;
}

function process_pr($action, $pr) {
	$client = client();
	$owner = $pr->base->repo->owner->login;
	$repo = $pr->base->repo->name;
	$commit = $pr->head->sha;
	$short = substr($commit, 0, 10);
	error_log(sprintf("action:%s owner:%s repo:%s commit:%s", $action, $owner, $repo, $commit));
	$client->api('repo')->statuses()->create(
		$owner, $repo, $commit,
		[
			'state' => 'pending',
			'context' => 'MakeBusy',
			'target_url' => "http://docker.2600hz.com/status.php?ref=$short",
		]);
	error_log("builder: $short $owner:$repo:$commit");
	if(pcntl_fork() > 0) {
		exec("mkdir -p ~/volume/log/$short");
		exec("./build.sh $short $owner:$repo:$commit > ~/volume/log/$short/build.log 2>&1 &");
		exit(0);
	}
}
