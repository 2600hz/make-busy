<?php

namespace MakeBusy\Kazoo;

use \Kazoo\SDK as KazooSDK;
use \Kazoo\AuthToken\ApiKey;
use \Kazoo\AuthToken\User;

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

            self::$sdk = new KazooSDK($auth_token, $options);

            //$http_client = self::$sdk->getHttpClient();
            //$http_client->addListener('before', array(new MakebusyListener(), 'onRequestBeforeSend'));
        }

        return self::$sdk;
    }
}
