<?php

namespace Service;

use Yaf\Application;
use Yaf\Registry;
use Core\Service;
use Core\Logger;
use Core\Redis;
use Lib\Exception;


/**
 * 教程
 */
class Tutorial extends Service
{

    /**
     *
     * @var Tutorial
     */
    private static $instance;


    /**
     * @return Tutorial
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }
    
    public function create()
    {

    }

    public function edit()
    {

    }
}