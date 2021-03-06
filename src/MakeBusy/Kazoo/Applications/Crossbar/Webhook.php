<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \MakeBusy\Common\Log;
use \MakeBusy\Common\Configuration;

class Webhook
{
    private $test_account;
    private $webhook;

    private static $counter = 1;
    private $loaded = false;

    public function __construct(TestAccount $account, array $options = array()) {
        $name = sprintf("%s WH %d", $account->getBaseType(), self::$counter++);
        $this->test_account = $account;
        $kazoo_webhook = $account->getFromCache('Webhooks', $name);
        if (is_null($kazoo_webhook)) {
            $this->initialize($account, $name, $options);
        } else {
            $this->setWebhook($kazoo_webhook);
            $this->loaded = true;
        }
    }

    public function initialize(TestAccount $test_account, $name, array $options = array()) {
        if (!empty( $options['uri'] ) && !empty( $options['hook'] )) {
            $account = $test_account->getAccount();
            $webhook = $account->Webhook();
            $webhook->name = $name;
            $webhook->uri = $options['uri'];
            $webhook->hook = $options['hook'];
            $webhook->http_verb = "post";
            $webhook->save();
            $this->setWebhook($webhook);
        } else {
            throw new Exception("Requires uri and hook");
        }
    }

    private function setTestAccount(TestAccount $test_account) {
        $this->test_account = $test_account;
    }

    private function setWebhook($hook) {
        $this->webhook = $hook;
    }
    
    public static function resetCounter() {
    	self::$counter = 1;
    }
    
}
