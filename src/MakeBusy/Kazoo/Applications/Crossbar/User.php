<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \MakeBusy\Kazoo\SDK;

use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class User {
    static private $counter = 1;

    private $test_account;
    private $user;
    private $loaded = false;
    private $callflow_numbers;

    public function __construct(TestAccount $account, array $options = array()) {
        $name = "User " . self::$counter++;
        $this->test_account = $account;
        $kazoo_user = $account->getKazooUser($name);
        if (is_null($kazoo_user)) {
            $this->initialize($account, $name, $options);
        } else {
            $this->setUser($kazoo_user);
            $this->loaded = true;
        }
    }

    public function initialize(TestAccount $test_account, $first_name, array $options = array()) {
        $account = $test_account->getAccount();

        $email = str_replace(' ', '', $first_name) . "@2600hz.com";

        $user = $account->User();
        $user->first_name = $first_name;
        $user->last_name = "MakeBusy";
        $user->username = $email;
        $user->email = $email;
        $user->password = Utils::randomString();
        $user->priv_level = "admin";
        $user->enabled = TRUE;

        $user->makebusy = new stdClass();
        $user->makebusy->test = TRUE;

        $user->caller_id = new stdClass();

        $user->caller_id->internal = new stdClass();
        $user->caller_id->internal->name = "Internal " . $first_name;
        $user->caller_id->internal->number = "2000";

        $user->caller_id->external = new stdClass();
        $user->caller_id->external->name = "External " . $first_name;
        $user->caller_id->external->number = "200000000";

        $user->caller_id->emergency = new stdClass();
        $user->caller_id->emergency->name = "Emergency " . $first_name;
        $user->caller_id->emergency->number = "2911";

        $user = $this->mergeOptions($user, $options);
        $user->save();
        $this->setUser($user);
    }

    private function mergeOptions($user, $options) {
        foreach ($options as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $user->$key = $this->mergeOptions($user->$key, $value);
            } else if (is_null($value)) {
                unset($user->$key);
            } else {
                $user->$key = $value;
            }
        }

        return $user;
    }

    private function setTestAccount($account){
        $this->test_account = $account;
    }

    private function getTestAccount(){
        return $this->test_account;
    }

    private function getAccount(){
        return $this->getTestAccount()->getAccount();
    }

    public function getUser(){
        return $this->user->fetch();
    }

    private function setUser($user){
        $this->user = $user;
    }

    public function getId(){
        return $this->getUser()->getId();
    }

    public static function callflowNode(User $user) {
        $flow = new stdClass();

        $flow->module = "user";

        $flow->data = new stdClass();
        $flow->data->id = $user->getId();
        $flow->data->timeout = "20";
        $flow->data->can_call_self = false;

        $flow->children = new stdClass();

        return $flow;
    }

    public function createUserCallFlow(array $numbers, array $objects = array()) {
        if ($this->loaded) {
            return;
        }
        $account = $this->getAccount();
        $callflow = $account->Callflow();
        $callflow->numbers = $numbers;

        $callflow->makebusy = new stdClass();
        $callflow->makebusy->test = TRUE;

        $callflow->flow = User::callflowNode($this);

        $callflow->save();

        $objects['callflow'][] = $callflow;

        $this->callflow_numbers = $numbers;

        return $objects;
    }

    public function getCallflowNumbers() {
        return $this->callflow_numbers;
    }

    public function setUserParam($param,$value) {
        $user = $this->getUser();
        $user->$param = $value;
        $user->save();
    }

    public function getUserParam($param){
        return $this->getUser()->$param;
    }

    public function enableUserCF(array $options = array()) {
        $user = $this->getUser();
        $user->call_forward = new stdClass();
        foreach ($options as $key => $value) {
            $user->call_forward->$key = $value;
            if (is_null($value)) {
                unset($options[$key]);
            }
        }
        $user->save();
    }

    public function resetUserCF(array $options = array()) {
        $user = $this->getUser();
        unset($user->call_forward);
        $user->save();
    }

    public function getCfParam($param){
        return $this->getUser()->call_forward->$param;
    }

    public function setCfParam($param, $value){
        $user = $this->getUser();
        $user->call_forward->$param = $value;
        $user->save();
    }

    public function resetCfParams($number = null){
        $user = $this->getUser();

        $user->call_forward = new StdClass;

        if ($number){
            $user->call_forward->enabled        = TRUE;
            $user->call_forward->number         = $number;
        } else {
            $user->call_forward->enabled        = FALSE;
        }

        $user->call_forward->substitute         = FALSE;
        $user->call_forward->require_keypress   = FALSE;
        $user->call_forward->keep_caller_id     = FALSE;
        $user->call_forward->direct_calls_only  = FALSE;
        $user->call_forward->failover           = FALSE;
        $user->save();
    }

    public function getCidParam($type = "external"){
       return $this->getUser()->caller_id->$type;
    }

}
