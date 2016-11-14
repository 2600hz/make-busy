<?php

namespace MakeBusy\Kazoo\Applications\Callflow;

use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;
use \CallflowBuilder\Builder;

use \CallflowBuilder\Node\Hotdesk;
use \CallflowBuilder\Node\Park;
use \CallflowBuilder\Node\Intercom;
use \CallflowBuilder\Node\Privacy;
use \CallflowBuilder\Node\Voicemail;
use \CallflowBuilder\Node\CallForward;

use \MakeBusy\Common\Log;
use \stdClass;

class FeatureCodes
{
    const FEATURE_PREFIX = "*";
    const REGEX_PREFIX   = "^\*";
    const REGEX_SUFFIX   = "([0-9]*)$";

    public static function create(TestAccount $account) {
        self::createHotdeskLogin($account);
        self::createHotdeskLogout($account);
        self::createHotdeskToggle($account);
        self::createPark($account);
        self::createParkValet($account);
        self::createParkRetrieve($account);
        self::createCfActivate($account);
        self::createCfDeactivate($account);
        self::createCfToggle($account);
        self::createCfUpdate($account);
        self::createIntercom($account);
        self::createPrivacy($account);
        self::createVmCheck($account);
        self::createVmCompose($account);
    }

    public static function createHotdeskLogin($account, $code = "11") {
        $hotdesk = new Hotdesk();
        $hotdesk->action("login");
        $builder       = new Builder(array(self::FEATURE_PREFIX . $code));
        $data          = $builder->build($hotdesk);
        Log::debug("attempting to create hotdesk login feature code");
        return $account->createCallflow($data);
    }

    public static function createHotdeskLogout($account, $code = "12") {
        $hotdesk = new Hotdesk();
        $hotdesk->action("logout");
        $builder        = new Builder(array(self::FEATURE_PREFIX . $code));
        $data           = $builder->build($hotdesk);
        Log::debug("attempting to create hotdesk logout feature code");
        return $account->createCallflow($data);
    }

    public static function createHotdeskToggle($account, $code = "13") {
        $hotdesk = new Hotdesk();
        $hotdesk->action("toggle");
        $builder = new Builder(array(self::FEATURE_PREFIX . $code));
        $data    = $builder->build($hotdesk);
        Log::debug("attempting to create hotdesk toggle feature code");
        return $account->createCallflow($data);
    }

    public static function createPark($account, $code = "3"){
        $park = new Park();
        $park->action("auto");
        $builder = new Builder(array(), array(self::REGEX_PREFIX . $code . self::REGEX_SUFFIX));
        $data = $builder->build($park);
        Log::debug("attempting to create auto park feature code");
        return $account->createCallflow($data);
    }

    public static function createParkValet($account, $code = "4") {
        $park = new Park();
        $park->action("park");
        $builder = new Builder(array(), array(self::REGEX_PREFIX . $code . self::REGEX_SUFFIX));
        $data = $builder->build($park);
        Log::debug("attempting to create park feature code");
        return $account->createCallflow($data);
    }

    public static function createParkRetrieve($account, $code = "5") {
        $park = new Park();
        $park->action("retrieve");
        $builder = new Builder(array(), array(self::REGEX_PREFIX . $code . self::REGEX_SUFFIX));
        $data = $builder->build($park);
        Log::debug("attempting to create park retrieve feature code");
        return $account->createCallflow($data);
    }

    public static function createCfActivate($account, $code = "72") {
        $call_forward = new CallForward();
        $call_forward->action("activate");
        $builder = new Builder(
            array(self::FEATURE_PREFIX . $code),
            array(self::REGEX_PREFIX . $code . self::REGEX_SUFFIX)
        );
        $data = $builder->build($call_forward);
        Log::debug("attempting to create callforward activate feature code");
        return $account->createCallflow($data);

    }

    public static function createCfDeactivate($account, $code = "73") {
        $call_forward = new CallForward();
        $call_forward->action("deactivate");
        $builder = new Builder(array(self::FEATURE_PREFIX . $code));
        $data  = $builder->build($call_forward);
        Log::debug("attempting to create callforward deactivate feature code");
        return $account->createCallflow($data);
    }

     //TODO: encountering error when setting this to standard code of *56 tracked bug in: KAZOO-3122
    public static function createCfUpdate($account, $code = "76") {
        $call_forward = new CallForward();
        $call_forward->action("update");
        $builder = new Builder(array(self::FEATURE_PREFIX . $code));
        $data = $builder->build($call_forward);
        Log::debug("attempting to create callforward update feature code");
        return $account->createCallflow($data);
    }

    public static function createCfToggle($account, $code = "74") {
        $call_forward = new CallForward();
        $call_forward->action("toggle");
        $builder = new Builder(
            array(),
            array(self::REGEX_PREFIX . $code . self::REGEX_SUFFIX));
        $data = $builder->build($call_forward);
        Log::debug("attempting to create callforward toggle feature code");
        return $account->createCallflow($data);
    }

    public static function createIntercom($account, $code = "0") {
        $intercom    = new Intercom();
        $builder = new Builder(
            array(),
            array(self::REGEX_PREFIX . $code . self::REGEX_SUFFIX));
        $data = $builder->build($intercom);
        Log::debug("attempting to create intercom feature code");
        return $account->createCallflow($data);
    }

    public static function createPrivacy($account, $code = "67") {
        $privacy    = new Privacy();
        //$privacy->mode("full");
        $builder = new Builder(
            array(),
            array(self::REGEX_PREFIX . $code . self::REGEX_SUFFIX));
        $data = $builder->build($privacy);
        Log::debug("attempting to create privacy feature code");
        return $account->createCallflow($data);
    }

    public static function createVmCheck($account, $code = "97"){
        $voicemail    = new Voicemail();
        $voicemail->action("check");
        $builder = new Builder(array(self::FEATURE_PREFIX . $code));
        $data = $builder->build($voicemail);
        Log::debug("attempting to create voicemail check feature code");
        return $account->createCallflow($data);
    }

    public static function createVmCompose($account, $code = "\*"){
        $voicemail    = new Voicemail();
        $voicemail->action("compose");
        $builder = new Builder(
            array(),
            array(self::REGEX_PREFIX . $code . self::REGEX_SUFFIX)
        );
        $data = $builder->build($voicemail);
        Log::debug("attempting to create voicemail compose feature code");
        return $account->createCallflow($data);
    }
}

