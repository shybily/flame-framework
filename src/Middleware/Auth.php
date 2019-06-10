<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/2/27
 * Time: 4:38 PM
 */

namespace shybily\framework\Middleware;


abstract class Auth implements MiddlewareInterface {

    /**
     * @param $request
     * @param $response
     * @return bool
     */
    abstract public function run($request, $response);
}