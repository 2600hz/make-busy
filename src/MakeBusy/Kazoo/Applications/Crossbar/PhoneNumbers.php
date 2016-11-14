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

  private $test_account;
  private $phone_number;

    public function __construct(TestAccount $test_account, $number, $activate = TRUE) {
        $this->setTestAccount($test_account);

        $account = $this->getAccount();
        $phone_number=$account->PhoneNumber();
        $phone_number->fetch($number);
        if ($number[0]=="+") {
            $phone_number->save(substr($number,1));
        } else {
            $phone_number->save($number);
        }

        if ($activate) {
            $phone_number->activate($number);
        }

        $this->setPhoneNumber($phone_number);
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
        return $this->phone_number->fetch();
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
        $phone_number->save();
    }

    public function changeLookup($value) {
        $phone_number=$this->getPhoneNumber();
        if (!isset($phone_number->cnam)) {
            $phone_number->cnam=new stdClass();
        }
        $phone_number->cnam->enable_lookup=$value;
        $phone_number->cnam->inbound_lookup=$value;
        $phone_number->save();
    }

    public function activate() {
        $phone_number=$this->getPhoneNumber();
        $phone_number->activate($phone_number->getId());
        $phone_number->save();
    }

    public function setFailover($failovertype, $destination) {
        $phone_number=$this->getPhoneNumber();
        $phone_number->failover=new stdClass();
        if ($failovertype == 'e164' && strpos($destination, "+") !== 0) {
            $destination = "+" . $destination;
        }
        $phone_number->failover->$failovertype = $destination;
        $phone_number->save();
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
       $phone_number->save();
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
       $phone_number->save();
    }

}
