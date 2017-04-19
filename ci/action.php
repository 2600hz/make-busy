<?php
$ref = $_POST['ref'];
$pr = $_POST['pr'];
$action = $_POST['action'];

// typechecks, just in case of malicious input
$branch = "";
$pr_uri = "";
if (preg_match('/^\d+$/', $pr)) {
	$branch = "BRANCH=pull/$pr/head";
	$pr_uri = "&pr=$pr";
}
if (! preg_match('/^[\w|\d]{10}$/', $ref)) {
	exit(1);
}

if ($action == "run_again") {
	if(pcntl_fork() > 0) {
		exec("mkdir -p ~/volume/log/$ref");
		exec("$branch ./build.sh $ref > ~/volume/log/$ref/build.log 2>&1 &");
		exit(0);
	}
} elseif ($action == "remove_locks") {
	exec("rm -f /tmp/makebusy/*");
} elseif ($action == "rebuild") {
	if(pcntl_fork() > 0) {
		exec("mkdir -p ~/volume/log/$ref");
		exec("KZ_BUILD_FLAGS=--no-cache ./build.sh $ref > ~/volume/log/$ref/build.log 2>&1 &");
		exit(0);
	}
}

header(sprintf("Location: status.php?ref=%s$pr_uri", $ref));
