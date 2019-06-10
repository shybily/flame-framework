<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/2/28
 * Time: 11:21 AM
 */

namespace shybily\framework;

use Exception;
use FastRoute\Dispatcher;
use flame;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use shybily\framework\Exception\ErrorHandler;
use shybily\framework\Router\Exception\NotFoundException;
use shybily\framework\Router\Exception\MethodNotAllowException;
use function FastRoute\simpleDispatcher;

/**
 * Class Router
 * @package shybily\framework
 * @property RouteDispatcher $_dispatcher
 * @property RouteCollector  $_routerCollection
 */
class Router {

    private static $_instances = [];

    private $_router;
    private $_dispatcher;
    private $_routerCollection;
    private $_methodConfig = [];
    private $_allowMethod  = [
        'get',
        'post',
        'option',
        'head',
        'put',
        'delete',
    ];

    public function __construct() {
        $this->_routerCollection = new RouteCollector(new Std(), new GroupCountBased());
    }

    protected function defaultMethodConfig() {
        return [
            'namespace'  => 'app\Controller',
            'prefix'     => null,
            'middleware' => [],
        ];
    }

    /**
     * @param array    $config
     * @param callable $call
     */
    public function group(array $config, callable $call) {

        $this->_methodConfig['namespace']  = isset($config['namespace']) ? $config['namespace'] : $this->_methodConfig['namespace'];
        $this->_methodConfig['prefix']     = isset($config['prefix']) ? $config['prefix'] : $this->_methodConfig['prefix'];
        $this->_methodConfig['middleware'] = isset($config['middleware']) ? explode('|', $config['middleware']) : $this->_methodConfig['middleware'];

        $call($this);

        $this->_methodConfig = $this->defaultMethodConfig();
    }

    /**
     *
     */
    public function run() {
        if (empty($this->_router)) {
            return;
        }
        $this->_dispatcher = simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->_router as $method => $item) {
                foreach ($item as $path => $conf) {
                    $r->addRoute(strtoupper($method), $path, $conf);
                }
            }
        });
        app('server')->after(function ($request, $response, $match) {
            if ($match) {
                return;
            }
            $this->restful($request, $response, $match);
        });
    }

    /**
     * @param flame\http\server_request  $request
     * @param flame\http\server_response $response
     * @param                            $match
     */
    protected function restful(flame\http\server_request $request, flame\http\server_response $response, $match) {
        if ($match) {
            return;
        }
        $httpMethod = strtoupper($request->method);
        $uri        = rawurldecode($request->path);
        try {
            $routeInfo = $this->_dispatcher->dispatch($httpMethod, $uri);
            switch ($routeInfo[0]) {
                case Dispatcher::NOT_FOUND:
                    throw new NotFoundException();
                case Dispatcher::METHOD_NOT_ALLOWED:
                    throw new MethodNotAllowException();
                case Dispatcher::FOUND:
                    $handler = $routeInfo[1];
                    $vars    = $routeInfo[2];
                    if (!empty($handler['middleware'])) {
                        foreach ($handler['middleware'] as $middleware) {
                            middleware($middleware)->run($request, $response);
                        }
                    }
                    $method = null;
                    if (is_array($handler['handler'])) {
                        $method = [
                            static::$_instances[$handler['handler'][0]],
                            $handler['handler'][1],
                        ];
                    } else {
                        $method = $handler['handler'];
                    }
                    $result = call_user_func_array($method, array_merge(array_values($vars), [$request]));;
                    $response->header["content-type"] = "application/json; charset=utf-8";

                    $response->status = 200;
                    $response->body   = json_encode([
                        'error_code'    => 0,
                        'error_message' => '',
                        'data'          => $result,
                    ]);
                    break;
            }
        } catch (Exception $exception) {
            $handler = app()->getErrorHandler();
            if ($handler instanceof ErrorHandler) {
                $handler->report($exception)->render($request, $response, $exception);
            }
        }

    }

    /**
     * @param $httpMethod
     * @param $path
     * @param $method
     */
    private function _pushRouter($httpMethod, $path, $method) {
        $config['middleware'] = $this->_methodConfig['middleware'];
        if (!empty($this->_methodConfig['prefix'])) {
            $path = $this->_methodConfig['prefix'] . $path;
        }
        if (!empty($config['middleware'])) {
            foreach ($this->_methodConfig['middleware'] as $key => $item) {
                if (empty(middleware($item))) {
                    Log::error("middleware not found", ['middleware' => $item]);
                    continue;
                }
                if (in_array($item, $config['middleware'])) {
                    continue;
                }
                $config['middleware'][] = $item;
            }
        }
        if (is_string($method)) {
            $method = explode('@', $method);
            $class  = empty($this->_methodConfig['namespace']) ? $method[0] : $this->_methodConfig['namespace'] . "\\" . $method[0];

            static::$_instances[$class] = new $class();
            $config['handler']          = [
                $class,
                $method[1],
            ];
        } elseif (is_callable($method)) {
            $config['handler'] = $method;
        }

        $this->_router[$httpMethod][$path] = $config;
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this
     */
    public function __call($name, $arguments) {
        if (!in_array(strtolower($name), $this->_allowMethod)) {
            throw new \RuntimeException("不允许的HTTP方法", 90011);
        }

        $this->_pushRouter(...array_merge([$name], $arguments));
        return $this;
    }
}