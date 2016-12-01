<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \CallflowBuilder\Builder;
use \CallflowBuilder\Node\Media as MediaNode;
use \MakeBusy\Common\Log;

class Media
{
    private static $counter = 1;
    private $test_account;
    private $media;
    private $loaded = false;

    public function __construct(TestAccount $account) {
        $name = sprintf("%s MD %d", $account->getBaseType(), self::$counter++);
        $this->test_account = $account;
        $kazoo_media = $account->getFromCache('Medias', $name);
        if (is_null($kazoo_media)) {
            $this->initialize($account, $name);
        } else {
            $this->setMedia($kazoo_media);
            $this->loaded = true;
        }
    }

    public function initialize(TestAccount $test_account, $name) {
        $account = $this->getAccount();

        $media = $account->Media();
        $media->name = $name;

        $media->language = "mk-bs"; //set language for conference tests

        $media->save();
        $this->setMedia($media);
    }

    private function mergeOptions($media, $options) {
        foreach ($options as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $media->$key = $this->mergeOptions($media->$key, $value);
            } else if (is_null($value)) {
                unset($media->$key);
            } else {
                $media->$key = $value;
            }
        }

        return $media;
    }

    public function setFile($file,$mimetype) { // POST file for play
        $media = $this->getMedia();
        $media->postRaw($file,$mimetype);
        $media->description = "C:\\fakepath\\".basename($file);
        $media->filename=basename($file,".wav");    //set filename
        $media->save();
    }

    private function getTestAccount() {
        return $this->test_account;
    }

    public function getMedia() {
        return $this->media->fetch();
    }

    private function setMedia($media) {
        $this->media = $media;
    }

    public function getId() {
        return $this->getMedia()->getId();
    }

    public function getCallflowNumbers() {
        return $this->callflow_numbers;
    }

    public function setCallflowNumbers(array $numbers){
        $this->callflow_numbers = $numbers;
    }

    public function createCallflow(array $numbers, array $options = array()) {
        if (! $this->loaded) {
            return;
        }
        $builder = new Builder($numbers);
        $media_callflow = new MediaNode($this->getId());
        $data = $builder->build($media_callflow);

        $this->setCallflowNumbers($numbers);

        return $this->getTestAccount()->createCallflow($data);
    }

    private function setTestAccount(TestAccount $test_account) {
        $this->test_account = $test_account;
    }

    private function getAccount() {
        return $this->test_account->getAccount();
    }
}

