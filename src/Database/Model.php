<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/3/28
 * Time: 16:23
 */

namespace shybily\framework\Database;

use JsonSerializable;
use flame;
use shybily\framework\Database\Query\QueryBuilder;
use shybily\framework\Log;
use shybily\framework\Database\Exception\DbException;


class Model implements JsonSerializable {

    public $connection = 'default';
    public $table      = "";
    public $primaryKey = 'id';

    protected $attributes   = [];
    protected $changes      = [];
    protected $exists       = false;
    protected $deleted      = false;
    protected $db           = null;
    protected $queryBuilder = null;

    protected $timestamp       = false;
    protected $createdAt       = 'created_at';
    protected $updatedAt       = 'updated_at';
    protected $timestampFormat = "Y-m-d H:i:s";

    public function __construct() { }

    /**
     * @return flame\mysql\client|null
     */
    public function getConnection() {
        if (!$this->db instanceof flame\mysql\client) {
            $this->db = getMysql($this->connection);
        }
        return $this->db;
    }

    /**
     * @param flame\mysql\client $connection
     * @return $this
     */
    public function setConnection(flame\mysql\client $connection) {
        $this->db = $connection;
        return $this;
    }

    /**
     * @return array|bool|flame\mysql\result
     * @throws DbException
     */
    public function save() {
        if ($this->deleted) {
            Log::error("model has been deleted");
            return false;
        }
        if (empty($this->attributes)) {
            return false;
        }

        if ($this->exists) {
            $res = $this->update();
        } else {
            $res = $this->insert();
        }

        if (empty($res)) {
            return false;
        }
        $this->exists && $this->changes = [];
        return $res;
    }

    /**
     * @return bool
     */
    public function delete() {
        if (!$this->exists) {
            return false;
        }
        try {
            $res = $this->newQuery()->getConnection()->delete(
                $this->table,
                ["{$this->primaryKey}" => $this->attributes[$this->primaryKey]]
            );
        } catch (\Exception $exception) {
            Log::error("db query failed", [
                'function' => __FUNCTION__,
                'code'     => $exception->getCode(),
                'message'  => $exception->getMessage(),
            ]);
            return false;
        }
        $res = isset($res['affected_rows']) && $res['affected_rows'] >= 1 ? true : false;
        if ($res) {
            $this->exists     = false;
            $this->changes    = $this->attributes;
            $this->attributes = [];
            $this->deleted    = true;
        }
        return $res;
    }

    /**
     * @return bool
     * @throws DbException
     */
    private function insert() {
        if ($this->timestamp) {
            $timestamp = date($this->timestampFormat);

            $this->__set($this->createdAt, $timestamp);
            $this->__set($this->updatedAt, $timestamp);
        }
        return $this->newQuery()->insert($this->attributes);
    }

    /**
     * @return array|bool|flame\mysql\result
     * @throws DbException
     */
    protected function update() {
        if (empty($this->changes)) {
            return false;
        }
        if ($this->timestamp) {
            $timestamp = date($this->timestampFormat);
            $this->__set($this->updatedAt, $timestamp);
        }
        return $this->newQuery()->update(
            [$this->primaryKey => $this->attributes[$this->primaryKey]],
            $this->changes
        );
    }

    /**
     * @param array   $data
     * @param boolean $exists
     * @return $this
     */
    public function setAttributes($data = [], $exists = true) {
        if (empty($data)) {
            return $this;
        }
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        $this->exists  = $exists;
        $this->changes = [];
        return $this;
    }

    /**
     * @return array
     */
    public function toArray() {
        return $this->attributes;
    }

    /**
     * @param $id
     * @return Model|null
     */
    public static function find($id) {
        if (empty($id)) {
            return null;
        }
        $static = new static();
        $info   = $static->newQuery()->where([$static->primaryKey => $id])->first();
        if (empty($info)) {
            return null;
        }

        return $info;
    }

    /**
     * ON DUPLICATE KEY 实现
     * 需要有唯一索引实现更新
     * @param array $uniq
     * @param array $data
     * @param array $increment
     * @return bool|flame\mysql\result|mixed
     */
    public static function incrementOrCreate(array $uniq, array $data, array $increment) {
        $static = new static();
        if (empty($uniq) || empty($data)) {
            return false;
        }
        $data = array_merge($uniq, $data);
        $keys = $values = $sets = [];
        foreach ($data as $key => $value) {
            $value    = is_numeric($value) ? intval($value) : $static->getConnection()->escape($value);
            $keys[]   = "`{$key}`";
            $values[] = $value;
        }

        foreach ($increment as $key => $value) {
            $value  = intval($value);
            $sets[] = "`{$key}` = `{$key}` + {$value}";
        }

        $sql = "INSERT INTO {$static->table} (" . join(",", $keys) . ") VALUES (" . join(",", $values)
            . ") ON DUPLICATE KEY UPDATE " . join(',', $sets);
        $res = $static->getConnection()->query($sql);
        Log::debug("execute sql", [
            'sql'    => $sql,
            'result' => $res,
        ]);
        return $res;
    }

    /**
     * @return QueryBuilder|null
     */
    public function newQuery() {
        $this->queryBuilder = new QueryBuilder($this->getConnection(), $this->table, get_class($this));
        return $this->queryBuilder;
    }

    /**
     * @return QueryBuilder
     */
    public static function query() {
        $static = new static();
        return $static->newQuery();
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        $this->attributes[$name] = $value;
        $this->changes[$name]    = $value;
    }

    /**
     * @return array
     */
    public function jsonSerialize() {
        return empty($this->attributes) ? null : $this->attributes;
    }
}