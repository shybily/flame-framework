<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/3/13
 * Time: 16:58
 */

namespace shybily\framework;

use Symfony\Component\Console\Application as Container;
use RuntimeException;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Console {

    /**
     * @var Application
     */
    protected $app = null;
    /**
     * @var Container
     */
    protected $container = [];
    /**
     * @var array
     */
    protected $commands = [];

    /**
     * Console constructor.
     * @param Application $app
     */
    public function __construct(Application $app) {
        $this->app = $app;
        if (empty($this->commands)) {
            throw new RuntimeException("no command found");
        }
        $this->container = new Container();
        $this->container->setAutoExit(false);
        foreach ($this->commands as $class) {
            $instance = new $class();
            if ($instance instanceof Command) {
                $this->container->add($instance);
            }
        }
    }

    /**
     * @param string $command
     * @return bool
     */
    public function has(string $command) {
        return $this->container->has($command);
    }

    /**
     * @param InputInterface|null  $input
     * @param OutputInterface|null $output
     * @return int
     * @throws Exception
     */
    public function run(InputInterface $input = null, OutputInterface $output = null) {
        return $this->container->run($input, $output);
    }

}