<?php

namespace MakeBusy\Kazoo\Applications\Konami;

use \stdClass;

use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Configuration;

use \MakeBusy\FreeSWITCH\Sofia\Profiles;
use \MakeBusy\FreeSWITCH\Sofia\Gateways;
use \MakeBusy\FreeSWITCH\Channels\Channel as FsChannel;
use \MakeBusy\FreeSWITCH\Esl\Connection as EslConnection;

use \MakeBusy\Kazoo\Applications\Crossbar\Device;
use \MakeBusy\Kazoo\Applications\Crossbar\User;
use \MakeBusy\Kazoo\Applications\Crossbar\Channel as MkBsChannel;

class Setup {
    public static $a_device;
    public static $b_device;
    public static $c_device;

    public static $d_user;
    public static $d_device_1;
    public static $d_device_2;
    public static $d_device_3;

    public static function configureKazoo($test_account) {
        $run = Utils::randomString(3);

        self::$a_device = new Device($test_account, TRUE, self::buildDeviceOptions("caller_" . $run, "1001"));
        self::$a_device->createCallflow(array("1001"));

        self::$b_device = new Device($test_account, TRUE, self::buildDeviceOptions("callee_" . $run, "1002"));
        self::$b_device->createCallflow(array("1002"));

        self::$c_device = new Device($test_account, TRUE, self::buildCallerIdOptions("target_s_" . $run, "1003"));
        self::$c_device->createCallflow(array("1003"));

        self::$d_user = new User($test_account, self::buildCallerIdOptions("target_m_" . $run, "1004"));
        self::$d_user->createUserCallFlow(array("1004"));

        self::$d_device_1 = new Device($test_account
                                       ,TRUE
                                       ,array_merge(array('owner_id' => self::$d_user->getId())
                                                    ,self::buildDeviceOptions("target_m_1_" . $run)
                                       )
        );
        self::$d_device_2 = new Device($test_account
                                       ,TRUE
                                       ,array_merge(array('owner_id' => self::$d_user->getId())
                                                    ,self::buildDeviceOptions("target_m_2_" . $run)
                                       )
        );
        self::$d_device_3 = new Device($test_account
                                       ,TRUE
                                       ,array_merge(array('owner_id' => self::$d_user->getId())
                                                    ,self::buildDeviceOptions("target_m_3_" . $run)
                                       )
        );
    }

    private static function buildDeviceOptions($name, $num=NULL) {
        $options = self::buildCallerIdOptions($name, $num);

        $options["sip"] = array("username" => $name);
        return $options;
    }

    private static function &buildCallerIdOptions($name, $num=NULL) {
        $cid = array("name" => $name
                     ,"number" => $num
        );

        $caller_id = array("internal" => $cid
                           ,"external" => $cid
                           ,"emergency" => $cid
        );

        $options = array("caller_id" => $caller_id);
        return $options;
    }

    public static function configureFreeSWITCH() {
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_device_id = self::$c_device->getId();
        $d_device_1_id = self::$d_device_1->getId();
        $d_device_2_id = self::$d_device_2->getId();
        $d_device_3_id = self::$d_device_3->getId();

        $profile  = Profiles::getProfile('auth');
        $gateways = $profile->getGateways();

        $gateways->loadFromAccounts();
        $profile->syncGateways();

        $gateways->findByName($a_device_id)->register();
        $gateways->findByName($b_device_id)->register();
        $gateways->findByName($c_device_id)->register();
        $gateways->findByName($d_device_1_id)->register();
        $gateways->findByName($d_device_2_id)->register();
        $gateways->findByName($d_device_3_id)->register();

        $esl = EslConnection::getInstance();
        $esl->events("CALL_UPDATE DETECTED_TONE");
    }

    public static function deconfigureFreeSWITCH() {
        $a_device_id = self::$a_device->getId();
        $b_device_id = self::$b_device->getId();
        $c_device_id = self::$c_device->getId();
        $d_device_1_id = self::$d_device_1->getId();
        $d_device_2_id = self::$d_device_2->getId();
        $d_device_3_id = self::$d_device_3->getId();

        $profile = Profiles::getProfile('auth');
        $gateways = $profile->getGateways();
        $gateways->loadFromAccounts();

        $gateways->findByName($a_device_id)->kill();
        $gateways->findByName($b_device_id)->kill();
        $gateways->findByName($c_device_id)->kill();
        $gateways->findByName($d_device_1_id)->kill();
        $gateways->findByName($d_device_2_id)->kill();
        $gateways->findByName($d_device_3_id)->kill();
    }

    /**
     * This should return an array of Scenario assoc-arrays:
     array (
       array (
         'type' => 'attended',
         'method' => 'dtmf',
         'initiator' => 'transferor',
         'target_type' => 'single',
       ),
       array (
         'type' => 'attended',
         'method' => 'dtmf',
         'initiator' => 'transferor',
         'target_type' => 'multi',
       ),
       ...
     )
     * Shamelessly stolen from :
     * https://stackoverflow.com/questions/6311779/finding-cartesian-product-with-php-associative-arrays/15973172#15973172
     */
    public static function cartesianProduct($input) {
        $result = array(array());

        foreach ($input as $key => $values) {
            $append = array();

            foreach($result as $product) {
                foreach($values as $item) {
                    $product[$key] = $item;
                    $append[] = $product;
                }
            }
            $result = $append;
        }

        return $result;
    }

}
