<?php

namespace MakeBusy\FreeSWITCH\Esl;

use \MakeBusy\FreeSWITCH\Esl\Exceptions\ConnectionException;
use \MakeBusy\FreeSWITCH\Esl\Exceptions\AuthenticationException;
use \MakeBusy\Common\Configuration;
use \MakeBusy\FreeSWITCH\Channels\Channels;
use \MakeBusy\FreeSWITCH\Sofia\Profiles;

use \MakeBusy\Common\Log;

class Connection extends Socket
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
            self::$instances[$type] = new Connection($params["ip_address"], $params["port"], $params["auth_key"], array(), $type);
        }
        return self::$instances[$type];
    }

    /**
     * Initializes a new instance of ESLconnection, and connects to the host
     * $host on the port $port, and supplies $password to freeswitch.
     */
    private function __construct($host = NULL, $port = NULL, $auth = NULL, $options = array(), $type) {
        $this->channels = new Channels($this);
        $this->profiles = new Profiles($this);
        $this->ip_address = $host;
        $this->ip_port = $port;
        $this->auth_key = $auth;
        $this->connect_options = $options;
        $this->type = $type;
        $this->do_connect();
    }

    public function do_connect() {
        $this->connect($this->ip_address, $this->ip_port, $this->connect_options);
        $event = $this->recvEvent();

        if ($event->getHeader('Content-Type') != 'auth/request') {
            throw new ConnectionException("unexpected header recieved during authentication: " . $event->getType());
        }

        $auth = $this->auth_key;
        $event = $this->sendRecv("auth {$auth}");

        $reply = $event->getHeader('Reply-Text');
        if (!strstr($reply, '+OK')) {
            throw new AuthenticationException("connection refused: {$reply}");
        }
        $this->events("all");

        $this->authenticated = TRUE;
        return $this;
    }

    public function clone() {
        return new Connection($this->ip_address, $this->ip_port, $this->auth_key, $this->connect_options, $this->type);
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

    /**
     * Returns the UNIX file descriptor for the connection object, if the
     * connection object is connected. This is the same file descriptor that was
     * passed to new($fd) when used in outbound mode.
     */
    public function socketDescriptor() {
        return $this->getStatus();
    }

    /**
     * Test if the connection object is connected. Returns 1 if connected,
     * 0 otherwise.
     */
    public function connected() {
        if ($this->validateConnection() && $this->authenticated) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * When FS connects to an "Event Socket Outbound" handler, it sends a
     * "CHANNEL_DATA" event as the first event after the initial connection.
     * getInfo() returns an ESLevent that contains this Channel Data.
     * getInfo() returns NULL when used on an "Event Socket Inbound" connection.
     */
    public function getInfo() {
        throw new ConnectionException("has not implemented this yet!");
    }

    /**
     * Sends a command to FreeSwitch. Does not wait for a reply. You should
     * immediately call recvEvent or recvEventTimed in a loop until you get
     * the reply. The reply event will have a header named "content-type" that
     * has a value of "api/response" or "command/reply". To automatically wait
     * for the reply event, use sendRecv() instead of send().
     */
    public function send($command) {
        if (empty($command)) {
            throw new ConnectionException("requires non-blank command to send.");
        }

        // send the command out of the socket
        return $this->sendCmd($command);
    }

    /**
     * Internally sendRecv($command) calls send($command) then recvEvent(), and
     * returns an instance of ESLevent. recvEvent() is called in a loop until it
     * receives an event with a header named "content-type" that has a value of
     * "api/response" or "command/reply", and then returns it as an instance of
     * ESLevent. Any events that are received by recvEvent() prior to the reply
     * event are queued up, and will get returned on subsequent calls to
     * recvEvent() in your program.
     */
    public function sendRecv($command) {
        Log::debug("fs %s: %s", $this->getType(), $command);

        // setup an array of content-types to wait for
        $waitFor = array('api/response', 'command/reply');

        // set a flag so recvEvent ignores the event queue
        $this->sentCommand = TRUE;

        // send the command
        $this->send($command);

        // collect and queue all the events
        do {
            $event = $this->recvEvent();
            $this->enqueueEvent($event);
        } while ($event === NULL || !in_array($event->getHeader('Content-Type'), $waitFor));

        // clear the flag so recvEvent uses the event queue
        $this->sentCommand = FALSE;
        // the last queued event was of the content-type we where waiting for,
        // so pop one off
        return array_pop($this->eventQueue);
    }

    public function eventQueue($uuid=NULL) {
        $eligible = array_filter($this->eventQueue
                                 ,function($e) use ($uuid) {
                                     return $uuid == $e->getUuid();
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
           if ($uuid == $found_event->getUuid()){
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
    public function api() {
        $args = func_get_args();
        $command = array_shift($args);

        $command = 'api ' .$command .' ' .implode(' ', $args);

        return $this->sendRecv($command);
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
    public function bgapi() {
        $args = func_get_args();
        $command = array_shift($args);

        $command = 'bgapi ' .$command .' ' .implode(' ', $args);

        return $this->sendRecv($command);
    }

    public function sendEvent($event) {
        throw new ConnectionException("does not implement this becuase there is no info on it in the docs!");

        //$command = 'sendevent ' .$event->name . ' ' .$event->serialize();
    }

    /**
     * Returns the next event from FreeSwitch. If no events are waiting, this
     * call will block until an event arrives. If any events were queued during
     * a call to sendRecv(), then the first one will be returned, and removed
     * from the queue. Otherwise, then next event will be read from the
     * connection.
     */
    public function recvEvent($uuid = NULL) {
        // if we are not waiting for an event and the event queue is not empty
        // shift one off

        if (!$this->sentCommand
            && ($event = $this->dequeueEvent($uuid))
        ) {
            return $event;
        }

        return $this->recvNewEvent($uuid);
    }

    private function &recvNewEvent($uuid) {
        $no_event = NULL;
        $event = new Event();
        // wait for the first line
        $this->setBlocking();
        do {
            $line = $this->readLine();
            // if we timeout while waiting return NULL
            $streamMeta = $this->getMetaData();
            if (!empty($streamMeta['timed_out'])) {
                return $no_event;
            }
        } while (empty($line));

        // save our first line
        $event->parseLine($line);
        // keep reading the buffer untill we get a new line
        $this->setNonBlocking();
        do {
            $line = $this->readLine();
            $event->parseLine($line);
        } while ($line != "\n");

        // if the response contains a content-length ...
        if ($contentLen = $event->getHeader('Content-Length')) {
            // ... add the content to this event
            $this->setBlocking();
            while ($contentLen > 0) {
                // our fread stops every 8192 so break up the reads into the
                // appropriate chunks
                if ($contentLen > 8192) {
                    $getLen = 8192;
                } else {
                    $getLen = $contentLen;
                }
                $content = $this->getContent($getLen);
                $event->addBody($content);
                $contentLen = $contentLen - strlen($content);
            }
        }


        $event->convertPlainEvent();

        $contentType = $event->getHeader('Content-Type');

        if ($contentType == 'text/disconnect-notice') {
            $this->disconnect();
            return $no_event;
        }

        $event_name = $event->getHeader('Event-Name');
        $fs_type = $this->getType();

        $event_uuid = $event->getUuid();

        if (isset($_ENV['DUMP_EVENTS'])) {
            Log::dump("fs $fs_type incoming: $event_name", $event);
        } else {
            Log::debug("fs %s incoming: %s %s", $fs_type, $event_name, $event_uuid);
        }
        $this->channels->newEvent($event);

        // return our ESLevent object
        if ( ! $uuid && ! $event_uuid ) {
            return $event;
        }

        if ( $event_uuid == $uuid ) {
            return $event;
        }

        // Event was not for our $uuid, enqueue and recvEvent again
        $this->enqueueEvent($event);
        return $this->recvNewEvent($uuid);
    }

    /**
     * Similar to recvEvent(), except that it will block for at most milliseconds.
     * A call to recvEventTimed(0) will return immediately. This is useful for
     * polling for events.
     */
    public function recvEventTimed($milliseconds, $uuid = NULL) {
        // set the stream timeout to the users preference
        $this->setTimeOut(0, $milliseconds);

        // try to get an event
        $event = $this->recvEvent($uuid);

        // restore the stream time out
        $this->restoreTimeOut();

        // return the results (null or event object)
        return $event;
    }

    /**
     * Specify event types to listen for. Note, this is not a filter out but
     * rather a "filter in," that is, when a filter is applied only the filtered
     * values are received. Multiple filters on a socket connection are allowed.
     */
    public function filter($header, $value) {
        return $this->sendRecv('filter ' .$header .' ' .$value);
    }

    /**
     * $event_type can have the value "plain" or "xml". Any other value
     * specified for $event_type gets replaced with "plain". See the event FS
     * wiki socket event command for more info.
     */
    public function events($event) {
        if (! isset($this->listen_for[$event])) {
            $this->listen_for[$event] = $this->sendRecv('events plain ' . $event);
        }
        return $this->listen_for[$event];
    }

    /**
     * Execute a dialplan application, and wait for a response from the server.
     * On socket connections not anchored to a channel (THIS!),
     * all three arguments are required -- $uuid specifies the channel to
     * execute the application on. Returns an ESLevent object containing the
     * response from the server. The getHeader("Reply-Text") method of this
     * ESLevent object returns the server's response. The server's response will
     * contain "+OK [Success Message]" on success or "-ERR [Error Message]"
     * on failure.
     */
    public function execute($app, $arg, $uuid) {
        $command = 'sendmsg';

        if (!empty($uuid)) {
            $command .= " {$uuid}";
        }

        $command .= "\ncall-command: execute\n";

        if (!empty($app)) {
            $command .= "execute-app-name: {$app}\n";
        }

        if (!empty($arg)) {
            $command .= "execute-app-arg: {$arg}\n";
        }

        if ($this->eventLock) {
            $command .= "event-lock: true\n";
        }

        if ($this->asyncExecute) {
            $command .= "async: true\n";
        }

        return $this->sendRecv($command);
    }

    /**
     * Same as execute, but doesn't wait for a response from the server. This
     * works by causing the underlying call to execute() to append "async: true"
     * header in the message sent to the channel.
     */
    public function executeAsync($app, $arg, $uuid) {
        $currentAsync = $this->asyncExecute;
        $this->asyncExecute = TRUE;

        $response = $this->execute($app, $arg, $uuid);

        $this->asyncExecute = $currentAsync;

        return $response;
    }

    public function setAsyncExecute($value = NULL) {
        $this->asyncExecute = !empty($value);
        return TRUE;
    }

    /**
     * Force sync mode on for a socket connection. This command has no effect on
     *  outbound socket connections that are not set to "async" in the dialplan,
     * since these connections are already set to sync mode. $value should be 1
     * to force sync mode, and 0 to not force it.
     */
    public function setEventLock($value = NULL) {
        $this->eventLock = !empty($value);
        return TRUE;
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
