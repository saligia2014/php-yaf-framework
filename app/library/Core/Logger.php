<?php

namespace Core;

use Yaf\Registry;

class Logger
{

    /**
     *
     * @return Logger
     */
    private static $instance;

    /**
     *
     * @return Logger
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new Logger;
        }
        return self::$instance;
    }

    public function __construct()
    {
        $conf = Registry::get('conf')->log->syslog;
        openlog($conf->ident, LOG_PID | LOG_PERROR, $conf->facility);
    }

    public function debug($message)
    {
        $this->log(LOG_DEBUG, $message);
    }

    public function error($message)
    {
        $this->log(LOG_ERR, $message);
    }

    protected function log($priority, $message)
    {
        $message = (is_string($message) || is_numeric($message)) ? $message : var_export($message, true);
        syslog($priority, $message);
    }

    public function __destruct()
    {
        closelog();
    }
}
