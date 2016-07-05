<?php
namespace Core;

use Yaf\Registry;
use Yaf\Config\Ini;
use Yaf\Exception;

class DB
{


    private static $instances = [];

    /**
     *
     * @param string $id
     * @return DB
     */
    public static function getInstance($id = 'main')
    {
        if (empty(self::$instances[$id])) {
            self::$instances[$id] = new DB($id);
        }
        return self::$instances[$id];
    }


    private $forceMaster;

    /**
     *
     * @var \PDO
     */
    private $master;


    private $slaves;

    /**
     *
     * @var Ini
     */
    private $conf;

    public function __construct($id)
    {
        $this->conf = Registry::get('db')->{$id};
        $this->forceMaster = false;
    }

    public function forceMaster($force)
    {
        $this->forceMaster = $force;
    }


    /**
     *
     * @return \PDO
     */
    public function master()
    {
        if (empty($this->master)) {
            $conf = $this->conf->master;
            $this->master = new \PDO($conf->dsn, $conf->user, $conf->pass);
            if (isset($conf->set_names)) {
                $this->master->query('SET NAMES ' . $conf->set_names);
            }
        }
        return $this->master;
    }

    /**
     *
     * @return \PDO
     */
    public function slave()
    {
        if ($this->forceMaster) {
            return $this->master();
        }

        if (empty($this->conf->slaves)) {
            return $this->master();
        }

        if (empty($this->slave)) {
            $slaves = $this->conf->slaves->toArray();
            $num = count($slaves);
            $startId = rand(0, $num - 1);
            for ($i = $startId; $i < $num + $startId; $i++) {
                $id = $i < $num ? $i : $i - $num;
                $conf = $slaves[$id];
                try {
                    $this->slave = new \PDO($conf['dsn'], $conf['user'], $conf['pass']);
                    if (isset($conf['set_names'])) {
                        $this->slave->query('SET NAMES ' . $conf['set_names']);
                    }
                    return $this->slave;
                } catch (\PDOException $e) {
                    Logger::getInstance()
                        ->error("mysql: can not connect {$conf['dsn']} " . $e->getMessage());
                }
            }
            throw new Exception('all db slaves unavailable', 1);
        }

        return $this->slave;
    }
}


