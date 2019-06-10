<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/3/3
 * Time: 7:38 PM
 */

namespace shybily\framework\Database\Query;

use Closure;
use Exception;
use shybily\framework\Database\Collection;
use shybily\framework\Database\Exception\DbException;
use shybily\framework\Database\Model;
use shybily\framework\Log;
use flame;


class QueryBuilder {

    protected $table;
    protected $connection = "default";
    protected $itemClass;
    protected $offset     = null;
    protected $limit      = null;
    protected $orderBy    = null;
    protected $groupBy    = null;
    protected $columns    = [];
    protected $where      = [];
    protected $between    = [];
    protected $count      = false;
    protected $lockShare  = false;
    protected $forUpdate  = false;
    protected $operator   = [
        '=',
        '<',
        '>',
        ">=",
        "<=",
        "!=",
    ];


    /**
     * QueryBuilder constructor.
     * @param        $connection
     * @param string $table
     * @param string $itemClass
     */
    public function __construct($connection, string $table, string $itemClass) {
        $this->connection = $connection;
        $this->table      = $table;
        $this->itemClass  = $itemClass;
    }

    /**
     * @return flame\mysql\client|string|null
     */
    public function getConnection() {
        if (is_string($this->connection)) {
            $this->connection = getMysql($this->connection);
        }
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getTable() {
        return $this->table;
    }

    public function __destruct() {

    }

    public function select(array $columns = ['*']) {
        if (in_array("*", $columns)) {
            $this->columns = ['*'];
        } else {
            $this->columns = [];
            foreach ($columns as $item) {
                if (in_array($item, $this->columns)) {
                    continue;
                }
                $this->columns[] = "`{$item}`";
            }
        }
        return $this;
    }

    /**
     * @param string $raw
     * @return $this
     */
    public function selectRaw(string $raw) {
        $this->columns[] = $raw;
        return $this;
    }

    public function where($column, $operator = null, $value = null) {
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $key   = trim($key);
                $value = is_numeric($value) ? intval($value) : $this->getConnection()->escape($value);

                $this->where[] = "`{$key}` = {$value}";
            }
            return $this;
        }
        if (!in_array($operator, $this->operator)) {
            return $this;
        }
        $value = is_numeric($value) ? intval($value) : $this->getConnection()->escape($value);

        $this->where[] = "`{$column}` {$operator} {$value}";

        return $this;
    }

    /**
     * @param       $column
     * @param array $values
     * @return $this
     */
    public function whereIn($column, array $values = []) {
        if (empty($values)) {
            return $this;
        }
        $column = trim($column);
        foreach ($values as &$value) {
            $value = is_numeric($value) ? intval($value) : $this->getConnection()->escape($value);
        }

        $this->where[] = "`{$column}` IN (" . join(",", $values) . ")";
        return $this;
    }

    /**
     * @param string $column
     * @param        $a
     * @param        $b
     * @return $this
     */
    public function between(string $column, $a, $b) {
        $this->between[$column] = [
            is_numeric($a) ? intval($a) : $this->getConnection()->escape($a),
            is_numeric($b) ? intval($b) : $this->getConnection()->escape($b),
        ];
        return $this;
    }

    /**
     * @param int $num
     * @param int $offset
     * @return $this
     */
    public function limit(int $num, int $offset = 0) {
        $this->limit  = $num;
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param string $orderBy
     * @return $this
     */
    public function orderBy(string $orderBy = '') {
        if (!empty($orderBy)) {
            $this->orderBy = $orderBy;
        }
        return $this;
    }

    /**
     * @param string $groupBy
     * @return $this
     */
    public function groupBy(string $groupBy = "") {
        if (!empty($groupBy)) {
            $this->groupBy = $groupBy;
        }
        return $this;
    }

    /**
     * @return Model|null
     */
    public function first() {
        $collection = $this->get();
        if (empty($collection) || $collection->empty()) {
            return null;
        }
        return $collection->first();
    }

    /**
     * @return $this
     */
    public function forUpdate() {
        $this->lockShare = false;
        $this->forUpdate = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function lockInShareMode() {
        $this->lockShare = true;
        $this->forUpdate = false;
        return $this;
    }

    /**
     * @param Closure $fun
     * @return mixed|null
     * @throws Exception
     */
    public function transaction(Closure $fun) {
        try {
            $tx     = $this->getConnection()->begin_tx();
            $result = $fun(new self($tx, $this->table, $this->itemClass));
            $tx->commit();
        } catch (Exception $exception) {
            if (!empty($tx)) {
                $tx->rollback();
            }
            Log::error("transaction failed rollback", [
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]);
            throw $exception;
        } finally {
            Log::debug("transaction finish", [
                'result' => isset($result) ?? null,
            ]);
            unset($tx);
        }
        return isset($result) ? $result : null;
    }

    /**
     * @return Collection|null
     */
    public function get() {
        $sql = $this->toSql();
        Log::debug("prepare sql", ['sql' => $sql]);
        try {
            $res = $this->getConnection()->query($sql);
        } catch (\Exception $exception) {
            Log::error("query sql failed", [
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
                'sql'     => $sql,
            ]);
            return null;
        }

        $collection = new Collection();
        if (empty($res)) {
            return $collection;
        }

        $row = $res->fetch_all();
        foreach ($row as $item) {
            $object = new $this->itemClass();
            $object->setAttributes($item);
            $collection->push($object);
        }

        return $collection;
    }

    /**
     * @return int
     */
    public function count() {
        $this->count = true;
        $total       = $this->get()->first();
        return isset($total->total) ? 0 : intval($total->total);
    }

    /**
     * @param array $data
     * @return bool
     * @throws DbException
     */
    public function insert(array $data) {
        if (empty($data)) {
            return false;
        }
        try {
            $res = $this->getConnection()->insert($this->table, $data);
        } catch (\Exception $exception) {
            Log::error("inset failed", [
                'table'   => $this->table,
                'data'    => $data,
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]);
            throw new DbException($exception->getMessage(), 80010);
        }
        return isset($res['affected_rows']) && $res['affected_rows'] >= 1 ? true : false;
    }

    /**
     * @param array $where
     * @param array $data
     * @return array|flame\mysql\result
     * @throws DbException
     */
    public function update(array $where, array $data) {
        try {
            $res = $this->getConnection()->update($this->table, $where, $data);
        } catch (\Exception $exception) {
            Log::error("update failed", [
                'table'   => $this->table,
                'data'    => $data,
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]);
            throw new DbException($exception->getMessage(), 80010);
        }
        return $res;
    }

    /**
     * @return string
     */
    public function toSql() {

        if ($this->count) {
            $this->columns = ['COUNT(*) AS `total`'];
        } elseif (empty($this->columns)) {
            $this->columns = ['*'];
        }

        $sql = sprintf("SELECT %s FROM `{$this->table}`", join(',', $this->columns));
        if (!empty($this->where)) {
            $sql .= " WHERE " . join(" AND ", $this->where);
        }
        if (!empty($this->between)) {
            $between = [];
            foreach ($this->between as $key => $item) {
                if (!is_array($item) || empty($item)) {
                    continue;
                }
                $between[] = "`$key` BETWEEN {$item[0]} AND {$item[1]}";
            }
            if (!empty($between)) {
                $sql .= " AND " . join(" AND ", $between);
            }
        }
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY {$this->groupBy}";
        }
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY {$this->orderBy}";
        }
        if ($this->limit > 0) {
            $sql .= " LIMIT {$this->limit}";
        }
        if ($this->offset > 0) {
            $sql .= " OFFSET {$this->offset}";
        }
        if ($this->lockShare) {
            $sql .= " LOCK IN SHARE MODE";
        }
        if ($this->forUpdate) {
            $sql .= " FOR UPDATE";
        }

        return $sql;
    }


}