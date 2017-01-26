<?php

namespace MakeBusy\FreeSWITCH\Sofia;

use \DOMDocument;
use \MakeBusy\Common\Log;
use \Exception;

class Profile
{
    private $params = array();
    private $name;
    private $esl;
    private $gateways;

    public function __construct($esl, $name) {
        $this->name = $name;
        $this->esl = $esl;
    }

    public function getEsl() {
        return $this->esl;
    }

    public function getName() {
        return $this->name;
    }

    public function getGateways() {
        if (is_null($this->gateways)) {
            $this->gateways = new Gateways($this);
        }
        return $this->gateways;
    }

    public function getGateway($name) {
        return $this->getGateways()->getGateway($name);
    }

    public function setGateways(Gateways $gateways) {
        $this->gateways = $gateways;
        return $this;
    }

    public function getParam($param) {
        if (isset($this->params[$param])) {
            return $this->params[$param];
        }

        return null;
    }

    public function setParam($param, $value) {
        $this->params[$param] = $value;
        return $this;
    }

    public function getProfileStatus() {
        return $this->getEsl()->api_f('sofia status profile %s', $this->getName());
    }

    public function getProfileParam($param) {
        $response = $this->getProfileStatus();
        $body = $response->getBody();
        $fields = explode("\n", $body);
        foreach ($fields as $field){
            $list = preg_split('/\s+/', $field);
            if ($param == $list[0]){
                return $list[1];
            }
        }
    }

    public function getSipURI(){
        return $this->getProfileParam("URL");
    }

    public function getSipIp(){
        return $this->getProfileParam("Ext-SIP-IP");
    }

    public function register($wait = true) {
        foreach($this->getGateways()->getGateways() as $gateway) {
            if ($gateway->getParam('register')) {
                $gateway->register($wait);
            }
        }
    }

    public function getRegistered() {
        $re = [];
        foreach($this->getGateways()->getGateways() as $gateway) {
            if ($gateway->getParam('register')) {
                $re[] = $gateway;
            }
        }
        return $re;
    }

    public function getUnregistered() {
        $re = [];
        foreach($this->getGateways()->getGateways() as $gateway) {
            if (! $gateway->getParam('register')) {
                $re[] = $gateway;
            }
        }
        return $re;
    }

    public function rescan() {
        $this->esl->api_f('sofia profile %s rescan', $this->getName());
    }

    public function restart() {
        $this->esl->api_f('sofia profile %s restart', $this->getName());
    }

    public function stop() {
        $this->esl->api_f('sofia profile %s stop', $this->getName());
    }

    public function start() {
        $this->esl->api_f('sofia profile %s start', $this->getName());
        return $this;
    }

    public function capture($enable) {
        if ($enable) {
            $this->esl->api_f('sofia profile %s capture on', $this->getName());
        } else {
            $this->esl->api_f('sofia profile %s capture off', $this->getName());
        }
        return $this;
    }

    public function siptrace($enable) {
        if ($enable) {
            $this->esl->api_f('sofia profile %s siptrace on', $this->getName());
        } else {
            $this->esl->api_f('sofia profile %s siptrace off', $this->getName());
        }
        return $this;
    }

    public function asXml() {
        $dom = $this->asDomDocument();
        return $dom->saveXML();
    }

    public function asDomDocument(DOMDocument $dom = null) {
        if (!$dom) {
            $dom = new DOMDocument('1.0', 'utf-8');
        }

        $root = $dom->createElement('profile');
        $root->setAttribute('name', $this->getName());

        $settings = $dom->createElement('settings');
        foreach($this->params as $param => $value) {
            $child = $dom->createElement('param');
            $child->setAttribute('name', $param);
            $child->setAttribute('value', $value);
            $settings->appendChild($child);
        }
        $root->appendChild($settings);

        $gateways = $this->getGateways()->asDomDocument($dom);
        $root->appendChild($gateways);

        $dom->appendChild($root);

        return $dom;
    }

    public function waitForGateways($gateways, $timeout = 10) {
        Log::debug("fs %s check for %d gateways present in profile for %d seconds", $this->getEsl()->getType(), count($gateways), $timeout);
        $this->esl->api_f('sofia profile %s siptrace off', $this->getName());
        $waitMap = [];
        foreach($gateways as $gw) {
            $waitMap[$gw->getName()] = 1;
        }

        while(count($waitMap) > 0) {
            $ev = $this->esl->api_f('sofia profile %s gwlist', $this->getName());
            foreach(explode(" ", $ev->getBody()) as $name) {
                unset($waitMap[$name]);
            }
            if (($gw_count = count($waitMap)) > 0) {
                Log::debug("fs %s %d gateways missing from fs, wait", $this->getEsl()->getType(), $gw_count);
                sleep(1);
            }
        }
        return count($waitMap);
    }


    public function waitForRegister($gateways, $timeout = 10){
        Log::debug("fs %s wait: for registration events:%d for %d seconds", $this->getEsl()->getType(), count($gateways), $timeout);
        $this->getEsl()->events("CUSTOM sofia::gateway_state");
        $start = time();

        $waitMap = [];
        foreach($gateways as $gw) {
            $waitMap[$gw->getName()] = 1;
        }

        while(count($waitMap) > 0){
            $event = $this->getEsl()->recvEvent();
            if ((time() - $start) >= $timeout){
                Log::error("fs %s timeout waiting register, remains: %s", $this->getEsl()->getType(), count($waitMap));
                return count($waitMap);
            }

            if (!$event) {
                continue;
            }

            if ($event->getHeader("Event-Name") == "CUSTOM" && $event->getHeader("Event-Subclass") == "sofia%3A%3Agateway_state") {
                if ($event->getHeader("State") == "REGED") {
                    unset($waitMap[$event->getHeader("Gateway")]);
                    Log::debug("fs %s gateway %s registered, remains: %s", $this->getEsl()->getType(), $event->getHeader("Gateway"), count($waitMap));
                }
            }
        }
        Log::debug("fs %s has all registrations", $this->getEsl()->getType());
        return 0;
    }

    public function waitForAdd($counter = 1, $timeout = 10){
        Log::debug("fs %s wait: for gateway add events:%d for %d seconds", $this->getEsl()->getType(), $counter, $timeout);
        $this->getEsl()->events("CUSTOM sofia::gateway_state");
        $start = time();

        while($counter > 0){
            $event = $this->getEsl()->recvEvent();
            if ((time() - $start) >= $timeout){
                Log::error("fs %s timeout waiting gateway add", $this->getEsl()->getType());
                return null;
            }

            if (!$event) {
                continue;
            }

            if ($event->getHeader("Event-Name") == "CUSTOM" && $event->getHeader("Event-Subclass") == "sofia%3A%3Agateway_add") {
                $counter--;
            }
        }
        Log::debug("fs %s has gateways added", $this->getEsl()->getType());
    }

}
