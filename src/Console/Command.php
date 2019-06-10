<?php
/**
 * Created by PhpStorm.
 * User: shybily <shybily@gmail.com>
 * Date: 2019/3/13
 * Time: 16:46
 */

namespace shybily\framework\Console;

use Symfony\Component\Console\Command\Command as Base;


abstract class Command extends Base {
    protected $name = 'defaultName';

    public function __construct() {
        parent::__construct($this->name);
    }
}