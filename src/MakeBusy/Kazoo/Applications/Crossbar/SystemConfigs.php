<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class SystemConfigs
{
    private $test_account;

    public function getSystemConfigs(TestAccount $test_account,$filter=null) {
        $account = $test_account->getAccount();
        return $account->SystemConfigs()->fetch();
    }

    public function setSystemConfigsCrossbarParam(TestAccount $test_account,$parametr,$value) {
        $account = $test_account->getAccount();
        $config = $account->SystemConfig();
        $config->$parametr=$value;
        $config->update("crossbar");
    }

    public function setCarrierAcl(TestAccount $test_account,$carrier_name,$cidr,$type,$networklist) {
        $account = $test_account->getAccount();
        $config = $account->SystemConfig();
        $config->fetchEcallmgr();
        $config->acls->$carrier_name=new stdClass();
        $config->acls->$carrier_name->type = $type;
        $name = "network-list-name";
        $config->acls->$carrier_name->$name = $networklist;
        $config->acls->$carrier_name->cidr  = $cidr;
        $config->acls->$carrier_name->makebusy = new stdClass();
        $config->acls->$carrier_name->makebusy->test = TRUE;
        $config->update("ecallmgr");
    }

    public function removeCarrierAcl(TestAccount $test_account, $carrier_name, $cidr){
        $account = $test_account->getAccount();
        $config = $account->SystemConfig();
        $config->fetchEcallMgr();
    }

    public function get(TestAccount $test_account) {
        $acc = $test_account->getAccount();
        $encoded = $acc->SystemConfig()->fetch();
        return json_decode($encoded->toJson());
    }

    public function createSection(TestAccount $test_account, $section) {
        $account = $test_account->getAccount();
        $config = $account->SystemConfig();
        $config->update($section);
    }

    public function setSectionKey(TestAccount $test_account, $section, $name, $value) {
        $account = $test_account->getAccount();
        $config = $account->SystemConfig()->fetch($section);
        $config->default->profiles->default->{$name} = $value;
        $config->update($section);
    }

    private function setSystemConfig($SystemConfig) {
        $this->systemconfig=$systemConfig;
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
