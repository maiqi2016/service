<?php

namespace service\components;

use yii\base\Object;

/**
 * Helper components
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2016-3-9 12:46:22
 */
class Helper extends Object
{

    // --- Mixed ---

    /**
     * Control execution once at current request
     *
     * @param callable $logicHandler
     *
     * @return void
     */
    public static function executeOnce($logicHandler)
    {
        static $container = [];

        $key = self::functionCallTrance(1, [
            'class',
            'function'
        ]);
        $key = md5(json_encode($key));

        if (!isset($container[$key])) {
            call_user_func($logicHandler);
            $container[$key] = true;
        }
    }

    /**
     * 获取单例
     *
     * @param mixed    $params
     * @param callable $logicHandler
     *
     * @return mixed
     */
    public static function singleton($params, $logicHandler)
    {
        static $container = [];

        $params = (array) $params;
        $key = md5(json_encode($params));
        if (!isset($container[$key])) {
            $container[$key] = call_user_func_array($logicHandler, $params);
        }

        return $container[$key];
    }

    /**
     * 获取分页相关的数据
     *
     * @param $page
     * @param $pageSize
     *
     * @return array
     */
    public static function page($page, $pageSize)
    {
        $pageSize = intval($pageSize) ?: 0;
        $page = intval($page);
        if (!is_int($page)) {
            $page = 1;
        }

        $page = $page < 1 ? 1 : $page;
        $offset = $pageSize * ($page - 1);
        $mysql = sprintf('LIMIT %d OFFSET %d', $pageSize, $offset); // LIMIT $offset, $pageSize

        return [
            $offset,
            $pageSize,
            $mysql
        ];
    }

    /**
     * Create a uuid
     *
     * @access public
     * @return string
     */
    public static function gUid()
    {
        $charId = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);
        $uuid = null;
        $uuid .= substr($charId, 0, 8) . $hyphen;
        $uuid .= substr($charId, 8, 4) . $hyphen;
        $uuid .= substr($charId, 12, 4) . $hyphen;
        $uuid .= substr($charId, 16, 4) . $hyphen;
        $uuid .= substr($charId, 20, 12);

        return $uuid;
    }

    /**
     * Get and set a buffer at same session
     *
     * @param mixed   $name
     * @param string  $value   Default value for setter
     * @param mixed   $default Default value for getter
     * @param boolean $once    Flash
     *
     * @return mixed
     */
    public static function buffer($name = null, $value = null, $default = null, $once = false)
    {
        static $_config = [];
        static $_preConfig = [];

        // Get all
        if (empty($name)) {
            return $_config;
        }

        if (is_string($name)) {

            // Get one
            if (is_null($value)) {
                // Flash (only once)
                if (isset($_preConfig[$name])) {
                    $result = $_preConfig[$name];
                    unset($_preConfig[$name]);

                    return $result;
                }

                return isset($_config[$name]) ? $_config[$name] : $default;
            }

            // Setting one
            if ($once && isset($_config[$name])) {
                $_preConfig[$name] = $value;
            } else {
                $_config[$name] = $value;
            }

            return null;
        }

        // Batch
        if (is_array($name)) {
            $_config = array_merge($_config, $name);

            return null;
        }

        return null;
    }

    /**
     * Count the memory info or cost info
     *
     * @access public
     *
     * @param string           $start
     * @param string           $end
     * @param integer | string $dec m:memory info
     *
     * @return string
     */
    public static function cost($start, $end = null, $dec = 4)
    {
        static $_info = [];
        static $_mem = [];

        if (is_float($end)) {
            $_info[$start] = $end;

            return null;
        }

        if (empty($end)) {
            $_info[$start] = microtime(true);
            if (function_exists('memory_get_usage')) {
                $_mem[$start] = memory_get_usage();
            }

            return null;
        }

        if (!isset($_info[$end])) {
            $_info[$end] = microtime(true);
        }

        if (function_exists('memory_get_usage') && $dec == 'm') {
            if (!isset($_mem[$end])) {
                $_mem[$end] = memory_get_usage();
            }

            return 'memy: ' . number_format(($_mem[$end] - $_mem[$start]) / 1024, 3) . ' kb';
        } else {
            return 'time: ' . number_format(($_info[$end] - $_info[$start]), $dec) * 1000 . ' ms';
        }
    }

    /**
     * Get client IP address
     *
     * @access public
     *
     * @param integer $type 0:IP 1:IPv4
     * @param boolean $adv  Advance mode
     *
     * @return mixed
     */
    public static function getClientIp($type = 0, $adv = false)
    {
        static $ip = null;
        $type = $type ? 1 : 0;

        if ($ip !== null) {
            return $ip[$type];
        }

        $get = function ($ip) use ($type) {

            if (empty($ip)) {
                return null;
            }

            // Check ip address
            $long = sprintf('%u', ip2long($ip));
            $ip = $long ? [
                $ip,
                $long
            ] : [
                '0.0.0.0',
                0
            ];

            return $ip[$type];
        };

        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim($arr[0]);

                return $get($ip);
            }

            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } else if (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            return $get($ip);
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];

            return $get($ip);
        }

        return null;
    }

    /**
     * Dump for object、array、string...
     *
     * @access public
     *
     * @param mixed   $var Variable
     * @param boolean $exit
     * @param boolean $strict
     * @param boolean $echo
     * @param string  $tag Border tag
     *
     * @return mixed
     */
    public static function dump($var, $exit = false, $strict = false, $echo = true, $tag = 'pre')
    {
        $startTag = $tag ? '<' . $tag . '>' : null;
        $endTag = $tag ? '</' . $tag . '>' : null;

        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = $startTag . htmlspecialchars($output, ENT_QUOTES) . $endTag;
            } else {
                $output = $startTag . print_r($var, true) . $endTag;
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = $startTag . htmlspecialchars($output, ENT_QUOTES) . $endTag;
            }
        }
        if ($echo) {
            echo($output);
            $exit ? exit() : null;

            return null;
        } else {
            return $output;
        }
    }

    /**
     * Get current url
     *
     * @access public
     * @return string
     */
    public static function currentUrl()
    {
        $scheme = 'http';
        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            $scheme = 'https';
        }

        $url = $scheme . '://' . $_SERVER['HTTP_HOST'];
        if ($_SERVER['SERVER_PORT'] != '80') {
            $url .= ':' . $_SERVER['SERVER_PORT'];
        }
        $url .= $_SERVER['REQUEST_URI'];

        return $url;
    }

    /**
     * Parse url to items
     *
     * @access public
     *
     * @param string $url
     *
     * @return string
     */
    public static function getUrlItems($url)
    {
        $items = parse_url($url);
        $port = isset($items['port']) ? ':' . $items['port'] : null;
        $items['base_url'] = $items['scheme'] . '://' . $items['host'] . $port . '/';

        $items['params'] = [];
        if (isset($items['query'])) {
            parse_str($items['query'], $items['params']);
        }

        return $items;
    }

    /**
     * Add params for url
     *
     * @access public
     *
     * @param $url
     * @param $params
     *
     * @return string
     */
    public static function addParamsForUrl($url, $params)
    {
        $items = self::getUrlItems($url);
        $params = array_merge($items['params'], $params);

        return trim($items['base_url'] . '?' . http_build_query($params), '?');
    }

    /**
     * Strip the param of the url
     *
     * @access public
     *
     * @param mixed  $unset
     * @param string $url
     *
     * @return string
     */
    public static function unsetParamsForUrl($unset, $url = null)
    {
        $url = $url ?: self::currentUrl();
        $items = self::getUrlItems($url);

        foreach ((array) $unset as $val) {
            unset($items['params'][$val]);
        }

        $url = trim($items['base_url'] . '?' . http_build_query($items['params']), '?');

        return $url;
    }

    /**
     * Build url query
     *
     * @param array  $params
     * @param string $url
     *
     * @return null|string
     */
    public static function httpBuildQuery($params, $url = null)
    {
        if (empty($params)) {
            return $url;
        }

        $query = http_build_query($params);
        $url .= (strpos($url, '?') !== false) ? '&' . $query : '?' . $query;

        return rtrim($url, '&?');
    }

    /**
     * Build url query in order
     *
     * @param array  $params
     * @param string $url
     *
     * @return null|string
     */
    public static function httpBuildQueryOrderly($params, $url = null)
    {
        if (empty($params)) {
            return $url;
        }

        $query = null;
        foreach ($params as $key => $value) {
            if (is_numeric($key)) {
                $query = rtrim($query, '&') . $value;
            } else {
                $query .= ($key . '=' . $value . '&');
            }
        }

        $query = rtrim($query, '&');
        $url .= (strpos($url, '?') !== false) ? '&' . $query : '?' . $query;

        return rtrim($url, '&?');
    }

    /**
     * Array to xml
     *
     * @access public
     *
     * @param array   $params
     * @param boolean $weChatModel
     *
     * @return string
     */
    public static function arrayToXml($params, $weChatModel = false)
    {
        $params = (array) $params;
        $xml = '<xml>';
        foreach ($params as $key => $val) {
            if (is_numeric($val)) {
                $xml .= '<' . $key . '>' . $val . '</' . $key . '>';
            } else {
                $begin = $weChatModel ? '<![CDATA[' : null;
                $end = $weChatModel ? ']]>' : null;
                $xml .= '<' . $key . '>' . $begin . $val . $end . '</' . $key . '>';
            }
        }
        $xml .= '</xml>';

        return $xml;
    }

    /**
     * Xml to array
     *
     * @access public
     *
     * @param string $xml
     *
     * @return array
     */
    public static function xmlToArray($xml)
    {
        if (empty($xml)) {
            return [];
        }

        libxml_disable_entity_loader(true);
        $object = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $params = json_decode(json_encode($object), true);

        return $params;
    }

    /**
     * cURL
     *
     * @access public
     *
     * @param string   $url
     * @param string   $type
     * @param array    $params
     * @param array    $queryBuilder
     * @param callable $optionHandle
     * @param boolean  $async
     * @param boolean  $https
     *
     * @return array
     */
    public static function cURL($url, $type = 'GET', $params = null, $queryBuilder = null, $optionHandle = null, $async = false, $https = false)
    {
        $type = strtoupper($type);
        $options = [];

        // https
        if ($https) {
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = false;
        }

        // enabled sync
        if ($async) {
            $options[CURLOPT_NOSIGNAL] = true;
            $options[CURLOPT_TIMEOUT_MS] = 1000;
        }

        // enabled show header
        $options[CURLOPT_HEADER] = false;

        // enabled auto show return info
        $options[CURLOPT_RETURNTRANSFER] = true;

        // connect
        $options[CURLOPT_FRESH_CONNECT] = true;
        $options[CURLOPT_FORBID_REUSE] = true;

        // method
        $options[CURLOPT_CUSTOMREQUEST] = strtoupper($type);

        // use method POST
        $params = (array) $params;
        $queryBuilder = $queryBuilder ?: (self::className() . '::httpBuildQuery');

        if (strtoupper($type === 'POST')) {
            $options[CURLOPT_POST] = true;
            if (!empty($params)) {
                $options[CURLOPT_POSTFIELDS] = call_user_func($queryBuilder, $params, $url);
            }
        } else if ($type === 'GET') {
            $url = call_user_func($queryBuilder, $params, $url);
        }

        // address
        $options[CURLOPT_URL] = $url;

        $curl = curl_init();

        // callback
        if ($optionHandle) {
            $options = call_user_func_array($optionHandle, [$options]);
        }

        curl_setopt_array($curl, $options);
        $content = curl_exec($curl);

        return $content === false ? curl_error($curl) : $content;
    }

    /**
     * Send post by form
     *
     * @access public
     *
     * @param array $params
     *
     * @return string
     */
    public static function postForm($url, $params)
    {
        $html = "<form id='form' name='form' action='{$url}' method='POST'>";
        foreach ($params as $key => $value) {
            $value = str_replace("'", "&apos;", $value);
            $html .= "<input type='hidden' name='{$key}' value='{$value}'/>";
        }

        //submit按钮控件请不要含有name属性
        $html .= "<input type='submit' value='ok' style='display:none;'></form>";
        $html .= "<script>document.forms['form'].submit();</script>";

        return $html;
    }

    /**
     * Auto perfect the html
     *
     * @access public
     *
     * @param string $html
     *
     * @return string
     */
    public static function perfectHtml($html)
    {
        // strip fraction of open or close tag from end
        // (e.g. if we take first x characters, we might cut off a tag at the end!)
        $html = preg_replace('/<[^>]*$/', null, $html);

        // put open tags into an array
        preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
        $openTags = $result[1];

        // put all closed tags into an array
        preg_match_all('#</([a-z]+)>#iU', $html, $result);
        $closeTags = $result[1];
        $lenOpened = count($openTags);

        // if all tags are closed, we can return
        if (count($closeTags) == $lenOpened) {
            return $html;
        }

        // close tags in reverse order that they were opened
        $openTags = array_reverse($openTags);

        // self closing tags
        $sc = [
            'br',
            'input',
            'img',
            'hr',
            'meta',
            'link'
        ];

        // ,'frame','i-frame','param','area','base','base-font','col'
        // should not skip tags that can have content inside!
        for ($i = 0; $i < $lenOpened; $i++) {
            $ot = strtolower($openTags[$i]);
            if (!in_array($openTags[$i], $closeTags) && !in_array($ot, $sc)) {
                $html .= '</' . $openTags[$i] . '>';
            } else {
                unset($closeTags[array_search($openTags[$i], $closeTags)]);
            }
        }

        return $html;
    }

    // --- Array ---

    /**
     * Change the keys name
     *
     * @access public
     *
     * @param array $items
     * @param array $keys
     *
     * @return array
     */
    public static function changeKeys($items, $keys)
    {
        $keys = (array) $keys;
        foreach ($keys as $oldKey => $newKey) {
            if (!isset($items[$oldKey])) {
                continue;
            }
            $items[$newKey] = $items[$oldKey];
            unset($items[$oldKey]);
        }

        return $items;
    }

    /**
     * Get the difference of two array
     *
     * @access public
     *
     * @param array    $old
     * @param array    $now
     * @param callable $callAction
     *
     * @return mixed
     */
    public static function getDiffWithAction($old, $now, $callAction = null)
    {
        if ($old == $now) {
            return false;
        }

        /**
         * $new = [1, 2, 5, 6]
         * $old = [1, 2, 3, 4]
         * $intersect = [1, 2]
         * $add = [5, 6]
         * $del = [3, 4]
         */
        $intersect = array_intersect($now, $old);

        $add = array_diff($now, $intersect);
        $del = array_diff($old, $intersect);

        return $callAction ? call_user_func($callAction, $add, $del) : [
            $add,
            $del
        ];
    }

    /**
     * Sort for Two-dimensional
     *
     * @access public
     *
     * @param array  $arr
     * @param string $keys Sort by key
     * @param string $type Desc or asc
     *
     * @return array
     */
    public static function arraySort($arr, $keys, $type = 'ASC')
    {
        $keysValue = $newArray = [];
        foreach ($arr as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }

        switch (ucwords($type)) {
            case 'ASC' :
                asort($keysValue);
                break;

            default :
                arsort($keysValue);
                break;
        }

        reset($keysValue);
        foreach ($keysValue as $k => $v) {
            $newArray[$k] = $arr[$k];
        }

        return $newArray;
    }

    /**
     * More-dimension array to one
     *
     * @access public
     *
     * @param array  $items
     * @param string $key
     * @param string $split
     *
     * @return array
     */
    public static function moreDimensionArrayToOne($items, $key, $split = '.')
    {
        $result = [];
        foreach ($items as $_key => $item) {
            $_key = $key . $split . $_key;
            if (!is_array($item)) {
                $result[$_key] = $item;
            } else {
                $result = array_merge($result, self::moreDimensionArrayToOne($item, $_key, $split));
            }
        }

        return $result;
    }

    /**
     * One-dimension array to more
     *
     * @access public
     *
     * @param array  $items
     * @param string $split
     *
     * @return array
     */
    public static function oneDimensionArrayToMore($items, $split = '.')
    {
        $result = [];

        $build = function (&$target, $key, $value) use (&$build, $split) {

            if (empty($key)) {
                return null;
            }

            $_key = array_shift($key);
            if (!isset($target[$_key])) {
                $target[$_key] = empty($key) ? $value : [];
            }

            $build($target[$_key], $key, $value);
        };

        foreach ($items as $key => $item) {
            $key = explode($split, $key);
            $build($result, $key, $item);
        }

        return $result;
    }

    /**
     * Assert the array is empty
     *
     * @access public
     *
     * @param array $value
     *
     * @return boolean
     */
    public static function arrayEmpty($value)
    {
        if (!is_array($value)) {
            return false;
        }

        if (empty($value)) {
            return true;
        }

        $empty = true;
        array_walk($value, function ($val) use (&$empty) {
            if (!self::arrayEmpty($val)) {
                $empty = false;
            }
        });

        return $empty;
    }

    /**
     * Object to array
     *
     * @access public
     *
     * @param object $obj
     *
     * @return array
     */
    public static function objToArr($obj)
    {
        return json_decode(json_encode($obj), true);
    }

    /**
     * Array to object
     *
     * @access public
     *
     * @param $arr
     *
     * @return object
     */
    public static function arrToObj($arr)
    {
        return json_decode(json_encode($arr));
    }

    /**
     * Unique for More-dimensional
     *
     * @access public
     *
     * @param array $data
     *
     * @return array
     */
    public static function moreDimensionArrayUnique($data)
    {
        $data = array_map('serialize', $data);
        $data = array_unique($data);
        $data = array_map('deSerialize', $data);

        return $data;
    }

    /**
     * Get string for `in` from array
     *
     * @access public
     *
     * @param array  $array
     * @param string $_key
     * @param string $split
     * @param string $handleCallFn
     *
     * @return string
     */
    public static function handleArray($array, $_key = '0', $split = ',', $handleCallFn = null)
    {
        $string = null;
        if (!empty($array)) {
            foreach ($array as $key => $val) {
                $value = $handleCallFn ? call_user_func($handleCallFn, $val[$_key]) : $val[$_key];
                $string .= $value . $split;
            }
            $string = rtrim($string, $split);
        }

        return $string;
    }

    /**
     * Get the One/Two-dimensional from Two-dimensional
     *
     * @access public
     *
     * @param array  $array
     * @param string $keyTag
     * @param mixed  $valTag
     *
     * @return array
     */
    public static function arrayColumn($array, $keyTag = null, $valTag = null)
    {
        $valTag = (array) $valTag;
        $valTagLen = count($valTag);

        $_array = [];
        foreach ($array as $key => $val) {
            $key = $keyTag ? (isset($val[$keyTag]) ? $val[$keyTag] : $key) : $key;

            if ($valTagLen == 1) {
                $val = isset($val[current($valTag)]) ? $val[current($valTag)] : null;
            } else if ($valTagLen > 1) {
                $val = self::getValueByKey($val, $valTag);
            }
            $_array[$key] = $val;
        }

        return $_array;
    }

    /**
     * Create select'html
     *
     * @param array   $array
     * @param string  $name
     * @param string  $selected
     * @param string  $selectedModel value & key
     * @param boolean $disabled
     * @param string  $class
     *
     * @return string
     */
    public static function createSelect($array, $name, $selected = null, $selectedModel = 'key', $disabled = false, $class = 'form-control')
    {
        $class = $class ? 'class="' . $class . '"' : null;
        $disabled = $disabled ? 'disabled=disabled' : null;
        $tpl = '<select ' . $class . ' name="' . $name . '" ' . $disabled . '>';

        foreach ($array as $value => $info) {
            $operationObj = ($selectedModel == 'key') ? $value : $info;
            $checkedState = ($operationObj == $selected) ? 'selected="selected"' : null;
            $tpl .= '<option value="' . $value . '" ' . $checkedState . '>' . $info . '</option>';
        }
        $tpl .= '</select>';

        return $tpl;
    }

    /**
     * Create radio'html
     *
     * @param array   $array
     * @param string  $name
     * @param string  $selected
     * @param string  $selectedModel value & key
     * @param boolean $disabled
     * @param string  $class
     *
     * @return string
     */
    public static function createRadio($array, $name, $selected = null, $selectedModel = 'key', $disabled = false, $class = 'radio-inline')
    {
        $class = $class ? 'class="' . $class . '"' : null;
        $disabled = $disabled ? 'disabled=disabled' : null;
        $tpl = null;

        foreach ($array as $value => $info) {
            $tpl .= '<label ' . $class . ' ' . $disabled . '>';
            $operationObj = ($selectedModel == 'key') ? $value : $info;
            $checkedState = ($operationObj == $selected) ? 'checked="checked"' : null;
            $tpl .= '<input type="radio" name="' . $name . '" value="' . $value . '" ' . $checkedState . '> ' . $info;
            $tpl .= '</label>';
        }

        return $tpl;
    }

    /**
     * Create checkbox'html
     *
     * @param array   $array
     * @param string  $name
     * @param mixed   $selected
     * @param string  $selectedModel value & key
     * @param boolean $disabled
     * @param string  $class
     *
     * @return string
     */
    public static function createCheckbox($array, $name, $selected = null, $selectedModel = 'key', $disabled = false, $class = 'checkbox-inline')
    {
        $class = $class ? 'class="' . $class . '"' : null;
        $disabled = $disabled ? 'disabled=disabled' : null;
        $tpl = null;

        foreach ($array as $value => $info) {
            $tpl .= '<label ' . $class . ' ' . $disabled . '>';
            $operationObj = ($selectedModel == 'key') ? $value : $info;
            $selected = is_array($selected) ? $selected : explode(',', $selected);
            $checkedState = in_array($operationObj, $selected) ? 'checked="checked"' : null;
            $tpl .= '<input type="checkbox" name="' . $name . '" value="' . $value . '" ' . $checkedState . '> ' . $info;
            $tpl .= '</label>';
        }

        return $tpl;
    }

    /**
     * Get array'keys by array'values
     *
     * @access public
     *
     * @param array $array
     * @param mixed $values Use a comma to separate or use an array
     *
     * @return mixed
     */
    public static function getKeyByValue($array, $values)
    {
        $isString = is_string($values) ? true : false;

        if (empty($values)) {
            return $values;
        }

        if ($isString) {
            $values = explode(',', $values);
        }

        $values = self::getValueByKey(array_flip($array), $values);

        return $isString ? implode(',', $values) : $values;
    }

    /**
     * Get array'values by array'keys
     *
     * @access public
     *
     * @param array $array
     * @param mixed $keys Use a comma to separate or use an array
     *
     * @return mixed
     */
    public static function getValueByKey($array, $keys)
    {
        $isString = is_string($keys) ? true : false;

        if (empty($keys)) {
            return $keys;
        }

        if ($isString) {
            $keys = explode(',', $keys);
        }

        $values = [];
        foreach ($keys as $val) {
            $values[] = isset($array[$val]) ? $array[$val] : null;
        }

        return $isString ? implode(',', $values) : $values;
    }

    /**
     * array['helloWorld'] to arr['hello_world']
     *
     * @access public
     *
     * @param array  $array
     * @param string $split
     *
     * @return array
     */
    public static function keyCamelToUnder($array, $split = '_')
    {
        if (!is_array($array)) {
            return $array;
        }

        foreach ($array as $key => $value) {
            $newKey = self::camelToUnder($key, $split);
            $array[$newKey] = $array[$key];
            if ($newKey != $key) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * array['hello_world'] to arr['helloWorld']
     *
     * @access public
     *
     * @param array   $array
     * @param boolean $small Camel of case
     * @param string  $split
     *
     * @return array
     */
    public static function keyUnderToCamel($array, $small = true, $split = '_')
    {
        if (!is_array($array)) {
            return $array;
        }

        foreach ($array as $key => $value) {
            $newKey = self::underToCamel($key, $small, $split);
            $array[$newKey] = $array[$key];
            if ($newKey != $key) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Get the tree by array
     *
     * @access public
     *
     * @param array  $items
     * @param string $id
     * @param string $pid
     * @param string $subName The key for subName
     *
     * @return array
     */
    public static function tree($items, $id = 'id', $pid = 'pid', $subName = 'sub')
    {
        if (empty($items)) {
            return [];
        }

        $items = self::arrayColumn($items, 'id');

        $tree = [];
        foreach ($items as $item) {
            if (!empty($items[$item[$pid]])) {
                $items[$item[$pid]][$subName][] = &$items[$item[$id]];
            } else {
                $tree[] = &$items[$item[$id]];
            }
        }

        return $tree;
    }

    /**
     * If empty return default
     *
     * @param array  $item
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function emptyDefault($item, $key, $default = null)
    {
        return empty($item[$key]) ? $default : $item[$key];
    }

    /**
     * If not set return default
     *
     * @param array  $item
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function issetDefault($item, $key, $default = null)
    {
        return isset($item[$key]) ? $item[$key] : $default;
    }

    /**
     * Pull items from array by keys
     *
     * @access public
     *
     * @param array $target
     * @param array $keys
     *
     * @return array
     */
    public static function pullSome($target, $keys)
    {
        $_target = [];
        foreach ($keys as $oldKey => $newKey) {
            if (is_numeric($oldKey)) {
                $oldKey = $newKey;
            }
            if (isset($target[$oldKey])) {
                $_target[$newKey] = $target[$oldKey];
            }
        }

        return $_target;
    }

    /**
     * Pop item and unset it
     *
     * @access public
     *
     * @param array   $target
     * @param string  $item
     * @param boolean $valueModel
     *
     * @return mixed
     */
    public static function popOne(&$target, $item, $valueModel = false)
    {
        if ($valueModel) {
            $data = $item = array_search($item, $target);
            if ($item === false) {
                return null;
            }
        } else {
            if (!isset($target[$item])) {
                return null;
            }
            $data = $target[$item];
        }
        unset($target[$item]);

        return $data;
    }

    /**
     * Pop items and unset they
     *
     * @access public
     *
     * @param array   $target
     * @param array   $items
     * @param boolean $valueModel
     *
     * @return mixed
     */
    public static function popSome(&$target, $items, $valueModel = false)
    {
        $value = [];
        foreach ($items as $item) {
            $value[$item] = self::popOne($target, $item, $valueModel);
        }

        return $value;
    }

    // --- File ---

    /**
     * Write content to file
     *
     * @access public
     *
     * @param string  $file
     * @param string  $message
     * @param string  $firstMessage
     * @param integer $permission
     * @param string  $mode
     *
     * @return boolean
     */
    public static function writeFile($file, $message, $firstMessage = null, $permission = 0777, $mode = 'ab')
    {
        if (!file_exists($file)) {
            $first = true;
            touch($file);
        }

        if (!$handle = @fopen($file, $mode)) {
            return false;
        }

        flock($handle, LOCK_EX);
        if (isset($first) && !empty($firstMessage) && $mode == 'ab') {
            fwrite($handle, $firstMessage);
        }
        fwrite($handle, $message);
        flock($handle, LOCK_UN);
        fclose($handle);

        @chmod($file, $permission);

        return true;
    }

    /**
     * Iterator for directory
     *
     * @access public
     *
     * @param string   $directory
     * @param callable $fileFn
     * @param callable $dirFn
     * @param array    &$tree
     *
     * @return mixed
     */
    public static function directoryIterator($directory, $fileFn = null, $dirFn = null, &$tree = [])
    {
        if (!is_dir($directory) || !($handle = opendir($directory))) {
            return [];
        }

        while (false !== ($item = readdir($handle))) {

            if ($item == '.' || $item == '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $result = null;
                if (is_callable($dirFn)) {
                    $result = call_user_func_array($dirFn, [
                        $path
                    ]);
                }

                if (false !== $result) {
                    self::directoryIterator($result ?: $path, $fileFn, $dirFn, $tree[$item]);
                }
            } else {
                $result = null;
                if (is_callable($fileFn)) {
                    $result = call_user_func_array($fileFn, [
                        $path
                    ]);
                }

                if (false !== $result) {
                    $tree[] = $result ?: $path;
                }
            }
        }

        closedir($handle);

        return null;
    }

    /**
     * Recursion create directory
     *
     * @access public
     *
     * @param string  $newDir
     * @param integer $permission
     *
     * @return boolean
     */
    public static function createDirectory($newDir, $permission = 0777)
    {
        if (strripos($newDir, DIRECTORY_SEPARATOR) != 0) {
            $parentDir = substr($newDir, 0, strripos($newDir, DIRECTORY_SEPARATOR));
        }

        if (isset($parentDir) && !is_dir($parentDir)) {
            if (!self::createDirectory($parentDir)) {
                return false;
            }
            if (!self::createDirectory($newDir)) {
                return false;
            }

            return true;
        } else {
            if (!is_dir($newDir)) {
                if (@mkdir($newDir, $permission, true)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
    }

    /**
     * Recursion list all of the directory files
     *
     * @access public
     *
     * @param string $directory
     * @param array  $range
     * @param string $type
     *
     * @return array
     */
    public static function readDirectory($directory, $range = [], $type = 'OUT')
    {
        $tree = [];
        $type = strtoupper($type);

        self::directoryIterator($directory, function ($file) use ($type, $range) {

            $suffix = self::getSuffix($file);

            if ('OUT' == $type && in_array($suffix, $range)) {
                return false;
            }

            if ('IN' == $type && !in_array($suffix, $range)) {
                return false;
            }

            return $file;
        }, function () use ($range) {
            return $range ? false : null;
        }, $tree);

        return $tree;
    }

    /**
     * Recursion delete file of directory
     *
     * @access public
     *
     * @param string  $directory
     * @param boolean $removeDirectory Delete directory together
     *
     * @return void
     */
    public static function removeDirectory($directory, $removeDirectory = true)
    {
        self::directoryIterator($directory, function ($file) {
            @unlink($file);
        });

        $removeDirectory && @rmdir($directory);
    }

    /**
     * Zip directory
     *
     * @access public
     *
     * @param string $directory
     * @param string $zipFilePath
     *
     * @return mixed
     */
    public static function archiveDirectory($directory, $zipFilePath = null)
    {
        $zip = new \ZipArchive();
        if (true !== $zip->open($zipFilePath, \ZipArchive::CREATE)) {
            return 'create zip file failed';
        }

        if (is_array($directory)) {
            foreach ($directory as $localName => $file) {
                $zip->addFile($file, is_numeric($localName) ? null : $localName);
            }
        } else {
            self::directoryIterator($directory, function ($file) use ($zip) {
                $zip->addFile($file);
            });
        }

        return $zip->close();
    }

    /**
     * Create deep path
     *
     * @access public
     * @return string
     */
    public static function createDeepPath()
    {

        // most 2000 in the same directory
        $deep = time() % 2000;

        $deep = str_pad($deep, 4, '0', STR_PAD_LEFT) . '/';

        $time = substr(microtime(true), -6, 5);
        $time = intval(str_replace('.', null, $time));
        $deep .= $time < 5000 ? '0' : '1';

        $deep .= substr($time, -3, 3);

        return $deep;
    }

    // --- String ---

    public static $specialChar = '`-=[];\'\,.//~!@#$%^&*()_+{}:"|<>?·【】；’、，。、！￥…（）—：“《》？';

    /**
     * String length
     *
     * @access public
     *
     * @param $str
     *
     * @return int
     */
    public static function length($str)
    {
        return mb_strlen($str);
        // return count(self::split($str));
    }

    /**
     * Interception of a string specifying the physical length
     *
     * @access public
     *
     * @param string $str
     * @param int    $length
     * @param int    $zhCnLength Length of zh
     * @param int    $enLength   Length of en
     *
     * @return string
     */
    public static function subStr($str, $length, $zhCnLength = 2, $enLength = 1)
    {
        $str = self::split($str);
        $nowLen = 0;
        $nowStr = null;
        foreach ($str as $val) {
            if (3 == strlen($val)) {
                $nowLen += $zhCnLength;
            } else {
                $nowLen += $enLength;
            }
            $nowStr .= $val;
            if ($nowLen >= $length) {
                return $nowStr;
            }
        }

        return $nowStr;
    }

    /**
     * Substring
     *
     * @access public
     *
     * @param $str
     * @param $start
     * @param $length
     * @param $charset
     * @param $suffix
     *
     * @return string
     */
    public static function mSubStr($str, $start = 0, $length, $charset = 'utf-8', $suffix = '..')
    {
        if (function_exists('mb_substr')) {
            $slice = mb_substr($str, $start, $length, $charset);
        } else {
            if (function_exists('iconv_substr')) {
                $slice = iconv_substr($str, $start, $length, $charset);
                if (false === $slice) {
                    $slice = null;
                }
            } else {
                $re['utf-8'] = '/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/';
                $re['gb2312'] = '/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/';
                $re['gbk'] = '/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/';
                $re['big5'] = '/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/';
                preg_match_all($re[$charset], $str, $match);
                $slice = join(null, array_slice($match[0], $start, $length));
            }
        }

        return !empty($suffix) ? $slice . $suffix : $slice;
    }

    /**
     * Split the string with nil
     *
     * @access public
     *
     * @param $string
     *
     * @return array
     */
    public static function split($string)
    {
        preg_match_all('/[\s\S]/u', $string, $array);

        return $array[0];
    }

    /**
     * Get the rand string
     *
     * @param integer $len
     * @param string  $type alphabet number upper-alphabet lower-alphabet mixed
     * @param string  $addChars
     *
     * @return string
     */
    public static function randString($len = 6, $type = 'mixed', $addChars = null)
    {
        $str = null;
        switch ($type) {
            case 'alphabet' :
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 'number' :
                $chars = str_repeat('0123456789', 3);
                break;
            case 'upper-alphabet' :
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case 'lower-alphabet' :
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            default :
                // Remove alphabet `OLl` and number `01`
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
        }

        if ($len > 10) {
            $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
        }

        if ($type != 4) {
            $chars = str_shuffle($chars);
            $str = substr($chars, 0, $len);
        } else {
            for ($i = 0; $i < $len; $i++) {
                $str .= self::mSubStr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1, 'utf-8', false);
            }
        }

        return $str;
    }

    /**
     * Get the rand string of readability
     *
     * @access public
     *
     * @param $length
     *
     * @return string
     */
    public static function readability($length)
    {
        $consonant = [
            'b',
            'c',
            'd',
            'f',
            'g',
            'h',
            'j',
            'k',
            'l',
            'm',
            'n',
            'p',
            'r',
            's',
            't',
            'v',
            'w',
            'x',
            'y',
            'z'
        ];
        $vocal = [
            'a',
            'e',
            'i',
            'o',
            'u'
        ];
        $string = null;
        srand((double) microtime() * 1000000);
        $max = $length / 2;
        for ($i = 1; $i <= $max; $i++) {
            $string .= $consonant[rand(0, 19)];
            $string .= $vocal[rand(0, 4)];
        }

        return $string;
    }

    /**
     * Recursion cut string
     *
     * @access  public
     *
     * @param string $string
     * @param array  $rule
     * @param string $splitBy
     *
     * @return string
     * @example :
     *          string: $url = http://www.w3school.com.cn/php/func_array_slice.asp
     *          one: get the `func`
     *          $result = $obj->cutString($url, ['/^0^desc', '_^0']);
     *          two: get the `asp`
     *          $result = $obj->cutString($url, '.^0^desc');
     */
    public static function cutString($string, $rule, $splitBy = '^')
    {
        $rule = is_array($rule) ? $rule : [
            $rule
        ];
        if (empty($rule)) {
            return $string;
        }

        foreach ($rule as $val) {
            $detail = explode($splitBy, $val);
            $string = explode($detail[0], $string);
            if (!empty($detail[2]) && strtolower($detail[2]) == 'desc') {
                $key = count($string) - $detail[1] - 1;
                $string = isset($string[$key]) ? $string[$key] : false;
            } else {
                $string = isset($string[$detail[1]]) ? $string[$detail[1]] : false;
            }
            if ($string === false) {
                break;
            }
        }

        return $string;
    }

    /**
     * Get the suffix
     *
     * @access public
     *
     * @param $filename
     *
     * @return string
     */
    public static function getSuffix($filename)
    {

        // Method  1
        $path = parse_url($filename, PHP_URL_PATH);

        return pathinfo($path, PATHINFO_EXTENSION);

        // Method 2
        // return strtolower(trim(array_pop(explode('.', $filename))));

        // Method 3
        // list($ext) = explode('.', strrev($filename));
        // return strrev($ext);
    }

    /**
     * helloWorld to hello_world
     *
     * @access public
     *
     * @param string $str
     * @param string $split
     *
     * @return string
     */
    public static function camelToUnder($str, $split = '_')
    {
        $str = strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', $split, $str));

        return $str;
    }

    /**
     * hello_world to helloWorld
     *
     * @access public
     *
     * @param string  $str
     * @param boolean $small Camel of case
     * @param string  $split
     *
     * @return string
     */
    public static function underToCamel($str, $small = true, $split = '_')
    {
        $str = str_replace($split, ' ', $str);
        $str = ucwords($str);
        $str = str_replace(' ', null, $str);

        return $small ? lcfirst($str) : $str;
    }

    /**
     * Remove the html tag
     *
     * @access public
     *
     * @param mixed $data
     *
     * @return string
     */
    public static function deleteHtml($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = self::deleteHtml($val);
            }
        } else {
            if (is_string($data)) {
                $data = strip_tags(preg_replace('/<\/?([a-z]+)[^>]*>/i', null, $data));
            }
        }

        return $data;
    }

    /**
     * Filter the special char
     *
     * @param string $string
     *
     * @return string
     */
    public static function filterSpecialChar($string)
    {
        $string = self::deleteHtml($string);
        $specialArr = self::split(self::$specialChar);
        foreach ($specialArr as $val) {
            $string = str_replace($val, null, $string);
        }

        return $string;
    }

    /**
     * Handle the string to array
     *
     * @access public
     *
     * @param string   $string
     * @param string   $split
     * @param callable $handleCallFn
     * @param boolean  $unique
     * @param boolean  $filter
     *
     * @return string | array
     */
    public static function handleString($string, $split = ',', $handleCallFn = null, $unique = true, $filter = true)
    {
        $fullShaped = self::split('，；。‘“？！');
        $halfShaped = str_split(',;.\'"?!');
        foreach ($fullShaped as $key => $val) {
            $string = str_replace($val, $halfShaped[$key], $string);
        }
        if ($split) {
            $strArr = explode($split, $string);
            if (function_exists($handleCallFn)) {
                foreach ($strArr as $key => $val) {
                    $strArr[$key] = call_user_func($handleCallFn, $val);
                }
            }
            $unique && $strArr = array_unique($strArr);
            $filter && $strArr = array_filter($strArr);

            return $strArr;
        }

        return $string;
    }

    /**
     * Handle result for cURL usually use json
     *
     * @param string $result
     * @param string $tagState
     * @param string $tagInfo
     * @param string $tagData
     *
     * @return array
     */
    public static function handleCurlResult($result, $tagState = 'state', $tagInfo = 'info', $tagData = 'data')
    {
        $_result = json_decode($result, true);
        if (is_null($_result)) {
            return [
                $tagState => -1,
                $tagInfo => empty($result) ? 'An error has occurred with blank' : $result,
                $tagData => null
            ];
        }

        return $_result;
    }

    /**
     * Handle result for CLI usually use json
     *
     * @param string $result
     * @param string $tagState
     * @param string $tagInfo
     * @param string $tagData
     *
     * @return array
     */
    public static function handleCliResult($result, $tagState = 'state', $tagInfo = 'info', $tagData = 'data')
    {
        $handler = function ($state, $info, $data = null) use ($tagState, $tagInfo, $tagData) {
            return [
                $tagState => $state,
                $tagInfo => $info,
                $tagData => $data
            ];
        };

        // client or service entrance error
        if (empty($result) || empty($result[0])) {
            return $handler(-1, 'An error has occurred with blank');
        }

        $_result = json_decode($result[0], true);
        if (!is_array($_result) || is_null($_result)) {
            return $handler(-1, $result[0]);
        }

        if (isset($_result[0])) {

            // service error
            if (count($_result) > 1) {
                return $handler(-1, implode("\n", $_result));
            }

            $__result = json_decode($_result[0], true);

            // format error
            if (!is_array($__result) || is_null($__result)) {
                return $handler(-1, $_result[0]);
            }
            $_result = $__result;
        }

        // format error
        if (!isset($_result[$tagState])) {
            return $handler(-1, implode("\n", $_result));
        }

        // result false
        if (!$_result[$tagState]) {
            return $handler(0, $_result[$tagInfo]);
        }

        return $handler($_result[$tagState], $_result[$tagInfo], $_result[$tagData]);
    }

    /**
     * Parse json string
     *
     * @param string $target
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function parseJsonString($target, $default = null)
    {
        if (empty($target) || !is_string($target)) {
            return $target;
        }

        $result = json_decode($target, true);
        $default = is_null($default) ? $target : $default;
        $result = is_null($result) ? $default : $result;

        return $result;
    }

    /**
     * 生成订单编号
     *
     * @access public
     *
     * @param $channel
     * @param $userId
     *
     * @return string
     */
    public static function createOrderNumber($channel, $userId)
    {
        $orderNumber = substr($channel, -1);
        $orderNumber .= date('ym');
        $orderNumber .= str_pad(rand(0, 999), 3, 0, STR_PAD_LEFT);
        $orderNumber .= strrev(str_pad(substr($userId, -4), 4, 0, STR_PAD_LEFT));
        $orderNumber .= date('s');

        return $orderNumber;
    }

    /**
     * 整数加密成字符串 - 如用于隐藏分页
     *
     * @access public
     *
     * @param integer $num
     *
     * @return string
     */
    public static function integerEncode($num)
    {
        $num = intval($num);
        $padLength = floor(strlen($num) / 3) + 3;
        $num = str_pad($num, $padLength, 0, STR_PAD_LEFT);
        $str = base64_encode($num);

        $items = str_split($str);
        array_walk($items, function (&$char) {
            if (ctype_upper($char)) {
                $char = strtolower($char);
                $_chr = chr(ord($char) + 1);
                $char = ctype_lower($_chr) ? $_chr : 'a';
            } else if (ctype_lower($char)) {
                $char = strtoupper($char);
                $_chr = chr(ord($char) + 1);
                $char = strtoupper($_chr) ? $_chr : 'A';
            } else if (is_numeric($char)) {
                $char = 9 - $char;
            }
        });

        return implode('', $items);
    }

    /**
     * 整数密码串解密
     *
     * @access public
     *
     * @param string $str
     *
     * @return mixed
     */
    public static function integerDecode($str)
    {
        $items = str_split($str);
        array_walk($items, function (&$char) {
            if (ctype_lower($char)) {
                $_chr = chr(ord($char) - 1);
                $char = ctype_lower($_chr) ? $_chr : 'z';
                $char = strtoupper($char);
            } else if (ctype_upper($char)) {
                $_chr = chr(ord($char) - 1);
                $char = ctype_upper($_chr) ? $_chr : 'Z';
                $char = strtolower($char);
            } else if (is_numeric($char)) {
                $char = 9 - $char;
            }
        });
        $str = implode('', $items);
        $num = base64_decode($str);

        return is_numeric($num) ? intval($num) : false;
    }

    // --- Others ---

    /**
     * Join items string by split - use fro url split
     *
     * @access public
     *
     * @param string $split
     * @param array  $items
     *
     * @return string
     */
    public static function joinString($split, ...$items)
    {
        $total = count($items) - 1;
        $_items = [];

        foreach ($items as $key => $value) {

            if (is_array($value)) {
                $value = implode($split, $value);
            }

            if (empty($value)) {
                continue;
            }

            if ($key == 0) {
                $_items[] = rtrim($value, $split);
            } else if ($key == $total - 1) {
                $_items[] = ltrim($value, $split);
            } else {
                $_items[] = trim($value, $split);
            }
        }

        return implode($split, $_items);
    }

    /**
     * Create Sign
     *
     * @access public
     *
     * @param array  $param
     * @param mixed  $merge
     * @param string $salt
     *
     * @return string
     */
    public static function createSign($param, $merge = 'app_sign', $salt = null)
    {
        $param = http_build_query($param);
        parse_str($param, $param);

        ksort($param);
        $sign = strrev(md5(json_encode($param)));
        $salt = $salt ? md5(strrev($salt)) : null;
        $sign = strtoupper(sha1($sign) . $salt);

        if (!empty($merge)) {
            $param[$merge] = $sign;

            return $param;
        }

        return $sign;
    }

    /**
     * Validation Sign
     *
     * @access private
     *
     * @param array  $param
     * @param string $keyName
     * @param string $salt
     *
     * @return boolean
     */
    public static function validateSign($param, $keyName = 'app_sign', $salt = null)
    {
        if (empty($param[$keyName])) {
            return false;
        }

        $_sign = $param[$keyName];
        unset($param[$keyName]);

        $sign = self::createSign($param, null, $salt);
        if (strcmp($sign, $_sign) !== 0) {
            return false;
        }

        return true;
    }

    /**
     * List function back trance
     *
     * @access public
     *
     * @param mixed $index
     * @param array $keys
     *
     * @return mixed
     */
    public static function functionCallTrance($index = 1, $keys = ['function'])
    {
        // exclude self
        is_numeric($index) && $index += 1;
        $backTrance = self::arrayColumn(debug_backtrace(), null, $keys);
        if ($index === 'all') {
            return $backTrance;
        }

        return isset($backTrance[$index]) ? $backTrance[$index] : null;
    }
}