<?php

require_once "vendor/autoload.php";

// $argv is for testing (to run from console)
$type = isset($_GET['type']) ? $_GET["type"] : $argv[1];

$_ENV['LOG_CONSOLE'] = 1;

error_log("input:" . "Type=$type\n");

$filename = "/tmp/" . $type . ".xml";
if( file_exists($filename) ) {
	$include = file_get_contents($filename);
} else {
	error_log("FILE " . $filename . " NOT FOUND\n");
	$include = "<!-- file ". $filename . " not found -->";
}
error_log("response: " . $filename ."\n" . htmlentities($include));
echo $include;
