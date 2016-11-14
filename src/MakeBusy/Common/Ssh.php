<?php

namespace MakeBusy\Common;

use \Exception;

use phpseclib\Net\SSH2;
use phpseclib\Crypt\RSA;

/**
 * Ssh wrapper to simplify phpseclib annoyances
 *
 * @package
 * @author "Sean Wysor" <sean@2600hz.com>
 * @version $Id$
 */
class Ssh
{
    private $ssh;
    private $prompt;
    private $password;
    private $prompt_pattern;
    private $prompt_password;
    private $host;
    private $timeout;

    const MAX_RETRY = 3;

    public function __construct($host, $port = 22, $timeout = 10) {
        $ssh = new SSH2($host, $port, $timeout);
        //define('NET_SSH2_LOGGING', SSH2::LOG_REALTIME);
        $this->setSsh($ssh);
        $this->setHost($host);
    }

    /**
     * login
     *  this lib seems to crash due to unable to create pty on this system
     * to avoid this, we retry a few times to init the ssh class
     *
     * @param array $options
     * @return boolean success|failure
     */
    public function login(array $options, $retry = 0) {
        if (self::MAX_RETRY <= $retry) {
            throw new Exception("Ssh connection failed");
        }
        $ssh  = $this->getSsh();
        $pass = $this->getCredsFromOptions($options);
        $this->setPassword($options['password']);
        try {
            if (!$ssh->login($options['username'], $pass)) {
                return false;
            }
            $this->detectPrompt();
        } catch (Exception $e) {
            $retry++;
            $this->login($options, $retry);
        }
        return true;
    }

    public function getCredsFromOptions($options){
        if (!empty($options['ssh_key'])) {
            return $this->initSshKey($options['ssh_key']);
        }
        return $options['password'];
    }

    public function initSshKey($key) {
        $ssh = $this->getSsh();
        $rsa = new RSA();
        $rsa->loadKey($key);
        return $rsa;
    }


    /**
     * read prompt or pattern, remove special (unreadable) chars from output
     *
     * @param mixed $pattern
     * @return string raw_out
     */
    public function read($pattern = null) {
        $ssh = $this->getSsh();
        if (is_null($pattern)){
            $pattern = $this->getPrompt();
            $raw_out = $ssh->read($pattern);
        } else {
            $raw_out = $ssh->read($pattern, SSH2::READ_REGEX);
        }
        return preg_replace('/[^\x00-\x7F]+/', '', $raw_out);
    }

    /**
     * sudoRead read with the possiblity of a prompt, if prompt found, send password
     *
     * @return string raw_out
     */
    public function sudoRead() {
        $pattern = $this->getPromptPassword();
        $ssh = $this->getSsh();
        $raw_out = $this->read($pattern);

        if (preg_match($this->getPasswordPattern(), $raw_out)) {
            $password = $this->getPassword();
            $ssh->write($password . "\n"); // we use \n here because it seems to be all that is needed
            return $this->read();
        }
        return $raw_out;
    }

    /**
     * write SSH writes commands into the same FIFO that gets read, so read that out of the buffer
     * anytime non-empty commands are received the \r is not a mistake, it needs to be here so the buffer
     * is emptied cleanly for subsequent commands
     *
     * @param mixed $command
     * @return void
     */
    public function write($command = null) {
        $og_timeout = $this->getTimeout();
        $this->setTimeout(1);
        $this->read();
        $ssh = $this->getSsh();
        $ssh->write($command . "\r\n");
        if (!is_null($command)){
            $ssh->read($command);
        }
        $this->setTimeout($og_timeout);
    }

    public function exec($command) {
        $ssh = $this->getSsh();
        return $ssh->exec($command);
    }

    /**
     * sudoExec
     * sudo only asks for the password the first time, so read
     *
     * @param mixed $command
     * @return void
     */
    public function sudoExec($command){
        $ssh = $this->getSsh();
        $this->write('sudo ' . $command);
        return $this->sudoRead();
    }

    public function setTimeout($timeout){
        $ssh = $this->getSsh();
        $this->timeout = $timeout;
        $ssh->setTimeout($timeout);
        return $this;
    }
    public function getTimeout(){
        return $this->timeout;
    }

    public function getSsh() {
        return $this->ssh;
    }

    public function setSsh($ssh) {
        $this->ssh = $ssh;
        return $this;
    }

    public function getHost() {
        return $this->host;
    }

    public function setHost($host) {
        $this->host = $host;
        return $this;
    }

    /**
     * detectPrompt used to dynamically detect and setup prompts and patterns
     * used to navigate the shell. users can set custom prompts, so this might still not catch everything
     * @return void
     */
    private function detectPrompt() {
        $ssh = $this->getSsh();
        $this->setTimeout(1);
        $ssh->read("/.*[\%\$\#]{1}/", 2);
        $this->write();
        $prompt = trim($ssh->read("/.*[\%\$\#]{1}/", 2));
        $this->setPrompt($prompt);
        $this->setTimeout(10);
        return $this;
    }

    private function getPassword(){
        return $this->password;
    }

    private function setPassword($password){
        $this->password = $password;
        return $this;
    }

    private function reset(){
        $this->getSsh()->reset();
        return $this;
    }

    private function setPrompt($prompt){
        $pattern = preg_quote($prompt, "/");
        $password_pattern = "[Pp]assword[^:]*:";
        $this->prompt = $prompt;
        $this->prompt_pattern = "/$pattern/";
        $this->prompt_password = "/(?:$password_pattern|$pattern)/";
        $this->password_pattern = "/$password_pattern/";
        return $this;
    }

    private function getPrompt(){
        return $this->prompt;
    }

    private function getPromptPattern(){
        return $this->prompt_pattern;
    }

    private function getPromptPassword(){
        return $this->prompt_password;
    }

    private function getPasswordPattern(){
        return $this->password_pattern;
    }
}
