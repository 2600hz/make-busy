<?php

namespace MakeBusy\Common;
use stdClass;

class Utils
{
    public static function randomString($length = 10, $chars = 'alphanumeric') {
        // Alpha lowercase
        if ($chars == 'alphalower') {
            $chars = 'abcdefghijklmnopqrstuvwxyz';
        }

        // Numeric
        if ($chars == 'numeric') {
            $chars = '1234567890';
        }

        // Alpha Numeric
        if ($chars == 'alphanumeric') {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        }

        // Hex
        if ($chars == 'hex') {
            $chars = 'ABCDEF1234567890';
        }

        $char_length = strlen($chars) - 1;

        $random_string = "";
        for($i = 0 ; $i < $length ; $i++){
            $random_string .= $chars[rand(0, $char_length)];
        }

        return $random_string;
    }

    public static $caribbean_npa = array(
        "242"
        ,"246"
        ,"264"
        ,"268"
        ,"284"
        ,"340"
        ,"345"
        ,"441"
        ,"473"
        ,"649"
        ,"664"
        ,"670"
        ,"671"
        ,"684"
        ,"721"
        ,"758"
        ,"767"
        ,"784"
        ,"787"
        ,"809"
        ,"829"
        ,"849"
        ,"868"
        ,"869"
        ,"876"
        ,"939"
    );

    public static $tollfree_npa = array("800", "888", "877", "866", "855");

    //NEEDS WORK, WHAT IF NUMBER IS RESTRICTED????
    //THIS UTIL SHOULD DO MORE THAN JUST GENERATE LEGAL NAMP NUMBERS,
    //IT SHOULD ALSO AVOID RESTRICTED PREFIXES
    //WE SHOULD BREWAK THIS UP INTO MULTIPLE UTILS LIKE
    //GET RESTRICTED NUMBER
    //GET NORMAL NUMBER
    //GET INTERNATIONAL NUMBER
    public static function randomNumber() {
        return '' . Utils::npa() . Utils::nxx() . Utils.xxxx();
    }

    public static function npa() {
        return '' . rand(2,9) . Utils::randomString(2,"numeric");
    }

    public static function safe_npa() {
        $npa = Utils::npa();
        while (in_array($npa,self::$caribbean_npa)) {
            $npa = Utils::npa();
        }

        while (in_array($npa,self::$tollfree_npa)) {
            $npa = Utils::npa();
        }

        return '' . $npa;
    }

    public static function randomUsDid() {
        $safe = Utils::safe_npa();
        $nxx = Utils::nxx();
        $xxxx = Utils::xxxx();

        return '' . $safe . $nxx . $xxxx;
    }

    public static function nxx() {
        $n = rand(2,9);
        $x1 = rand(0,9);
        $x2 = rand(0,9);

        if($x1 == 1 && $x2 == 1) {
            $x1 = 2;
        }

        return '' . $n . $x1 . $x2;
    }

    public static function xxxx() {
        return Utils::randomString(4, "numeric");
    }

    public static function randomTollFree() {
        $npa = array_rand(self::$tollfree_npa);
        return '' . $npa . Utils::nxx() . Utils::xxxx();
    }

    public static function mset($obj, array $keys, $value = null) {
        $chunk = $obj;
        $last = count($keys)-1;
        foreach($keys as $id => $key) {
            if ($last == $id) {
                if (is_null($value)) {
                    unset($chunk->$key);
                } else {
                    $chunk->$key = $value;
                }
            } else {
                $chunk->$key = isset($chunk->$key)? $chunk->$key : new stdClass();
                $chunk = $chunk->$key;
            }
        }
    }

}
