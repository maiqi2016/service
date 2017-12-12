<?php

namespace service\controllers;

use Oil\src\Helper;
use service\models\kake\Attachment;
use service\models\kake\Config;
use service\models\Main;
use service\models\service\User as ServiceUser;
use yii;
use yii\base\DynamicModel;
use yii\web\Controller;
use yii\web\Response;

/**
 * Main controller
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-01-10 14:22:05
 * @method mixed cache($key, $fetchFn, $time = null, $dependent = null)
 * @method mixed dump($var, $strict = false, $exit = true)
 */
class MainController extends Controller
{
    /**
     * @var array user
     */
    protected $user;

    protected $validated = false;

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
        $useCache = $this->identityVerification($_GET);

        $config = (new Config())->listConfigKVP();

        $config['use_cache'] = $useCache;
        Yii::$app->params = array_merge(Yii::$app->params, $config);

        // TODO auth validate

        return parent::beforeAction($action);
    }

    /**
     * Api user verification
     *
     * @access public
     *
     * @param array $params
     *
     * @return boolean
     */
    public function identityVerification(&$params)
    {
        // use cache
        $useCache = true;
        if (isset($params['app_cache']) && $params['app_cache'] == 'no') {
            $useCache = false;
        }

        if ($this->validated) {
            return $useCache;
        }

        Yii::info(json_encode($params, JSON_UNESCAPED_UNICODE));
        $api = Helper::popOne($params, 'r');

        // params error
        if (empty($params['app_sign'])) {
            $this->fail('api parameter validation failed', 'common', -1, 412);
        }

        // validate sign
        if (!Helper::validateSign($params, 'app_sign')) {
            $this->fail([
                'signature verification failed',
                'api' => $params['app_api']
            ], 'common', -1, 422);
        }

        // language
        if (isset($params['app_lang'])) {
            Yii::$app->language = $params['app_lang'];
        }

        // empty user id or password
        if (empty($params['app_id']) || empty($params['app_secret'])) {
            $this->fail('account validation failed', 'common', -1, 412);
        }

        // user exists
        $user = (new ServiceUser())->first([
            'app_id' => $params['app_id'],
            'app_secret' => $params['app_secret'],
            'state' => 1
        ], Yii::$app->params['use_cache']);

        if (empty($user)) {
            $this->fail('account validation failed', 'common', -1, 403);
        }

        $this->user = (object) Helper::pullSome($user, [
            'id',
            'type',
            'app',
            'remark'
        ]);

        $this->validated = true;

        // unset var
        Helper::popSome($params, [
            'app_api',
            'app_id',
            'app_secret',
            'app_lang',
            'app_cache',
            'app_sign'
        ]);
        $params['r'] = $api;

        return $useCache;
    }

    /**
     * Common error handler
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
     * Translate lang
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
     * Call method cross namespace
     *
     * @access public
     *
     * @param string  $controller
     * @param string  $namespace
     * @param boolean $new
     *
     * @return mixed
     */
    public function controller($controller, $namespace = 'kake', $new = true)
    {
        if (!strpos($controller, 'Controller')) {
            $controller = Helper::underToCamel($controller, false, '-') . 'Controller';
        }
        $class = '\service\controllers\\' . $namespace . '\\' . $controller;

        if (!$new) {
            return $class;
        }

        return Helper::singleton($class, function () use ($class) {
            return new $class($this->id, $this->module);
        });
    }

    /**
     * Response message about success
     *
     * @access public
     *
     * @param mixed  $data
     * @param mixed  $lang
     * @param string $package
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
     * Response message about fail
     *
     * @access public
     *
     * @param mixed   $lang
     * @param string  $package
     * @param integer $statusCode
     *
     * @return void
     */
    public function fail($lang, $package = 'common', $state = 0, $statusCode = 400)
    {
        $info = $this->lang($lang, $package);
        Yii::info($info);

        header("HTTP/1.1 {$statusCode}" . Response::$httpStatuses[$statusCode]);
        exit(json_encode([
            'state' => $state,
            'info' => $info,
            'data' => null
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Validate data
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
     * Register get model
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
        static $pool = [];

        $db = $db ?: DB_KAKE;
        $key = $db . '.' . $table;

        if (!isset($pool[$key])) {
            $class = '\service\models\\' . $db . '\\' . Helper::underToCamel($table, false);

            if (!class_exists($class)) {
                $this->fail([
                    'param illegal',
                    'param' => $table
                ], 'common', -1, 404);
            }

            $pool[$key] = new $class;
        }

        return $pool[$key];
    }

    /**
     * Get table name
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
     * Params about $_GET
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

        array_walk($params, function (&$value) {
            if (is_numeric($value)) {
                $value = (string) $value;
            } else {
                $value = Helper::parseJsonString($value);
            }
        });

        return $params;
    }

    /**
     * Common for find record
     *
     * @access public
     * @return void
     */
    public function actionDetail()
    {
        $option = $this->getParams();
        $table = $this->getTableName($option);
        $model = $this->model($table);

        $detail = $model->first(function ($ar) use ($table, $model, $option) {
            return $model->handleActiveRecord($ar, $table, $option);
        }, Yii::$app->params['use_cache']);

        $this->success($detail);
    }

    /**
     * Common for select record
     *
     * @access public
     * @return void
     */
    public function actionList()
    {
        $option = $this->getParams();
        $table = $this->getTableName($option);
        $model = $this->model($table);

        $list = $model->all(function ($ar) use ($table, $model, $option) {
            return $model->handleActiveRecord($ar, $table, $option);
        }, null, Yii::$app->params['use_cache']);

        $this->success($list);
    }

    /**
     * Common insert record
     *
     * @access public
     *
     * @param string $table
     * @param string $db
     *
     * @return void
     */
    public function actionNewly($table, $db = null)
    {
        $model = $this->model($table, $db);

        $result = $model->add(current($this->getData()));
        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }

    /**
     * Common update record
     *
     * @access public
     *
     * @param string $table
     * @param string $where
     * @param string $db
     *
     * @return void
     */
    public function actionEdit($table, $where, $db = null)
    {
        $model = $this->model($table, $db);
        $where = Helper::parseJsonString($where);

        $result = $model->edit($where, current($this->getData()));
        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success(true);
    }

    /**
     * Common insert or update record
     *
     * @access public
     *
     * @param string $table
     * @param string $where
     * @param string $db
     *
     * @return void
     */
    public function actionNewlyOrEdit($table, $where, $db = null)
    {
        $model = $this->model($table, $db);
        $where = Helper::parseJsonString($where);

        $result = $model->updateOrInsert($where, current($this->getData()));
        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }

    /**
     * Get meta about model (for yii2)
     *
     * @access public
     *
     * @param string $table
     * @param string $db
     *
     * @return void
     */
    public function actionModelMeta($table, $db = null)
    {
        $model = $this->model($table, $db);
        $_model = [];

        $properties = Yii::$app->oil->reflection->getPropertiesName($model, null);
        $methods = Yii::$app->oil->reflection->getMethodsName($model, null);

        foreach ($properties as $item) {
            if (strpos($item, '_') !== 0) {
                continue;
            }

            if (!preg_match('/_model$/', $item)) {
                $_model[$item] = $model->$item;
                continue;
            }

            $field = null;
            if (strpos($model->$item, '::')) {
                list($model->$item, $field) = explode('::', $model->$item);
            }

            $targetModel = 'service\models\\' . $model->$item;
            $item = str_replace('_model', '', $item);
            $field = isset($field) ? '_' . $field : $item;

            $_model[$item] = (new $targetModel)->$field;
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
     * Common handler data about relation table like foreign key
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
     * Get data
     *
     * @access protected
     * @return array
     */
    protected function getData()
    {
        $data = $this->getParams();

        $add = Helper::emptyDefault($data, 'attachment_add');
        $del = Helper::emptyDefault($data, 'attachment_del');
        $tags_record = Helper::emptyDefault($data, 'tags_record');

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
     * Common select record for backend
     *
     * @access public
     *
     * @param string $table
     * @param string $db
     *
     * @return void
     */
    public function actionListForBackend($table, $db = null)
    {
        $model = $this->model($table, $db);

        $size = Yii::$app->request->get('size', Yii::$app->params['pagenum']);
        empty($size) && $size = null;

        $options = $this->getParams();
        $all = $model->all(function ($list) use ($model, $table, $options) {
            return $model->handleActiveRecord($list, $table, $options);
        }, $size, Yii::$app->params['use_cache']);

        $this->success($all);
    }

    /**
     * Common find record for backend
     *
     * @access public
     *
     * @param string $table
     * @param string $db
     *
     * @return void
     */
    public function actionGetForBackend($table, $db = null)
    {
        $model = $this->model($table, $db);

        $options = $this->getParams();
        $record = $model->first(function ($first) use ($model, $table, $options) {
            return $model->handleActiveRecord($first, $table, $options);
        }, Yii::$app->params['use_cache']);

        $this->success($record);
    }

    /**
     * Common insert record for backend
     *
     * @access public
     *
     * @param string $table
     * @param string $db
     *
     * @return void
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
                (new Attachment())->updateStateByIds($attachment['add'], $model->state);
            }

            $this->orderTagsRecord($tagsRecord, $model->id);

            return ['id' => $model->id];
        }, 'insert record and attachment');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }

    /**
     * Common update record for backend
     *
     * @access public
     *
     * @param string $table
     * @param string $where
     * @param string $db
     *
     * @return void
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
                $attachmentModel->updateStateByIds($attachment['add'], $record->state);
                $attachmentModel->updateStateByIds($attachment['del'], 0);
            }

            $this->orderTagsRecord($tagsRecord, $record->id);

            return true;
        }, 'update record and attachment');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }

    /**
     * Common front record for backend
     *
     * @access public
     *
     * @param string  $table
     * @param integer $id
     * @param string  $db
     *
     * @return void
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
     * Clear cache
     *
     * @access public
     * @return void
     */
    public function actionClearCache()
    {
        Yii::$app->cache->flush();
        $this->success();
    }

    /**
     * Call api for SMS
     *
     * @access public
     *
     * @param string $phone
     * @param string $content
     *
     * @return mixed
     */
    public function callSmsApi($phone, $content)
    {
        $conf = Yii::$app->params;

        $response = Yii::$app->oil->api->fields('account', 'password')->auth($conf['sms_id'], md5($conf['sms_secret']))->host($conf['sms_host'])->service('json/sms/Submit')->params([
            'phones' => $phone,
            'content' => $content,
            'sign' => $conf['sms_sign'],
            'sendtime' => null
        ])->optionsHandler(function ($options, $params) {
            $options[CURLOPT_POSTFIELDS] = json_encode($params);
            $options[CURLOPT_HTTPHEADER] = [
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen(json_encode($params))
            ];

            return $options;
        })->request();

        if (!empty($response['info'])) {
            $response['result'] = $response['info'];
        }

        Yii::trace('SMS Info：' . $phone . ', ' . $content);

        if (!empty($response['result'])) {
            Yii::error('SMS Error: ' . json_encode($response, JSON_UNESCAPED_UNICODE));
        }

        return $response;
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
