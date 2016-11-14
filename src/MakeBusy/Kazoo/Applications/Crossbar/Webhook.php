<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class Webhook
{
    private $test_account;
    private $webhook;

    public function __construct(TestAccount $test_account, array $options = array()) {
        if (!empty( $options['uri'] ) && !empty( $options['hook'] )) {
            $this->setTestAccount($test_account);
            $account = $test_account->getAccount();

            $name = "Webhook " . Utils::randomString();
            $webhook = $account->Webhook();
            $webhook->name = $name;
            $webhook->uri = $options['uri'];
            $webhook->hook = $options['hook'];
            $webhook->http_verb = "post";
            $webhook->save();
        } else {
            throw new Exception("Requires uri and hook");
        }
    }

    private function setTestAccount(TestAccount $test_account) {
        $this->test_account = $test_account;
    }
}
