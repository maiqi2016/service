<?php

namespace service\components;

use yii\base\Object;

/**
 * Service components
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2016-2-27 16:53:30
 */
class Service extends Object
{

    /**
     * @var string of the fields
     */
    public $appIdKey = 'app_id';

    public $appSecretKey = 'app_secret';

    /**
     * @var array API function need params
     */
    protected $_params = [];

    /**
     * @var string API host
     */
    protected $_host = 'http://localhost';

    /**
     * @var integer API port
     */
    protected $_port = 80;

    /**
     * @var string API function
     */
    protected $_service = null;

    /**
     * @var int curl Timeout
     */
    protected $_timeout = 3000;

    /**
     * @var array Allow method
     */
    protected $_allowMethod = [
        'GET',
        'POST',
        'HEAD',
        'PUT',
        'PATCH',
        'DELETE'
    ];

    /**
     * @var string cURL method
     */
    protected $_method = 'POST';

    /**
     * @var callable options handler
     */
    protected $_optionsHandler = null;

    /**
     * @var array error log
     */
    private $_error = [];

    /**
     * @var array curl options
     */
    public $debug;

    /**
     * __constructor
     *
     * @access public
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct();
    }

    /**
     * Set fields of call api
     *
     * @access public
     *
     * @param string $id
     * @param string $secret
     *
     * @return object
     */
    public function fields($id, $secret)
    {
        if (empty($id) || empty($secret)) {
            $this->_setError('Fields of the $id and $secret can not be empty.');
        }

        $this->appIdKey = $id;
        $this->appSecretKey = $secret;

        return $this;
    }

    /**
     * User auth username & password for service
     *
     * @access public
     *
     * @param string $id
     * @param string $secret
     *
     * @return object
     */
    public function auth($id, $secret)
    {
        if (empty($id) || empty($secret)) {
            $this->_setError('Params of the $id and $secret is required.');
        }

        $this->_params[$this->appIdKey] = $id;
        $this->_params[$this->appSecretKey] = $secret;

        return $this;
    }

    /**
     * API service host
     *
     * @access public
     *
     * @param string $host
     *
     * @return object
     */
    public function host($host)
    {
        if (!filter_var($host, FILTER_VALIDATE_URL)) {
            $this->_setError('Param $host must be validate url.');
        }

        $this->_host = $host;

        return $this;
    }

    /**
     * API service port
     *
     * @access public
     *
     * @param integer $port
     *
     * @return object
     */
    public function port($port)
    {
        if (!is_numeric($port) || $port < 1 || $port > 65535) {
            $this->_setError('Param $port must be numeric and between 1 and 65535.');
        }
        $this->_port = (int) $port;

        return $this;
    }

    /**
     * API service function for service
     *
     * @access public
     *
     * @param string $service exp:common.Config.listConfig
     *
     * @return object
     */
    public function service($service)
    {
        if (!is_string($service)) {
            $this->_setError('Param $service must be string.');
        }

        $this->_service = '/' . $service;

        return $this;
    }

    /**
     * API service function need params
     *
     * @access public
     *
     * @param array $params
     *
     * @return object
     */
    public function params($params)
    {
        if (empty($params)) {
            $params = [];
        }
        if (!is_array($params) || isset($params[0])) {
            $this->_setError('Param $params must be associative array like [key => value].');
        }
        $this->_params = array_merge((array) $params, $this->_params);

        return $this;
    }

    /**
     * API service request timeout
     *
     * @access public
     *
     * @param integer $timeout
     *
     * @return object
     */
    public function timeout($timeout)
    {
        if ($timeout < 1 || !is_int($timeout)) {
            $this->_setError('Param $timeout must be nonzero natural number.');
        }
        $this->_timeout = $timeout;

        return $this;
    }

    /**
     * Method of cURL
     *
     * @access public
     *
     * @param string $method
     *
     * @return object
     */
    public function method($method)
    {
        if (!in_array(strtoupper($method), $this->_allowMethod)) {
            $this->_setError('The cURL method don\'t allow. must be ' . implode(',', $this->_allowMethod));
        }

        $this->_method = strtoupper($method);

        return $this;
    }

    /**
     * Set callback for handle options of curl
     *
     * @access public
     *
     * @param callable $fn
     *
     * @return object
     */
    public function optionsHandler($fn)
    {
        if (!is_callable($fn)) {
            $this->_setError('The options handler invalid is not callable.');
        }

        $this->_optionsHandler = $fn;

        return $this;
    }

    /**
     * Request service
     *
     * @access public
     *
     * @param boolean $debug
     * @param boolean $async
     *
     * @return array
     */
    public function request($debug = false, $async = false)
    {

        if ($error = $this->error()) {
            return [
                'state' => 0,
                'info' => $error,
                'data' => null
            ];
        }

        $this->_host = rtrim($this->_host, '/');
        if (80 !== $this->_port) {
            $this->_host = $this->_host . ':' . $this->_port;
        }
        $this->_host .= $this->_service;

        return $this->_curl($debug, $async);
    }

    /**
     * Send POST request by curl
     *
     * @access private
     *
     * @param boolean $debug
     * @param boolean $async
     * @param boolean $https
     *
     * @return array
     */
    private function _curl($debug = false, $async = false, $https = false)
    {
        $content = Helper::cURL($this->_host, $this->_method, null, null, function ($options) {

            if ($this->_optionsHandler) {
                $options = call_user_func($this->_optionsHandler, $options, $this->_params);
            }

            $this->debug = $options;

            return $options;
        }, $async, $https);

        $debug && exit($content);
        $result = json_decode($content, true);

        if (is_null($result)) {
            return [
                'state' => 0,
                'info' => $content,
                'data' => null
            ];
        }

        return $result;
    }

    /**
     * Log error message
     *
     * @access private
     *
     * @param string $message
     *
     * @return bool
     */
    private function _setError($message)
    {
        if (is_string($message) && !empty($message)) {
            array_push($this->_error, $message);

            return true;
        }

        return false;
    }

    /**
     * Get the first error
     *
     * @access public
     * @return string
     */
    public function error()
    {
        return current($this->_error);
    }

    /**
     * Get all of the errors
     *
     * @access public
     * @return mixed
     */
    public function errors()
    {
        return empty($this->_error) ? false : $this->_error;
    }
}