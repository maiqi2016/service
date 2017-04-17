<?php

namespace service\components;

use yii\base\Object;

/**
 * Reflection the class to get documents
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2015-12-21 16:10:27
 */
class Reflection extends Object
{

    /**
     * @var object Class name
     */
    private $_class;

    /**
     * @var object Class method
     */
    private $_method;

    /**
     * @var object Class property
     */
    private $_property;

    /**
     * @var string tag 说明标示开头字符
     * @var string info 类方法说明
     * @var string access 类方法或类的权限修饰符标示
     * @var string static 类方法或类的静态修饰符标示
     * @var string author 代码作者标示
     * @var string param 方法参数标示
     * @var string field 字段注释
     * @var string file 文件路径
     * @var string line 代码起始行数
     * @var string default 参数默认值
     * @var string comment_filter 过滤注释中的字符
     * @var string var_tag 变量开头符
     * @var string var_reference_tag 引用变量开头符
     * @var string reg 所有匹配的正则
     */
    private $_config = [
        'tag' => '@',
        'info' => 'info',
        'access' => 'access',
        'static' => 'static',
        'author' => 'author',
        'param' => 'param',
        'field' => 'var',
        'file' => 'file',
        'line' => 'line',
        'default' => 'default',
        'comment_filter' => "\r\n\t *#",
        'var_tag' => '$',
        'var_reference_tag' => '&',
        'reg' => [
            'tags' => '/(%s[\w-_]+)[\ +]?(.*)/i',
            'param_name' => '/\ [&]?\$[\w\[\]\'\"\-\>]+/i',
            'param_type' => '/\ \w+/i',
            'field_comment' => '/([\w\_\d]+)\ (.*)/i'
        ]
    ];

    /**
     * __constructor
     *
     * @access public
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        if (!empty($config['config'])) {
            $this->_config = array_merge($this->_config, $config['config']);
        }

        $attribute = [
            'access',
            'author',
            'param',
            'field'
        ];
        foreach ($attribute as $attr) {
            $this->_config[$attr] = $this->_config['tag'] . $this->_config[$attr];
        }

        parent::__construct();
    }

    /**
     * Get single property document
     *
     * @access public
     *
     * @param object $class
     * @param string $property
     *
     * @return mixed
     */
    public function getPropertyDocument($class, $property)
    {
        // property exists
        if (!property_exists($class, $property)) {
            return 'class property un exists';
        }

        $this->_class = $class;
        $this->_property = $property;

        $reflection = new \ReflectionProperty($this->_class, $this->_property);

        $info = $this->_parseProperty($reflection);

        return $info;
    }

    /**
     * Get single method document
     *
     * @access public
     *
     * @param object $class
     * @param string $method
     *
     * @return mixed
     */
    public function getMethodDocument($class, $method)
    {
        // method exists
        if (!method_exists($class, $method)) {
            return 'class method un exists';
        }

        $this->_class = $class;
        $this->_method = $method;

        $reflection = new \ReflectionMethod($this->_class, $this->_method);

        $info = $this->_parseMethod($reflection);
        $info[$this->_config['file']] = $reflection->getFileName();
        $info[$this->_config['line']] = $reflection->getStartLine();

        return $info;
    }

    /**
     * Get all properties document of the class
     *
     * @access public
     *
     * @param object $class
     * @param mixed  $inClass For extends default only self
     *
     * @return array
     */
    public function getPropertiesDocument($class, $inClass = 'self')
    {
        $properties = $this->getPropertiesName($class, $inClass);

        $info = [];
        if (!empty($properties)) {
            foreach ($properties as $property) {
                $info[$property] = $this->getPropertyDocument($class, $property);
            }
        }

        return $info;
    }

    /**
     * Get all methods document of the class
     *
     * @access public
     *
     * @param object $class
     * @param mixed  $inClass For extends default only self
     *
     * @return array
     */
    public function getMethodsDocument($class, $inClass = 'self')
    {
        $methods = $this->getMethodsName($class, $inClass);

        $info = [];
        if (!empty($methods)) {
            foreach ($methods as $method) {
                $info[$method] = $this->getMethodDocument($class, $method);
            }
        }

        return $info;
    }

    /**
     * Get all properties name of the class
     *
     * @access public
     *
     * @param object $class
     * @param mixed  $inClass For extends default only self
     *
     * @return array
     */
    public function getPropertiesName($class, $inClass = 'self')
    {
        $reflection = new \ReflectionClass($class);

        if ($inClass == 'self') {
            $inClass = [$reflection->name];
        }

        $properties = $reflection->getProperties();
        $_properties = [];

        if (!empty($properties)) {
            $inClass = (array) $inClass;

            foreach ($properties as $key => $property) {
                if (empty($inClass)) {
                    $_properties[] = $property->name;
                } else if (in_array($property->class, $inClass)) {
                    $_properties[] = $property->name;
                }
            }
        }

        return $_properties;
    }

    /**
     * Get all method name of the class
     *
     * @access public
     *
     * @param object $class
     * @param mixed  $inClass For extends default only self
     *
     * @return array
     */
    public function getMethodsName($class, $inClass = 'self')
    {
        $reflection = new \ReflectionClass($class);

        if ($inClass == 'self') {
            $inClass = [$reflection->name];
        }

        $methods = $reflection->getMethods();
        $_methods = [];

        if (!empty($methods)) {
            $inClass = (array) $inClass;

            foreach ($methods as $key => $method) {
                if (empty($inClass)) {
                    $_methods[] = $method->name;
                } else if (in_array($method->class, $inClass)) {
                    $_methods[] = $method->name;
                }
            }
        }

        return $_methods;
    }

    /**
     * Get document of the class
     *
     * @param mixed $class
     *
     * @return array
     */
    public function getClassDocument($class)
    {
        $document = (new \ReflectionClass($class))->getDocComment();
        $document = $this->_parseCommentDoc($document);

        return $document;
    }

    /**
     * Parse the reflection object of property
     *
     * @access private
     *
     * @param object $reflection
     *
     * @return array
     */
    private function _parseProperty($reflection)
    {
        // get the document
        $commentDoc = $this->_parseCommentDoc($reflection->getDocComment());

        // modifier
        if ($reflection->isPublic()) {
            $commentDoc[$this->_config['access']] = 'public';
        } elseif ($reflection->isProtected()) {
            $commentDoc[$this->_config['access']] = 'protected';
        } else {
            $commentDoc[$this->_config['access']] = 'private';
        }

        $commentDoc[$this->_config['static']] = $reflection->isStatic() ? true : false;

        $reflection->setAccessible(true);
        $commentDoc[$this->_config['default']] = $reflection->getValue(new $reflection->class());

        unset($commentDoc[$this->_config['info']]);

        return $commentDoc;
    }

    /**
     * Parse the reflection object of method
     *
     * @access private
     *
     * @param object $reflection
     *
     * @return array
     */
    private function _parseMethod($reflection)
    {
        // get the document
        $commentDoc = $this->_parseCommentDoc($reflection->getDocComment());

        // if non author of the method then get the author of the class
        if (empty($commentDoc[$this->_config['author']])) {

            $classCommentDoc = $reflection->getDeclaringClass()->getDocComment();
            $classCommentDoc = $this->_parseCommentDoc($classCommentDoc);

            if (!empty($classCommentDoc[$this->_config['author']])) {
                $commentDoc[$this->_config['author']] = $classCommentDoc[$this->_config['author']];
            }
        }
        // parse params
        $params = $this->_parseParams($reflection->getParameters());

        // merge the params
        if (!empty($commentDoc[$this->_config['author']])) {
            if (isset($commentDoc[$this->_config['param']])) {
                foreach ($commentDoc[$this->_config['param']] as $name => &$info) {
                    if (isset($params[$name])) {
                        $info = array_merge($params[$name], $info);
                    }
                }
            }
        }

        // modifier
        if ($reflection->isPublic()) {
            $commentDoc[$this->_config['access']] = 'public';
        } elseif ($reflection->isProtected()) {
            $commentDoc[$this->_config['access']] = 'protected';
        } else {
            $commentDoc[$this->_config['access']] = 'private';
        }

        $commentDoc[$this->_config['static']] = $reflection->isStatic() ? true : false;

        return $commentDoc;
    }

    /**
     * Parse the reflection object of params
     *
     * @access private
     *
     * @param object $reflection
     *
     * @return array
     */
    private function _parseParams($reflection)
    {
        $params = [];

        foreach ($reflection as $param) {
            // param name
            $name = $this->_config['var_tag'] . $param->getName();

            // reference param tag
            if ($param->isPassedByReference()) {
                $name = $this->_config['var_reference_tag'] . $name;
            }

            $params[$name] = [];

            // default value
            if ($param->isDefaultValueAvailable()) {
                $params[$name][$this->_config['default']] = $param->getDefaultValue();
            }
        }

        return $params;
    }

    /**
     * Parse the document string
     *
     * @access private
     *
     * @param string $commentDoc
     *
     * @return array
     */
    private function _parseCommentDoc($commentDoc)
    {
        $comment = array_slice(explode("\n", $commentDoc), 1, -1);

        $_comment = [];
        foreach ($comment as $val) {
            $val = ltrim($val, $this->_config['comment_filter']);

            // blank line
            if (empty($val)) {
                continue;
            }

            // get @xxx and describe
            preg_match(sprintf($this->_config['reg']['tags'], $this->_config['tag']), $val, $result);

            // get describe of the method
            if (empty($result)) {
                if (!isset($_comment['info'])) {
                    $_comment[$this->_config['info']] = trim($val);
                }

                continue;
            }

            $_comment[$this->_config['info']] = isset($_comment[$this->_config['info']]) ? trim($_comment[$this->_config['info']]) : null;

            // get others documents
            $tagName = trim($result[1]);
            $tagValue = trim($result[2]);

            $doc = ' ' . $tagValue;

            // document of the param
            if ($this->_config['param'] == $tagName) {

                $_param = [];

                // match param type
                preg_match($this->_config['reg']['param_type'], $doc, $type);
                if (empty($type)) {
                    continue;
                }

                $_param['type'] = trim($type[0]);

                // match param name
                preg_match($this->_config['reg']['param_name'], $doc, $name);
                if (empty($name)) {
                    continue;
                }
                $name = trim($name[0]);

                $_replace = $_param;
                $_replace['name'] = $name;

                // get document describe
                $_param['info'] = trim(str_replace($_replace, [
                    null,
                    null
                ], $doc));

                if (!empty($name)) {
                    $_comment[$this->_config['param']][$name] = $_param;
                } else {
                    $_comment[$this->_config['param']][] = $_param;
                }

                // return the documents
            } else {
                if ($this->_config['field'] == $tagName) {

                    $fieldArr = [];

                    if (!empty($doc)) {

                        preg_match($this->_config['reg']['field_comment'], $doc, $field);

                        if (!empty($field)) {
                            $fieldArr[] = [
                                'type' => trim($field[1]),
                                'info' => trim($field[2])
                            ];
                        }

                        if (empty($_comment[$this->_config['field']])) {
                            $_comment[$this->_config['field']] = $fieldArr;
                        } else {
                            $_comment[$this->_config['field']] = array_merge($_comment[$this->_config['field']], $fieldArr);
                        }
                    }

                    // others documents
                } else {
                    $_comment[$tagName][] = $tagValue;
                }
            }
        }

        return $_comment;
    }
}