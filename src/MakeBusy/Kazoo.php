<?php
namespace MakeBusy;
use \MakeBusy\Kazoo\SDK;

class Kazoo {

    protected function __construct() {
    }

    public static function loadAccounts($filter = array('has_key' => 'makebusy')) {
        $re = [];
        foreach(SDK::getInstance()->Accounts($filter) as $element) {
            $re[] = $element->fetch();
        }
        return $re;
    }

}