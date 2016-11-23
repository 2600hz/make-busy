<?php

namespace MakeBusy\Kazoo;

use \Exception;
use \stdClass;
use \MakeBusy\Common\Configuration;
use \MakeBusy\Common\Utils;
use \MakeBusy\Kazoo\SDK;
use \MakeBusy\Common\Log;

use \MakeBusy\Kazoo\Gateways as KazooGateways;
use \MakeBusy\Kazoo\Applications\Crossbar\User;
use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Kazoo\Applications\Crossbar\Resource;
use \MakeBusy\Kazoo\Applications\Crossbar\RingGroup;
use \MakeBusy\Kazoo\Applications\Crossbar\Voicemail;
use \MakeBusy\Kazoo\Applications\Crossbar\Conference;
use \MakeBusy\Kazoo\Applications\Crossbar\Media;
use \MakeBusy\Kazoo\Applications\Crossbar\Webhook;
use \MakeBusy\Kazoo\Applications\Crossbar\Connectivity;
use \MakeBusy\Kazoo\Applications\Crossbar\PhoneNumbers;
use \MakeBusy\Kazoo\Applications\Crossbar\Registrations;

abstract class AbstractTestAccount
{
    private $account;
    private $cache = [];

    private $loaded = false;
    private static $counter = 1; // count created accounts

    public function __construct($type) {
        $name = sprintf("BS %s %s", $type, self::$counter++);
        $acc = self::load(['filter_name' => $name, 'filter_makebusy.type' => $type]);
        if (isset($acc[0])) {
            $this->loaded = true;
            $this->setAccount($acc[0]->getId());
            KazooGateways::loadFromAccount($this); // get all devices, create gateways, keep devices in $devices cache for later use
        } else {
            $this->create($type, $name);
            $this->setup();
        }
    }

    public function isLoaded() {
        return $this->loaded;
    }

    function addToCache($collection, $item) {
        // fucking oop (special case for users (no name, first_name instead) and phone_number (no name, id only))
        $name = isset($item->first_name)? $item->first_name : isset($item->name)? $item->name : $item->id;
        $this->cache[$collection][$name] = $item;
    }

    function getFromCache($collection, $name) {
        $cached = $this->getCache($collection);
        return isset($cached[$name])? $cached[$name] : null;
    }

    function getCache($collection) {
        if (!isset($this->cache[$collection])) {
            $this->cache[$collection] = [];
        }
        return $this->cache[$collection];
    }

    public function getRegistrations($filter) {
        return Registrations::getRegistrations($this, $filter);
    }

    public function createDevice($profile, $register = TRUE, array $options = array()) {
        return new Device($this, $profile, $register, $options);
    }

    public function createResource($profile, array $rules, $prefix = null, $emergency = FALSE, $register = FALSE, $global = FALSE) {
        return new Resource($this, $profile, $rules, $prefix, $emergency, $register, $global);
    }

    public function createUser(array $options = array()) {
        return new User($this, $options);
    }

    public function createConference(array $pins = array(), array $options = array()) {
        return new Conference($this, $pins, $options);
    }

    public function createMedia() {
        return new Media($this);
    }

    public function createWebhook(array $options = []) {
        return new Webhook($this, $options);
    }

    public function createVm($box_number, array $options = array()) {
        return new Voicemail($this, $box_number, $options);
    }

    public function createRingGroup(array $numbers, array $members, $strategy = "simultaneous") {
        return new RingGroup($this, $numbers, $members, $strategy);
    }

    public function createConnectivity(array $options = array()) {
        return new Connectivity($this, $options);
    }

    public function createPhoneNumber($number, array $options = array()) {
        return new PhoneNumbers($this, $number, $options);
    }

    public static function nukeTestAccounts($type = null) {
        Log::debug("Reset Kazoo Makebusy config");
        if (is_null($type)) {
            $filter = array('has_key' => 'makebusy');
        } else {
            $filter = array('filter_makebusy.type' => $type);
        }

        foreach(SDK::getInstance()->Accounts($filter) as $element) {
            $account = $element->fetch();
            Log::debug("nuking old makebusy account %s (%s)", $account->getId(), $account->name);
            $account->remove();
       }
    }

    public static function nukeGlobalResources() {
       $filter = array('filter_makebusyresource' => "true");
       foreach(SDK::getInstance()->Resources($filter) as $element) {
            $resource = $element->fetch();
            Log::debug("nuking old makebusy resource %s (%s)", $resource->getId(), $resource->name);
            $resource->remove();
       }
    }

    public static function load($filter = array('has_key' => 'makebusy')) {
        $re = [];
        foreach(SDK::getInstance()->Accounts($filter) as $element) {
            $re[] = $element->fetch();
        }
        return $re;
    }

    public function setup() {
    }

    public function create($type, $name) {

        $account = SDK::getInstance()->Account(null);
        $account->name = $name;

        $account->language = "mk-bs";

        $account->makebusy = new stdClass();
        $account->makebusy->test = TRUE;
        $account->makebusy->type = $type;
        $account->caller_id = new stdClass();

        $account->caller_id->internal = new stdClass();
        $account->caller_id->internal->name = "Internal " . $name;
        $account->caller_id->internal->number = "0000";

        $account->caller_id->external = new stdClass();
        $account->caller_id->external->name = "External " . $name;
        $account->caller_id->external->number = "000000000";

        $account->caller_id->emergency = new stdClass();
        $account->caller_id->emergency->name = "Emergency " . $name;
        $account->caller_id->emergency->number = "911";

        //Create custom realm base on config realm
        $params=Configuration::getSection('sdk');
        $account->realm = Utils::randomString(4) . "." . $params["auth_realm"];

        Log::debug("attempting to create new makebusy test account");
        $account->save();

        // TODO: this is a bug with the SDK, we shouldn't need
        //   to fetch it like this post-save
        $this->setAccount($account->getId());

        Log::info("created new makebusy test account %s (%s)", $account->getId(), $account->name);
    }

    public function __destruct() {
        // TODO: uncomment to remove the test account
        //  when the test is complete... is is currently
        //  useful to leave for debuging
        // $this->getAccount()->remove();
    }

    public function __toString() {
        return $this->getAccount()->getId();
    }

    public function getAccount() {
        return $this->account;
    }

    public function getAccountRealm() {
        return $this->getAccount()->realm;
    }

    public function getAccountId() {
        return $this->getAccount()->getId();
    }

    protected function setAccount($account_id) {
        $this->account = SDK::getInstance()->Account($account_id);
    }
}
