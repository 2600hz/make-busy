<?php
require_once 'vendor/autoload.php';
$file = $_GET['run'];
header('Content-Type: text/plain');
if (preg_match('/^[\w|\d]{10}$/', $file)) {
	$path = sprintf("../../tests/log/%s", $file);
	readfile($path);
} else {
	echo("Bad report requested\n");
}
