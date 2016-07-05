<?php


namespace Core;

use Yaf\Exception;
use DateTime;

abstract class Model
{

    const COLUMN_INT = 1;

    const COLUMN_STRING = 2;

    const COLUMN_TEXT = 3;

    const COLUMN_FLOAT = 4;

    const COLUMN_TIME = 5;

    const CREATE_TIME_KEY = 'created_at';

    const UPDATE_TIME_KEY = 'updated_at';

    const DATE_FORMAT = 'Y-m-d';

    const TIME_FORMAT = 'H:i:s';

    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    const STATE_SHOW = 0;

    const STATE_DELETE = 1;

    private static $models = [];

    /**
     * @param string $name
     * @return Model
     */
    public static function instance($name)
    {
        if (empty(Model::$models[$name])) {
            $class = '\\' . $name . 'Model';
            Model::$models[$name] = new $class;
        }
        return Model::$models[$name];
    }

    /**
     *
     * @var string
     */
    protected $table;

    /**
     * array(
     *      'id' => Model::COLUMN_INT,
     *      'name' => Model::COLUMN_STRING,
     *      'lat' => Model::COLUMN_FLOAT,
     *      'lng' => Model::COLUMN_FLOAT,
     *      'enabled' => Model::COLUMN_BOOL,
     * )
     *
     *
     * @var array
     */
    protected $columns = [];

    private $useCache = true;

    private $cachePrefix = 'db/';

    private $cacheExpires = 518400;


    public function __construct($table, $columns = [])
    {
        $this->table = $table;
        $this->columns = $columns;
    }

    public function table()
    {
        return $this->table;
    }

    public function format(&$data)
    {
        foreach ($this->columns as $name => $type) {
            if (isset($data[$name])) {
                $data[$name] = $this->formatColumn($data[$name], $type);
            }
        }
    }

    protected function useCache($use = true)
    {
        $this->useCache = (bool)$use;
    }

    private function formatColumn($col, $type)
    {
        switch ($type) {
            case Model::COLUMN_INT:
                return (int)$col;

            case Model::COLUMN_STRING:
                return $col;

            case Model::COLUMN_FLOAT:
                return (float)$col;

            case Model::COLUMN_TIME:
                return new DateTime($col);
            default :
                return $col;
        }
    }

    public function formatDateTime(&$data)
    {
        foreach ($this->columns as $name => $type) {
            if (isset($data[$name]) && $type === Model::COLUMN_TIME) {
                if ($data[$name] instanceof DateTime) {
                    $data[$name] = $data[$name]->format(Model::DATETIME_FORMAT);
                } else {
                    throw new Exception('COLUMN_TIME must be an instance of DateTime', 1);
                }
            }
        }
    }

    public function insert(array $data)
    {
        $cols = array();
        $vals = array();

        if (isset($this->columns[Model::CREATE_TIME_KEY]) && empty($data[Model::CREATE_TIME_KEY])) {
            $data[Model::CREATE_TIME_KEY] = new DateTime();
        }

        if (isset($this->columns[Model::UPDATE_TIME_KEY]) && empty($data[Model::UPDATE_TIME_KEY])) {
            $data[Model::UPDATE_TIME_KEY] = new DateTime();
        }

        $this->formatDateTime($data);

        $placeHolders = array();
        foreach ($data as $col => $val) {
            $cols[] = $col;
            $vals[] = $val;
            $placeHolders[] = '?';
        }
        $sql = 'INSERT INTO `' . $this->table
            . '` (`' . implode('`,`', $cols) . '`) VALUES ('
            . implode(',', $placeHolders) . ')';

        $stmt = Stmt::create($sql);
        foreach ($vals as $i => $val) {
            $stmt->bindValue($i + 1, $val);
        }
        if ($stmt->execute()) {
            $data['id'] = $stmt->lastInsertId();
            $this->format($data);
            return $data['id'];
        }
        return 0;
    }

    /**
     * @param array $data = array(
     *      'key' => value,
     *      'num' => ['++' => 1]    // num = num + 1
     *      'count' => ['--' => 2]  // count = count - 2
     * )
     *
     * @param array $cond
     * @param int $offset
     * @param int $limit
     * @return bool
     * @throws Exception
     */
    public function update(array $data, array $cond, $offset = 0, $limit = 0)
    {
        $vals = array();

        if (isset($this->columns[Model::UPDATE_TIME_KEY]) && empty($data[Model::UPDATE_TIME_KEY])) {
            $data[Model::UPDATE_TIME_KEY] = new DateTime();
        }

        $this->formatDateTime($data);

        $sql = 'UPDATE `' . $this->table . '` SET ';
        foreach ($data as $col => $val) {
            if (is_array($val)) {
                list($op, $v) = each($val);
                if ($op === '++') {
                    $sql .= "`$col` = `$col` + {$v},";
                } else if ($op === '--') {
                    $sql .= "`$col` = `$col` - {$v},";
                }
            } else {
                $sql .= '`' . $col . '`= ?,';
                $vals[] = $val;
            }
        }

        $parsed = $this->parseCond($cond);
        $sql = rtrim($sql, ',') . $this->getWherePart($parsed, $vals);
        if ($limit > 0) {
            $sql = sprintf("$sql LIMIT %u,%u", $offset, $limit);
        }

        $stmt = Stmt::create($sql);
        $ok = $stmt->execute($vals);
        if ($ok) {
            $ids = $this->findIdsFromDb($parsed, $offset, $limit);
            foreach ($ids as $id) {
                $this->delCache($id);
            }
        }
        return $ok;
    }

    protected function findIdsFromDb($cond, $offset, $limit)
    {
        $vals = [];
        $where = $this->getWherePart($cond, $vals);
        $sql = "SELECT id FROM `{$this->table}`" . $where;
        if ($limit > 0) {
            if ($offset <= 0) {
                $offset = 0;
            }
            $sql = sprintf("$sql LIMIT %u,%u", $offset, $limit);
        }

        $stmt = Stmt::create($sql);
        $stmt->execute($cond['params']);
        $rs = $stmt->fetchAll();
        $ids = [];
        foreach ($rs as &$row) {
            $ids[] = $row['id'];
        }
        return $ids;
    }

    public function save(&$data)
    {
        if (isset($data['id']) && $data['id'] > 0) {
            return $this->update($data, ['id' => $data['id']]);
        }
        $data['id'] = $this->insert($data);
        return $data['id'] > 0;
    }

    public function find(array $cond, array $order = [])
    {
        $rows = $this->findAll($cond, $order, 0, 1);
        return isset($rows[0]) ? $rows[0] : null;
    }

    protected function delCache($id)
    {
        try {
            Redis::getInstance('cache')->del($this->cachekey($id));
        } catch (\Exception $e) {
            Logger::getInstance()->error($e->getMessage());
        }
    }

    private function getWherePart($parsed, array &$vals)
    {
        if ($parsed['where']) {
            foreach ($parsed['params'] as $val) {
                $vals[] = $val;
            }
            return ' WHERE ' . implode(' AND ', $parsed['where']);
        }
        return '';
    }

    /**
     * @desc $condition['id'] = ['>=' => $id] means "id >= $id".
     * @desc  $condition['id'] = [1,2,3] means "id IN (1,2,3)".
     * @desc  $condition['id'] = ['like' => $id] means "id like %$id%".
     * @desc $condition['id'] = ['notin' => array] means "id not in (array)".
     * @param array $condition
     * @return array
     * @throws Exception
     */
    protected function parseCond(array $condition)
    {
        $whereParts = [];
        $params = [];
        $cacheIds = [];
        foreach ($condition as $key => $value) {

            if ($value instanceof DateTime) {
                $value = $value->format(Model::DATETIME_FORMAT);
            }

            if (is_string($key)) {
                $col = $this->quoteColumn($key);
                if (is_array($value)) {
                    foreach ($value as $i => $v) {
                        if ($v instanceof DateTime) {
                            $value[$i] = $v->format(Model::DATETIME_FORMAT);
                        }
                    }

                    if (count($value)) {
                        list($op, $val) = each($value);
                        if (in_array((string)$op, ['!=', '<>', '<', '>', '<=', '>='])) {
                            $whereParts[] = "$col $op ?";
                            $params[] = $val;
                        } //between ? and ?
                        else if ((string)$op === 'between') {
                            $whereParts[] = "$col BETWEEN ? AND ?";
                            $params[] = $val[0];
                            $params[] = $val[1];
                        } //like
                        else if ($op === 'like') {
                            $whereParts[] = "$col LIKE ?";
                            $params[] = "%$val%";

                            //NOT IN condition:   $key NOT IN ($val...)
                        } else if ($op === 'notin') {
                            if (is_array($val) && count($val)) {
                                $placeholders = [];
                                foreach (array_unique($val) as $v) {
                                    $placeholders[] = '?';
                                    $params[] = $v;
                                }
                                $whereParts[] = "$col NOT IN (" . implode(',', $placeholders) . ')';
                            }

                            //IN condition:  $key IN ($value...)
                        } else {
                            $placeholders = [];
                            $uniqVals = array_unique($value);
                            foreach ($uniqVals as $v) {
                                $placeholders[] = '?';
                                $params[] = $v;
                            }
                            $whereParts[] = "$col IN (" . implode(',', $placeholders) . ')';
                            if ($key == 'id') {
                                $cacheIds = array_values($uniqVals);
                            }
                        }

                        //Empty array consider as a false condition
                    } else {
                        $whereParts[] = 'FALSE';
                    }
                } else {
                    $whereParts[] = "$col = ?";
                    $params[] = $value;
                    if ($key == 'id') {
                        $cacheIds = [$value];
                    }
                }
            } else {
                throw new Exception("Model: error mysql column: $key", 1);
            }
        }

        $cond = [
            'where' => $whereParts,
            'params' => $params,
            'cache' => false,
        ];

        if ($this->useCache && $cacheIds && count($condition) == 1) {
            $cond['cache'] = $cacheIds;
        }

        return $cond;
    }

    public function cachekey($id)
    {
        return $this->cachePrefix . $this->table . '/' . $id;
    }

    protected function quoteColumn($name)
    {
        return '`' . str_replace('.', '`.`', $name) . '`';
    }

    public function findAll(array $cond = [], array $order = [], $offset = 0, $limit = 0)
    {
        $parsedCond = $this->parseCond($cond);

        if ($parsedCond['cache'] && empty($order) && $offset == 0 && $limit <= 1) {
            $result = $this->getFromCache($parsedCond['cache']);
        } else {
            $result = $this->findAllFromDb($parsedCond, $order, $offset, $limit);
        }

        return $result;
    }

    protected function getFromCache($ids)
    {
        $keys = [];
        foreach ($ids as $id) {
            $keys[] = $this->cachekey($id);
        }

        //retrieve from redis cache server
        $redis = Redis::getInstance('cache');
        try {
            $rs = $redis->mget($keys);
        } catch (\Exception $e) {
            Logger::getInstance()->error($e->getMessage());
        }

        $missed = [];
        foreach ($rs as $i => $row) {
            if (!is_array($row) || empty($row['id'])) {
                $id = $ids[$i];
                unset($rs[$i]);
                if ($id) {
                    $missed[] = $id;
                }
            }
        }

        //get missed data by id from mysql db and update to cache
        if ($missed) {
            $dbrs = $this->findAllFromDb($this->parseCond(['id' => $missed]));
            try {
                foreach ($dbrs as $row) {
                    $redis->set($this->cachekey($row['id']), $row, $this->cacheExpires);
                }
            } catch (\Exception $e) {
                Logger::getInstance()->error($e->getMessage());
            }

        } else {
            $dbrs = [];
        }

        return array_merge($rs, $dbrs);
    }

    protected function findAllFromDb($cond, array $order = [], $offset = 0, $limit = 0)
    {
        $vals = [];
        $where = $this->getWherePart($cond, $vals);
        $sql = "SELECT * FROM `{$this->table}`" . $where . $this->orderPart($order);
        if ($limit > 0) {
            if ($offset <= 0) {
                $offset = 0;
            }
            $sql = sprintf("$sql LIMIT %u,%u", $offset, $limit);
        }

        $stmt = Stmt::create($sql);
        $stmt->execute($cond['params']);
        $rs = $stmt->fetchAll();
        foreach ($rs as &$row) {
            $this->format($row);
        }
        return $rs;
    }

    protected function wherePart($cond, array &$vals)
    {
        $parsed = $this->parseCond($cond);
        if ($parsed['where']) {
            foreach ($parsed['params'] as $val) {
                $vals[] = $val;
            }
            return ' WHERE ' . implode(' AND ', $parsed['where']);
        }
        return '';
    }

    protected function orderPart(array $order)
    {
        $parts = array();
        foreach ($order as $key => $od) {
            $od = strtoupper($od);
            if (in_array($od, ['ASC', 'DESC'])) {
                $parts[] = "`$key` $od";
            }
        }

        if ($parts) {
            return " ORDER BY " . implode(',', $parts);
        }

        return '';
    }

    protected function limitPart($page, $limit)
    {
        if ($limit > 0) {
            $offset = ($page - 1) * $limit;
            if ($offset <= 0) {
                $offset = 0;
            }
            return sprintf(" LIMIT %u,%u", $offset, $limit);
        }
        return '';
    }

    public function delete($cond, $offset = 0, $limit = 0)
    {
        $parsed = $this->parseCond($cond);
        $ids = $this->findIdsFromDb($parsed, $offset, $limit);
        $ok = true;
        if ($ids) {
            $sql = "DELETE FROM `{$this->table}` WHERE `id` IN (" . implode(',', $ids) . ')';
            if ($limit > 0) {
                $sql = sprintf("$sql LIMIT %u,%u", $offset, $limit);
            }
            $stmt = Stmt::create($sql);
            $ok = $stmt->execute();
            if ($ok) {
                foreach ($ids as $id) {
                    $this->delCache($id);
                }
            }
        }
        return $ok;
    }

    /**
     * count by condition
     * @param array $cond
     * @return int
     */
    public function count(array $cond = [])
    {
        $vals = array();
        $where = $this->wherePart($cond, $vals);
        if ($where) {
            $sql = "SELECT COUNT(*) total from `{$this->table}`" . $where;
            $stmt = Stmt::create($sql);
            $stmt->execute($vals);
            $num = (int)$stmt->fetch()['total'];
        } else {
            //整表统计有一定误差
            $sql = "SHOW TABLE STATUS LIKE '{$this->table}'";
            $stmt = Stmt::create($sql);
            $stmt->execute();
            $num = (int)$stmt->fetch()['Rows'];
        }

        return $num;
    }

    /**
     * @param int $id
     * @param array $order
     * @return mixed
     */
    public function findById($id, array $order = [])
    {
        return $this->find(['id' => $id], $order);
    }

    /**
     * @param int|array $id
     * @return bool
     */
    public function deleteById($id)
    {
        return $this->delete(['id' => $id]);
    }

    /**
     * @param int|array $id
     * @param array $data
     * @return bool
     */
    public function updateById($id, array $data)
    {
        return $this->update($data, ['id' => $id]);
    }
}
