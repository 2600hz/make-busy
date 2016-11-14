<?php

namespace MakeBusy\Common;
use \MakeBusy\Common\Log;
use \MakeBusy\Common\Ssh;
use \MakeBusy\Common\Configuration as Config;

/**
 * Call ID Cache - This program records any call ID used in a test, then checks for errors or failures in a test.
 * If any errors or failures exist, then the program fetches the SIP log for each Call ID, then saves the logs in a folder.
 * The next test function will purge the list of CallIDs, so the program starts on a clean slate.
 * @author Sean Wysor <sean@2600hz.com>
 * @author Joshua Evans <shoowa@2600hz.com>
 */

class LogCollector {

    // Fetch the information of the SSH section of the config.json file.
    private static $ssh_config          =   Config::getSection('ssh');

    // Organize EWR server information into useful variables.
    private static $ewr_server          =   $ssh_config['targets'][0];
    private static $ewr_name            =   $ewr_server['host'];
    private static $ewr_port            =   $ewr_server['port'];
    private static $ewr_name            =   $ewr_server['username'];
    private static $ewr_pw              =   $ewr_Server['password'];
    private static $ewr_sipify          =   $ewr_server['scripts']['sipify'];
    private static $ewr_kazoo_log       =   $ewr_server['logs']['kazoo'];
    private static $ewr_journal_log     =   $ewr_server['logs']['journal'];
    private static $credentials         =   ["username" => $ewr_name, "password" => $ewr_pw];

    // On the Makebusy server, create a unique folder for a test.
    private static function createFolder($test_name) {
        $date           =  date("Y-m-d-h-i-sa");
        $chrono_mark    =  strftime($date);
        $folder_name    =  $test_name . $chrono_mark;
        $folder_path    =  "/usr/local/share/makebusy/$folder_name";
        mkdir($folder_path);
        Log::info("Created folder %s for test %s.", $folder_name, $test_name);
        return $folder_path;
    }

    // On the Makebusy server, execute a bash script to pattern match a SIP log, and store the result in the folder called $folder_name.
    public static function fetchMakebusySipLogs($call_id_list, $folder) {
        foreach($call_id_list as $call_id) {
            $file_name = $call_id . '.txt';
            `/usr/local/bin/siptrace4.sh $call_id $folder $file_name`;
            Log::info("Saved SIP log %s in the folder %s.", $file_name, $folder);
        }
    }

    // SSH into the EWR server, then RETURN the SSH session.
    public static function loginEWR() {
        $ssh = new Ssh(self::$ewr_name, self::$ewr_port);
        $ssh->login(self::$credentials);
        return $ssh;
    }

/*    public static function fetchRemoteLogs($call_id_list, $folder_name) {

        // loop?
            // Pass the SSH instance to fetchRemoteSIPLog()
            // Pass the SSH instance to fetchRemoteJournalLog()
            // the above classes could RETURN or store it in a folder.
        }
    }
*/

    // Retrieve SIP log of a list of calls.
    public static function collect($call_ids, $test_name) {

        // Check for the absence of a collection of call IDs.
        if (empty($call_ids)) {
            Log::("No call IDs found for %s.", $test_name);
        } else {
            $folder     =   self::createFolder($test_name);
            $ssh_ewr    =   self::loginEWR();
            // loop through call IDs
                self::fetchMakebusySipLogs($call_ids, $folder);
                // fetch remote SIP logs
                // fetch remote Journal logs
        }
    }

}
