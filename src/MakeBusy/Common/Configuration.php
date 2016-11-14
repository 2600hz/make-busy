<?php

namespace MakeBusy\Common;

use \Exception;


class Configuration
{
    const CONFIG_FILE = '/../../../config.json';
    private static $instance;
    private $config;

    private function __construct(){
        $this->from_json();
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

        $config = json_decode(file_get_contents(realpath($file)), true);
        $this->config = $config;
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
