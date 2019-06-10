<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/2/27
 * Time: 2:42 PM
 */

namespace shybily\framework;

use flame;
use shybily\framework\Exception\ConfigException;
use shybily\framework\Exception\ErrorHandler;
use shybily\framework\Middleware\MiddlewareInterface;
use Closure;
use Exception;

require_once "Functions.php";


class Application {
    public $router;
    public $config = [];

    private static $instances    = [];
    private static $middleware   = [];
    private static $errorHandler = null;

    public function __construct() {
        $this->router = new Router();
        $this->set("app", $this);
    }

    /**
     * @param $configPath
     * @throws ConfigException
     */
    public function loadConfig($configPath) {
        if (!file_exists($configPath)) {
            Log::error("config not exists", [
                'path' => $configPath,
            ]);
            throw new ConfigException("config not exists");
        }
        $this->config = array_merge($this->config, require $configPath);
    }

    /**
     * @param $key
     * @return array|mixed|null
     */
    public function config($key) {
        $keys   = explode(".", $key);
        $config = $this->config;
        foreach ($keys as $key) {
            if (empty($config) || !is_array($config)) {
                break;
            }
            $config = isset($config[$key]) ? $config[$key] : null;
        }
        return $config;
    }

    /**
     * @param $name
     * @param $class
     */
    public function registerMiddleware(string $name, string $class) {
        $object = new $class();
        if (isset(self::$middleware[$name]) || !($object instanceof MiddlewareInterface)) {
            Log::error('middleware has been registered or not implements with MiddlewareInterface');
            return;
        }
        self::$middleware[$name] = $object;
    }

    /**
     * @param $name
     * @return MiddlewareInterface|null
     */
    public function getMiddleware(string $name) {
        return isset(self::$middleware[$name]) ? self::$middleware[$name] : null;
    }

    /**
     * @param string $class
     */
    public function registerErrorHandler(string $class) {
        $handler = new $class();
        if (!empty(self::$errorHandler) || !($handler instanceof ErrorHandler)) {
            Log::error('error handler has been registered or not implements with ErrorHandler');
            return;
        }
        self::$errorHandler = $handler;
    }

    /**
     * @return ErrorHandler|null
     */
    public function getErrorHandler() {
        return self::$errorHandler;
    }

    /**
     * @return Closure|null
     */
    public function getAfterHook() {
        return isset(self::$middleware['after']) ? self::$middleware['after'] : null;
    }

    /**
     * @param $name
     * @param $instance
     */
    public static function set($name, $instance) {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = $instance;
        }
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public static function get($name) {
        return !empty(self::$instances[$name]) ? self::$instances[$name] : null;
    }

    /**
     * @param Console|null $console
     */
    public function run(?Console $console = null) {
        $this->initDatabase();
        if (!$console) {
            $this->initServer();
            $this->router->run();
        } else {
            $this->runConsole($console);
        }
    }

    /**
     * @param Console $console
     */
    private function runConsole(Console $console) {

        try {
            $exitCode = $console->run();
        } catch (Exception $exception) {
            Log::error("run command failed", [
                'message' => $exception->getMessage(),
            ]);
        } finally {
            Log::debug("command quit");
            if (!isset($exitCode) || $exitCode != 0) {
                flame\quit();
            }
        }
    }

    /**
     * 初始化flame\http\server
     */
    private function initServer() {
        $server = new flame\http\server($this->config('listen'));
        Log::info("server start", ['listen' => $this->config('listen')]);
        $after = $this->getAfterHook();
        if (!empty($after) || $after instanceof Closure) {
            Log::debug("register after hook");
            $server->after($after);
        }
        $this->set('server', $server);
    }

    private function initDatabase() {
        $config = $this->config('database');
        if (empty($config)) {
            return;
        }
        foreach ($config as $type => $conf) {
            $func = "flame\\{$type}\\connect";
            if (!function_exists($func)) {
                Log::error("init database failed, [{$type}] not found", [
                    'function' => $func,
                ]);
                continue;
            }
            foreach ($conf as $key => $item) {
                if (empty($item)) {
                    continue;
                }
                Log::info("init database [{$type}][{$key}] with config [{$item}]");
                $this->set($type . "_" . $key, $func($item));
            }
        }

    }
}