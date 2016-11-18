<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \CallflowBuilder\Builder;
use \CallflowBuilder\Node\Conference as ConferenceNode;
use \CallflowBuilder\Node\Language;
use \MakeBusy\Common\Log;

class Conference
{
    private static $counter = 1;
    private $loaded = false;
    private $test_account;
    private $conference;

    public function __construct(TestAccount $account, array $pins = array(), array $options = array()) {
        $name = "Conference " . self::$counter++;
        $this->test_account = $account;
        $kazoo_conf = $account->getFromCache('Conferences', $name);

        if (is_null($kazoo_conf)) {
            $this->initialize($account, $name, $pins, $options);
        } else {
            $this->setConference($kazoo_conf);
            $this->loaded = true;
        }
    }

    public function initialize(TestAccount $test_account, $name, array $pins = array(), array $options = array()) {
        $account = $this->getAccount();
        $conference = $account->Conference();
        $conference->name = $name;

        $conference->member = new stdClass();
        $conference->member->pins = array();
        $conference->member->pins = $pins;
        $conference->member->join_muted = FALSE;
        $conference->member->join_deaf = FALSE;
        $conference->member->numbers = array();

        $conference->makebusy = new stdClass();
        $conference->makebusy->test = TRUE;

        $conference = $this->mergeOptions($conference, $options);

        $conference->save();

        $this->setConference($conference);
    }

    public function getConference() {
        return $this->conference->fetch();
    }

    private function setConference($conference) {
        $this->conference = $conference;
    }

    private function setCallflow($callflow) {
        $this->callflow = $callflow;
    }

    private function getCallflow() {
        return $this->callflow->fetch();
    }

    public function getId() {
        return $this->getConference()->getId();
    }

    public function getCallflowNumbers() {
        return $this->callflow_numbers;
    }

    public function setCallflowNumbers(array $numbers) {
        $this->callflow_numbers = $numbers;
    }

    public function createCallflow(array $numbers, array $options = array()) { // Need Language for test call from PSTN
        if ($this->loaded) {
            return;
        }
        $builder = new Builder($numbers);
        $mkbs    = new Language("mk-bs");
        $conference_callflow = new ConferenceNode($this->getId());
        $mkbs->addChild($conference_callflow);
        $data = $builder->build($mkbs);
        $this->setCallflowNumbers($numbers);
        $callflow = $this->getTestAccount()->createCallflow($data);
        $this->setCallFlow($callflow);
        return $callflow;
    }

    public function createServiceCallflow(array $numbers, array $options = array()) { // Create Callflow for ConferenceService without data_id
        if ($this->loaded) {
            return;
        }
        $builder = new Builder($numbers);
        $mkbs    = new Language("mk-bs");
        $conference_callflow = new ConferenceNode();
        $mkbs->addChild($conference_callflow);
        $data = $builder->build($mkbs);
        return $this->getTestAccount()->createCallflow($data);
    }

    public function setWelcomePrompt($media_id=NULL) { // Set Welcome Prompt for Conference Module
        $callflow = $this->getCallflow();
        if ($callflow->flow->children->_) {   //if conferencenode last than set path with children (getLast)
            $path=$callflow->flow->children->_;
        } else {
            $path=$callflow->flow;
        }
        $path->data->welcome_prompt = new stdClass();
        if ($media_id) {
            $path->data->welcome_prompt->media_id = $media_id; //set media_id
        } else {
            unset($path->data->welcome_prompt);
        }

        $callflow->save();
    }

    public function enableWelcomePrompt($enable=TRUE) { // Set Welcome Prompt for Conference Module
        $callflow = $this->getCallflow();
        if ($callflow->flow->children->_) {   //if conferencenode last than set path with children (getLast)
            $path=$callflow->flow->children->_;
        } else {
            $path=$callflow->flow;
        }

        if (!$path->data->welcome_prompt) {
            $path->data->welcome_prompt = new stdClass();
        }

        $path->data->welcome_prompt->play = $enable;
        $callflow->save();
    }

    public function clearPins() { //clear Pins after each test
        $conference = $this->getConference();

        if (isset($conference->member->pins)) {
            unset($conference->member->pins);
        }

        if (isset($conference->moderator->pins)) {
            unset($conference->moderator->pins);
        }

        $conference->save();
    }

    private function getTestAccount() {
        return $this->test_account;
    }

    private function setTestAccount(TestAccount $test_account) {
        $this->test_account = $test_account;
    }

    private function getAccount() {
        return $this->test_account->getAccount();
    }

    private static function callflowNodeDefaults() {
        return array(
            'timeout'       => "20",
            'can_call_self' => FALSE,
            'suppress_clid' => null,
            'static_invite' => null,
            'delay'         => null
        );
    }

    private function mergeOptions($conference, $options) {
        foreach ($options as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $conference->$key = $this->mergeOptions($conference->$key, $value);
            } else if (is_null($value)) {
                unset($conference->$key);
            } else {
                $conference->$key = $value;
            }
        }

        return $conference;
    }

    public function setMemberOption($option,$value) {
        $conference = $this->getConference();
        $conference->member->$option=$value;
        $conference->save();
    }

    public function setModeratorOption($option,$value) {
        $conference = $this->getConference();
        $conference->moderator->$option=$value;
        $conference->save();
    }

    public function setMemberPin(array $pins = array()) {
        $conference = $this->getConference();
        $conference->member->pins = $pins;
        $conference->save();
    }

    public function setModeratorPin(array $pins = array()) {
        $conference = $this->getConference();
        $conference->moderator->pins = $pins;
        $conference->save();
    }

    public function setMaxUsers($value) {
        $conference = $this->getConference();
        if (is_null($value)) {
            unset($conference->max_participants);
        } else {
            $conference->max_participants = $value;
        }
        $conference->save();
    }

    public function setConferenceNumbers(array $numbers = array()) { // set conference number for login via ConferenceService
        if ($this->loaded) {
            return;
        }
        $conference = $this->getConference();
        if (is_null($numbers)) {
            unset($conference->conference_numbers);
        } else {
            $conference->conference_numbers = $numbers;
        }
        $conference->save();
    }

    public function reset() {
        $this->setMemberOption("join_muted", false);
        $this->setMemberOption("join_deaf", false);
    }
}
