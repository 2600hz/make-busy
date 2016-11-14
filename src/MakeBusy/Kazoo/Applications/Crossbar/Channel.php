<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Configuration;

class Channel
{
    private $test_account;
    private $api_channel;

    public function __construct(TestAccount $test_account, $uuid) {
        $this->setTestAccount($test_account);
        $this->setUuid($uuid);
    }

    public function transfer($target, $takeback_dtmf = "*1", $moh = NULL) {
        $data = array("action" => "transfer"
                      ,"target" => $target
                      ,"takeback_dtmf" => $takeback_dtmf
                      ,"moh" => $moh
        );

        $this->api_channel->executeCommand(array_filter($data));
        return $this;
    }

    public function hangup() {
        $data = array("action" => "hangup");

        $this->api_channel->executeCommand(array_filter($data));
        return $this;
    }

    private function getTestAccount() {
        return $this->test_account;
    }

    public function getChannel() {
        return $this->api_channel->fetch();
    }

    public function getId() {
        return $this->api_channel->getId();
    }

    private function setTestAccount(TestAccount $test_account) {
        $this->test_account = $test_account;
    }

    private function setUuid($uuid) {
        $this->api_channel = $this->getAccount()->Channel($uuid);
    }

    private function getAccount() {
        return $this->test_account->getAccount();
    }

}
