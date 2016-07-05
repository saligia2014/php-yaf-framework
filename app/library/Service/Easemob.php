<?php


namespace Service;

use Yaf\Registry;
use Core\Service;
use Lib\Exception;
use Core\Redis;

class Easemob extends Service
{

    /**
     *
     */
    private static $instance;


    /**
     * @return Easemob
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }


    private $conf;

    private $host;

    public function __construct()
    {
        $this->conf = Registry::get('conf')->easemob;
        $this->host = $this->conf->host . '/' . $this->conf->org_name . '/' . $this->conf->app_name;
        $this->initToken();
    }

    public function signup($salt)
    {
        $name = sha1($salt . time());
        $pass = sha1($salt . $name . time());
        if ($this->call('/users', ['username' => $name, 'password' => $pass])) {
            return array($name, $pass);
        }
    }

    private $token;

    private $uuid;

    private $expires;

    public function initToken()
    {
        $cache = Redis::getInstance('cache');
        $rs = $cache->get('easemob_token_info');
        if ($rs) {
            $info = explode(' ', $rs);
            if (is_array($info)) {
                $this->token = $info[0];
                $this->uuid = $info[1];
                $this->expires = (int)$info[2];
            }
        }

        if (empty($this->token)) {
            $this->refreshToken();
        }
    }

    public function refreshToken()
    {
        $rs = $this->curl($this->host . '/token', [], array(
            'grant_type' => 'client_credentials',
            'client_id' => $this->conf->client_id,
            'client_secret' => $this->conf->client_secret,
        ));

        if (is_array($rs)) {
            $this->token = $rs['access_token'];
            $this->uuid = $rs['application'];
            $this->expires = $rs['expires_in'] + time() - 60; //提前1分钟
            $cache = Redis::getInstance('cache');
            $cache->set('easemob_token_info',
                $this->token . ' ' . $this->uuid . ' ' . $this->expires,
                $rs['expires_in'] - 60);
        }
    }

    public function call($api, array $data = array())
    {
        if ($this->expires < time()) {
            $this->refreshToken();
        }
        return $this->curl($this->host . $api, ["Authorization: Bearer {$this->token}"], $data);
    }

    private function curl($url, array $headers, array $data)
    {
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $rs = curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
            $ok = true;
        } else {
            $ok = false;
            $err = json_decode($rs, true);
            if (is_array($err)) {
                $msg = $err['error'];
            } else {
                $msg = $rs;
            }
            $this->setError(Exception::SERVER_ERROR, 'easemob api: ' . $msg);
        }

        curl_close($ch);
        if ($ok) {
            return json_decode($rs, true);
        }
    }
}
