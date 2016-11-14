<?php

namespace MakeBusy\Common;
use \MakeBusy\Common\Log;

date_default_timezone_set('UTC');

/**
 * Call ID Cache - This program records any call ID used in a test, then checks for errors or failures in a test.
   If any errors or failures exist, then the program fetches the SIP log for each Call ID, then saves the logs in a folder.
   The next test function will purge the list of CallIDs, so the program starts on a clean slate.
 * @author Sean Wysor <sean@2600hz.com>
 * @author Joshua Evans <shoowa@2600hz.com>
 */
class CallIdCache
{
    // Define a list to hold Call IDs.
    private static $call_id_list = [];

    // Create a variable to hold the folder name for different functions.
    private static $folder_name = '';

    // Add a Call ID to the list during the CHANNEL_CREATE event.
    public static function add($call_id) {
        self::$call_id_list[] = $call_id;
    }

    // Withdraw the first Call ID from the list.
    public static function shift() {
        $call_id = array_shift(self::$call_id_list);
        return $call_id;
    }

    // Point the Call ID List variable at an empty list for a fresh start.
    // Point the folder name variable at an empty string.
    public static function flush() {
        self::$call_id_list = [];
        self::$folder_name = '';
    }

    // Build a unique folder for a test.
    public static function createFolder($test_name) {
        $date = date("Y-m-d-h-i-sa");
        $chrono_mark = strftime($date);
        $folder_name = self::$folder_name = $test_name . $chrono_mark;
        mkdir("/usr/local/share/makebusy/$folder_name");
        Log::info("Created folder %s for test %s.", $folder_name, $test_name);
    }

    // On the Makebusy server, execute a bash script to pattern match a SIP log, and store the result in the folder called $folder_name.
    public static function fetchMakebusySipLogs() {
        $folder_name = self::$folder_name;
        foreach(self::$call_id_list as $call_id) {
            $file_name = $call_id . '.txt';
            `/usr/local/bin/siptrace4.sh $call_id $folder_name $file_name`;
            Log::info("Saved SIP log %s in the folder %s.", $file_name, $folder_name);
        }
    }

    // On the Makebusy server, execute a bash script that makes the Makebusy server SSH into the EWR server,
    // then seek a call ID in the EWR journal log, then save the result in a folder.
    public static function seekJournalLog() {
//        something
    }

    // On the Makebusy server, execute a bash script that Secure Copies parsed log files from the EWR server to MakeBusy server.
    public static function fetchJournalLog() {
//        something
    }
}
