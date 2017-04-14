<?php

require_once "vendor/autoload.php";

use \MakeBusy\FreeSWITCH\Esl\Connection as EslConnection;
use \MakeBusy\Kazoo\Gateways as KazooGateways;

// $argv is for testing (to run from console)
$type = isset($_GET['type']) ? $_GET["type"] : $argv[1];

$_ENV['LOG_CONSOLE'] = 1;
KazooGateways::loadFromAccounts();

$include = EslConnection::getInstance($type)
	->getProfiles()
	->getProfile("profile")
	->getGateways()
	->asXmlInclude();

error_log("response:\n" . htmlentities($include));
echo $include;