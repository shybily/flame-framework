<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/3/3
 * Time: 8:46 PM
 */

namespace shybily\framework\Database;

use JsonSerializable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

class Collection implements JsonSerializable, ArrayAccess, IteratorAggregate {

    protected $items = [];

    public function __construct() {
    }

    /**
     * @param Model $item
     */
    public function push(Model $item) {
        $this->items[] = $item;
    }

    public function empty() {
        return empty($this->items);
    }

    /**
     * @return Model|null
     */
    public function first() {
        if (empty($this->items)) {
            return null;
        }
        return $this->items[0];
    }

    /**
     * @return Model|null
     */
    public function pop() {
        return array_pop($this->items);
    }

    /**
     * @return array
     */
    public function toArray() {
        $list = [];
        if (empty($this->items)) {
            return $list;
        }
        foreach ($this->items as $item) {
            if (is_array($item)) {
                $list[] = $item;
            }
            if ($item instanceof Model) {
                $list[] = $item->toArray();
            }
        }
        return $list;
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed $key
     * @return bool
     */
    public function offsetExists($key) {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed $key
     * @return mixed
     */
    public function offsetGet($key) {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($key, $value) {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key) {
        unset($this->items[$key]);
    }

    public function getIterator() {
        return new ArrayIterator($this->items);
    }

    public function __get($key) {
        return isset($this->items[$key]) ? $this->items[$key] : null;
    }

    public function jsonSerialize() {
        return $this->toArray();
    }

}