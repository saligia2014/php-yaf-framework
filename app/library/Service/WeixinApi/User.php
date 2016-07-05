<?php

namespace Service\WeixinApi;

use Yaf\Application;
use Yaf\Registry;
use Core\Service;
use Core\Logger;
use Core\Redis;
use Lib\Exception;
use Service\Weixin;

/**
 * 微信公众平台 用户管理
 */
class User
{

    /**
     *
     * @var User
     */
    private static $instance;


    /**
     * @return User
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * @param $params
     * @return mixed
     * @desc 获取access_token
     */
    public function oauth2($params)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?' .
            http_build_query($params);

        $info = Weixin::getInstance()->query($url);

        return $info;
    }

    /**
     * @param $openid
     * @param $token
     * @return mixed
     */
    public function snsUserinfo($openid, $token)
    {
        $query = [
            'openid' => $openid,
            'access_token' => $token
        ];
        $url = 'https://api.weixin.qq.com/sns/userinfo?' .
            http_build_query($query);
        $info = Weixin::getInstance()->query($url);
        return $info;
    }

    /**
     * @param $openid
     */
    public function userInfo($openid)
    {
        $url = '/user/info';
    }
}
