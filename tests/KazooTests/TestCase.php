<?php

namespace KazooTests;

require_once('ESL.php');

//use \PHPUnit\Framework\TestCase;
//use \PHPUnit_Framework_TestSuite;

use \MakeBusy\Common\Configuration;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;
use \MakeBusy\Kazoo\AbstractTestAccount;
use \MakeBusy\FreeSWITCH\Esl\Connection as EslConnection;
use \MakeBusy\Kazoo\Applications\Callflow\FeatureCodes;
use \MakeBusy\Kazoo\SDK;

use \Exception;
use \Kazoo\Api\Exception\ApiException;
use \Kazoo\HttpClient\Exception\HttpException;
use \Kazoo\Api\Exception\Conflict;

use \ReflectionClass;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;

function handleError($e) {
    $error = json_decode((string) $e->getResponse()->getBody());
    unset($error->auth_token);
    Log::error("Kazoo API error: %s", json_encode($error, JSON_PRETTY_PRINT));
    throw($e);
}

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
	protected static $is_suite = false;
	protected static $setup = false;
    protected static $account = null;
    protected static $type;
    protected static $base_type;
    protected static $system_configs = [];

    protected static $tones = [
    		"TALKING" => 500,
    		"TWO-WAY-TALKING" => 550
    ];

    /**
    * @dataProvider sipUriProvider
    */
    public function testMain($sipUri) {
    	try {
    		self::safeCall(function() use ($sipUri) {
    			$this->main($sipUri);
    		});
    	}
    	catch(Exception $e) {
    		Log::error("Generic exception error: %s, code: %d", $e->getMessage(), $e->getCode());
    		self::flushChannels();
    		throw($e);
    	}
    	self::flushChannels();
    }

    public static function flushChannels() {
    	self::hangupSofiaChannels("auth");
    	self::hangupSofiaChannels("carrier");
    	self::hangupSofiaChannels("pbx");
    }

    public static function hangupSofiaChannels($profile_name) {
    	$profile = self::getProfile($profile_name);
    	$profile->getEsl()->hangupChannels();
    }

/*     
    public static function suite()
    {
    	$class = get_called_class();
    	$type = AbstractTestAccount::shortName($class);
    	$base_type = AbstractTestAccount::shortName(get_parent_class($class));

    	$suite = new PHPUnit_Framework_TestSuite;
    	
    	if($base_type != "TestCase") {
    		$suite->addTest(new PHPUnit_Framework_TestSuite($class));
    		return $suite;
    	}

    	$obj = new ReflectionClass($class);
    	$filename = $obj->getFileName();
    	$path = str_replace("TestCase.php", "/", $filename);
    	
    	$directory = new RecursiveDirectoryIterator($path , RecursiveDirectoryIterator::SKIP_DOTS);
    	$fileIterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
    	foreach ($fileIterator as $file) {
    		if ($file->getExtension() == "php") {
    			if ($file->isReadable()) {
    				include_once $file->getPathname();
    			} else {
    				Log::info("not readable ? %s", $file->getPathname());
    			}
    		}
    	}

    	$this_class = get_called_class();
    	$children = array();
    	foreach( get_declared_classes() as $class ){
    		if( is_subclass_of( $class, $this_class) ) {
    			$children[] = new ReflectionClass($class);
    		}
    	}

    	foreach($children as $child) {
    		$has_tests = false;
    		foreach ($child->getMethods() as $method) {
    			if($suite->isTestMethod($method) && $method->getName() != 'testMain') {
    				$has_tests = true;
    				$t = PHPUnit_Framework_TestSuite::createTest($child, $method->getName());
    				$suite->addTest($t);
    			}
    		}
    		if(! $has_tests ) {
	    		$t = PHPUnit_Framework_TestSuite::createTest($child, 'testMain');
	    		$suite->addTest($t);
    		}
    	}

    	$suite->setName($obj->name);
    	self::$is_suite= true;
    	
    	return $suite;
    }   
 */

    // override this to run a test
    public function main($sip_uri) {
    	self::assertTrue(true);
    }

    // override this to save system configs
    protected static function system_configs() {
        return [];
    }

    // override this to set up case
    public static function setUpCase() {
    	self::safeCall(function() {
	        FeatureCodes::create(self::$account);
	        self::$account->createOffnetNoMatch();
	        self::$account->createAccountMetaflow();
    	});
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

    public static function safeCall($callable, $try=0) {
        try {
            $callable();
        }
        catch(Conflict $e) {
        	if($try < 3) {
        		Log::debug("Conflict exception caught, retrying");
        		self::safeCall($callable, $try + 1);        		
        	} else {
        		Log::debug("Conflict exception caught, NOT retrying");
        		handleError($e);        		
        	}
        }
        catch(ApiException $e) {
            handleError($e);
        }
        catch(HttpException $e) {
            handleError($e);
        }
        catch(Exception $e) {
            Log::error("Generic exception error: %s, code: %d", $e->getMessage(), $e->getCode());
            throw($e);
        }
    }

    public function setUp() {
//    	if(self::$is_suite) {
//    		$this->setUpBeforeClass();
//    	}
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
    	$base_type = AbstractTestAccount::shortName(get_parent_class($class));

    	Log::notice("BEFORE test: %s case: %s, %s", $base_type, self::$base_type, self::$setup);
    	
    	if(self::$setup && isset(self::$account) && $base_type == self::$base_type) {
    		self::$account->reset(self::$type);
	   		return;
    	}
    	
    	self::safeCall(function() {
	        self::saveSystemConfigs();
	        self::SystemConfig("token_buckets/default")->fetch()->change(["tokens_fill_rate"], 100);
    	});


    	AbstractTestAccount::nukeTestAccounts();
    	/*
    	if (isset($_ENV['CLEAN'])) {
            Log::debug("Cleaning MakeBusy traces from Kazoo");
            AbstractTestAccount::nukeTestAccounts();
        } else {
        	if((! isset(self::$base_type)) || self::$base_type != $base_type) {
        		Log::debug("Resetting MakeBusy Account");
        		AbstractTestAccount::nukeTestAccounts();
        	} else {
        		Log::debug("Trying to use pre-created Kazoo's MakeBusy setup, creating entities if necessary");        		
        	}
        }
        */

        self::resetSofiaProfile("auth");
        self::resetSofiaProfile("pbx");
        self::resetSofiaProfile("carrier");

        self::$setup = true;
        self::$base_type = $base_type;
        
        self::safeCall(function() {
//            if( ! isset($_ENV['SKIP_ACCOUNT'])) {
                self::$account = new TestAccount(get_called_class());
//            }
            static::setUpCase();
        });

//         if(isset(self::$account)) {
//             $is_loaded = self::$account->isLoaded();
//         } else {
//             $is_loaded = false;
//         }
        
//         static::syncProfiles($is_loaded);
        static::syncProfiles(false);

    }
    
    public static function syncProfiles($is_loaded) {
    	self::syncSofiaProfile("auth", $is_loaded);
    	self::syncSofiaProfile("carrier", $is_loaded);
    	self::syncSofiaProfile("pbx", $is_loaded);
    }

    public static function tearDownAfterClass() {
        Log::notice("Teardown test: %s case: %s\n\n", self::$type, self::$base_type);
        self::safeCall(function() {
            static::tearDownCase();
        });
        self::flushChannels();
        self::restoreSystemConfigs();
    }

    public static function resetSofiaProfile($profile_name) {
    	$profile = self::getProfile($profile_name);
    	$profile->resetGateways();
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
                $status = $profile->status();
                Log::error("fs %s sofia status:\n%s", $profile_name, $status == null ? "down" : $status->getBody());
                throw new Exception("gateways weren't registered");
            }
        }
        if ( ($wait = $profile->waitForGateways($profile->getUnregistered())) > 0) {
            Log::error("fs %s %d gateways are absent in profile", $profile_name, $wait);
            $status = $profile->status();
            Log::error("fs %s sofia status:\n%s", $profile_name, $status == null ? "down" : $status->getBody());
            throw new Exception("gateways are absent");
        }
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
        static::onChannelReady($ch);
        return $ch;
    }

    public static function ensureEvent($ev) {
        self::assertInstanceOf("\\ESLevent", $ev, "Expected event wasn't received");
        return $ev;
    }

    public static function onChannelReady($ch) {
    	$ch->startToneDetection("MAKEBUSY");
    }

    public static function onChannelAnswer($ch) {
    	$ch->startToneDetection("MAKEBUSY");
    }
    
    // channel a is calling (originating), channel b is ringing
    public static function ensureAnswer($channel_a, $channel_b, $timeout = 5) {
        $channel_b->answer();
        self::ensureAnswered($channel_b, $timeout);
        self::ensureAnswered($channel_a, $timeout);
        Log::info("call %s has answered call %s", $channel_b->getUuid(), $channel_a->getUuid());
    }

    public static function ensureAnswered($channel, $timeout=5) {
    	self::ensureChannel($channel);
    	self::ensureEvent($channel->waitAnswer($timeout));
    	static::onChannelAnswer($channel);
    	return $channel;
    }
    
    public static function ensureTalking($first_channel, $second_channel, $tone = "TALKING", $timeout = 5) {
    	$freq = self::$tones[$tone];
    	$first_channel->playTone($freq, $timeout*1000, 0, 1);
    	$res = $second_channel->waitForTone($tone, $timeout);
    	$first_channel->breakout();
    	self::assertTrue($res, sprintf("channels are not talking : %s", $tone));
    }
    
    public static function ensureNotTalking($first_channel, $second_channel, $tone = "TALKING", $timeout = 5) {
    	$freq = self::$tones[$tone];
    	$first_channel->playTone($freq, $timeout*1000, 0, 1);
        $res = $second_channel->waitForTone($tone, $timeout);
        $first_channel->breakout();
        self::assertFalse($res, sprintf("channels are talking and they shouldn't : %s", $tone));
    }

    public static function ensureTwoWayAudio($a_channel, $b_channel, $timeout = 5) {
    	self::ensureTalking($a_channel, $b_channel, "TALKING", $timeout);
    	self::ensureTalking($b_channel, $a_channel, "TWO-WAY-TALKING", $timeout);
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
    	self::assertTrue($channel->waitForTone($descriptor, $timeout), sprintf("expected prompt %s not detected", $descriptor));
    }

    public static function waitForPrompt($channel, $descriptor, $timeout = 10) {
    	return $channel->waitForTone($descriptor, $timeout);
    }
    
    public static function expectEvent($channel, $event_name, $timeout = 10) {
    	$event = self::ensureEvent($channel->waitEvent($timeout, $event_name));
    	if($event_name== "CHANNEL_ANSWER") {
   			static::onChannelAnswer($channel);
    	}
    }

    public static function expectAnswer($channel, $timeout = 10) {
    	return self::expectEvent($channel, "CHANNEL_ANSWER", $timeout);
    }

    public static function assertIsSet($object, $key, $message = null) {
        self::assertTrue(isset($object->$key), $message);
    }

    public static function assertNotSet($object, $key, $message = null) {
        self::assertFalse(isset($object->$key), $message);
    }

    private static function saveSystemConfigs() {
        $configs = array_merge(static::system_configs(), ["token_buckets"]);
        foreach($configs as $config) {
            self::$system_configs[$config] = self::SystemConfig($config)->fetch();
        }
    }

    private static function restoreSystemConfigs() {
        $configs = array_merge(static::system_configs(), ["token_buckets"]);
        foreach($configs as $config) {
            self::$system_configs[$config]->save();
        }
    }

    public static function deleteSystemConfig($config) {
       try {
            self::SystemConfig($config)->remove();
        }
        catch(Exception $e) {
        }
    }

    public static function SystemConfig($config) {
        return SDK::getInstance()->SystemConfig($config);
    }

}
