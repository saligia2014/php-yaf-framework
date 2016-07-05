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
 * 微信公众平台 数据统计
 */
class Statistics
{

    /**
     *
     * @var Statistics
     */
    private static $instance;


    /**
     * @return Statistics
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }
}
