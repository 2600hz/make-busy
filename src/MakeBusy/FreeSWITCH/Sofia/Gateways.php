<?php
namespace MakeBusy\FreeSWITCH\Sofia;

use \DOMDocument;
use \MakeBusy\Common\Log;

class Gateways
{
    private $profile;
    private $gateways = array();

    public function __construct(Profile $profile) {
        $this->profile = $profile;
        $this->profile->setGateways($this);
    }

    public function getGateways() {
        return $this->gateways;
    }

    private function esl() {
        $this->profile->getEsl();
    }

    public function getProfileName() {
        return $this->profile->getName();
    }

    public function add(Gateway $gateway) {
        $name = $gateway->getName();
        $this->gateways[$name] = $gateway;
        return $this;
    }

    public function remove($name) {
        if (isset($this->gateways[$name])) {
            unset($this->gateways[$name]);
        }
        return $this;
    }

    public function getGateway($name) {
        if (isset($this->gateways[$name])) {
            return $this->gateways[$name];
        }
        return null;
    }

    public function status() {
        $status = array();

        foreach ($this->gateways as $gateway) {
            $status[$gateway->getName()] = array(
                'profile' => null,
                'status' => 'inactive',
                'makebusy' => TRUE,
                'calls' => array(
                    'in' => 0,
                    'out' => 0
                ),
                'failed-calls' => array(
                    'in' => 0,
                    'out' => 0
                )
            );
        }

        $result = $this->esl()->api('sofia xmlstatus gateway');
        $xml = simplexml_load_string($result->getBody());
        $profile_name = $this->getProfileName();

        foreach ($xml->gateway as $gateway) {
            $gateway = (array)$gateway;

            if ($gateway['profile'] != $profile_name) {
                continue;
            }

            $status[$gateway['name']] = array(
                'profile' => $gateway['profile'],
                'status' => strtolower($gateway['status']),
                'calls' => array(
                    'in' => (int)$gateway['calls-in'],
                    'out' => (int)$gateway['calls-out']
                ),
                'failed-calls' => array(
                    'in' => (int)$gateway['failed-calls-in'],
                    'out' => (int)$gateway['failed-calls-out']
                )
            );
        }

        return $status;
    }

    public function asXml() {
        $dom = new DOMDocument('1.0', 'utf-8');
        $gateways = $this->asDomDocument($dom);
        $dom->appendChild($gateways);
        return $dom->saveXML();
    }

    public function asXmlInclude() {
        $dom = new DOMDocument('1.0', 'utf-8');

        // TOOD: since we are using kazoo-freeswitch (and kazoo-configs)
        //   its existing pre-processor include is inside the 'gateways'
        //   element on the sofia profile sipinterface_1.  Therefore,
        //   we have no 'root' element...so strip off 'gateways'
        $gateways = $this->asDomDocument($dom);
        foreach($gateways->childNodes as $gateway) {
            $dom->appendChild($gateway->cloneNode(TRUE));
        }

        return $dom->saveXML();
    }

    public function asDomDocument(DOMDocument $dom = null) {
        if (!$dom) {
            $dom = new DOMDocument('1.0', 'utf-8');
        }

        $root = $dom->createElement('gateways');

        foreach ($this->gateways as $gateway) {
            $child = $gateway->asDomDocument($dom);
            $root->appendChild($child);
        }

        return $root;
    }

}
