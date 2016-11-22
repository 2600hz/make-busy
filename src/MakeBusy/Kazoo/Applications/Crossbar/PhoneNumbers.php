<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \MakeBusy\Kazoo\SDK;

use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Configuration;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;
use \MakeBusy\Common\Log;

class PhoneNumbers
{
    private static $counter = 1;
    private $test_account;
    private $phone_number;
    private $loaded = false;

    public function __construct(TestAccount $account, $number, array $options = []) {
        $this->test_account = $account;
        $kazoo_phonenumber = $account->getFromCache('PhoneNumbers', $number);
        if (is_null($kazoo_phonenumber)) {
            $this->initialize($account, $number, $options);
        } else {
            $kazoo_phonenumber->notNew();
            $this->setPhoneNumber($kazoo_phonenumber);
            $this->loaded = true;
        }
    }

    public function initialize(TestAccount $test_account, $number, array $options = []) {
        $this->setTestAccount($test_account);

        $account = $this->getAccount();
        $phone_number = $account->PhoneNumber();
        $phone_number->fetch($number);
        $this->setPhoneNumber($phone_number);

        if (isset($options['cnam'])) {
            $this->setCnam($options['cnam']);
        }

        if (isset($options['change_lookup'])) {
            $this->changeLookup($options['change_lookup']);
        }

        if ($number[0]=="+") {
            $phone_number->save(substr($number,1));
        } else {
            $phone_number->save($number);
        }

        if (isset($options['activate'])) {
            $phone_number->activate($options['activate']);
        } else {
            $phone_number->activate(true);
        }
        return $this;
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

    public function getPhoneNumber() {
        return $this->phone_number;
    }

    private function setPhoneNumber($phone_number) {
        $this->phone_number = $phone_number;
    }

    public function setCnam($display_name) {
        $phone_number=$this->getPhoneNumber();
        if (!isset($phone_number->cnam)) {
            $phone_number->cnam=new stdClass();
        }
        $phone_number->cnam->display_name=$display_name;
        return $phone_number;
    }

    public function changeLookup($value) {
        $phone_number=$this->getPhoneNumber();
        if (!isset($phone_number->cnam)) {
            $phone_number->cnam=new stdClass();
        }
        $phone_number->cnam->enable_lookup=$value;
        $phone_number->cnam->inbound_lookup=$value;
        return $this;
    }

    public function activate() {
        $phone_number=$this->getPhoneNumber();
        $phone_number->activate($phone_number->getId());
        return $this;
    }

    public function setFailover($failovertype, $destination) {
        $phone_number=$this->getPhoneNumber();
        $phone_number->failover=new stdClass();
        if ($failovertype == 'e164' && strpos($destination, "+") !== 0) {
            $destination = "+" . $destination;
        }
        $phone_number->failover->$failovertype = $destination;
        return $this;
    }

    public function setE911($postal_code,$street_address,$extended_address,$locality,$region,$customer_name="") {
        $phone_number=$this->getPhoneNumber();
        $phone_number->dash_e911=new stdClass();
        $phone_number->dash_e911->postal_code=$postal_code;
        $phone_number->dash_e911->street_address=$street_address;
        $phone_number->dash_e911->extended_address=$extended_address;
        $phone_number->dash_e911->locality=$locality;
        $phone_number->dash_e911->region=$region;
        $phone_number->dash_e911->customer_name=$customer_name;
        return $this;
    }

    public function remove() {
       $phone_number=$this->getPhoneNumber();
       $phone_number->remove();
    }

    public function getId() {
        return $this->getPhoneNumber()->getId();
    }

    public function toNpan(){
        return substr($this->getId(), 1);
    }

    public static function toNpanS($n) {
        return substr($n, 1);
    }

    public function to1Npan(){
        return $this->getId();
    }

    public function toE164(){
        return '+' . $this->getId();
    }

    public function toUrlE164(){
        return '%2B' . $this->getId();
    }

    public function resetE911() {
        $phone_number=$this->getPhoneNumber();
        $phone_number->dash_e911=new stdClass();
        return $this;
    }

    public function save() {
        $this->getPhoneNumber()->save();
        return $this;
    }

}
