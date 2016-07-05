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
 * 微信公众平台 消息管理
 */
class Message
{

    /**
     *
     * @var Message
     */
    private static $instance;


    /**
     * @return Message
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }
}
