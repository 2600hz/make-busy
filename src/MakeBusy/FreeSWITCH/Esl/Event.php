<?php

namespace MakeBusy\FreeSWITCH\Esl;

class Event
{
    static private $instance;

    private $headers = array('Event-Name' => 'COMMAND');

    private $body = NULL;

    private $hdrPointer = NULL;

    static public function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new Event();
        }

        return self::$instance;
    }

    public function __toString() {
        $name = $this->getHeader("Event-Name");
        $uuid = $this->getHeader("Unique-ID") ? ":" . $this->getHeader("Unique-ID") : "";

        switch($name) {
        case "CUSTOM": $name .= ":" . urldecode($this->getHeader("Event-Subclass"));
        default: TRUE;
        }

        return $name . $uuid;
    }

    public function reset()  {
        $this->headers = array('Event-Name' => 'COMMAND');
        $this->body = NULL;
        $this->hdrPointer = NULL;
    }

    public function parseLine($line) {
        if ($line == "\n") {
            return;
        } else if (preg_match('/^(.*?)\:\s*(.*)\s*$/', $line, $matches)) {
            $this->addHeader($matches[1], $matches[2]);
        } else {
            $this->addBody($line);
        }
    }

    /**
     * Turns an event into colon-separated 'name: value' pairs similar to a
     * sip/email packet (the way it looks on '/events plain all').
     */
    public function serialize($format = NULL) {
        $contentType = $this->getHeader('Content-Type');
        $reply = '';

        foreach ($this->headers as $key => $value) {
            if ($contentType == 'text/event-plain') {
                if ($key == 'Content-Type' || $key == 'Content-Length') {
                    continue;
                }
            }
            $reply .= $key .': ' .$value ."\n";
        }

        if (!empty($this->body)) {
            $reply .= 'Content-Length: ' .strlen($this->body) ."\n\n";
            $reply .= $this->body;
        }

        return $reply;
    }

    /**
     * Sets the priority of an event to $number in case it's fired.
     */
    public function setPriority($number = 0) {
        // TODO: I looked and tested but I can not figure out what these values
        // should be.....
        switch ($number) {
            case 0:
                $priority = 'NORMAL';
                break;
            case -1:
                $priority = 'LOW';
                break;
            case 1:
                $priority = 'HIGH';
                break;
            default:
                $priority = 'NORMAL';
        }
        $this->addHeader('priority', $priority);
    }

    /**
     * Gets the header with the key of $header_name from an event object.
     */
    public function getHeader($header_name) {
        if (isset($this->headers[$header_name])){
            return $this->headers[$header_name];
        }
        return NULL;
    }

    /**
     * Gets the body of an event object.
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Gets the event type of an event object.
     */
    public function getType() {
        $eventName = $this->getHeader('Event-Name');
        if (!empty($eventName)) {
            return $eventName;
        } else {
            return 'COMMAND';
        }
    }

    public function getUuid() {
        return $this->getHeader("Unique-ID");
    }

    /**
     * Add $value to the body of an event object. This can be called multiple
     * times for the same event object.
     */
    public function addBody($value) {
        if (is_null($this->body)) {
            $this->body = $value;
        } else {
            $this->body .= $value;
        }
    }

    /**
     * Add a header with key = $header_name and value = $value to an event
     * object. This can be called multiple times for the same event object.
     */
    public function addHeader($header_name, $value) {
        $this->headers[trim($header_name, " \r\n")] = trim($value, " \r\n");
    }

    /**
     * Delete the header with key $header_name from an event object.
     */
    public function delHeader($header_name) {
        if (isset($this->headers[$header_name])){
            unset($this->headers[$header_name]);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Sets the pointer to the first header in an event object, and returns
     * it's key name. This must be called before nextHeader is called.
     */
    public function firstHeader() {
        $this->hdrPointer = array_keys($this->headers);
        return $this->nextHeader();
    }

    /**
     * Moves the pointer to the next header in an event object, and returns
     * it's key name. firstHeader must be called before this method to set the
     * pointer. If you're already on the last header when this method is called,
     * then it will return NULL.
     */
    public function nextHeader() {
        if (is_array($this->hdrPointer)) {
            return array_shift($this->hdrPointer);
        } else {
            return NULL;
        }
    }

    public function convertPlainEvent($ignore_content_type = FALSE) {
        $contentType = $this->getHeader('Content-Type');

        // if the content-type is a plain-event or we specify a flag
        if ($contentType == 'text/event-plain' || $ignore_content_type ) {
            // if there is nothing in the body we are good to go
            if (empty($this->body)) return;

            // if there are contents in the body remove them....
            $body = $this->body;
            $this->body = NULL;

            // ...then convert them into headers
            $body = explode("\n", $body);
            foreach ((array)$body as $line) {
                if ($line == "\n") {
                    continue;
                } else if (strstr($line, ':')) {
                    list($key, $value) = explode(':', $line);
                    $this->addHeader($key, $value);
                } else {
                    $this->addBody($line);
                }
            }
        }
    }
}
