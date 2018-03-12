<?php

namespace service\components;

use Oil\dispatcher\Dispatch;
use yii\base\Object;
use Exception;
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
     * @const string class key
     */
    const CLS = 'class';

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
        $params = is_string($params) ? [self::CLS => $params] : $params;

        if (!isset($this->oil[$name])) {
            $this->oil[$name] = [];
        } else if (isset($this->oil[$name]) && is_string($this->oil[$name])) {
            $this->oil[$name] = [self::CLS => $this->oil[$name]];
        }

        $this->oil[$name] = ArrayHelper::merge($this->oil[$name], $params);
    }

    /**
     * Create oil instance
     *
     * @param string $name
     *
     * @return object
     * @throws Exception
     */
    public function instance($name)
    {
        $prefix = self::className() . PHP_EOL;

        if (!isset($this->oil[$name])) {
            throw new Exception($prefix . "Un configured options '{$name}' in \$app->params[oil]");
        }

        $params = $this->oil[$name];

        if (is_string($params)) {
            $params = [self::CLS => $params];
        }

        if (!isset($params[self::CLS])) {
            throw new Exception($prefix . 'The oil config index ' . self::CLS . ' no set');
        }

        $class = $params[self::CLS];
        unset($params[self::CLS]);

        return Dispatch::instance($class, $params);
    }
}