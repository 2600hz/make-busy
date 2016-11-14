<?php

namespace MakeBusy\Kazoo;

use \GuzzleHttp\Event\BeforeEvent;

use Shared\Common\Log;

/**
 *
 */
class MakebusyListener
{
    /**
     *
     * @param \Kazoo\SDK $sdk
     */
    public function __construct() {
    }

    /**
     *
     * @param \Guzzle\Event\BeforeEvent $event
     */
    public function onRequestBeforeSend(BeforeEvent $event) {
         Log::debug("issuing Kazoo request: %s %s", $event->getRequest()->getMethod(), $event->getRequest()->getUrl());
    }
}
