<?php

namespace MakeBusy\Kazoo\Applications\Crossbar;

use \stdClass;

use \CallflowBuilder\Node\RingGroup as RingGroupNode;
use \CallflowBUilder\Builder;
use \MakeBusy\Common\Log;

class RingGroup
{
    private static $counter = 1;
    private $ring_group;
    private $test_account;

    public function __construct(TestAccount $test_account, array $numbers, array $members, $strategy = null) {
        $this->setTestAccount($test_account);
        if(! $test_account->isLoaded()) {
            $this->createCallFlow($numbers, $members, $strategy);
        }
    }

    public function createCallflow(array $numbers, array $members, $strategy) {
        $builder = new Builder($numbers);
        $ring_group = new RingGroupNode("RingGroup_" . self::$counter++);
        if (!empty($strategy)) {
            $ring_group->strategy($strategy);
        }
        $options = self::endpointDefaults();
        $endpoints = array();

        foreach ($members as $member){
            $member = array_merge($options, $member);
            $id = $member['id'];
            $endpoints[$id] = array (
                'type' => $member['type'],
                'timeout' => $member['timeout'],
                'delay' => $member['delay']
            );
        }

        $ring_group->endpoints($endpoints);
        $data = $builder->build($ring_group);

        return $this->getTestAccount()->createCallflow($data);
    }

    public static function callflowNode(array $members) {
        $options = self::callflowNodeDefaults();
        // TODO: probably not required and if it remains should
        //  be a util...
        foreach ($options as $key => $value) {
            if (is_null($value)) {
                unset($options[$key]);
            }
        }

        $flow = new stdClass();
        $flow->module = "ring_group";
        $flow->data = (object) $options;
        $flow->data->name = "name";
        $flow->data->endpoints = self::endpointNode($members);
        $flow->children = new stdClass();

        return $flow;
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

    private static function endpointNode(array $members){
        $endpoint = array();
        foreach ($members as $id => $type){
            $object = new stdClass();
            $object->timeout = "20";
            $object->delay = "0";
            $object->id = $id;
            $object->endpoint_type = $type;
            array_push($endpoint, $object);
        }
        return $endpoint;
    }

    private static function endpointDefaults() {
        return [
            'timeout' => "20",
            'delay' => "0"
        ];
    }
    private static function callflowNodeDefaults() {
        return array(
            'timeout' => "20",
            'strategy' => "simultaneous"
        );
    }
}
