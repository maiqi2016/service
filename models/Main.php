<?php

namespace service\models;

use yii\db\ActiveRecord;
use yii;
use service\components\Helper;

/**
 * Main model
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2016-11-18 09:18:45
 */
class Main extends ActiveRecord
{
    /**
     * @var object db object
     */
    protected $db;

    /**
     * @var string db identity key
     */
    const DB_IDENTITY = 'db_identity';

    /**
     * @var array Field
     */
    public $_state = [
        0 => '删除',
        1 => '正常'
    ];

    /**
     * @var array Field
     */
    public $_app = [
        0 => 'common',
        1 => 'frontend',
        2 => 'backend'
    ];

    /**
     * @var array state 验证规则
     */
    public $_rule_state = [
        'state_default' => [
            ['state'],
            'default',
            'value' => 1
        ],
        'state_range' => [
            ['state'],
            'in',
            'range' => [
                0,
                1
            ]
        ]
    ];

    /**
     * @var array remark 验证规则
     */
    public $_rule_remark = [
        'remark_string' => [
            ['remark'],
            'string',
            'max' => 128
        ]
    ];

    /**
     * @var array phone 验证规则
     */
    public $_rule_phone = [
        'phone_string' => [
            ['phone'],
            'string',
            'min' => 11,
            'max' => 32
        ],
        'phone_standard' => [
            ['phone'],
            'match',
            'pattern' => '/^[\d]([\d\-\ ]+)?[\d]$/',
            'message' => '{attribute} {value} 不是一个规范的格式'
        ],
    ];

    /**
     * @var array password 验证规则
     */
    public $_rule_password = [
        'password_required' => [
            ['password'],
            'required'
        ],
        'password_string' => [
            ['password'],
            'string',
            'min' => 6,
            'max' => 32
        ]
    ];

    /**
     * @var array username 验证规则
     */
    public $_rule_username = [
        'username_string' => [
            ['username'],
            'string',
            'min' => 1,
            'max' => 32
        ]
    ];

    /**
     * @var array captcha 验证规则
     */
    public $_rule_captcha = [
        'captcha_required' => [
            ['captcha'],
            'required'
        ],
        'captcha_string' => [
            ['captcha'],
            'string',
            'min' => 4,
            'max' => 8
        ]
    ];

    /**
     * @var array add_time 验证规则
     */
    public $_rule_add_time = [
        'add_time_safe' => [
            ['add_time'],
            'safe'
        ]
    ];

    /**
     * @var array update_time 验证规则
     */
    public $_rule_update_time = [
        'update_time_safe' => [
            ['update_time'],
            'safe'
        ]
    ];

    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return Yii::$app->{Yii::$app->session->get(self::DB_IDENTITY)};
    }

    /**
     * 动态切换数据库
     *
     * @access public
     *
     * @param string $identity 数据库链接标识
     *
     * @return void
     */
    public static function setDb($identity)
    {
        Yii::info('切换数据库到: ' . $identity);
        Yii::$app->session->set(self::DB_IDENTITY, $identity);
    }

    /**
     * 统一 Model 层返回给 Controller 层的数据格式 (多类型的情况下)
     *
     * @access public
     *
     * @param mixed $result
     * @param array $attribute
     *
     * @return array
     */
    public function result($result, $attribute = [])
    {
        // 字符串结果标示错误
        $data = (is_string($result) || $result === false) ? [
            'state' => 0,
            'info' => $result,
            'data' => null
        ] : [
            'state' => 1,
            'info' => null,
            'data' => $result
        ];

        return array_merge($data, $attribute);
    }

    /**
     * 事务安全
     *
     * @access public
     *
     * @param callable $logicFn
     * @param string   $info
     *
     * @return mixed
     */
    public function trans($logicFn, $info)
    {

        $info = sprintf('执行事务%s', $info);

        // begin
        $transaction = static::getDb()->beginTransaction();

        try {

            $result = call_user_func($logicFn);

            // commit
            $transaction->commit();
            Yii::info($info . '成功');

        } catch (yii\db\Exception $e) {

            // rollback
            $transaction->rollBack();
            Yii::error($info . '失败, ' . $e->getMessage());

            $result = $e->getMessage();
        }

        return $this->result($result);
    }

    /**
     * New record
     *
     * @access public
     *
     * @param array $attributes
     *
     * @return array
     */
    public function add($attributes)
    {
        $model = new static;
        $model->attributes = $attributes;

        if ($model->validate()) {
            $model->save();
            $result = $model->id;
        } else {
            $result = current($model->getFirstErrors());
            Yii::error($result);
        }

        return $this->result($result);
    }

    /**
     * Edit record
     *
     * @access public
     *
     * @param array  $where
     * @param array  $attributes
     * @param object $model
     *
     * @return array
     */
    public function edit($where, $attributes, $model = null)
    {
        $model = $model ?: new static;
        $record = $model::findOne($where);

        if (empty($record)) {
            return $this->result('abnormal operation');
        }

        foreach ($attributes as $field => $value) {
            if (is_callable($value)) {
                $value = call_user_func($value, $record->{$field});
            }
            $record->{$field} = $value;
        }

        if (!$record->save()) {
            return $this->result(current($record->getFirstErrors()));
        }

        return $this->result($record);
    }

    /**
     * 有则更新,否则新增
     *
     * @access public
     *
     * @param array    $where
     * @param array    $updateData
     * @param callable $existsFn
     *
     * @return array
     */
    public function updateOrInsert($where, $updateData, $existsFn = null)
    {
        $exists = static::findOne($where);
        $result = null;

        // update
        if ($exists) {

            if ($existsFn && ($existsFnResult = call_user_func($existsFn, $exists)) !== true) {
                return $existsFnResult;
            }

            $exists->attributes = $updateData;
            if (!$effect = $exists->save()) {
                $result = current($exists->getFirstErrors());
            }

            // insert
        } else {

            $insertData = array_merge($where, $updateData);
            unset($insertData['add_time'], $insertData['update_time']);

            $this->attributes = $insertData;

            if (!$res = $this->insert()) {
                $result = current($this->getFirstErrors());
            } else {
                $result = $this->id;
                // 重置上一次操作的对象
                $this->isNewRecord = true;
                $this->id = null;
            }
        }

        return $this->result($result, [
            'type' => $exists ? 'update' : 'insert'
        ]);
    }

    /**
     * 获取单条数据
     *
     * @param array | callable $handler
     *
     * @return array
     */
    public function first($handler = null)
    {
        $one = static::find();
        if (is_array($handler)) {
            $one->where($handler);
        } else if (is_callable($handler)) {
            $one = call_user_func($handler, $one);
        }

        $table = static::tableName();
        $key = md5(json_encode([
            $one,
            Yii::$app->request->get(),
            Yii::$app->request->post()
        ]));

        return $this->cache($table . '.first.' . $key, function () use ($one) {
            return $one->asArray()->one();
        }, null, $this->cacheDbDependent($table));
    }

    /**
     * 获取全部数据 通常用于后台
     *
     * @access public
     *
     * @param array | callable $handler
     * @param integer          $pageSize
     * @param array            $additional
     * @param boolean          $debugSql
     *
     * @return array
     */
    public function all($handler = null, $pageSize = null, $additional = [], $debugSql = false)
    {
        $list = static::find();

        if (is_array($handler)) {
            $list->where($handler);
        } else if (is_callable($handler)) {
            $list = call_user_func($handler, $list);
        }

        $table = static::tableName();
        $key = md5(json_encode([
            $list,
            Yii::$app->request->get(),
            Yii::$app->request->post()
        ]));

        return $this->cache($table . '.all.' . $key, function () use ($list, $pageSize, $additional, $debugSql) {

            if (isset($pageSize)) {
                $count = $list->count();

                $pagination = new yii\data\Pagination(['totalCount' => $count]);
                $pagination->setPageSize($pageSize);

                $list = $list->offset($pagination->offset)->limit($pagination->limit);
            }

            if ($debugSql) {
                exit($list->createCommand()->getRawSql());
            }
            $list = $list->asArray()->all();
            $list = $this->getFieldInfo($list, $additional);

            return isset($pageSize) ? [
                $list,
                $pagination
            ] : $list;
        }, null, $this->cacheDbDependent($table));
    }

    /**
     * 获取对应字段的描述
     *
     * @param array $list
     * @param array $additional
     *
     * @return array
     */
    public function getFieldInfo($list, $additional = [])
    {
        if (empty($list)) {
            return $list;
        }

        $model = new static;
        $infoKeys = $addInfoKeys = [];
        empty($additional) && $additional = [];

        foreach ($list[0] as $key => $value) {

            $infoField = '_' . $key;
            $infoModel = '_' . $key . '_model';

            if (!isset($model->$infoField) && !isset($model->$infoModel)) {
                continue;
            }

            $obj = $model;
            if (isset($model->$infoModel)) {
                $_model = '\service\models\\' . $model->$infoModel;
                $_model = new $_model;

                if (!isset($_model->$infoField)) {
                    continue;
                }
                $obj = $_model;
            }
            $infoKeys[$key] = $obj->$infoField;

            if (!empty($additional[$key])) {
                $tag = $additional[$key];
                $addInfoField = '_' . $key . '_' . $tag;

                if (!isset($obj->$addInfoField)) {
                    continue;
                }
                $addInfoKeys[$key] = $obj->$addInfoField;
            }
        }

        foreach ($list as &$item) {
            foreach ($infoKeys as $key => $value) {
                if (!isset($value[$item[$key]])) {
                    continue;
                }
                $item[$key . '_info'] = $value[$item[$key]];
            }

            foreach ($addInfoKeys as $key => $value) {
                $tag = $additional[$key];
                $item[$key . '_' . $tag . '_info'] = $value[$item[$key]];
            }
        }

        return $list;
    }

    /**
     * 无需验证的操作
     *
     * @access public
     *
     * @param array   $actions
     * @param boolean $app
     *
     * @return boolean
     */
    public function viaValidation($actions, $app = false)
    {
        $actions = (array) $actions;
        $action = Yii::$app->controller->id . '/' . Yii::$app->controller->action->id;
        if ($app) {
            $action = Yii::$app->id . '/' . $action;
        }

        return !in_array($action, $actions);
    }

    /**
     * Dump variable
     *
     * @param mixed $var
     * @param bool  $strict
     * @param bool  $exit
     *
     * @return void
     */
    public function dump($var, $strict = false, $exit = true)
    {
        if (empty($_SERVER['argv']) || empty($_SERVER['argv'][1])) {
            Helper::dump($var, $exit, $strict);
        } else {
            exit(json_encode([
                'state' => 1,
                'info' => 'DEBUG',
                'data' => $var
            ], JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 获取缓存
     *
     * @param mixed                   $key
     * @param callable                $fetchFn
     * @param int                     $time
     * @param \yii\caching\Dependency $dependent
     *
     * @return mixed
     */
    public function cache($key, $fetchFn, $time = null, $dependent = null)
    {
        if (!Yii::$app->params['use_cache'] || Yii::$app->session->getFlash('no_cache')) {
            return call_user_func($fetchFn);
        }

        if (!(is_string($key) && strpos($key, '.') !== false)) {
            $key = static::className() . '-' . md5(json_encode($key));
        }

        $data = Yii::$app->cache->get($key);

        if (false === $data) {
            Yii::trace('缓存命中失败并重新获取写入: ' . $key);
            $data = call_user_func($fetchFn);
            $time = $time ?: Yii::$app->params['cache_time'];
            $result = Yii::$app->cache->set($key, $data, $time, $dependent);

            if ($result === false) {
                Yii::error('写入缓存失败: ' . $key);
            }
        } else {
            Yii::trace('缓存命中成功: ' . $key);
        }

        return $data;
    }

    /**
     * 加工AR模型对象
     *
     * @access public
     *
     * @param object $activeRecord
     * @param string $table
     * @param mixed  $options
     *
     * @return object
     */
    public function handleActiveRecord($activeRecord, $table, $options = [])
    {
        if (is_string($options)) {
            $options = Helper::parseJsonString($options);
            if (!$options) {
                return $activeRecord;
            }
        }

        if (!empty($options['join'])) {
            $options['join'] = Helper::parseJsonString($options['join'], []);
            foreach ($options['join'] as $item) {
                if (empty($item['table'])) {
                    continue;
                }

                $types = [
                    'left',
                    'right',
                    'inner'
                ];
                if (empty($item['type']) || !in_array($item['type'], $types)) {
                    $item['type'] = 'left';
                }

                $as = empty($item['as']) ? $item['table'] : $item['as'];

                $leftId = empty($item['left_on_field']) ? $item['table'] . '_id' : $item['left_on_field'];
                $rightId = empty($item['right_on_field']) ? 'id' : $item['right_on_field'];

                $leftTable = empty($item['left_table']) ? $table : $item['left_table'];
                $on = "`${leftTable}`.`${leftId}` = `${as}`.`${rightId}`";

                if (isset($item['sub'])) {
                    $item['sub']['from'] = $item['table'];
                    $subQuery = $this->handleActiveRecord(new yii\db\Query(), $item['table'], $item['sub']);
                    $target = [$as => $subQuery];
                } else {
                    $target = "${item['table']} AS `${as}`";
                }

                $action = $item['type'] . 'Join';
                $activeRecord->$action($target, $on);
            }
        }

        if (!empty($options['select'])) {
            $options['select'] = Helper::parseJsonString($options['select']);
            $activeRecord->select($options['select']);
        }

        if (!empty($options['from'])) {
            $activeRecord->from($options['from']);
        }

        if (!empty($options['where'])) {
            $options['where'] = Helper::parseJsonString($options['where'], []);
            foreach ($options['where'] as $item) {
                $operator = isset($item['or']) ? 'or' : 'and';
                $action = $operator . 'Where';
                unset($item['or']);
                $activeRecord->{$action}($item);
            }
        }

        if (!empty($options['group'])) {
            $options['group'] = Helper::parseJsonString($options['group']);
            if (is_array($options['group']) && is_numeric(key($options['group']))) {
                $options['group'] = implode(',', $options['group']);
            }
            $activeRecord->groupBy($options['group']);
        }

        if (!empty($options['order'])) {
            $options['order'] = Helper::parseJsonString($options['order']);
            if (is_array($options['order']) && is_numeric(key($options['order']))) {
                $options['order'] = implode(',', $options['order']);
            }
            $activeRecord->orderBy($options['order']);
        }

        if (!empty($options['offset'])) {
            $activeRecord->offset($options['offset']);
        }

        if (!empty($options['limit'])) {
            $activeRecord->limit($options['limit']);
        }

        if (!empty($options['distinct'])) {
            $activeRecord->distinct();
        }

        return $activeRecord;
    }

    /**
     * 缓存之数据库依赖
     *
     * @access public
     *
     * @param string $table
     *
     * @return \yii\caching\Dependency
     */
    public function cacheDbDependent($table)
    {
        $dp = new yii\caching\DbDependency([
            'sql' => sprintf('SELECT `update_time` FROM `%s` ORDER BY `update_time` DESC LIMIT 1', $table)
        ]);
        $dp->db = $this->db;

        return $dp;
    }
}
