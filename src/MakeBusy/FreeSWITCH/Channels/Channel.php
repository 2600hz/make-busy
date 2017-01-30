<?php
namespace MakeBusy\FreeSWITCH\Channels;

use \MakeBusy\FreeSWITCH\Esl\Event;
use \MakeBusy\FreeSWITCH\Channels\Exceptions\ChannelException;
use \MakeBusy\Common\Log;

class Channel
{
    private $event;
    private $esl;

    public function __construct($esl, Event $event) {
        $this->event = clone $event;
        $this->esl = $esl;
        Log::debug("channel:%s is new %s", $this->getUuid(), $event->getHeader('Call-Direction'));
    }

    public function __destruct() {
        Log::debug("channel:%s is destructing, issuing uuid_kill", $this->getUuid());
        // oob destruction
        $this->esl->clone()->api_f("uuid_kill %s", $this->getUuid());
    }

    public function __toString() {
        return $this->getUuid();
    }

    public function getUuid() {
        return $this->getEvent()->getHeader('Unique-ID');
    }

    public function getEvent() {
        return $this->event;
    }

    public function answer() {
        $uuid = $this->getUuid();
        Log::debug("channel:%s attemptinng answer", $uuid);
        $this->esl->api("uuid_answer $uuid");
    }

    public function breakout() {
        $uuid = $this->getUuid();
        Log::debug("channel:%s attempting break", $uuid);
        $this->esl->api("uuid_break $uuid");
    }

    public function transfer($destination, $both = FALSE) {
        $uuid = $this->getUuid();

        if ($both) {
            $parties = "-both";
        } else {
            $parties = "-bleg";
        }

        $transfer = "uuid_transfer $uuid $parties $destination";

        Log::debug("channel:%s attempting transfer parties:%s destination:%s", $uuid, $parties, $destination);
        $this->esl->api($transfer);
    }

    public function hangup() {
        $uuid = $this->getUuid();
        Log::debug("channel:%s attempting hangup", $uuid);
        $this->esl->api("uuid_kill $uuid");
    }

    public function dump(){
        $uuid  = $this->getUuid();
        $event = $this->esl->api("uuid_dump $uuid");
        $event->convertPlainEvent(TRUE);
        return $event;
    }

    public function getAnswerState(){
        $event = $this->dump();
        return $event->getHeader("Answer-State");
    }

    public function getCallerIdNumber(){
        $event = $this->dump();
        return $event->getHeader("Caller-Caller-ID-Number");
    }

    public function getCallerIdName(){
        $event = $this->dump();
        return $event->getHeader("Caller-Caller-ID-Name");
    }

    public function getChannelCallState(){
        $event = $this->dump();
        return $event->getHeader("Channel-Call-State");
    }

    public function getAutoAnswerDetected() {
        $event = $this->dump();
        return $event->getHeader("variable_sip_auto_answer_detected");
    }

    public function log() {
        $args = func_get_args();
        $format = array_shift($args);
        $message = vsprintf($format, $args);

        $uuid = $this->getUuid();
        $this->esl->execute("log", "INFO $uuid|$message", $uuid);
    }

    public function onHold(){
        $uuid = $this->getUuid();
        Log::debug("channel:%s attempting hold", $uuid);
        $this->esl->api("uuid_hold $uuid");
    }

    public function offHold(){
        $uuid = $this->getUuid();
        Log::debug("channel:%s attempting resume", $uuid);
        $this->esl->api("uuid_hold off $uuid");
    }

    public function sendDtmf($dtmf_digit_string, $duration = "W"){
        $uuid = $this->getUuid();
        $dtmf = "$dtmf_digit_string\@$duration";
        Log::debug("channel:%s attempting to send dtmf:%s", $uuid, $dtmf);
        $this->esl->api("uuid_send_dtmf $uuid $dtmf");
    }

    public function recvDtmf($dtmf_digit_string, $duration = "w"){
        $uuid = $this->getUuid();
        $dtmf = sprintf("%s[@%s]", $dtmf_digit_string, $duration);
        Log::debug("channel:%s attempting to recieve dtmf:%s", $uuid, $dtmf);
        $this->esl->api("uuid_recev_dtmf $uuid $dtmf");
    }

    public function deflect($refer_uri){
        $uuid = $this->getUuid();
        Log::debug("channel:%s attempting to deflect to %s", $uuid, $refer_uri);
        $this->esl->api("uuid_deflect $uuid $refer_uri");
    }

    public function playMedia($media_file){
        $uuid = $this->getUuid();
        Log::debug("channel:%s attempting to play media file %s", $uuid, $media_file);
        $this->esl->api("uuid_broadcast $uuid $media_file both");
    }

    public function sayText($text,$leg){
        $uuid = $this->getUuid();
        $say = "say::en\sname_spelled\spronounced\s$text";
        Log::debug("channel:%s attempting to say %s leg:%s", $uuid, $say, $leg);
        $value = $this->esl->api("uuid_broadcast $uuid $say $leg");
    }

    public function playTone($freq, $duration = 2000, $space = 0, $loops = 1){
        $uuid = $this->getUuid();
        $tone_stream = "tone_stream://%($duration,$space,$freq);loops=$loops";
        Log::debug("channel:%s attempting to play tone_stream:%s", $uuid, $tone_stream);
        $this->esl->execute("playback", $tone_stream, $uuid);
    }

    public function stopTone() {
        $uuid = $this->getUuid();
        $this->esl->api("uuid_break $uuid");
    }

    public function detectLeTone($freq, $timeout = 30000, $hits=1) {
        Log::debug("channel:%s waiting for tone:%s hits:%s", $this->getUuid(), $freq, $hits);
        $this->bgDetectLeTone($freq, $hits);
        return $this->waitDetectTone($freq, $timeout / 1000);
    }

    public function bgDetectLeTone($freq, $hits=1) {
        $uuid = $this->getUuid();
        $this->esl->execute("tone_detect", "$freq $freq r 0 stop_tone_detect '' $hits", $uuid);
    }

    public function detectTone($name, $timeout = 5){
        $uuid = $this->getUuid();
        Log::debug("channel:%s waiting for tone:%s detection for:%s seconds", $uuid, $name, $timeout);
        $this->esl->api("spandsp_start_tone_detect $uuid $name");
        $tone = $this->waitDetectTone($name, $timeout);
        $this->esl->api("spandsp_stop_tone_detect $uuid");
        return $tone;
    }

    public function setVariables($name, $value) {
        $uuid = $this->getUuid();
        Log::debug("channel:%s set variable:%s to value:%s", $uuid, $name, $value);
        $this->esl->api("uuid_setvar $uuid $name $value");
    }

    public function getVariable($name) {
        $uuid = $this->getUuid();
        Log::debug("channel:%s get variable:%s", $uuid, $name);
        return $this->esl->api("uuid_getvar $uuid $name");
    }

    public function waitDetectTone($freq, $timeout) {
        $uuid = $this->getUuid();
        if ( ($event = $this->waitEvent($timeout, "DETECTED_TONE") ) ) {
            Log::debug("channel:%s detected tone %s", $uuid, $freq);
            return $event->getHeader("Detected-Tone");
        }
        return NULL;
    }

    public function waitAnswer($timeout = 5) {
        $uuid = $this->getUuid();
        Log::debug("channel:%s waiting for answer for %d seconds", $uuid, $timeout);
        return $this->waitEvent($timeout, "CHANNEL_ANSWER");
    }

    public function waitPark($timeout = 5) {
        $uuid = $this->getUuid();
        Log::debug("channel:%s waiting for answer for %d seconds", $uuid, $timeout);
        return $this->waitEvent($timeout, "CHANNEL_PARK");
    }

    private function waitEvent($timeout, $event_name) {
        $uuid = $this->getUuid();
        Log::debug("channel:%s wait event name:%s for %d seconds", $uuid, $event_name, $timeout);

        $start = time();
        // $this->esl->events($event_name);
        while(1){
            $now = time();
            $time_left = ($timeout - ($now - $start)) * 1000;

            if (($now - $start) >= $timeout){
                Log::notice("channel:%s timeout waiting for event name:%s", $uuid, $event_name);
                return NULL;
            }

            $event = $this->esl->recvEventTimed($time_left, $uuid);
            if (!$event) {
                continue;
            }

            if ($event->getHeader("Event-Name") === $event_name) {
                return $event;
            }
        }
    }

    public function waitBridge($timeout = 10){
        return $this->waitEvent($timeout, "CHANNEL_BRIDGE");
    }

    public function waitHangup(){
        $uuid = $this->getUuid();
        return $this->esl->getChannels()->waitForDestroy($uuid);
    }

    public function waitDestroy($timeout = 10) {
        return $this->waitEvent($timeout, "CHANNEL_DESTROY");
    }

    public function waitCallUpdate($timeout = 10) {
        $uuid = $this->getUuid();
        return $this->waitEvent($timeout, "CALL_UPDATE");
    }

    public function deflectChannel(Channel $ch, $referred_by) {
        $call_uuid = $ch->getUuid();
        $to_tag = $ch->getVariable('sip_to_tag');
        $from_tag = $ch->getVariable('sip_from_tag');
        $sip_uri = urldecode($ch->getVariable('sip_req_uri'));

        $refer_to =     '<sip:' . $sip_uri
                 . '?Replaces=' . $call_uuid
               . '%3Bto-tag%3D' . $to_tag
             . '%3Bfrom-tag%3D' . $from_tag
             . '>';

        $this->setVariables('sip_h_refer-to', $refer_to);
        $this->setVariables('sip_h_referred-by', $referred_by);
        $this->deflect($refer_to);
        $this->waitDestroy();
    }

}
