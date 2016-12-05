<?php
require_once 'vendor/autoload.php';

$content = file_get_contents("php://input");
$req = json_decode($content);
if ($_SERVER('HTTP_X_GITHUB_EVENT') == 'pull_request') {
	if ($req['action'] == 'opened') {
		process_pr($req['pull-request']);
	}
}

function client() {
	$client = new \Github\Client();
	$client->authenticate("d7aeda7cc8417886ab0c0468de147ef54bdf3d1d", null,  Github\Client::AUTH_HTTP_TOKEN);
}

function process_pr($pr) {
	$client = client();
	$client->api('repo')->statuses()->create('MakeBusy', $pr->base->repo->full_name, $pr->head->sha, ['state' => 'pending']);
}
