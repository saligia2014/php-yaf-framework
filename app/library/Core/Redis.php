<?php

namespace Core;


use Yaf\Exception;
use Yaf\Registry;


class Redis
{

    private static $instances;

    /**
     * @param $id
     * @param bool $timeout
     * @return Redis
     */
    public static function getInstance($id, $timeout = true)
    {
        $key = $id . ($timeout ? '' : '_nt');
        if (empty(self::$instances[$key])) {
            self::$instances[$key] = new Redis($id, $timeout);
        }
        return self::$instances[$key];
    }

    private $redis;

    private $isCluster = false;

    public function __construct($id, $timeout = true)
    {
        $conf = Registry::get('redis')->{$id};
        if (!$conf) {
            throw new Exception("Redis: $id config not exits");
        }

        if (isset($conf->seeds)) {
            $this->redis = $this->getCluster($conf, $timeout);
            $this->isCluster = true;
        } else {
            $this->redis = $this->getRedis($conf, $timeout);
        }
    }

    private function getCluster($conf, $timeout)
    {
        try {
            if ($timeout) {
                $redis = new \RedisCluster(null, $conf->seeds->toArray(),
                    $conf->timeout, $conf->read_timeout);
            } else {
                $redis = new \RedisCluster(null, $conf->seeds->toArray());
            }
        } catch (\Exception $e) {
            throw new Exception('Redis: failed to connect to server');
        }

        return $redis;
    }

    private function getRedis($conf, $timeout)
    {
        $redis = new \Redis();
        if ($timeout) {
            $redis->connect($conf->get('ip'), $conf->get('port'), $conf->get('timeout'));
        } else {
            $redis->connect($conf->get('ip'), $conf->get('port'));
        }

        $redis->select($conf->get('db'));
        return $redis;
    }

    public function __call($name, $arguments)
    {
        foreach ($arguments as $i => $arg) {
            if (is_array($arg)) {
                $arguments[$i] = msgpack_pack($arg);
            }
        }

        $rs = call_user_func_array([$this->redis, $name], $arguments);
        return $this->unpackArray($rs);
    }

    public function hMGet($key, $fields)
    {
        return $this->unpackArray($this->redis->hMGet($key, $fields));
    }

    public function hMset($key, $hash)
    {
        return $this->redis->hMset($key, $hash);
    }

    public function scan(&$cursor, $pattern = '*', $count = 0)
    {
        if ($this->isCluster) {
            return $this->redis->scan($cursor, $count, $pattern);
        }

        return $this->redis->scan($cursor, $pattern, $count);
    }

    public function zRevRangeByScore($key, $max, $min, $opt)
    {
        $rs = $this->redis->zRevRangeByScore($key, $max, $min, $opt);
        return $this->unpackArrayList($rs);
    }

    public function zRangeByScore($key, $min, $max, $opt)
    {
        $rs = $this->redis->zRangeByScore($key, $min, $max, $opt);
        return $this->unpackArrayList($rs);
    }

    public function zRange($key, $min, $max, $opt)
    {
        $rs = $this->redis->zRange($key, $min, $max, $opt);
        return $this->unpackZSet($rs);
    }

    private function unpackZSet(array $vals)
    {
        $list = [];
        foreach ($vals as $val => $score) {
            $data = $this->unpackArray($val);
            if (is_array($data)) {
                $data['_score'] = $score;
                $list[] = $data;
            } else {
                $list[$val] = $score;
            }
        }
        return $list;
    }

    public function mget($keys)
    {
        if ($this->isCluster) {
            $rs = call_user_func_array([$this->redis, 'mget'], $keys);
        } else {
            $rs = $this->redis->mget($keys);
        }

        return $this->unpackArrayList($rs);
    }

    public function sMembers($key)
    {
        $rs = $this->redis->sMembers($key);
        return $this->unpackArrayList($rs);
    }

    public function hGetAll($key)
    {
        $rs = $this->redis->hGetAll($key);
        return $this->unpackArrayList($rs);
    }

    public function lRange($key, $start, $stop)
    {
        $rs = $this->redis->lrange($key, $start, $stop);
        return $this->unpackArrayList($rs);
    }

    public function blpop($key, $timeout)
    {
        if (is_array($key)) {
            $key[] = $timeout;
            $rs = call_user_func_array([$this->redis, 'blpop'], $key);
        } else {
            $rs = $this->redis->blPop($key, $timeout);
        }
        if (is_array($rs) && count($rs) == 2) {
            return $this->unpackArray($rs[1]);
        }
        return false;
    }

    public function brpop($key, $timeout)
    {
        if (is_array($key)) {
            $key[] = $timeout;
            $rs = call_user_func_array([$this->redis, 'brpop'], $key);
        } else {
            $rs = $this->redis->brPop($key, $timeout);
        }
        if (is_array($rs) && count($rs) == 2) {
            return $this->unpackArray($rs[1]);
        }
        return false;
    }

    private function unpackArrayList($rs)
    {
        $list = [];
        if (is_array($rs)) {
            foreach ($rs as $i => $val) {
                $list[$i] = $this->unpackArray($val);
            }
        }
        return $list;
    }

    private function unpackArray($val)
    {
        if (is_string($val)) {
            $data = @msgpack_unpack($val);
            if (is_array($data)) {
                return $data;
            }
        }

        return $val;
    }

}



