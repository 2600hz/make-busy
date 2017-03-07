<?php
$ref = $_POST['ref'];
$action = $_POST['action'];

if ($action == "run_again") {
	if(pcntl_fork() > 0) {
		exec("mkdir -p ~/volume/log/$ref");
		exec("./build.sh $ref > ~/volume/log/$ref/build.log 2>&1 &");
		exit(0);
	}
} elseif ($action == "remove_locks") {
	exec("rm -f /tmp/makebusy/*");
} elseif ($action == "rebuild") {
	if(pcntl_fork() > 0) {
		exec("mkdir -p ~/volume/log/$ref");
		exec("KZ_BUILD=--no-cache ./build.sh $ref > ~/volume/log/$ref/build.log 2>&1 &");
		exit(0);
	}
}

header(sprintf("Location: status.php?ref=%s", $ref));
