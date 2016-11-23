<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \MakeBusy\Kazoo\SDK;

use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Configuration;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;
use \MakeBusy\Kazoo\Applications\Crossbar\SystemConfigs;
use \MakeBusy\FreeSWITCH\Sofia\Gateway;
use \MakeBusy\FreeSWITCH\Esl\Connection as EslConnection;
use \MakeBusy\Common\Log;

class Connectivity
{
    private static $counter = 1;
    private static $gateway_counter = 0; // because servers is an array, starting from zero

    private $test_account;
    private $connectivity;
    private $loaded = false;
    private $number;
    public $gateways = []; // instances of Sofia\Gateway

    public function __construct(TestAccount $account, array $options = array()) {
        $this->number = self::$counter++; // to name gateways
        $name = "Connectivity " . $this->number;
        $this->test_account = $account;
        $kazoo_connectivity = $account->getFromCache('Connectivities', $name);
        if (is_null($kazoo_connectivity)) {
            $this->initialize($account, $name, $options);
        } else {
            $this->setConnectivity($kazoo_connectivity);
            $counter = self::$gateway_counter;
            foreach($this->getConnectivity()->servers as $element) {
                $this->createSofiaGateway($element, $counter++);
            } 
            $this->loaded = true;
        }
    }

    public function initialize(TestAccount $test_account, $name, array $options = array()) {
        $account = $this->getAccount();
        $connectivity = $account->Connectivity();
        $connectivity->name = $name;
        $connectivity->account = new stdClass();
        $connectivity->account->trunks = 0;
        $connectivity->account->inbound_trunks = 0;
        $connectivity->account->auth_realm=$account->realm;
        $connectivity->DIDs_Unassigned = new stdClass();
        $connectivity->billing_account_id=$account->getId();
        $connectivity->servers =  array();
        $connectivity->makebusy = new stdClass();
        $connectivity->makebusy->test = TRUE;
        $connectivity->save();
        $this->setConnectivity($connectivity);
    }

    public function isLoaded() {
        return $this->loaded;
    }

    private function getTestAccount() {
         return $this->test_account;
    }

    private function setTestAccount(TestAccount $test_account) {
        $this->test_account = $test_account;
    }

    private function getAccount() {
        return $this->test_account->getAccount();
    }

    public function getConnectivity() {
        return $this->connectivity;
        //->fetch();
    }

    private function setConnectivity($connectivity) {
        $this->connectivity = $connectivity;
    }

    public function getId() {
        return $this->getConnectivity()->getId();
    }

    public function addGateway($profile, $type, $credential = null, $password = null) {
        if ($this->isLoaded()) {
            return self::$gateway_counter++;
        }
        $connectivity = $this->getConnectivity();
        $gateway_id = self::$gateway_counter++;
        $name = sprintf("conn-%s-gw-%s", $this->number, $gateway_id);

        $element = new stdClass();
        $element->auth = new stdClass();
        $element->server_name = $name;
        $element->DIDs = new stdClass();
        $element->makebusy = new stdClass();

        switch ($type) {
           case "Password":
                $element->auth->auth_method = "Password";
                $element->auth->auth_user = $credential;
                $element->auth->auth_password = $password;
                $element->makebusy->profile   = $profile;
                $element->makebusy->register = TRUE;
                break;
           case 'IP':
                $element->auth->auth_method="IP";
                $element->auth->ip=$credential;
                $element->makebusy->profile   = $profile;
                $element->makebusy->register = FALSE;
                break;
           default:
                $element->auth->auth_method   = 'Password';
                $element->auth->auth_user     = 'noreg';
                $element->auth->auth_password = 'register';
                $element->makebusy->profile   = $profile;
                $element->makebusy->register  = FALSE;
        }

        $element->server_type = "FreeSWITCH";
        $element->monitor = new stdClass();
        $element->monitor->monitor_enabled = new stdClass();

        $element->options = new stdClass();
        $element->options->caller_id = new stdClass();
        $element->options->e911_info = new stdClass();
        $element->options->failover = new stdClass();
        $element->options->enabled = TRUE;
        $element->options->international = FALSE;
        $element->options->media_handling = 'bypass';

        $element->makebusy->test = TRUE;
        $element->makebusy->gateway = TRUE;
        $element->makebusy->proxy = Configuration::randomSipTarget();

        $element->makebusy->id = strtolower(Utils::randomString(28, "hex"));
        array_push($connectivity->servers, $element);
        $this->createSofiaGateway($element, $gateway_id);

        $connectivity->save();
        return $gateway_id;
    }

    public static function getProfile($profile) {
        return EslConnection::getInstance($profile)->getProfiles()->getProfile("profile");
    }

    public function createSofiaGateway($element, $gateway_id) {
        $gateway = new Gateway(self::getProfile($element->makebusy->profile), $element->server_name);
        $gateway->fromConnectivity($element, $this->getAccount()->realm);
        $this->gateways[$gateway_id] = $gateway;
        return $gateway;
    }

    public function setAcl($name, $ip) {
        $test_account = $this->getTestAccount();
        $cidr = $ip . "/32";
        SystemConfigs::setCarrierAcl($test_account, $name, $cidr, "allow", "trusted");
        return $this;
    }

    public function removeAcl($name, $ip){
        $test_account = $this->getTestAccount();
        $cidr = $ip . "/32";
        SystemConfigs::removeCarrierAcl($test_account, $name, $cidr);
    }

    public function getGateway($id) {
        return $this->gateways[$id];
    }

    public function getGatewayId($gatewayid) {
        return $this->getConnectivity()->servers[$gatewayid]->makebusy->id;
    }

    public function getGatewayAuthParam($gatewayid,$param) {
        return $this->getConnectivity()->servers[$gatewayid]->auth->$param;
    }

    public function assignNumber($gatewayid, $number) {
        $connectivity = $this->getConnectivity();
        $number = '+' . $number;
        $connectivity->servers[$gatewayid]->DIDs->$number = new stdClass();
        $connectivity->save();
    }

    public function setInviteFormat($gateway_id, $format) {
        $connectivity = $this->getConnectivity();
        $connectivity->servers[$gateway_id]->options->inbound_format = $format;
        $connectivity->save();
    }

    public function resetInviteFormat($gateway_id) {
        $connectivity = $this->getConnectivity();
        unset($connectivity->servers[$gateway_id]->options->inbound_format);
        $connectivity->save();
    }

    // failover_type: sip|e164, destination: either number or sip uri
    public function setFailover(int $gatewayid, $number, $failover_type, $destination) {
        $connectivity = $this->getConnectivity();
        Utils::mset($connectivity->servers[$gatewayid], ['DIDs', $number, 'failover', $failover_type], $destination);
        $connectivity->save();
    }

    public function resetFailover(int $gatewayid, $number) {
        $connectivity = $this->getConnectivity();
        Utils::mset($connectivity->servers[$gatewayid], ['DIDs', $number, 'failover']);
        $connectivity->save();
    }

    public function setTransport($gatewayid,$transport) {
        $connectivity = $this->getConnectivity();
        $connectivity->servers[$gatewayid]->makebusy->transport=$transport;
        $connectivity->save();
    }

    public function setPort($gatewayid,$port) {
        $connectivity = $this->getConnectivity();
        $connectivity->servers[$gatewayid]->makebusy->port=$port;
        $connectivity->save();
    }

}
