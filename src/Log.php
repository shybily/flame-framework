<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/3/27
 * Time: 21:07
 */

namespace shybily\framework;

class Log {
    private static function _writeLog($level, $message, $extra = []) {
        if (!function_exists("flame\\log\\{$level}")) {
            return;
        }
        call_user_func("flame\\log\\{$level}", join(" ", [$message, json_encode($extra)]));
    }

    public static function info($message, $extra = []) {
        self::_writeLog(__FUNCTION__, $message, $extra);
    }

    public static function error($message, $extra = []) {
        self::_writeLog(__FUNCTION__, $message, $extra);
    }

    public static function trace($message, $extra = []) {
        self::_writeLog(__FUNCTION__, $message, $extra);
    }

    /**
     * debug 打开的时候才会输出
     * APP_DEBUG = true
     * @param       $message
     * @param array $extra
     */
    public static function debug($message, $extra = []) {
        if (env('APP_DEBUG')) {
            self::_writeLog(__FUNCTION__, $message, $extra);
        }
    }

    public static function warn($message, $extra = []) {
        self::_writeLog(__FUNCTION__, $message, $extra);
    }

    public static function warning($message, $extra = []) {
        self::_writeLog(__FUNCTION__, $message, $extra);
    }

    public static function fail($message, $extra = []) {
        self::_writeLog(__FUNCTION__, $message, $extra);
    }

    public static function fatal($message, $extra = []) {
        self::_writeLog(__FUNCTION__, $message, $extra);
    }
}