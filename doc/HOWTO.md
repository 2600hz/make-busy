# How to write tests

## Overview

To write a test you need to define a test case by subclassing TestCase class. In test case you can define a number of
TestAccount instances. Each TestAccount can have several instances of Device, User, Resource and Voicemail as class
static members. After setting up testing environment you need to define actual tests, by subclassing your defined TestCase.

## FreeSwitch types

You can have several (at least one) FreeSwitch instances that will simulate various SIP devices distinguished by "type".
Recommended is two, one for "auth" type devices like Devices, and another for "carrier" type devices, like Resources.
Please see supplied [config.json.dist](../etc/config.json.dist) for details.

## Complete example

Class DeviceTestCase:

```PHP
<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;

class DeviceTestCase extends TestCase
{
    protected static $a_device;
    protected static $b_device;

    const A_EXT = '1001';
    const B_EXT = '1002';
    const A_NUMBER = '5552221001';
    const B_NUMBER = '5552221002';

    public static function setUpCase() {
        self::$a_device = self::$account->createDevice("auth");
        self::$a_device->createCallflow([self::A_EXT, self::A_NUMBER]);

        self::$b_device = self::$account->createDevice("auth");
        self::$b_device->createCallflow([self::B_EXT, self::B_NUMBER]);
    }
}
```

class Call (wher we're going to ensure Devices can call each other):

```PHP
<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class Call extends DeviceTestCase {

    // here you do changes to your setup required to run the test, like updating Kazoo's Device properties
    public function setUpTest() {
    }

    // here you revert all the changes made in setUp()
    public function tearDownTest() {
    }

    // here you can test SomethingElse, test function names must begin with test prefix
    public function testSomethingElse() {
    }

    // main entry, will be called once per each Kamailio target
    public function main($sip_uri) {
        $target = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_b);
        self::ensureEvent($channel_a->waitPark());
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}
```

For convinience there are number of checks and utility functions defined in TestCase class, namely: ensureChannel
checks the result to be a Channel, and fails if it is not, ensureAnswer answers for $channel_b and ensures both channels
are answered, ensureEvent ensures the event was received, ensureTwoWayAudio checks channels can hear each other,
and hangupBridged ensures channels are properly hanged up. Please see [TestCase.php](../tests/KazooTests/TestCase.php)
for additional information.

Function main() will be called as many Kazoo's Kamailio targets are defined in config.json file, function testSomehtingElse()
will be called once without any arguments, and you basically should use only what you have defined in TestCase subclass,
functions setUp() and tearDown() will be called before and after test functions respectively.

Please see [Channel](../src/MakeBusy/FreeSWITCH/Channels/Channel.php) for methods defined for channel
(waitAnswer, waitHangup, sendDtmf, playTone, etc.)
