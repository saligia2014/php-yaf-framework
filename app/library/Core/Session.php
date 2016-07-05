<?php

namespace Core;

use Yaf\Registry;
use Yaf\Exception;

class Session
{

    /**
     *
     * @var Session
     */
    private static $instance;

    /**
     *
     * @return Session
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new Session;
        }
        return self::$instance;
    }

    private $conf;

    private $prefix = 'session_data/';

    private $token;

    public function __construct()
    {
        $this->retrieveToken();
    }

    public function setExpiration()
    {
        if ($this->token && $this->conf->duration) {
            Redis::getInstance('main')
                ->expire($this->prefix . $this->token, $this->conf->duration);
        }
    }

    private function retrieveToken()
    {
        $this->conf = Registry::get('conf')->security->session;
        $header = 'HTTP_' . strtoupper($this->conf->header_key);
        if (isset($_SERVER[$header])) {
            $this->token = trim($_SERVER[$header]);
        }

        if (!$this->token && isset($_COOKIE[$this->conf->cookie_key])) {
            $this->token = $_COOKIE[$this->conf->cookie_key];
        }

        if (!$this->get('__created_at')) {
            $this->token = null;
        }
    }

    public function initToken($token = null)
    {
        $time = time();

        if ($token) {
            $this->token = $token;
            $ok = Redis::getInstance('main')->hGet(
                $this->prefix . $this->token, '__created_at');

            if (!$ok) {
                throw new Exception('use session token failed', 1);
            }
        } else {
            $this->token = sha1($this->conf->token_salt . uniqid() . $time);
            $ok = Redis::getInstance('main')->hSetNx(
                $this->prefix . $this->token, '__created_at', $time);

            if (!$ok) {
                throw new Exception('create session token failed', 1);
            }
        }

        if ($this->conf->cookie_domain) {
            $domain = $this->conf->cookie_domain;
        } else {
            $domain = null;
        }
        setcookie($this->conf->cookie_key, $this->token,
            $this->conf->duration > 0 ? $time + $this->conf->duration : null,
            '/', $domain, null, true);
    }

    private function checkKey($key)
    {
        if ($key == '__created_at') {
            throw new Exception('Session reserved key word ' . $key, 1);
        }
    }

    public function set($key, $value)
    {
        $this->checkKey($key);

        if (!$this->token) {
            $this->initToken();
        }

        return Redis::getInstance('main')->hSet(
            $this->prefix . $this->token, $key, $value);
    }

    public function add($key, $value)
    {
        $this->checkKey($key);

        if (!$this->token) {
            $this->initToken();
        }

        return Redis::getInstance('main')->hSetNx(
            $this->prefix . $this->token, $key, $value);
    }

    public function get($key)
    {
        if ($this->token) {
            $value = Redis::getInstance('main')
                ->hGet($this->prefix . $this->token, $key);
            return $value;
        }
    }

    public function del($key)
    {
        if ($this->token) {
            return Redis::getInstance('main')
                ->hDel($this->prefix . $this->token, $key);
        }
        return true;
    }

    public function destory($token = null)
    {
        $key = $this->prefix . ($token ?: $this->token);
        return Redis::getInstance('main')->del($key);
    }

    public function token()
    {
        if (!$this->token) {
            $this->initToken();
        }
        return $this->token;
    }

    public function duration()
    {
        return $this->conf->duration;
    }

    public function __destruct()
    {
        $this->setExpiration();
    }
}

