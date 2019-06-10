<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/2/27
 * Time: 7:44 PM
 */

use shybily\framework\Support\Str;

if (!function_exists('app')) {
    /**
     * @param string $name
     * @return mixed|null
     */
    function app($name = 'app') {
        return shybily\framework\Application::get($name);
    }
}

if (!function_exists('env')) {
    /**
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        if (Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
            return substr($value, 1, -1);
        }
        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * @param $name
     * @return mixed
     */
    function config($name) {
        $app = shybily\framework\Application::get('app');
        return $app->config($name);
    }
}

if (!function_exists('getRedis')) {
    /**
     * @param string $name
     * @return flame\redis\client|null
     */
    function getRedis($name = 'default') {
        return shybily\framework\Application::get("redis_{$name}");
    }
}

if (!function_exists('getMysql')) {
    /**
     * @param string $name
     * @return flame\mysql\client|null
     */
    function getMysql($name = 'default') {
        return shybily\framework\Application::get("mysql_{$name}");
    }
}

//if (!function_exists("getPdo")) {
//    /**
//     * @param string $name
//     * @return \app\Library\Pool\Connection|mixed
//     * @throws \app\Exception\BaseException
//     */
//    function getPdo($name = 'default') {
//        return \app\Library\Pool\Pdo::getConnection($name);
//    }
//}

if (!function_exists('getMongodb')) {
    /**
     * @param string $name
     * @return flame\mongodb\client|null
     */
    function getMongodb($name = 'default') {
        return shybily\framework\Application::get("mongodb_{$name}");
    }
}

if (!function_exists('middleware')) {
    /**
     * @param $name
     * @return mixed
     */
    function middleware($name) {
        $app = shybily\framework\Application::get('app');
        return $app->getMiddleware($name);
    }
}