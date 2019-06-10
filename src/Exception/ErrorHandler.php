<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/3/28
 * Time: 16:13
 */

namespace shybily\framework\Exception;

use Exception;

abstract class ErrorHandler {
    protected $dontReport = [];

    public function report(Exception $exception) { }

    public function render($request, $response, Exception $exception) { }
}