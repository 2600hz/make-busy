<?php
namespace MakeBusy\Kazoo;

use \MakeBusy\FreeSWITCH\Esl\Connection as EslConnection;
use \MakeBusy\FreeSWITCH\Sofia\Gateway;
use \MakeBusy\FreeSWITCH\Sofia\Resource;
use \MakeBusy\Kazoo\AbstractTestAccount;
use \MakeBusy\Kazoo\SDK;
use \MakeBusy\Common\Log;

class Gateways {

    public static function getProfile($profile) {
        return EslConnection::getInstance($profile)->getProfiles()->getProfile("profile");
    }

    protected function __construct() {
    }

    // this is used to generate gateways for FreeSwitch, see gateways.php
    public static function loadFromAccounts($filter = array('has_key' => 'makebusy')) {
        foreach(SDK::getInstance()->Accounts($filter) as $element) {
            // TODO: this is a bug in the SDK you should not have, to fetch the account definition to change the working account id
            $account = $element->fetch();
            self::loadFromDevices($account);
            self::loadFromResource($account);
        }
    }

    public static function loadFromAccount(AbstractTestAccount $account) {
        $kazoo_account = $account->getAccount();
        $devices = self::loadFromDevices($kazoo_account);
        foreach($devices as $kazoo_device) {
            Log::debug("cache add kazoo device name:%s to account:%s, device id:%s", $kazoo_device->name, $kazoo_account->name, $kazoo_device->id);
            $account->addKazooDevice($kazoo_device);
        }
        $resources = self::loadFromResource($kazoo_account);
        foreach($resources as $kazoo_resource) {
            Log::debug("cache add kazoo resource name:%s to account:%s, device id:%s", $kazoo_resource->name, $kazoo_account->name, $kazoo_resource->id);
            $account->addKazooResource($kazoo_resource);
        }
        $users = self::loadFromUsers($kazoo_account);
        foreach($users as $kazoo_user) {
            Log::debug("cache add kazoo user name:%s to account:%s", $kazoo_user->first_name, $kazoo_account->name);
            $account->addKazooUser($kazoo_user);
        }
        $vms = self::loadFromVms($kazoo_account);
        foreach($vms as $vm) {
            Log::debug("cache add kazoo voicemailbox name:%s to account:%s", $vm->name, $kazoo_account->name);
            $account->addKazooVm($vm);
        }
    }

    public static function loadFromUsers($account) {
        $users = [];
        foreach($account->Users() as $element) {
            $user = $element->fetch();
            $users[] = $user;
        }
        return $users;
    }

    public static function loadFromVms($account) {
        $vms = [];
        foreach($account->VMBoxes() as $element) {
            $vm = $element->fetch();
            $vms[] = $vm;
        }
        return $vms;
    }

    public static function loadFromDevices($account) {
        $devices = [];
        foreach($account->Devices() as $element) {
            $device = $element->fetch();
            $profile = $device->makebusy->profile; // defined in Crossbar\Device
            Log::debug("create gateway for device id: %s", $device->id);
            $gateway = new Gateway(self::getProfile($profile), $device->id);
            $gateway->fromDevice($device, $account->realm);
            $devices[] = $device;
        }
        return $devices;
    }

    public static function loadFromResource($account) {
        $resources = [];
        foreach($account->Resources() as $element) {
            $resource = $element->fetch();
            $profile = $resource->makebusy->profile; // defined in Crossbar\Resource
            Log::debug("create gateway for resorce id: %s", $element->id);
            $gateway = new Gateway(self::getProfile($profile), $resource->id);
            $gateway->fromResource($resource, $account->realm);
            $resources[] = $resource;
        }
        return $resources;
    }

}