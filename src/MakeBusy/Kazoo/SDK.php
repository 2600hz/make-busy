<?php

namespace MakeBusy\Kazoo;

use \Kazoo\SDK as KazooSDK;
use \Kazoo\AuthToken\ApiKey;
use \Kazoo\AuthToken\User;
use \MakeBusy\Common\Log;

use \MakeBusy\Common\Configuration;

class SDK
{
    private static $sdk;

    public static function getInstance() {
        if (is_null(self::$sdk)) {
            $params = Configuration::getSection('sdk');

            if(!empty($params['api_key'])){
                $auth_token = new ApiKey($params['api_key']);
            } else {
                $auth_token = new User($params['auth_username'], $params['auth_password'], $params['auth_realm']);
            }

            if(!empty($params['base_url'])){
                $options = array('base_url' => $params['base_url']);
            } else {
                $options = array();
            }

            $logger = function() {
                $args = func_get_args();
                $severity = array_shift($args);
                $message = array_shift($args);
                Log::$severity("sdk %s", $message);
            };

            $entity_logger = function() {
                $args = func_get_args();
                $severity = array_shift($args);
                $header = array_shift($args);
                $entity = array_shift($args);
                if (isset($_ENV["LOG_ENTITIES"])) {
                    Log::dump("sdk $severity $header", print_r($entity, true));
                }
            };

            $options["logger"] = $logger;
            $options["entity_logger"] = $entity_logger;

            self::$sdk = new KazooSDK($auth_token, $options);

        }

        return self::$sdk;
    }
}
