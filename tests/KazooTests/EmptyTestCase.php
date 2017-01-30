<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Common\Log;

class EmptyTestCase extends TestCase
{
    public static function setUpCase() {
        Log::debug("Empty test case");
    }
}
