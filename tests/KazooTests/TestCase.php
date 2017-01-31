<?php

namespace KazooTests;

use \PHPUnit_Framework_TestCase;

use \MakeBusy\Common\Configuration;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

use \MakeBusy\FreeSWITCH\Channels\Channels;
use \MakeBusy\FreeSWITCH\Channels\Channel;
use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Kazoo\Applications\Crossbar\Resource;
use \MakeBusy\Kazoo\AbstractTestAccount;
use \MakeBusy\FreeSWITCH\Esl\Connection as EslConnection;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;
use \MakeBusy\Kazoo\Gateways;
use \Exception;
use Kazoo\Api\Exception\ApiException;
use Kazoo\HttpClient\Exception\NotFound;
use \MakeBusy\Kazoo\Applications\Callflow\FeatureCodes;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    protected static $account;
    protected static $type;
    protected static $base_type;

    /**
    * @dataProvider sipUriProvider
    */
    public function testMain($sipUri) {
        self::safeCall(function() use ($sipUri) {
            $this->main($sipUri);
        });
    }

    // override this to run a test
    public function main($sip_uri) {
    }

    // override this to set up case
    public static function setUpCase() {
        FeatureCodes::create(self::$account);
        self::$account->createOffnetNoMatch();
        self::$account->createAccountMetaflow();
    }

    // override this to cleanup after case
    public static function tearDownCase() {
    }

    // override this to set up particular test
    public function setUpTest() {
    }

    // override this to tear down particular test
    public function tearDownTest() {
    }

    public function sipUriProvider() {
        $re = [];
        foreach(Configuration::sipTargets() as $sipUri) {
            $re[] = [$sipUri];
        }
        return $re;
    }

    public static function getEsl($type = "auth") {
        return EslConnection::getInstance($type);
    }

    public static function getProfile($profile) {
        return self::getEsl($profile)->getProfiles()->getProfile("profile");
    }

    public static function getGateways($profile) {
        return self::getProfile($profile)->getGateways();
    }

    public static function safeCall($callable) {
        try {
            $callable();
        }
        catch(ApiException $e) {
            $error = json_decode((string) $e->getResponse()->getBody());
            unset($error->auth_token);
            Log::error("Kazoo API error: %s", json_encode($error, JSON_PRETTY_PRINT));
            throw($e);
        }
        catch(NotFound $e) {
            $error = json_decode((string) $e->getResponse()->getBody());
            unset($error->auth_token);
            Log::error("Kazoo resource error: %s", json_encode($error, JSON_PRETTY_PRINT));
            throw($e); // test suite uses it
        }
        catch(Exception $e) {
            Log::error("Generic exception error: %s, code: %d", $e->getMessage(), $e->getCode());
            throw($e); // test suite uses it
        }
    }

    public function setUp() {
        self::safeCall(function() {
            $this->setUpTest();
        });
    }

    public function tearDown() {
        self::safeCall(function() {
            $this->tearDownTest();
        });
    }

    public static function setUpBeforeClass() {
        $class = get_called_class();
        self::$type = AbstractTestAccount::shortName($class);
        self::$base_type = AbstractTestAccount::shortName(get_parent_class($class));
        Log::info("Start test: %s case: %s", self::$type, self::$base_type);
        if (isset($_ENV['CLEAN'])) {
            Log::debug("Cleaning MakeBusy traces from Kazoo");
            AbstractTestAccount::nukeTestAccounts();
        } else {
            Log::debug("Trying to use pre-created Kazoo's MakeBusy setup, creating entities if necessary");
        }

        self::safeCall(function() {
            if( ! isset($_ENV['SKIP_ACCOUNT'])) {
                self::$account = new TestAccount(get_called_class());
            }
            static::setUpCase();
        });

        if(isset(self::$account)) {
            $is_loaded = self::$account->isLoaded();
        } else {
            $is_loaded = false;
        }

        self::syncSofiaProfile("auth", $is_loaded);
        self::syncSofiaProfile("carrier", $is_loaded);
        self::syncSofiaProfile("pbx", $is_loaded);
    }

    public static function tearDownAfterClass() {
        Log::info("Teardown test: %s case: %s\n\n", self::$type, self::$base_type);
        self::safeCall(function() {
            static::tearDownCase();
        });
    }

    public static function syncSofiaProfile($profile_name, $loaded = false, $timeout = 10) {
        $profile = self::getProfile($profile_name);

        if(isset($_ENV['HUPALL'])) {
            $profile->getEsl()->api("hupall");
        }

        if ($loaded) {
            if (isset($_ENV['SKIP_REGISTER'])) {
                return;
            }

            if (isset($_ENV['RESTART_PROFILE'])) {
                $profile->safe_restart();
            } else {
                $profile->register(false);
            }
        } else {
            $profile->safe_restart();
        }

        if( ($wait = $profile->waitForRegister($profile->getRegistered())) > 0) {
            Log::error("fs %s %d gateways are not registered, repeat registration after 5 seconds", $profile_name, $wait);
            sleep(5);
            $profile->register(false);
            if( ($wait = $profile->waitForRegister($profile->getRegistered())) > 0) {
                Log::error("fs %s %d gateways are not registered still, giving up", $profile_name, $wait);
                Log::error("fs %s sofia status:\n%s", $profile_name, $profile->status()->getBody());
                throw new Exception("gateways weren't registered");
            }
        }
        if ( ($wait = $profile->waitForGateways($profile->getUnregistered())) > 0) {
            Log::error("fs %s %d gateways are absent in profile", $profile_name, $wait);
            Log::error("fs %s sofia status:\n%s", $profile_name, $profile->status()->getBody());
            throw new Exception("gateways are absent");
        }
    }

    public static function waitKazooForGateways($profile, $timeout = 10) {
        $not_seen = [];
        foreach($profile->getRegistered() as $r) {
            $name = $r->getName();
            $not_seen[$name] = true;
        }
        while(($timeout>0) && (($unseen = self::notSeenInKazoo($not_seen)) > 0)) {
            Log::debug("%d gateways are not present in kazoo, wait", $unseen);
            sleep(1);
            $timeout--;
        }
        self::assertFalse($timeout == 0, "error waiting for one or more gateways in Kazoo");
    }

    public static function notSeenInKazoo($not_seen) {
        $kazoo_devices = Gateways::loadFromKazoo(self::$account->getAccount(), 'Devices');
        $kazoo_resources = Gateways::loadFromKazoo(self::$account->getAccount(), 'Resources');

        foreach(array_merge($kazoo_devices, $kazoo_resources) as $r) {
            $name = $r->id;
            if(isset($not_seen[$name])) {
                unset($not_seen[$name]);
            }
        }
        return count($not_seen);
    }

    public static function getSipTargets() {
        return Configuration::sipTargets();
    }

    public static function getSipGateways() {
        return Configuration::sipGateways();
    }

    public static function getRandomSipTarget() {
        return Configuration::randomSipTarget();
    }

    public static function ensureChannel($ch) {
        self::assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $ch, "Expected channel wasn't created");
        return $ch;
    }

    public static function ensureEvent($ev) {
        self::assertInstanceOf("\\MakeBusy\\FreeSWITCH\\ESL\\Event", $ev, "Expected event wasn't received");
        return $ev;
    }

    // channel a is calling (originating), channel b is ringing
    public static function ensureAnswer($channel_a, $channel_b) {
        $channel_b->answer();
        self::ensureEvent($channel_b->waitAnswer());
        self::ensureEvent($channel_a->waitAnswer());
        Log::info("call %s has answered call %s", $channel_b->getUuid(), $channel_a->getUuid());
    }

    public static function ensureTalking($first_channel, $second_channel, $freq = 600) {
        $first_channel->playTone($freq, 3000, 0, 5);
        $tone = $second_channel->detectTone($freq);
        $first_channel->breakout();
        self::assertEquals($freq, $tone, "Expected tone wasn't heard");
    }

    public static function ensureNotTalking($first_channel, $second_channel, $freq = 600) {
        $first_channel->playTone($freq, 3000, 0, 5);
        $tone = $second_channel->detectTone($freq);
        $first_channel->breakout();
        self::assertNotEquals($freq, $tone, "Unexpected tone was detected");
    }

    public static function ensureTwoWayAudio($a_channel, $b_channel) {
        self::ensureTalking($a_channel, $b_channel, 1600);
        self::ensureTalking($b_channel, $a_channel, 600);
    }

    public static function hangupBridged($a_channel, $b_channel) {
        $a_channel->hangup();
        self::ensureEvent($a_channel->waitDestroy());
        self::ensureEvent($b_channel->waitDestroy());
    }

    public static function hangupChannels() {
        foreach (func_get_args() as $channel) {
            $channel->hangup();
            self::ensureEvent($channel->waitDestroy());
        }
    }

    public static function expectPrompt($channel, $descriptor, $timeout = 10) {
        $tone = $channel->detectTone($descriptor, $timeout);
        $expected = strtolower($descriptor);
        self::assertEquals($expected, $tone);
    }

}
