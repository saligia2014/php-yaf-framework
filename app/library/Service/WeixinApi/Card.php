<?php

namespace Service\WeixinApi;

use Yaf\Application;
use Yaf\Registry;
use Core\Service;
use Core\Logger;
use Core\Redis;
use Lib\Exception;


/**
 * 微信公众平台 微信卡券
 */
class Card
{
    /**
     *
     * @var Card
     */
    private static $instance;


    /**
     * @return Card
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }
}
