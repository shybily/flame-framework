<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/2/27
 * Time: 4:44 PM
 */

namespace shybily\framework\Middleware;


interface MiddlewareInterface {

    /**
     * @param $request
     * @param $response
     * @return boolean
     */
    public function run($request, $response);

}