<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class SystemConfigs
{
    private $test_account;

    public function __construct($test_account) {
        $this->setTestAccount($test_account);
    }

    public function getSystemConfigs($filter=null) {
        $account = $self->test_account->getAccount();
        return $account->SystemConfigs()->fetch();
    }

    public function setSystemConfigsCrossbarParam($parametr, $value) {
        $account = $self->test_account->getAccount();
        $config = $account->SystemConfig("crossbar");
        $config->$parametr = $value;
        $config->update();
    }

    public static function setDefaultConfParam($name, $value) {
        $account = $self->test_account->getAccount();
        $config = $account->SystemConfig("conferences");
        Utils::mset($config->default, ['profiles', 'default', $name], $value);
        $config->update();
    }

    public static function setCarrierAcl($carrier_name, $cidr, $type, $networklist) {
        $account = $self->test_account->getAccount();
        $config = $account->SystemConfig("ecallmgr");
        Utils::mset($config->default, ['acls', $carrier_name, 'type'], $type);
        Utils::mset($config->default, ['acls', $carrier_name, 'network-list-name'], $networklist);
        Utils::mset($config->default, ['acls', $carrier_name, 'cidr'], $cidr);
        Utils::mset($config->default, ['acls', $carrier_name, 'makebusy', 'test'], true);
        $config->update();
    }

    public static function removeCarrierAcl($carrier_name) {
        $account = $self->test_account->getAccount();
        $config = $account->SystemConfig("ecallmgr");
        Utils::mset($config->default, ['acls', $carrier_name]);
        $config->update();
    }

    public function get() {
        $acc = $sellf->test_account->getAccount();
        $encoded = $acc->SystemConfig()->fetch();
        return json_decode($encoded->toJson());
    }

    private function setSystemConfig($SystemConfig) {
        $this->systemconfig = $systemConfig;
    }

    private function getTestAccount() {
        return $this->test_account;
    }

    private function getAccount() {
        return $this->test_account->getAccount();
    }

    private function setTestAccount(TestAccount $test_account) {
        $this->test_account = $test_account;
    }
}
