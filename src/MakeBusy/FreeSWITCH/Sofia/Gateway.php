<?php

namespace MakeBusy\FreeSWITCH\Sofia;

use \DOMDocument;
use \MakeBusy\Common\Log;

class Gateway
{
    private $profile;
    private $name;
    private $params;
    private static $call_counter = 1;

    public function __construct(Profile $profile, $name) {
        $this->profile = $profile;
        $this->name = $name;
        $this->setParam("retry-seconds", 5);
        $this->getProfile()->getGateways()->add($this);
    }

    public function getEsl() {
        return $this->profile->getEsl();
    }

    public function getProfile() {
        return $this->profile;
    }

    public function getProfileName() {
        return $this->getProfile()->getName();
    }

    public function getName() {
        return $this->name;
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

    // freeswitch api wrapper
    public function api_originate($uri, $on_answer='&park', array $vars = array()) {
        $name = $this->getName();
        $channel_vars = $this->createChannelVariables($vars);
        $url = $channel_vars . "sofia/gateway/$name/$uri";
        $event = $this->getEsl()->bgapi("originate $url $on_answer");
        return $event->getHeader('Job-UUID');
    }

    public function originate($uri, $timeout=5, array $options = array(), $on_answer='&park') {
        $call_uuid = $this->call_uuid();
        $options['origination_uuid'] = $call_uuid;
        $job_uuid = $this->api_originate($uri, $on_answer, $options);
        return $this->getEsl()->getChannels()->waitForOutbound($call_uuid, 'Unique-ID', $timeout);
    }

    public function waitForInbound($number, $timeout = 5, $header = 'Caller-Destination-Number') {
        return $this->getEsl()->getChannels()->waitForInbound($number, $timeout, $header);
    }

    public function call_uuid() {
        return sprintf("BS-GATEWAY-%s-%s", $this->name, self::$call_counter++);
    }

    private function createChannelVariables($args) {
        if (empty($args)) return "";

        $vars = [];
        foreach($args as $key => $value) {
            $vars[] = $key . "=" . $value;
        }
        return "{" . join(",", $vars) . "}";
    }

    public function fromDevice($device, $realm) {
        if (!empty($device->sip->realm)) {
            $realm = $device->sip->realm;
        }

        $this->setParam('realm', $realm);
        $this->setParam('from-domain', $realm);

        if(!empty($device->sip->username)) {
            $this->setParam('username', $device->sip->username);
        }

        if(!empty($device->sip->password)) {
            $this->setParam('password', $device->sip->password);
        }

        if(!empty($device->makebusy->proxy)) {
            $this->setParam('proxy', $device->makebusy->proxy);
        }

        if(!empty($device->makebusy->register)) {
            $this->setParam('register', TRUE);
        } else {
            $this->setParam('register', FALSE);
        }
        
        return $this;
    }

    public function fromResource($resource, $realm){
        if (!empty($resource->sip->realm)) {
            $realm = $resource->sip->realm;
        }

        $this->setParam('realm', $realm);
        $this->setParam('from-domain', $realm);

        if(!empty($resource->sip->username)) {
            $this->setParam('username', $resource->sip->username);
        }

        if(!empty($resource->sip->password)) {
            $this->setParam('password', $resource->sip->password);
        }

        if(!empty($resource->makebusy->proxy)) {
            $this->setParam('proxy', $resource->makebusy->proxy);
        }

        if(!empty($resource->makebusy->register)) {
            $this->setParam('register', TRUE);
        } else {
            $this->setParam('register', FALSE);
        }
        return $this;
    }


    public function fromConnectivity($connectivity, $realm){
        $this->setParam('realm', $realm);
        $this->setParam('from-domain', $realm);

        if ($connectivity->auth->auth_method == "IP") {
            $this->setParam('username', "not-required");
            $this->setParam('password', "not-required");
        }

        if(!empty($connectivity->auth->auth_user)) {
            $this->setParam('username', $connectivity->auth->auth_user);
        }

        if(!empty($connectivity->auth->auth_password)) {
            $this->setParam('password', $connectivity->auth->auth_password);
        }

        if(!empty($connectivity->makebusy->proxy)) {
            $this->setParam('proxy', $connectivity->makebusy->proxy);
        }

        if(!empty($connectivity->makebusy->transport)) {
            $this->setParam('register-transport', $connectivity->makebusy->transport);
        }

        if(!empty($connectivity->makebusy->port)) {
            $value=$connectivity->makebusy->proxy.":".$connectivity->makebusy->port;
            $this->setParam('proxy', $value);
        }

        if(!empty($connectivity->makebusy->register)) {
            $this->setParam('register', TRUE);
        } else {
            $this->setParam('register', FALSE);
        }
        return $this;
    }

    public function register($wait = true) {
        $this->getEsl()->api_f('sofia profile %s register %s', $this->getProfileName(), $this->getName());
        if ($wait) {
            return $this->waitForRegister();
        }
    }

    public function unregister($wait = true) {
        $this->getEsl()->api_f('sofia profile %s unregister %s', $this->getProfileName(), $this->getName());
        if ($wait) {
            return $this->waitForUnRegister();
        }
    }

    public function reregister($wait = true) {
    	if(! $this->unregister($wait)) {
    		return false;
    	}
    	return $this->register($wait);
    }
    
    public function statusRegistry() {
        $data = $this->getEsl()->api_f('sofia status gateway %s::%s', $this->getProfileName(), $this->getName());
        if (preg_match('/State\s+REGED/i',$data->getBody(),$match) !== 0) { // search State REGED in output command sofia status gateway profile::gateway_id
            return TRUE;
        }
        return FALSE;
    }

    public function waitForRegister($timeout = 30){
        $gateway_name = $this->getName();
        $this->getEsl()->events("CUSTOM sofia::gateway_state");
        $start = time();

        while(1){
            $event = $this->getEsl()->recvEvent();
            if ((time() - $start) >= $timeout){
                Log::info("timeout waiting for %s gateway %s with username %s to register", $this->getProfileName(), $gateway_name, $this->getParam('username'));
                return null;
            }

            if (!$event) {
                continue;
            }

             if ($event->getHeader("Event-Name") == "CUSTOM"
                 && $event->getHeader("Event-Subclass") == "sofia::gateway_state"
                 && $event->getHeader("Gateway") == $gateway_name
                )
            {
                if ($event->getHeader("State") == "REGED"){
                    Log::debug("registered %s gateway %s with username %s", $this->getProfileName(), $gateway_name, $this->getParam('username'));
                    return TRUE;
                }
                elseif ($event->getHeader("State") == "FAIL_WAIT" || $event->getHeader("State") == "UNREGED")
                {
                    Log::info("failed to register %s gateway %s with username %s: %s",
                              $this->getProfileName(), $gateway_name, $this->getParam('username'), $event->getHeader("State"));
                    return FALSE;
                }
             }
         }
    }

    public function waitForUnRegister($timeout = 30){
        $gateway_name = $this->getName();
        $this->getEsl()->events("CUSTOM sofia::gateway_state");
        $start = time();
        while(1){
            $event = $this->getEsl()->recvEvent();
            if ((time() - $start) >= $timeout){
                Log::info("timeout waiting to unregister %s gateway %s with username %s", $this->getProfileName(), $gateway_name, $this->getParam('username'));
                return null;
            }
            if (!$event) {
               continue;
            }
            if ($event->getHeader("Event-Name") == "CUSTOM"
                && $event->getHeader("Event-Subclass") == "sofia::gateway_state"
                && $event->getHeader("Gateway") == $gateway_name
               )
            {
               if ($event->getHeader("State") == "NOREG") {
                   Log::debug("unregistered %s gateway %s with username %s", $this->getProfileName(), $gateway_name, $this->getParam('username'));
                   return TRUE;
               }
               elseif ($event->getHeader("State") == "REGED") {
                   Log::info("unable to unregistered %s gateway %s with username %s", $this->getProfileName(), $gateway_name, $this->getParam('username'));
                   return FALSE;
               }
            }
        }
   }

    public function kill() {
        return $this->getEsl()->api_f('sofia profile %s killgw %s', $this->getProfileName(), $this->getName());
    }

    public function restart() {
    	$this->getEsl()->api_f('sofia profile %s killgw %s', $this->getProfileName(), $this->getName());
    	$this->getProfile()->rescan();
    	$this->getEsl()->flushEvents();
    	return $this->register();
    }
    
    public function asXml() {
        $dom = new DOMDocument('1.0', 'utf-8');
        $gateway = $this->asDomDocument($dom);
        $dom->appendChild($gateway);
        return $dom->saveXML();
    }

    public function asDomDocument(DOMDocument $dom = null) {
        if (!$dom) {
            $dom = new DOMDocument('1.0', 'utf-8');
        }

        $root = $dom->createElement('gateway');
        $root->setAttribute('name', $this->getName());

        foreach($this->params as $param => $value) {
            $child = $dom->createElement('param');
            $child->setAttribute('name', $param);
            $child->setAttribute('value', $value);
            $root->appendChild($child);
        }

        return $root;
    }
}
