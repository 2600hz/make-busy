<?php

namespace MakeBusy\Common;

date_default_timezone_set('UTC');

/**
 * Log - In house logger built by Chris to log error and debug messages
 * @author Peter Lau <plau@2600hz.com>
 * @author Sean Wysor <sean@2600hz.com>
 * @author Chris Cole <chris@2600hz.com>
 * @author Maxime Roux <maxime@2600hz.com>
 */
class Log
{
    const EMERGENCY = LOG_EMERG;
    const ALERT = LOG_ALERT;
    const CRITICAL = LOG_CRIT;
    const ERROR = LOG_ERR;
    const WARNING = LOG_WARNING;
    const NOTICE = LOG_NOTICE;
    const INFO = LOG_INFO;
    const DEBUG = LOG_DEBUG;

    protected static $severity_map = [
        LOG_EMERG => "EMERGENCY",
        LOG_ALERT => "ALERT",
        LOG_CRIT => "CRITICAL",
        LOG_ERR => "ERROR",
        LOG_WARNING => "WARNING",
        LOG_NOTICE => "NOTICE",
        LOG_INFO => "INFO",
        LOG_DEBUG => "DEBUG"
    ];

    public static function emerg() {
        $message = self::formatMessage(func_get_args());
        self::writeLog(self::EMERGENCY, $message);
    }

    public static function alert() {
        $message = self::formatMessage(func_get_args());
        self::writeLog(self::ALERT, $message);
        self::mail($message);
    }

    public static function critical() {
        $message = self::formatMessage(func_get_args());
        self::writeLog(self::CRITICAL, $message);
    }

    public static function error() {
        $message = self::formatMessage(func_get_args());
        self::writeLog(self::ERROR, $message);
    }

    public static function warning() {
        $message = self::formatMessage(func_get_args());
        self::writeLog(self::WARNING, $message);
    }

    public static function notice() {
        $message = self::formatMessage(func_get_args());
        self::writeLog(self::NOTICE, $message);
    }

    public static function info() {
        $message = self::formatMessage(func_get_args());
        self::writeLog(self::INFO, $message);
    }

    public static function debug() {
        $message = self::formatMessage(func_get_args());
        self::writeLog(self::DEBUG, $message);
    }

    public static function printAndDebug() {
        $message = self::formatMessage(func_get_args());
        echo $message . "\n";
        self::writeLog(self::DEBUG, $message);
    }

    public static function trail() {
        $message = self::formatMessage(func_get_args());
        self::writeLog(self::DEBUG, $message);
    }

    public static function dump($label, $var) {
        self::writeLog(self::DEBUG, $label . ":");
        self::writeLog(self::DEBUG, print_r($var, TRUE));
    }

    public static function var_dump($var, $log_file = null) {
        if (is_null($log_file)) {
            self::writeLog(self::DEBUG, print_r($var, TRUE));
        } else {
            file_put_contents($log_file, $var."\n", FILE_APPEND);
        }
    }

    public static function mail($message) {
        $email_config = Configuration::getFromSection('log', 'email');
        $subject = empty($email_config['subject']) ? "Alert!" : self::replaceVars($email_config['subject']);
        $email_from = empty($email_config['from_address']) ? self::replaceVars("alert@{{hostname}}") : self::replaceVars($email_config['from_address']);
        $email_to = empty($email_config['to_address']) ? "engineering@2600hz.com" : self::replaceVars($email_config['to_address']);
        $text = "$message";
        $headers = "From: $email_from";
        mail(self::$email_to, $subject, $text, $headers);
    }

    protected static function replaceVars($string) {
        $string = str_replace('{{hostname}}', gethostname(), $string);
        return $string;
    }

    /*

        Logs to syslog

    */
    protected static function writeLog($severity, $message) {
        if (!empty($_SESSION['REQUEST_ID'])) {
            $message = '[' . $_SESSION['REQUEST_ID'] .'] '. $message;
        }

        switch(Configuration::getFromSection('log', 'log_type')) {
            case 'file':
                self::fileLog($severity, $message);
                return;
            case 'syslog':
                self::syslog($severity, $message);
                return;
        }
    }

    protected static function fileLog($severity, $message) {
        $time = strftime("%F %T", time());
        $message = $time ." [" . self::$severity_map[$severity] . "] ". $message;

        $log_file = Configuration::getFromSection('log', 'log_file');
        if (isset($_ENV['LOG_CONSOLE'])) {
            fwrite(STDERR, $message . "\n");
        }
        file_put_contents($log_file, $message."\n", FILE_APPEND);
    }

    public static function truncateLog() {
        $log_file = Configuration::getFromSection('log', 'log_file');
        file_put_contents($log_file, '');
    }

    protected static function syslog($severity, $message) {
        $log_name   = Configuration::getFromSection('log', 'log_name');
        $log_stream = Configuration::getFromSection('log', 'log_stream');
        openlog($log_name, LOG_PID, $log_stream);
        syslog($severity, $message);
        closelog();
    }

    protected static function formatMessage($arguments) {
        $format = array_shift($arguments);
        return vsprintf($format, $arguments);
    }

}
