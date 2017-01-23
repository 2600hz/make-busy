<?php

namespace MakeBusy\Common;

use \Exception;


class Configuration
{
    const CONFIG_FILE = '/../../../config.json';
    private static $instance;
    private $config;

    private function __construct(){
        $config = $this->from_json();
        if (isset($_ENV['KAZOO_URI'])) {
            $config = $this->override_sdk($config, $_ENV['KAZOO_URI']);
        }
        $this->config = $config;
    }

    // KAZOO_URI: user password realm url
    private function override_sdk($config, $uri) {
        list($user, $password, $realm, $uri) = explode(' ', $uri);
        $config['sdk']['auth_username'] = $user;
        $config['sdk']['auth_password'] = $password;
        $config['sdk']['auth_realm'] = $realm;
        $config['sdk']['base_url'] = $uri;
        return $config;
    }

    private function __clone(){
    }

    private function getConfig(){
        return $this->config;
    }

    public static function getInstance(){
        if (is_null(self::$instance)){
            self::$instance = new Configuration();
        }
        return self::$instance;
    }

    public static function randomSipTarget() {
        $targets = self::sipTargets();
        return $targets[array_rand($targets)];
    }

    public static function sipTargets() {
        $params = self::getSection('sip');
        return $params['targets'];
    }

    private function from_json($file = null){
        if (is_null($file)){
            $file = dirname(__FILE__) . self::CONFIG_FILE;
        }

        return json_decode(file_get_contents(realpath($file)), true);
    }

    public static function getSection($section) {
        $config = self::getInstance()->getConfig();
        if ( !empty( $config[$section] ) ) {
            return $config[$section];
        } else {
            throw new Exception("invalid configuration section $section specified");
        }
    }

    public static function getWebhooksUri() {
        $config = self::getInstance()->getConfig();
        if (!empty( $config['webhooks'] ) && !empty( $config['webhooks']['uri']) ) {
            return $config['webhooks']['uri'];
        } else {
            throw new Exception("webhooks configuration section is lacking");
        }
    }

    public static function getFromSection($section, $key){
        $config = self::getInstance()->getConfig();
        if (!empty( $config[$section] ) && !empty( $config[$section][$key]) ) {
            return $config[$section][$key];
        } else {
            throw new Exception("invalid configuration section $section specified");
        }
    }
}
