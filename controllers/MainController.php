<?php

namespace service\controllers;

use service\models\kake\Attachment;
use service\models\kake\Config;
use service\models\Main;
use service\models\service\User as ServiceUser;
use yii;
use yii\web\Controller;
use service\components\Helper;
use yii\base\DynamicModel;

/**
 * Main controller
 * @method mixed cache($key, $fetchFn, $time = null, $dependent = null)
 * @method mixed dump($var, $strict = false, $exit = true)
 */
class MainController extends Controller
{
    /**
     * @var array 用户信息
     */
    protected $user;

    /**
     * @var string language
     */
    const SESSION_LANGUAGE = 'language';

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();
        $this->enableCsrfValidation = false;
    }

    /**
     * @inheritDoc
     */
    public function beforeAction($action)
    {
        $this->identityVerification($_GET);

        $config = (new Config())->listConfigKVP([
            'app' => [
                0,
                $this->user->app
            ],
            'state' => 1
        ]);
        Yii::$app->params = array_merge(Yii::$app->params, $config);

        // 对应 api 的权限验证 TODO

        return parent::beforeAction($action);
    }

    /**
     * 调用者身份验证
     *
     * @access public
     *
     * @param array $params
     *
     * @return mixed
     */
    public function identityVerification(&$params)
    {
        $api = Helper::popOne($params, 'r');

        // 参数为空或错误
        if (empty($params) || empty($params['app_sign'])) {
            if (Helper::buffer('app_signed')) {
                $this->fail('api parameter validation failed', 'common', -1);
            }
            $this->fail('base parameter validation failed', 'common', -1);
        }

        // 验证签名
        if (!Helper::validateSign($params, 'app_sign')) {
            $this->fail([
                'signature verification failed',
                'api' => $params['app_api']
            ], 'common', -1);
        }

        // 标记当前已经验证过签名
        Helper::buffer('app_signed', true, null, true);

        // 语言包
        if (isset($params['app_lang'])) {
            Yii::$app->language = $params['app_lang'];
        }

        // 用户id或用户秘钥为空
        if (empty($params['app_id']) || empty($params['app_secret'])) {
            $this->fail('account validation failed', 'common', -1);
        }

        // 验证用户是否存在
        $user = (new ServiceUser())->first([
            'app_id' => $params['app_id'],
            'app_secret' => $params['app_secret'],
            'state' => 1
        ]);

        if (empty($user)) {
            $this->fail('account validation failed', 'common', -1);
        }

        $this->user = (object) Helper::pullSome($user, [
            'id',
            'type',
            'app',
            'remark'
        ]);

        // 缓存
        if (isset($params['app_cache']) && $params['app_cache'] == 'no') {
            Yii::$app->params['use_cache'] = false;
        }

        // 删除隐私变量
        $keys = [
            'app_api',
            'app_id',
            'app_secret',
            'app_lang',
            'app_cache',
            'app_sign'
        ];
        foreach ($keys as $key) {
            unset($params[$key]);
        }
        $params['r'] = $api;

        return true;
    }

    /**
     * 公共错误控制器
     *
     * @access public
     * @return void
     */
    public function actionError()
    {
        $api = trim(Yii::$app->request->get('r'), '/');
        $this->fail([
            'access to an interface that does not exist',
            'api' => strtr($api, '/', '.')
        ], 'common', -1);
    }

    /**
     * 语言包翻译 - 支持多个语言包
     *
     * @access public
     *
     * @param mixed  $lang
     * @param string $package
     *
     * @return string
     */
    public function lang($lang, $package = 'common')
    {
        if (is_string($lang)) {
            return Yii::t($package, $lang);
        }

        if (!is_array($lang)) {
            return null;
        }

        if (is_array(current($lang))) {
            $text = null;
            foreach ($lang as $_lang) {
                $text .= $this->lang($_lang, $package);
            }

            return $text;
        }

        $params = $lang;
        $lang = array_shift($params);

        return Yii::t($package, $lang, $params);
    }

    /**
     * 返回成功提示信息及数据
     *
     * @access public
     *
     * @param mixed  $data    返回数据
     * @param mixed  $lang    成功提示信息
     * @param string $package 语言包
     *
     * @return void
     */
    public function success($data = [], $lang = null, $package = 'common')
    {
        $info = $this->lang($lang, $package);
        $info && Yii::trace($info);

        exit(json_encode([
            'state' => 1,
            'info' => $info,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 返回失败提示信息
     *
     * @access public
     *
     * @param mixed   $lang    成功提示信息
     * @param string  $package 语言包
     * @param integer $state   状态码
     *
     * @return void
     */
    public function fail($lang, $package = 'common', $state = 0)
    {
        $info = $this->lang($lang, $package);
        Yii::info($info);

        exit(json_encode([
            'state' => $state,
            'info' => $info,
            'data' => null
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 验证数据
     *
     * @access public
     *
     * @param array   $params
     * @param array   $rules
     * @param boolean $response
     *
     * @return mixed
     */
    public function validate($params, $rules, $response = true)
    {
        $model = DynamicModel::validateData($params, $rules);

        if ($model->hasErrors()) {
            $error = current($model->getFirstErrors());

            $response && $this->fail($error);

            return $error;
        }

        return true;
    }

    /**
     * 动态实例化模型
     *
     * @access public
     *
     * @param string $table
     * @param string $db
     *
     * @return \service\models\Main
     */
    public function model($table, $db = null)
    {
        $db = $db ?: DB_KAKE;
        $class = '\service\models\\' . $db . '\\' . Helper::underToCamel($table, false);

        if (!class_exists($class)) {
            $this->fail([
                'param illegal',
                'param' => $table
            ], 'common', -1);
        }

        return new $class;
    }

    /**
     * 获取表名
     *
     * @access public
     *
     * @param array $option
     *
     * @return string
     */
    public function getTableName($option)
    {
        if (isset($option['table'])) {
            return $option['table'];
        }

        $controller = Helper::cutString(static::className(), [
            '\^0^desc',
            'Controller^0'
        ]);

        return Helper::camelToUnder($controller, '_');
    }

    /**
     * 获取 get 参数
     *
     * @access public
     * @return array
     */
    public function getParams()
    {
        $params = Yii::$app->request->get();
        Helper::popSome($params, [
            Yii::$app->request->csrfParam,
            'r'
        ]);

        return $params;
    }

    /**
     * 获取详情 - 常用于联表查询
     */
    public function actionDetail()
    {
        $option = $this->getParams();
        $table = $this->getTableName($option);
        $model = $this->model($table);

        $detail = $model->first(function ($ar) use ($table, $model, $option) {
            return $model->handleActiveRecord($ar, $table, $option);
        });

        $this->success($detail);
    }

    /**
     * 获取列表 - 常用于联表查询
     */
    public function actionList()
    {
        $option = $this->getParams();
        $table = $this->getTableName($option);
        $model = $this->model($table);

        $list = $model->all(function ($ar) use ($table, $model, $option) {
            return $model->handleActiveRecord($ar, $table, $option);
        });

        $this->success($list);
    }

    /**
     * 获取模型元信息 (for yii2)
     *
     * @param string $table
     * @param string $db
     */
    public function actionModelMeta($table, $db = null)
    {
        $model = $this->model($table, $db);
        $_model = [];

        $properties = Yii::$app->reflection->getPropertiesName($model, null);
        $methods = Yii::$app->reflection->getMethodsName($model, null);

        foreach ($properties as $item) {
            if (strpos($item, '_') === 0) {
                if (preg_match('/_model$/', $item)) {
                    $targetModel = 'service\models\\' . $model->$item;
                    $item = str_replace('_model', '', $item);
                    $_model[$item] = (new $targetModel)->$item;
                } else {
                    $_model[$item] = $model->$item;
                }
            }
        }

        $_methods = [
            'rules',
            'attributeLabels'
        ];
        foreach ($methods as $item) {
            if (in_array($item, $_methods)) {
                $_model['fn' . ucfirst($item)] = $model->{$item}();
            }
        }

        $this->success($_model);
    }

    /**
     * 处理标签相关的数据 - 相当于外键
     *
     * @access protected
     *
     * @param array   $tagsRecord
     * @param integer $foreignKeyId
     *
     * @return mixed
     * @throws yii\db\Exception
     */
    protected function orderTagsRecord($tagsRecord, $foreignKeyId)
    {
        if (empty($tagsRecord)) {
            return null;
        }

        foreach ($tagsRecord as $field => &$item) {

            $add = empty($item['add']) ? null : $item['add'];
            $del = empty($item['del']) ? null : $item['del'];

            if (empty($add) && empty($del)) {
                continue;
            }

            $model = $this->model($item['table'], $item['db']);
            if (!empty($add)) {
                $addIds = [];
                foreach ($add as $attr) {
                    $_model = clone $model;
                    $attr[$item['foreign_key']] = $foreignKeyId;
                    $_model->attributes = $attr;
                    if (!$_model->save()) {
                        throw new yii\db\Exception(current($_model->getFirstErrors()));
                    }
                    $addIds[] = $_model->id;
                }
                $item['add'] = $addIds;
            }

            if (!empty($del)) {
                $item['del'] = $model::updateAll(['state' => 0], ['id' => $del]);
            }
        }

        return $tagsRecord;
    }

    /**
     * 获取参数
     *
     * @access protected
     * @return array
     */
    protected function getData()
    {
        $data = $this->getParams();

        $add = Helper::parseJsonString(Helper::emptyDefault($data, 'attachment_add')); // 新增的附件
        $del = Helper::parseJsonString(Helper::emptyDefault($data, 'attachment_del')); // 删除的附件
        $tags_record = Helper::parseJsonString(Helper::emptyDefault($data, 'tags_record')); // 标签记录

        Helper::popSome($data, [
            'id',
            'table',
            'attachment_add',
            'attachment_del',
            'where',
        ]);

        return [
            $data,
            compact('add', 'del'),
            $tags_record
        ];
    }

    /**
     * 列出指定表数据
     *
     * @param string $table
     * @param string $db
     */
    public function actionListForBackend($table, $db = null)
    {
        $model = $this->model($table, $db);

        $size = Yii::$app->request->get('size', Yii::$app->params['pagenum']);
        if (empty($size) || $size == 'all') {
            $size = null;
        }

        $options = [
            'select' => Yii::$app->request->get('select'),
            'join' => Yii::$app->request->get('join'),
            'where' => Yii::$app->request->get('where'),
            'order' => Yii::$app->request->get('order')
        ];

        $all = $model->all(function ($list) use ($model, $table, $options) {
            return $model->handleActiveRecord($list, $table, $options);
        }, $size);

        $this->success($all);
    }

    /**
     * 查询指定表指定ID数据
     *
     * @param string $table
     * @param string $db
     */
    public function actionGetForBackend($table, $db = null)
    {
        $model = $this->model($table, $db);

        $options = [
            'select' => Yii::$app->request->get('select'),
            'join' => Yii::$app->request->get('join'),
            'where' => Yii::$app->request->get('where')
        ];

        $record = $model->first(function ($first) use ($model, $table, $options) {
            return $model->handleActiveRecord($first, $table, $options);
        });

        $this->success($record);
    }

    /**
     * 添加指定表数据
     *
     * @param string $table
     * @param string $db
     *
     * @throws yii\db\Exception
     */
    public function actionAddForBackend($table, $db = null)
    {
        $model = $this->model($table, $db);
        list($model->attributes, $attachment, $tagsRecord) = $this->getData();

        $result = $model->trans(function () use ($model, $attachment, $tagsRecord) {
            if (!$model->save()) {
                throw new yii\db\Exception(current($model->getFirstErrors()));
            }

            if (!empty($attachment['add'])) {
                $result = (new Attachment())->updateStateByIds($attachment['add'], $model->state);
                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }
            }

            $this->orderTagsRecord($tagsRecord, $model->id);

            return ['id' => $model->id];
        }, '添加或更新附件状态(若有附件)');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }

    /**
     * 更新指定表指定ID数据
     *
     * @param string $table
     * @param string $where
     * @param string $db
     *
     * @throws yii\db\Exception
     */
    public function actionUpdateForBackend($table, $where, $db = null)
    {
        $model = $this->model($table, $db);

        list($data, $attachment, $tagsRecord) = $this->getData();
        $model->attributes = $data;

        $result = $model->trans(function () use ($model, $where, $attachment, $tagsRecord, $data) {
            $where = Helper::parseJsonString($where);

            $record = $model::findOne($where);
            if (empty($record)) {
                throw new yii\db\Exception('abnormal operation');
            }

            foreach ($data as $field => $value) {
                $record->{$field} = $value;
            }

            if (!$record->save()) {
                throw new yii\db\Exception(current($record->getFirstErrors()));
            }

            if (!empty($attachment['add']) || !empty($attachment['del'])) {

                $attachmentModel = new Attachment();

                $result = $attachmentModel->updateStateByIds($attachment['add'], $record->state);
                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                $result = $attachmentModel->updateStateByIds($attachment['del'], 0);
                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }
            }

            $this->orderTagsRecord($tagsRecord, $record->id);

            return true;
        }, '更新记录和附件状态(若有附件)');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }

    /**
     * 前置记录
     *
     * @param string  $table
     * @param integer $id
     * @param string  $db
     */
    public function actionFrontForBackend($table, $id, $db = null)
    {
        $model = $this->model($table, $db);

        $result = $model->edit(['id' => $id], ['update_time' => date('Y-m-d H:i:s')], $model);
        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }

    /**
     * @inheritDoc
     */
    public function __call($name, $params)
    {
        $methods = [
            'dump',
            'cache'
        ];
        if (in_array($name, $methods)) {
            return (new Main())->{$name}(...$params);
        }

        return parent::__call($name, $params);
    }
}