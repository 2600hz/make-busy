<?php
	require_once 'vendor/autoload.php';
	$ref = $_GET['ref'];
	if (preg_match('/^[\w|\d]{40}$/', $ref)) {
		$ref = substr($ref, 0, 10);
	}
	if (isset($_GET['type'])) {
		$log = $_GET['type'];
	} else {
		$log = "build";
	}

	function tail($ref, $log) {
		session_start();
		$path = sprintf("../../volume/log/%s/%s.log", $ref, $log);
		$handle = fopen($path, 'r');
		if (isset($_SESSION[$ref . $log])) {
			$data = stream_get_contents($handle, -1, $_SESSION[$ref.$log]);
			echo $data;
		} else {
			fseek($handle, 0, SEEK_END);
			$_SESSION[$ref.$log] = ftell($handle);
		}
	}
	if (preg_match('/^[\w|\d]{10}$/', $ref) && isset($_GET['tail']) && $_GET['tail'] == "build") {
		tail($ref, $log);
	}
?>
<html>
<head>
	<script src="http://code.jquery.com/jquery-1.8.2.min.js"></script>
	<script src="http://creativecouple.github.com/jquery-timing/jquery-timing.min.js"></script>
</head>
<body>
<table width="100%">
<tr>
<td>
<form action="action.php" method="POST">
	<input type="hidden" name="action" value="run_again">
	<input type="hidden" name="ref" value="<?php echo $ref ?>">
	<input type="submit" value="Run again">
</form>
</td>
<td>
<form action="action.php" method="POST">
	<input type="hidden" name="action" value="rebuild">
	<input type="hidden" name="ref" value="<?php echo $ref ?>">
	<input type="submit" value="Rebuild and run">
</form>
</td>
<td colspan=3>
<form action="action.php" method="POST">
	<input type="hidden" name="action" value="remove_lock">
	<input type="hidden" name="ref" value="<?php echo $ref ?>">
	<input type="submit" value="Remove lock">
</form>
</td>
</tr>
<tr>
<td><a href="?ref=<?php echo $ref ?>&type=build">build.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=suite">suite.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=run">run.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=kazoo">kazoo.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=freeswitch">freeswitch.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=kamailio">kamailio.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=makebusy">makebusy.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=makebusy-fs-auth">makebusy-fs-auth.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=makebusy-fs-carrier">makebusy-fs-carrier.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=makebusy-fs-pbx">makebusy-fs-pbx.log</a></td>
</tr>
</table>

<?php
function show_log($ref, $log) {
	$path = sprintf("../../volume/log/%s/%s.log", $ref, $log);
	echo("<pre>");
	if (file_exists($path)) {
		readfile($path);
	} else {
		echo shell_exec(sprintf("docker logs %s.git-%s | ~/kazoo-docker/bin/uncolor", $log, $ref));
	}
	echo("</pre>");
}

if (preg_match('/^[\w|\d]{10}$/', $ref)) {
	if ($log == "build") {
echo <<<EOT
<script>
$(function() {
	$.repeat(1000, function() {
		$.get('?tail=build', function(data) {
			$('#build').append(data);
		});
	});
});
</script>
<pre id=build></pre>
EOT
	}
	elseif ($log == "run") {
		show_log($ref, "run");
	}
	elseif ($log == "suite") {
		show_log($ref, "suite");
	}
	elseif ($log == "kazoo") {
		show_log($ref, "kazoo");
	}
	elseif ($log == "freeswitch") {
		show_log($ref, "freeswitch");
	}
	elseif ($log == "kamailio") {
		show_log($ref, "kamailio");
	}
	elseif ($log == "makebusy") {
		show_log($ref, "makebusy");
	}
	elseif ($log == "makebusy-fs-auth") {
		show_log($ref, "makebusy-fs-auth");
	}
	elseif ($log == "makebusy-fs-carrier") {
		show_log($ref, "makebusy-fs-carrier");
	}
	elseif ($log == "makebusy-fs-pbx") {
		show_log($ref, "makebusy-fs-pbx");
	}
	else {
		echo("Bad type\n");
	}
} else {
	echo("Bad reference\n");
}
?>

</body>
</html>
