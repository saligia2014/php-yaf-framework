<?php

namespace Service;

use Yaf\Application;
use Yaf\Registry;
use Core\Service;
use Core\Logger;
use Core\Redis;
use Lib\Exception;


/**
 * 步骤
 */
class Step extends Service
{

    /**
     *
     * @var Step
     */
    private static $instance;


    /**
     * @return Step
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }
}