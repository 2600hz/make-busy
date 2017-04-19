<?php
require_once 'vendor/autoload.php';

$content = file_get_contents("php://input");
$req = json_decode($content);
if ($_SERVER['HTTP_X_GITHUB_EVENT'] == 'pull_request') {
	$pr = $req->pull_request;
	$repo = $pr->base->repo->name;
	$action = $req->action;
	error_log(sprintf("repo:%s action:%s", $repo, $action));
	switch ($repo) {
		case "kazoo":
			kazoo($action, $pr);
			break;
		case "make-busy":
			make_busy($action, $pr);
			break;
		case "kazoo-docker":
			kazoo_docker($action, $pr);
			break;
		case "make-busy-callflow":
			make_busy_callflow($action, $pr);
			break;
		case "make-busy-crossbar":
			make_busy_callflow($action, $pr);
			break;
		case "make-busy-conference":
			make_busy_conference($action, $pr);
			break;
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

function kazoo($action, $pr) {
	$client = client();
	$owner = $pr->base->repo->owner->login;
	$repo = $pr->base->repo->name;
	$commit = $pr->head->sha;
	$pr_number = $pr->number;
	$short = substr($commit, 0, 10);
	error_log(sprintf("action:%s pr:%s owner:%s repo:%s commit:%s", $action, $pr_number, $owner, $repo, $commit));
	if ($action == "closed") {
		error_log("skipping action");
		return;
	}
	error_log("builder: $short $owner:$repo:$commit");
	if(pcntl_fork() > 0) {
		exec("mkdir -p ~/volume/log/$short");
		exec("BRANCH=pull/$pr_number/head ./build.sh $short $owner:$repo:$commit > ~/volume/log/$short/build.log 2>&1 &");
		exit(0);
	}
}

function make_busy($action, $pr) {
	if ($action == "closed") {
		exec("cd ~/make-busy && git fetch && git rebase origin/master");
	}
}

function kazoo_docker($action, $pr) {
	if ($action == "closed") {
		exec("cd ~/kazoo-docker && git fetch && git rebase origin/master");
	}
}

function make_busy_callflow($action, $pr) {
	if ($action == "closed") {
		exec("cd ~/tests/Callflow && git fetch && git rebase origin/master");
	}
}

function make_busy_crossbar($action, $pr) {
	if ($action == "closed") {
		exec("cd ~/tests/Crossbar && git fetch && git rebase origin/master");
	}
}

function make_busy_conference($action, $pr) {
	if ($action == "closed") {
		exec("cd ~/tests/Conference && git fetch && git rebase origin/master");
	}
}
