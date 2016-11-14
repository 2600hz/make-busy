<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \MakeBusy\Common\Utils;

class Registrations
{
    private $test_account;

    public function getRegistrations(TestAccount $test_account,$filter=null) {
        $account = $test_account->getAccount();
        $registrations=$account->Registrations()->fetch();
        if ($filter) {
           list($key,$value)=explode("=", $filter);
           foreach ($account->Registrations()->fetch() as $registration) {
               if ($registration->$key==$value) {
                  return $registration;
               }
           }
        } else {
           return $registrations;
        }
    }

}
