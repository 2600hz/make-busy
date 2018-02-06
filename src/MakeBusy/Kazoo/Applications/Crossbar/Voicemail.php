<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \MakeBusy\Kazoo\SDK;

use \MakeBusy\Common\Utils;

use \CallflowBuilder\Builder;
use \CallflowBuilder\Node\Voicemail as VoicemailNode;
use \CallflowBuilder\Node\User as UserNode;
use \CallflowBuilder\Node\Language;
use \MakeBusy\Common\Log;

class Voicemail
{
    private static $counter = 1;
    private $test_account;
    private $voicemail_box;
    private $loaded = false;

    public function __construct(TestAccount $account, $box_number, array $options = array()) {
        $this->test_account = $account;
        $name = sprintf("%s VM %d", $account->getBaseType(), self::$counter++);
        $kazoo_vm = $account->getFromCache('VMBoxes', $name);
        if (is_null($kazoo_vm)) {
            $this->initialize($account, $name, $box_number, $options);
        } else {
            $this->setVoicemailBox($kazoo_vm);
            $this->loaded = true;
        }
    }

    public function initialize(TestAccount $test_account, $name, $box_number, array $options = array()) {
        $account = $this->getAccount();

        $voicemail_box = $account->VMBox();
        $voicemail_box->name = $name;
        $voicemail_box->require_pin = TRUE;
        $voicemail_box->is_setup = FALSE;
        $voicemail_box->pin = "0000";
        $voicemail_box->mailbox = $box_number;
        $voicemail_box->timezone = "America/Los_Angeles";
        $voicemail_box->media_extension = "wav";

        $voicemail_box->makebusy = new stdClass();
        $voicemail_box->makebusy->test = TRUE;

        foreach ($options as $key => $value) {
            $voicemail_box->$key = $value;
            if (is_null($value)) {
                unset($options[$key]);
            }
        }

        $voicemail_box->save();

        $this->setVoicemailBox($voicemail_box);
    }

    public function getVoicemailBox() {
        return $this->voicemail_box->fetch();
    }

    private function setVoicemailBox($voicemail_box) {
        $this->voicemail_box = $voicemail_box;
    }

    public function getId() {
        return $this->getVoicemailBox()->getId();
    }

    public function getVoicemailboxNumber() {
        return $this->getVoicemailBox()->mailbox;
    }

    public function getMessages() {
        return $this->getVoicemailBox()->Messages();
    }

    public function getVoicemailboxParam($param) {
        return $this->getVoicemailBox()->$param;
    }

    public function setVoicemailboxParam($param,$value) {
        $voicemailbox = $this->getVoicemailBox();
        $voicemailbox->$param = $value;
        $voicemailbox->save();
    }

    public function resetVoicemailboxParam($param) {
        $voicemailbox = $this->getVoicemailBox();
        unset($voicemailbox->$param);
        $voicemailbox->save();
    }

    public function createCallflow(array $numbers) {
        if ($this->loaded) {
            return;
        }
        $builder    = new Builder($numbers);
        $mkbs       = new Language($this->getTestAccount()->getLanguage());
        $voicemail  = new VoicemailNode($this->getId());
        $mkbs->addChild($voicemail);
        $data       = $builder->build($mkbs);

        return $this->getTestAccount()->createCallflow($data);
    }

    public function createCheckCallflow(array $numbers) {
        if ($this->loaded) {
            return;
        }
        $builder    = new Builder($numbers);
        $mkbs       = new Language($this->getTestAccount()->getLanguage());
        $voicemail  = new VoicemailNode();
        $mkbs->addChild($voicemail);
        $voicemail->action("check");
        $data       = $builder->build($mkbs);

        return $this->getTestAccount()->createCallflow($data);
    }

    public function createUserVmCallflow(array $numbers, $user_id){
        if ($this->loaded) {
            return;
        }
        $builder    = new Builder($numbers);
        $voicemail  = new VoicemailNode($this->getId());
        $user       = new UserNode($user_id);
        $mkbs       = new Language($this->getTestAccount()->getLanguage());

        $user->addChild($mkbs)->addChild($voicemail);

        $data       = $builder->build($user);
        return $this->getTestAccount()->createCallflow($data);
    }


    public static function callflowNode(Voicemail $voicemail, array $options = array()) {
        if (!isset($options['action']) or $options['action']!="check" ) {
            $options = array_merge(self::callflowNodeDefaults(), $options);
        }
        // TODO: probably not required and if it remains should
        //  be a util...
        foreach ($options as $key => $value) {
            if (is_null($value)) {
                unset($options[$key]);
            }
        }
        //todo: flesh this out, should be applied to every call flow that results in an IVR.
        $language = new stdClass();
        $language->module = "language";
        $data = new stdClass();

        $data->language = $this->getTestAccount()->getLanguage();
        $language->data = (object) $data;

        $flow = new stdClass();
        $flow->module = "voicemail";
        $flow->data = (object) $options;

        if (!isset($options['action']) or $options['action']!="check" ) {
            $flow->data->id = $voicemail->getId();
        }

        $flow->children = new stdClass();

        $language->children->_ = $flow;

        return $language;

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

    public function resetVoicemailBox() {
        $voicemailbox = $this->getVoicemailBox();
        $voicemailbox->require_pin = TRUE;
        $voicemailbox->is_setup    = TRUE;
        $voicemailbox->pin         = "0000";
        unset($voicemailbox->media);
        $voicemailbox->save();
        $voicemailbox->Messages()->remove();
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

    public static function resetCounter() {
    	self::$counter = 1;
    }
    
}
