<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

use \MakeBusy\FreeSWITCH\Esl\Connection as EslConnection;

use \CallflowBuilder\Builder;
use \CallflowBuilder\Node\Device as DeviceNode;
use \MakeBusy\FreeSWITCH\Sofia\Gateway;
use \MakeBusy\Kazoo\SDK;

class Device
{
    static private $counter = 1;
    static public $instance_id = 1;
    static private $call_counter = 1;

    private $test_account;
    private $device;
    private $name;
    private $profile;
    private $callflow_numbers;
    private $loaded = false;

    public function __construct(TestAccount $account, $profile, $register = TRUE, array $options = array()) {
        $name = "Device " . self::$counter++;
        $this->test_account = $account;
        $kazoo_device = $account->getKazooDevice($name);
        if (is_null($kazoo_device)) {
            $this->initialize($account, $name, $profile, $register, $options);
            $kazoo_device = $this->device;
            $gateway = new Gateway($this->getProfile(), $kazoo_device->id);
            $gateway->fromDevice($kazoo_device, $account->getAccount()->realm);
        } else {
            $this->setDevice($kazoo_device);
            $this->loaded = true;
            $this->name = $name;
            $this->profile = $profile;
        }
    }

    public function call_uuid() {
        return sprintf("BS-DEVICE-%s-%s", self::$instance_id, self::$call_counter++);
    }

    public function initialize($account, $name, $profile, $register, $options) {
        $this->account = $account;
        $this->name = $name;
        $this->profile = $profile;

        $device = $account->getAccount()->Device();
        $device->name = $name;

        $device->caller_id = new stdClass();

        $device->caller_id->internal = new stdClass();
        $device->caller_id->internal->name = "Internal " . $name;
        $device->caller_id->internal->number = "0000";

        $device->caller_id->external = new stdClass();
        $device->caller_id->external->name = "External " . $name;
        $device->caller_id->external->number = "000000000";

        $device->caller_id->emergency = new stdClass();
        $device->caller_id->emergency->name = "Emergency " . $name;
        $device->caller_id->emergency->number = "7778889999";

        $device->sip = new stdClass();
        $device->sip->username = "device_" . (self::$counter - 1);
        $device->sip->password = Utils::randomString();

        $device->makebusy = new stdClass();
        $device->makebusy->test = TRUE;
        $device->makebusy->gateway = TRUE;
        $device->makebusy->proxy = Configuration::randomSipTarget();
        $device->makebusy->profile = $profile;
        $device->makebusy->register = (bool)$register;

        $device->language = "mk-bs"; //set language for conference tests

        $device = $this->mergeOptions($device, $options);

        Log::debug("trying to create device %s", $device->name);
        $device->save();
        Log::info("created device %s (%s) with SIP username %s", $device->getId(), $device->name, $device->sip->username);

        $this->setDevice($device);
    }

    public function getProfile() {
        return EslConnection::getInstance($this->profile)->getProfiles()->getProfile("profile");
    }

    public function getGateway() {
        return $this->getProfile()->getGateway($this->device->id);
    }

    // returns Channel or null
    public function originate($uri, $timeout=5, array $options = array(), $on_answer='&park') {
        $gateway = $this->getGateway();
        $call_uuid = $this->call_uuid();
        $options['origination_uuid'] = $call_uuid;
        $job_uuid = $gateway->originate($uri, $on_answer, $options);
        return $gateway->getEsl()->getChannels()->waitForOutbound($call_uuid, 'Unique-ID', $timeout);
    }

    // returns Channel or null
    public function waitForInbound($number = null, $timeout = 5, $header = 'Caller-Destination-Number') {
        return $this->getGateway()->getEsl()->getChannels()->waitForInbound($number? $number : $this->getSipUsername(), $timeout, $header);
    }

    public function makeReferredByUri() {
        return sprintf("<sip:%s@%s:5060;transport=udp>", $this->getSipUsername(), $this->getProfile()->getSipIp());
    }

    private function mergeOptions($device, $options) {
        foreach ($options as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $device->$key = $this->mergeOptions($device->$key, $value);
            } else if (is_null($value)) {
                unset($device->$key);
            } else {
                $device->$key = $value;
            }
        }
        return $device;
    }

    public function isLoaded() {
        return $this->loaded;
    }

    private function getTestAccount() {
        return $this->test_account;
    }

    public function getDevice() {
        return $this->device->fetch();
    }

    private function setDevice($device) {
        $this->device = $device;
    }

    public function getId() {
        return $this->getDevice()->getId();
    }

    public function getSipUsername() {
        return $this->getDevice()->sip->username;
    }

    public function getCallflowNumbers() {
        return $this->callflow_numbers;
    }

    public function setCallflowNumbers(array $numbers){
        $this->callflow_numbers = $numbers;
    }

    public function createCallflow(array $numbers, array $options = array()) {
        if ($this->loaded) {
            return;
        }

        $builder = new Builder($numbers);
        $device_callflow = new DeviceNode($this->getId());
        $data = $builder->build($device_callflow);

        $this->setCallflowNumbers($numbers);

        return $this->getTestAccount()->createCallflow($data);
    }

    private function setTestAccount(TestAccount $test_account) {
        $this->test_account = $test_account;
    }

    private function getAccount() {
        return $this->test_account->getAccount();
    }

    public function disableDevice(){
        $device = $this->getDevice();
        Log::debug("trying to disable device %s", $device->name);
        $device->enabled = FALSE;
        $device->save();
        Log::info("disabled device %s", $device->name);
    }

    public function enableDevice(){
        $device = $this->getDevice();
        Log::debug("trying to enable device %s", $device->name);
        $device->enabled = TRUE;
        $device->save();
        Log::info("enabled device %s", $device->name);
    }

    public function setInviteFormat($format, $route = null){
        $device = $this->getDevice();
        $id = $device->getId();

        if ($format == "route"){
            Log::debug("setting route %s on device %s ", $route, $id);
            $device->sip->route = $route;
        }

        $device->sip->invite_format = $format;
        Log::debug("setting format %s on device %s", $format, $id);
        $device->save();
        Log::info("device %s saved format %s and route %s", $id, $format, $route);
    }

    public function getInviteFormat(){
        return $this->getDevice()->sip->invite_format;
    }

    public function getCfParam($param){
        return $this->getDevice()->call_forward->$param;
    }

    public function setCfParam($param, $value){
        $device = $this->getDevice();
        Log::debug("setting call flow sub-key %s to value %s on device %s", $param, $value, $device->getId() );
        $device->call_forward->$param = $value;
        $device->save();
        Log::info("successfully set call flow sub-key %s to value %s on device %s", $param, $value, $device->getId() );
    }

    public function setDeviceParam($param, $value){
        $device = $this->getDevice();
        Log::debug("trying to set key %s to value %s on device %s", $param, $value, $device->getId() );
        $device->$param = $value;
        $device->save();
        Log::info("successfully set key %s to value %s on device %s", $param, $value, $device->getId() );
    }

    public function getDeviceParam($param){
        return $this->getDevice()->$param;
    }

    public function resetCfParams($number = null){
        $device = $this->getDevice();
        Log::debug("resetting call flow parameters for device %s", $device->getId() );
        $device->call_forward = new StdClass;

        if ($number){
            $device->call_forward->enabled        = TRUE;
            $device->call_forward->number         = $number;
        } else {
            $device->call_forward->enabled        = FALSE;
        }

        $device->call_forward->substitute         = TRUE;
        $device->call_forward->substitute         = FALSE;
        $device->call_forward->require_keypress   = FALSE;
        $device->call_forward->keep_caller_id     = FALSE;
        $device->call_forward->direct_calls_only  = FALSE;
        $device->call_forward->failover           = FALSE;
        $device->save();
        Log::info("successfully reset Call Flows parameters for device %s", $device->getId() );
    }

    public function resetDeviceParam($param){
        $device = $this->getDevice();
        Log::debug("resetting key %s on device %s", $param, $device->getId() );
        unset($device->$param);
        $device->save();
        Log::info("successfully reset key %s on device %s", $param, $device->getId() );
    }

    public function unsetCid($type = "external"){
        $device = $this->getDevice();
        Log::debug("trying to unset caller ID on device %s", $device->getId() );
        unset($device->caller_id->$type->number);
        unset($device->caller_id->$type->name);
        $device->save();
        Log::info("successfully unset caller ID  on device %s", $device->getId() );
    }

    public function setCid($number, $name, $type = "external") {
        $this->setCidNumber($number, $type);
        $this->setCidName($number, $type);
    }

    public function setCidNumber($number, $type = "external"){
        $device = $this->getDevice();
        Log::debug("trying to set number %s on device %s", $number, $device->getId() );
        $device->caller_id->$key->number = $number;
        $device->save();
        Log::info("successfully set number %s on device %s", $number, $device->getId() );
    }

    public function setCidName($name, $type = "external"){
        $device = $this->getDevice();
        Log::debug("trying to set name %s on device %s", $name, $device->getId() );
        $device->caller_id->$key->name = $name;
        $device->save();
        Log::info("successfully set name %s on device %s", $name, $device->getId() );
    }

    public function getCidParam($type = "external"){
       return $this->getDevice()->caller_id->$type;
    }

    public function setUsername($username){
        $device = $this->getDevice();
        Log::debug("trying to set username %s on device %s", $username, $device->getId() );
        $device->sip->username = $username;
        $device->save();
        Log::info("successfully set username %s on device %s", $username, $device->getId() );
    }

    public function getUsername(){
        return $this->getDevice()->sip->username;
    }

    public function setPassword($password){
       $device = $this->getDevice();
       Log::debug("trying to set password %s on device %s", $password, $device->getId() );
       $device->sip->password = $password;
       $device->save();
       Log::info("successfully set password %s on device %s", $password, $device->getId() );
    }

    public function getPassword(){
        return $this->getDevice()->sip->password;
    }

    public function setRestriction($type, $action = "deny"){
        $device = $this->getDevice();
        $restriction = new stdClass();
        Log::debug("trying to set restriction %s on device %s", $action, $device->getId() );
        $restriction->action = $action;
        $device->call_restriction->$type = $restriction;
        $device->save();
        Log::info("successfully set restriction %s on device %s", $action, $device->getId() );
    }

    public function resetRestrictions(){
        $device = $this->getDevice();
        Log::debug("trying to reset restriction(s) on device %s", $device->getId() );
        foreach ($device->call_restriction as $restriction){
            if ($restriction->action == "deny"){
                $restriction->action = "inherit";
            }
        }
        $device->save();
        Log::info("successfully reset restriction(s) on device %s", $device->getId() );
    }
}

