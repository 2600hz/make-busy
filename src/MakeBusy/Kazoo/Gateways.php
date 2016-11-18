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
            self::createGatewaysFromDevices($account, self::loadFromKazoo($account, 'Devices'));
            self::createGatewaysFromResources($account, self::loadFromKazoo($account, 'Resources'));
        }
    }

    public static function loadFromAccount(AbstractTestAccount $account) {
        $kazoo_account = $account->getAccount();
        foreach(['Devices', 'Resources', 'Users', 'VMBoxes', 'Webhooks', 'Conferences', 'Medias'] as $collection) {
            $items = self::loadCollection($account, $collection);
        }
        self::createGatewaysFromDevices($kazoo_account, $account->getCache('Devices'));
        self::createGatewaysFromRessources($kazoo_account, $account->getCache('Resources'));
    }

    public function loadCollection($account, $collection) {
        foreach(self::loadFromKazoo($account, $collection) as $item) {
            Log::debug("cache add %s name:%s to account:%s", $collection, $item->name, $account->name);
            $account->addToCache($collection, $item);
        }
    }

    public static function loadFromKazoo($account, $collection) {
        $items = [];
        foreach($account->$collection() as $element) {
            $item = $element->fetch();
            $items[] = $item;
        }
        return $items;
    }

    public static function createGatewaysFromDevices($account, $devices) {
       foreach($devices as $device) {
            $profile = $device->makebusy->profile; // defined in Crossbar\Device
            Log::debug("create gateway for device id:%s", $device->id);
            $gateway = new Gateway(self::getProfile($profile), $device->id);
            $gateway->fromDevice($device, $account->realm);
        }
    }

    public static function createGatewaysFromResources($account, $resources) {
        foreach($resources as $resource) {
            $profile = $resource->makebusy->profile; // defined in Crossbar\Resource
            Log::debug("create gateway for resorce id: %s", $element->id);
            $gateway = new Gateway(self::getProfile($profile), $resource->id);
            $gateway->fromResource($resource, $account->realm);
        }
    }

}