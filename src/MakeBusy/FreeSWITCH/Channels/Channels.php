<?php
namespace MakeBusy\FreeSWITCH\Channels;

use \MakeBusy\FreeSWITCH\Esl\Event;
use \MakeBusy\Common\Log;

class Channels
{
    private $channels = array();
    private $wait_for = array();
    private $esl;

    public function __construct($esl) {
        $this->esl = $esl;
    }

    public function getChannel($value, $header = 'Unique-ID', $direction = null) {
        foreach ($this->getChannels() as $uuid => $channel) {
            if ( $uuid === $value && $header == 'Unique-ID' ) return $channel;
            $event = $channel->getEvent();
            $channel_value = $event->getHeader($header);

            if ((!$direction || $event->getHeader('Call-Direction') == $direction)
                && strpos($channel_value, $value) !== FALSE
            ) {
                Log::debug("found expected channel with call-id %s", $channel->getUuid());
                return $channel;
            }
        }

        return null;
    }

    // FreeSWITCH, please wait for an outbound call containing a $header:$value.
    public function waitForOutbound($value, $header = 'Caller-Destination-Number', $timeout = 5) {
        Log::debug("fs %s wait outbound channel header:%s value:%s for %d seconds", $this->esl->getType(), $header, $value, $timeout);
        $this->wait_for_dead[$value] = false; // death watch
        $start = time();
        while(1) {
            $this->esl->recvEventTimed(25);

            if ($this->wait_for_dead[$value]) {
                Log::notice("fs %s failed to create outbound channel header:%s value:%s", $this->esl->getType(), $header, $value);
                unset($this->wait_for_dead[$value]);
                return null;
            }

            $channel = $this->getChannel($value, $header, 'outbound');
            if ($channel) {
                Log::debug("fs %s new outbound channel header:%s value:%s", $this->esl->getType(), $header, $value);
                $this->remove($channel);
                return $channel;
            }

            if ((time() - $start) > $timeout) {
                Log::notice("fs %s timeout outbound channel header:%s value:%s", $this->esl->getType(), $header, $value);
                return null;
            }

            usleep(2500);
        }
    }

    // FreeSWITCH, please wait for an inbound call containing a $header:$value.
    public function waitForInbound($value, $timeout = 5, $header = 'Caller-Destination-Number') {
        Log::debug("fs %s wait inbound channel header:%s value:%s for %d seconds", $this->esl->getType(), $header, $value, $timeout);
        $start = time();
        while(1) {
            $this->esl->recvEventTimed(250);
            $channel = $this->getChannel($value, $header, 'inbound');
            if ($channel) {
                Log::debug("fs %s new inbound channel header:%s value:%s", $this->esl->getType(), $header, $value);
                $this->remove($channel);
                return $channel;
            }

            if ((time() - $start) > $timeout) {
                Log::notice("fs %s timeout inbound channel header:%s value:%s", $this->esl->getType(), $header, $value);
                return null;
            }
            usleep(25000);
        }
    }

    /*
    * Return TRUE if channel ID doesn't exist or return NULL if timeout after X seconds.
    */
    public function waitForDestroy($uuid, $timeout = 5){
         Log::debug("channel:%s wait for destroy for %d seconds", $uuid, $timeout);
         $start = time();
         while(1){
            $channel = $this->getChannel($uuid);

            // TODO: test this, I am not sure this works as expected....
            if (!$channel) {
                return TRUE;
            }

            if ((time() - $start) > $timeout) {
                Log::error("channel:%s timout waiting for CHANNEL_DESTROY", $uuid);
                return null;
            }

            $this->esl->recvEventTimed(25);
            usleep(250);
        }
    }

    public function remove(Channel $channel) {
        $uuid = $channel->getUuid();
        if (isset($this->channels[$uuid])) {
            unset($this->channels[$uuid]);
        }
    }

    public function newEvent(Event $event) {
        $event_name = $event->getHeader('Event-Name');
        switch($event_name) {
            case 'CHANNEL_CREATE':
                $this->created($event);
                break;
            case 'CHANNEL_DESTROY':
                $this->destroyed($event);
                break;
            default:
                break;
        }
    }

    private function created(Event $event) {
        if ($event->getHeader('Event-Name') != 'CHANNEL_CREATE') {
            Log::error("the header Event-Name doesn't contain the value 'CHANNEL_CREATE'");
            return null;
        }

        $this->add(new Channel($this->esl, $event));
    }

    private function destroyed(Event $event) {
        $uuid = $event->getHeader('Unique-ID');
        Log::debug("destroying channel %s", $uuid);
        if (isset($this->channels[$uuid])) {
            unset($this->channels[$uuid]);
        }
        if (isset($this->wait_for_dead[$uuid])) {
            $this->wait_for_dead[$uuid] = true;
        }
    }

    public function getChannels() {
        return $this->channels;
    }

    private function add(Channel $channel) {
        $this->channels[$channel->getUuid()] = $channel;
    }
}
