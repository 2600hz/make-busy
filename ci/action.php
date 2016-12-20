<?php
$ref = $_POST['ref'];
$action = $_POST['action'];

if ($action == "run_again") {
	if(pcntl_fork() > 0) {
		exec("mkdir -p ~/tests/log/$ref");
		exec("./build.sh $ref > ~/tests/log/$ref/build.log 2>&1 &");
		exit(0);
	}
} elseif ($action == "remove_lock") {
	exec("rm -f /tmp/build.lock");
}

header(sprintf("Location: status.php?ref=%s", $ref));
