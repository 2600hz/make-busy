<?php
namespace MakeBusy\FreeSWITCH\Sofia;

class Profiles
{
    private $profiles = [];
    private $esl;

    public function __construct($esl) {
        $this->esl = $esl;
        $this->addProfile("profile"); // default profile, must exist
    }

    public function addProfile($name) {
        $this->profiles[$name] = new Profile($this->esl, $name);
    }

    public function getProfiles() {
        return $this->$profiles;
    }

    public function getProfile($name) {
        return $this->profiles[$name];
    }
}
