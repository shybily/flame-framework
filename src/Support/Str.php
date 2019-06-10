<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/4/30
 * Time: 12:33
 */

namespace shybily\framework\Support;


class Str {

    /**
     *
     * @param string       $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles) {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string)$needle) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param string       $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles) {
        foreach ((array)$needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string)$needle) {
                return true;
            }
        }
        return false;
    }
}