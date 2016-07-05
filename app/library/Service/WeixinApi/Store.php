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
 * 微信公众平台 微信门店
 */
class Store
{

    /**
     *
     * @var Store
     */
    private static $instance;


    /**
     * @return Store
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }
}
