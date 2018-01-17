<?php

namespace MakeBusy\FreeSWITCH\Esl;

use \MakeBusy\Common\Configuration;
use \MakeBusy\FreeSWITCH\Channels\Channels;
use \MakeBusy\FreeSWITCH\Sofia\Profiles;

use \MakeBusy\Common\Log;

require_once('ESL.php');

class Connection extends \ESLconnection
{
    private static $instances = [];

    private $eventQueue = array();
    private $channels;
    private $profiles;
    private $ip_address;
    private $ip_port;
    private $connect_options;
    private $auth_key;
    private $type;
    private $listen_for = []; // cache of events subscribed to

    private $sentCommand = FALSE;
    private $authenticated = FALSE;
    private $eventLock = FALSE;
    private $asyncExecute = FALSE;

    public static function getInstance($type = "auth") {
        if (! array_key_exists($type, self::$instances)) {
            $section = Configuration::getSection('esl');
            $params = $section[$type];
            self::$instances[$type] = new Connection($type, $params["ip_address"], $params["port"], $params["auth_key"], array());            
        }
        return self::$instances[$type];
    }

    /**
     * Initializes a new instance of ESLconnection, and connects to the host
     * $host on the port $port, and supplies $password to freeswitch.
     */
    public function __construct($type, $host = NULL, $port = NULL, $auth = NULL, $options = array()) {
    	parent::__construct($host, $port, $auth );
    	$this->channels = new Channels($this);
    	$this->profiles = new Profiles($this);
    	$this->ip_address = $host;
        $this->ip_port = $port;
        $this->auth_key = $auth;
        $this->connect_options = $options;
        $this->type = $type;
        $this->events("ALL");
    }

    public function __clone() {
    	return new Connection($this->type, $this->ip_address, $this->ip_port, $this->auth_key, array());
    }

    public function __destruct() {
        // cleanly exit
        $this->disconnect();
    }

    public function getType() {
        return $this->type;
    }

    public function getIpAddress() {
        return $this->ip_address;
    }

    public function getChannels() {
    	return $this->channels;
    }

    public function getProfiles() {
    	return $this->profiles;
    }

    public function sizeOfQueue(){
        return count($this->eventQueue);
    }

    public function flushEvents(){
        unset($this->eventQueue);
        $this->eventQueue = array();
    }

    


    public function eventQueue($uuid=NULL) {
        $eligible = array_filter($this->eventQueue
                                 ,function($e) use ($uuid) {
                                 	return $uuid == $e->getHeader('Unique-ID');
                                 }
        );
        return $eligible;
    }

    public function resetEventQueue() {
        unset($this->eventQueue);
        $this->eventQueue = array();
    }

    private function enqueueEvent(&$event) {
        $this->eventQueue[] = $event;
    }

    private function &dequeueEvent($uuid=NULL) {
        $event = NULL;
        $index = NULL;

        if ( ! $this->eventQueue ) {
            return $event;
        }

        foreach ($this->eventQueue as $idx => $found_event) {
        	if ($uuid == $found_event->getHeader('Unique-ID')){
               $index = $idx;
               $event = $found_event;
           }
        }

        if ( empty($event) ) {
            return $event;
        }

        unset($this->eventQueue[$index]);

        return $event;
    }

    private function &dequeueFilteredEvent($value, $header, $direction = null) {
    	$event = NULL;
    	$index = NULL;
    	
    	if ( ! $this->eventQueue ) {
    		return $event;
    	}
    	
    	foreach ($this->eventQueue as $idx => $found_event) {
    		if ($header == 'Unique-ID' && $value == $found_event->getHeader('Unique-ID')) {
   				$event = $found_event;
   				$index = $idx;
   			} else {
   				$event_value = $found_event->getHeader($header);
   				if ((!$direction || $found_event->getHeader('Call-Direction') == $direction) && strpos($event_value, $value) !== FALSE) {
	   				$event = $found_event;
	   				$index = $idx;
   				}
   			}
    	}
    	
    	if ( empty($event) ) {
    		return $event;
    	}
    	
    	unset($this->eventQueue[$index]);
    	
    	return $event;
    }
    

    /**
     * requeueEvent
     *     put the event back in the queue (not used by anything yet).
     * @param mixed $event
     * @return void
     */
    private function requeueEvent($event) {
        array_unshift($this->eventQueue, $event);
    }

    /**
     * Send an API command to the FreeSWITCH server. This method blocks further
     * execution until the command has been executed. api($command, $args) is
     * identical to sendRecv("api $command $args").
     */

    public function api($cmd,$arg=null) {
    	switch (func_num_args()) {
    		case 1: return parent::api($cmd); break;
    		default: return parent::api($cmd, sprintf("%s", implode(' ', $arg)));
    	}
    }

    public function api_f() {
        $args = func_get_args();
        return $this->api(call_user_func_array('sprintf', $args));
    }

    /**
     * Send a background API command to the FreeSWITCH server to be executed in
     * it's own thread. This will be executed in it's own thread, and is
     * non-blocking. bgapi($command, $args) is identical to
     * sendRecv("bgapi $command $args")
     */
    function bgapi($cmd,$arg=null,$job_uuid=null) {
    	switch (func_num_args()) {
    		case 1: return parent::bgapi($cmd); break;
    		case 2: return parent::bgapi($cmd, sprintf("%s", implode(' ', $arg)));
    		default: return parent::bgapi($cmd,$arg,$job_uuid);
    	}
    }

    public function recvEvent($uuid = NULL) {
    	// if we are not waiting for an event and the event queue is not empty
    	// shift one off
    	if ($event = $this->dequeueEvent($uuid)) {
    		return $event;
		}
		return $this->recvNewEvent($uuid);
    }

    private function &recvNewEvent($uuid) {
    	$no_event = NULL;
    	$event = parent::recvEvent();

    	if($event) {
    		
    		$this->channels->newEvent($event);
    		
    		$event_name = $event->getHeader('Event-Name');
	    	$subclass_name = $event->getHeader('Event-Subclass');
	    	$fs_type = $this->getType();
	    	$event_uuid = $event->getHeader('Unique-ID');
	    	if (isset($_ENV['DUMP_EVENTS'])) {
	    		Log::dump("fs $fs_type incoming: $event_name", $event->serialize('json'));
	    	} else {
	    		Log::debug("fs %s incoming: %s %s", $fs_type, $event_name == "CUSTOM" ? $subclass_name : $event_name, $event_uuid);
	    	}
	    	
	    	
	    	// return our ESLevent object
	    	if ( ! $uuid && ! $event_uuid ) {
	    		return $event;
	    	}
	    	if ( $event_uuid == $uuid ) {
	    		return $event;
	    	}
	    	// Event was not for our $uuid, enqueue and recvEvent again
	    	$this->enqueueEvent($event);
    	}
    	return $this->recvNewEvent($uuid);
    }
    
    public function recvEventTimed($milliseconds, $uuid = NULL) {
    	if ($event = $this->dequeueEvent($uuid)) {
    		return $event;
    	}
    	
    	$event = parent::recvEventTimed($milliseconds);

    	if($event) {
    		
    		$this->channels->newEvent($event);
    		
    		$event_uuid = $event->getHeader('Unique-ID');

    		if ( ! $uuid && ! $event_uuid ) {
    			return $event;
    		}
    		
    		if ( $event_uuid == $uuid ) {
    			return $event;
    		}
    		
    		// Event was not for our $uuid, enqueue and recvEvent again
    		$this->enqueueEvent($event);
    		$event = NULL;
    	}
    	return $event;    	
    }

    public function recvFilteredEventTimed($milliseconds, $value, $header, $direction = null) {
    	if ($event = $this->dequeueFilteredEvent($value, $header, $direction)) {
    		return $event;
    	}
    	
    	$event = parent::recvEventTimed($milliseconds);
    	
    	if($event) {
    		
    		$this->channels->newEvent($event);
    		
    		$uuid = $event->getHeader('Unique-ID');
    		
    		if ( $uuid === $value && $header == 'Unique-ID' ) return $event;
    		$event_value = $event->getHeader($header);
    		if ((!$direction || $event->getHeader('Call-Direction') == $direction) && strpos($event_value, $value) !== FALSE) {
    			return $event;
    		}
    		    		    		
    		// Event was not for our header/value/direction, enqueue and recvEvent again
    		$this->enqueueEvent($event);
    		$event = NULL;
    	}
    	return $event;
    }

    /**
     * $event_type can have the value "plain" or "xml". Any other value
     * specified for $event_type gets replaced with "plain". See the event FS
     * wiki socket event command for more info.
     */
    public function events($event, $type='plain') {
         if (! isset($this->listen_for[$event])) {
             $this->listen_for[$event] = 1;
         }
         return parent::events($type, $event);
    }



    /**
     * Close the socket connection to the FreeSWITCH server.
     */
    public function disconnect() {
        // if we are connected cleanly exit
        if ($this->connected()) {
            $this->send('exit');
            $this->authenticated = FALSE;
        }
        // disconnect the socket
        return parent::disconnect();
    }
}
