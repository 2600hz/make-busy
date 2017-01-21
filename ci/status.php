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
	if ($log == "build") {
	}
?>
<html>
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
<td><a href="?ref=<?php echo $ref ?>&type=run">run.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=kazoo">kazoo.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=freeswitch">freeswitch.log</a></td>
<td><a href="?ref=<?php echo $ref ?>&type=kamailio">kamailio.log</a></td>
</tr>
</table>

<?php
function show_log($ref, $log) {
	$path = sprintf("../../volume/log/%s/%s.log", $ref, $log);
	if (file_exists($path)) {
		echo("<pre>");
		readfile($path);
		echo("</pre>");
	} else {
		echo("Log:$log not found\n");
	}
}

if (preg_match('/^[\w|\d]{10}$/', $ref)) {
	if ($log == "build") {
		show_log($ref, "build");
	}
	elseif ($log == "run") {
		show_log($ref, "run");
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
	else {
		echo("Bad type\n");
	}
} else {
	echo("Bad reference\n");
}
?>

</body>
</html>
