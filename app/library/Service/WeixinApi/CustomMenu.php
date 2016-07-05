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
 * 微信公众平台 自定义菜单
 */
class CustomMenu
{

    /**
     *
     * @var CustomMenu
     */
    private static $instance;


    /**
     * @return CustomMenu
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }
}
