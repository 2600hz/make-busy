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
use \Exception;
use Kazoo\Api\Exception\ApiException;
use Kazoo\HttpClient\Exception\NotFound;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    protected static $account;

    /**
    * @dataProvider sipUriProvider
    */
    public function testMain($sipUri) {
        $this->main($sipUri);
    }

    // override this to run a test
    public function main($sip_uri) {
    }

    // override this to set up case
    public static function setUpCase() {
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
            self::assertTrue(false, "Kazoo API error: " . json_encode($error, JSON_PRETTY_PRINT));
        }
        catch(NotFound $e) {
            $error = json_decode((string) $e->getResponse()->getBody());
            unset($error->auth_token);
            self::assertTrue(false, "Kazoo resource error: " . json_encode($error, JSON_PRETTY_PRINT));
        }
        catch(Exception $e) {
            print
            self::assertTrue(false, "Generic exception: " . $e->getMessage() . " code: " . $e->getCode());
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
        if (isset($_ENV['CLEAN'])) {
            AbstractTestAccount::nukeTestAccounts();
            // TODO: hup only test channels (e.g. BS-.*)
            self::getEsl("auth")->api("hupall");
        } else {
            Log::debug("use existing Kazoo's MakeBusy setup");
        }

        self::safeCall(function() {
            self::$account = new TestAccount(get_called_class());
            static::setupCase();
        });

        self::syncSofiaProfile("auth", self::$account->isLoaded());
        self::syncSofiaProfile("carrier", self::$account->isLoaded());
        self::syncSofiaProfile("pbx", self::$account->isLoaded());
    }

    public static function tearDownAfterClass() {
        self::safeCall(function() {
            static::tearDownCase();
        });
    }

    public static function syncSofiaProfile($profile_name, $loaded = false) {
        $profile = self::getProfile($profile_name);
        if ($loaded) {
            if (isset($_ENV['RESTART_PROFILE'])) {
                $profile->restart();
            } else {
                if (isset($_ENV['SKIP_REGISTER'])) {
                    return;
                }
                $profile->register(false);
            }
        } else {
            $profile->restart();
        }
        self::assertTrue(0 == $profile->waitForRegister($profile->getRegistered()), "some gateways weren't registered");
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
