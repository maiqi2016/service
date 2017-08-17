<?php

namespace service\components;

use yii\base\Object;

/**
 * Helper components
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-08-11 17:48:24
 */
class Helper extends Object
{
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
            $container[$key] = call_user_func($logicHandler, $params);
        }

        return $container[$key];
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
        $options[CURLOPT_URL] = str_replace(' ', '+', $url);

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
        return pathinfo(parse_url($filename, PHP_URL_PATH), PATHINFO_EXTENSION);
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
     * 整数加密成字符串 - 如用于隐藏分页
     *
     * @access public
     *
     * @param integer $num
     * @param integer $add
     *
     * @return string
     */
    public static function integerEncode($num, $add = 100024)
    {
        $num = intval($num);
        $num = $add ? $num + $add : $num;

        $len = strlen($num);
        $padLen = 3 - $len % 3;
        $padLen = ($padLen == 3) ? 0 : $padLen + $len;

        $num = str_pad($num, $padLen, 0, STR_PAD_LEFT);
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
                $char = ctype_upper($_chr) ? $_chr : 'A';
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
     * @param string  $str
     * @param integer $add
     *
     * @return mixed
     */
    public static function integerDecode($str, $add = 100024)
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

        if (!is_numeric($num)) {
            return false;
        }
        $num = intval($num);

        return $add ? $num - $add : $num;
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
     * Generate lottery code
     *
     * @param integer $digit
     * @param integer $total
     *
     * @return array
     */
    public static function generateCode($digit, $total)
    {
        $count = 0;
        $box = [];

        while ($count < $total) {
            $code = base_convert(md5(uniqid(rand(), true)), 18, 36);
            $code = strtoupper(substr($code, 0, $digit));

            if (strlen($code) == $digit && !isset($box[$code])) {
                $box[$code] = true;
                $count++;
            }
        }

        return array_keys($box);
    }

    /**
     * Generate rand num
     *
     * @param integer $begin
     * @param integer $end
     * @param integer $limit
     *
     * @return array
     */
    public static function generalRandMultipleNum($begin, $end, $limit)
    {
        $randArr = range($begin, $end);
        shuffle($randArr);

        return array_slice($randArr, 0, $limit);
    }
}