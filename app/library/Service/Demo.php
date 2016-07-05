<?php

namespace Service;

use Yaf\Application;
use Yaf\Registry;
use Core\Service;
use Core\Logger;
use Core\Redis;
use Lib\Exception;


/**
 * 微信公众平台
 */
class Demo extends Service
{

    /**
     *
     * @var Demo
     */
    private static $instance;


    /**
     * @return Demo
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    public function demo()
    {
        return 'demo';
    }

    public function api()
    {
//        $result = \WxUserModel::getInstance()->find([]);
//
//        $id = \WxUserModel::getInstance()->insert([]);

        return ["demo"];
    }
}