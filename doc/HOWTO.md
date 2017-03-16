# How to write tests

## Overview

To write a test you need to define a test case by subclassing TestCase class. In test case you can define a number of
TestAccount instances representing Kazoo's accounts (usually one).
Each TestAccount descendant class in turn can have several instances of Device, User, Resource, Voicemail and other Kazoo entities as class
static members. After setting up testing environment you need to define actual tests, by subclassing your defined TestCase.

## FreeSWITCH types

You can have several (at least one) FreeSWITCH instances that will simulate various SIP devices distinguished by "type".
Recommended is two, one for "auth" type devices like Devices, and another for "carrier" type devices, like Resources.
Please see supplied [config.json.dist](../etc/config.json.dist) for details.

## Programming notes

FreeSWITCH channels are represented by the [Channel](../src/MakeBusy/FreeSWITCH/Channels/Channel.php) class.

When a Channel object is destroyed, it tries to hang up the corresponding FreeSWITCH channel by issuing `uuid_kill` out of band (in separate FreeSWITCH connection), so pay attention for channel instances to be in scope.

## Complete example

Class DeviceTestCase:

```PHP
<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;

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

Class Call (where we're going to ensure Devices can call each other):

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

For convenience there are number of checks and utility functions defined in TestCase class, namely:

* `ensureChannel` - checks the result to be a Channel, and fails if it is not
* `ensureAnswer` - answers for $channel_b and ensures both channels are answered
* `ensureEvent` - ensures the event was received
* `ensureTwoWayAudio` - checks channels can hear each other
* `hangupBridged` ensures channels are properly hung up.

Please see [TestCase.php](../tests/KazooTests/TestCase.php) for additional information.

Function `main()` will be called with as many Kazoo-Kamailio targets are defined in config.json file

Function `testSomethingElse()` will be called once without any arguments and you basically should use only what you have defined in TestCase subclass.

Functions `setUpTest()` and `tearDownTest()` will be called before and after test functions respectively.

Please see [Channel](../src/MakeBusy/FreeSWITCH/Channels/Channel.php) for methods defined for channel (`waitAnswer`, `waitHangup`, `sendDtmf`, `playTone`, etc.)
