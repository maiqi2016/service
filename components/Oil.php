<?php

namespace service\components;

use yii\base\Object;
use Exception;
use ReflectionClass;
use yii\helpers\ArrayHelper;

/**
 * jtleon/oil dispatcher
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-12-11 12:58:47
 */
class Oil extends Object
{
    /**
     * @var array
     */
    public $oil;

    /**
     * Oil constructor.
     *
     * @param array $oil
     */
    public function __construct($oil = [])
    {
        $this->oil = $oil;

        parent::__construct();
    }

    /**
     * __getter
     *
     * @param string $name
     *
     * @return object
     * @throws Exception
     */
    public function __get($name)
    {
        static $instances = [];
        if (!isset($instances[$name])) {
            $instances[$name] = $this->instance($name);
        }

        return $instances[$name];
    }

    /**
     * Register oil
     *
     * @param string         $name
     * @param string | array $params
     *
     * @return void
     */
    public function register($name, $params)
    {
        $params = is_string($params) ? ['class' => $params] : $params;

        if (!isset($this->oil[$name])) {
            $this->oil[$name] = [];
        } else if (isset($this->oil[$name]) && is_string($this->oil[$name])) {
            $this->oil[$name] = ['class' => $this->oil[$name]];
        }

        $this->oil[$name] = ArrayHelper::merge($this->oil[$name], $params);
    }

    /**
     * Create oil instance
     *
     * @param string $name
     * @param string $classIndex
     *
     * @return object
     * @throws Exception
     */
    public function instance($name, $classIndex = 'class')
    {
        if (!isset($this->oil[$name])) {
            throw new Exception("Un configured options '{$name}' in \$app->params[oil]");
        }

        $params = $this->oil[$name];

        if (is_string($params)) {
            $params = [$classIndex => $params];
        }

        if (!isset($params[$classIndex])) {
            throw new Exception("The oil config index '{$classIndex}' no set");
        }

        $class = $params[$classIndex];
        unset($params[$classIndex]);

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($constructor === null || empty($parameters = $constructor->getParameters())) {
            return $reflection->newInstance();
        }

        $argument = [];
        foreach ($parameters as $item) {
            if (version_compare(PHP_VERSION, '5.6.0', '>=') && $item->isVariadic()) {
                break;
            }

            if (isset($params[$item->name])) {
                $argument[$item->name] = $params[$item->name];
            } else if ($item->isDefaultValueAvailable()) {
                $argument[$item->name] = $item->getDefaultValue();
            } else {
                throw new Exception("Un configured constructor'param '{$item->name}'");
            }
        }

        return $reflection->newInstanceArgs($argument);
    }
}