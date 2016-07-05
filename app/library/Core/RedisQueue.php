<?php

namespace Core;

class RedisQueue
{

    private $prefix = 'queue/';

    private $redisId;

    private static $instances = [];


    /**
     * @param string $redisId
     * @return RedisQueue
     */
    public static function getInstance($redisId = 'main')
    {
        if (empty(self::$instances[$redisId])) {
            self::$instances[$redisId] = new RedisQueue($redisId);
        }
        return self::$instances[$redisId];
    }

    public function __construct($redisId)
    {
        $this->redisId = $redisId;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function size($name)
    {
        return Redis::getInstance($this->redisId)->lLen($this->prefix . $name);
    }

    /**
     * @param string $name
     * @param mixed $data
     */
    public function push($name, $data)
    {
        return Redis::getInstance($this->redisId)->rPush($this->prefix . $name, $data);
    }

    /**
     * @param string $name
     * @param int $timeout
     * @return
     */
    public function bPop($name, $timeout = 60)
    {
        return Redis::getInstance($this->redisId, false)->blPop($this->prefix . $name, $timeout);
    }

    /**
     * @param string $name
     * @param int $timeout
     * @return
     */
    public function pop($name)
    {
        return Redis::getInstance($this->redisId)->lPop($this->prefix . $name);
    }

    /**
     * @param $name
     * @return string
     */
    public function sPop($name)
    {
        return Redis::getInstance($this->redisId)->sPop($this->prefix . $name);
    }
}

