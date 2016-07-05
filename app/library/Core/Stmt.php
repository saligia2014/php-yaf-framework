<?php

namespace Core;

use Yaf\Application;
use PDO;
use PDOStatement;

class Stmt
{

    private $dbId;

    private $sql;

    private $params = [];


    private static $logs = [];

    private static function log($sql, $params)
    {
        if (count(self::$logs) < 1000) {
            self::$logs[] = [
                'sql' => preg_replace("/[\r\n]/", ' ', $sql),
                'params' => $params,
            ];
        }
    }

    public static function getLogs()
    {
        return self::$logs;
    }

    /**
     *
     * @var PDOStatement
     */
    private $stmt;

    /**
     *
     * @param string $sql
     * @param string $dbId
     * @return Stmt
     */
    public static function create($sql, $dbId = 'main')
    {
        return new Stmt($sql, $dbId);
    }

    public function __construct($sql, $dbId = 'main')
    {
        $this->sql = trim($sql);
        $this->dbId = $dbId;
    }

    public function bindValue($param, $value, $valueType = null)
    {
        if (is_int($value)) {
            $valueType = PDO::PARAM_INT;
        } else {
            $valueType = PDO::PARAM_STR;
        }

        $this->params[$param] = [$value, $valueType];
    }

    public function execute(array $params = null)
    {
        $isWritable = false;
        if (strncasecmp($this->sql, 'select', 6) === 0) {
            $pdo = DB::getInstance($this->dbId)->slave();
        } else {
            $isWritable = true;
            $pdo = DB::getInstance($this->dbId)->master();
        }

        $this->stmt = $pdo->prepare($this->sql);
        $this->stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($this->params as $param => $value) {
            $this->stmt->bindValue($param, $value[0], $value[1]);
        }

        $ok = $this->stmt->execute($params);
        if (!$ok) {
            $info = $this->stmt->errorInfo();
            $uri = Application::app()
                ->getDispatcher()
                ->getRequest()
                ->getRequestUri();
            Logger::getInstance()->error(
                "sql: {$this->sql}, uri: {$uri}, error: " . implode(' ', $info));
        }

        if (Application::app()->environ() == ENV_DEV) {
            $vals = [];
            foreach ($this->params as $val) {
                $vals[] = $val[0];
            }
            if ($params) {
                foreach ($params as $val) {
                    $vals[] = $val;
                }
            }
            self::log($this->sql, $vals);
        }

        $this->_statisticSqlQuery($isWritable);

        return $ok;
    }

    public function fetch()
    {
        return $this->stmt->fetch();
    }

    public function fetchAll()
    {
        return $this->stmt->fetchAll();
    }

    public function lastInsertId()
    {
        return DB::getInstance($this->dbId)->master()->lastInsertId();
    }

    private function _statisticSqlQuery($isWritable = false)
    {
        $datePrefix = date('Y-m-d-H');
        $totalPrefix = "sql_query_total_num/{$datePrefix}";
        $writePrefix = "sql_query_write_num/{$datePrefix}";
        $redis = Redis::getInstance('main');
        $redis->incrBy($totalPrefix, 1);
        if ($isWritable) {
            $redis->incrBy($writePrefix, 1);
        }
    }

}
