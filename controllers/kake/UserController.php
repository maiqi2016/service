<?php

namespace service\controllers\kake;

use service\controllers\MainController;
use Oil\src\Helper;
use service\models\kake\AdminAuth;
use service\models\kake\LoginLog;
use service\models\kake\PhoneCaptcha;
use service\models\kake\User;
use yii;

/**
 * User controller
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-01-12 12:24:29
 */
class UserController extends MainController
{
    /**
     * 用户登录验证
     *
     * @access public
     * @return void
     */
    public function actionLoginCheck()
    {
        $params = $this->getParams();

        Yii::info('验证参数 ' . json_encode($params));
        $userModel = new User();
        $userModel->attributes = $params;
        if (!$userModel->validate()) {
            $error = current($userModel->getFirstErrors());

            $this->fail($error);
        }

        /**
         * @var $phone    string
         * @var $extra    string
         * @var $captcha  string
         * @var $type     string
         */
        extract($params);

        $captcha = (new PhoneCaptcha())->checkCaptcha($phone, $captcha, $type, Yii::$app->params['captcha_timeout']);
        if (!$captcha) {
            Yii::info('验证码错误, phone:' . $phone . ', password:' . $captcha);
            $this->fail('wrong user or password');
        }

        $user = $userModel->first(['phone' => $phone], Yii::$app->params['use_cache']);
        if (!$user) {
            /*
             Yii::info('用户名错误, phone:' . $phone);
             $this->fail('wrong user or password');
             */

            $extra = Helper::parseJsonString(base64_decode($extra));
            if (!is_array($extra)) { // newly
                $userModel->attributes = ['phone' => $phone];
                $userModel->insert();
            } else { // update
                /** @var array $extra */
                $userModel->edit($extra, ['phone' => $phone]);
            }

            $user = $userModel->first(['phone' => $phone], Yii::$app->params['use_cache']);
        }

        if (!$user['state']) {
            $rootUserIds = explode(',', Yii::$app->params['private']['root_user_ids']);
            if (!in_array($user['id'], $rootUserIds)) {
                Yii::info('用户被冻结, phone:' . $phone);
                $this->fail('wrong user or password');
            }
        }

        if (1 == $type && empty($user['manager'])) {
            Yii::info('非管理员登录, phone:' . $phone);
            $this->fail('wrong user or password');
        }

        $this->success($user);
    }

    /**
     * 记录登录日志
     *
     * @access public
     *
     * @param integer $id
     * @param string  $ip
     * @param integer $type
     *
     * @return void
     */
    public function actionLoginLog($id, $ip, $type)
    {
        $logModel = new LoginLog();
        $logModel->add([
            'user_id' => $id,
            'type' => Helper::getKeyByValue($logModel->_type, $type),
            'ip' => $ip
        ]);

        $this->success();
    }

    /**
     * 根据微信授权信息处理用户数据
     *
     * @access public
     * @return void
     */
    public function actionGetWithWeChat()
    {
        $user = $this->getParams();
        $user = Helper::pullSome($user, [
            'nickname' => 'username',
            'openid',
            'sex',
            'country',
            'province',
            'city',
            'headimgurl' => 'head_img_url'
        ]);

        $userModel = new User();
        $record = $userModel::find()->where(['openid' => $user['openid']])->asArray()->one();

        // exists
        if ($record) {
            if (!$record['state']) {
                $this->fail('the account has been frozen');
            }
            $this->success($record);
        }

        // register
        $userModel->attributes = $user;
        if (!$res = $userModel->insert()) {
            $this->fail(current($userModel->getFirstErrors()));
        }
        $user = $userModel::find()->where(['openid' => $user['openid']])->asArray()->one();

        $this->success($user);
    }

    /**
     * 编辑用户后台（管理员）权限
     *
     * @access public
     * @return void
     */
    public function actionEditAuth()
    {
        $model = new AdminAuth();
        $add = (array) Helper::parseJsonString(Yii::$app->request->get('add'));
        $del = (array) Helper::parseJsonString(Yii::$app->request->get('del'));

        foreach ($add as $item) {
            list($controller, $action) = explode('/', $item);
            $model->updateOrInsert([
                'controller' => $controller,
                'action' => $action,
                'user_id' => Yii::$app->request->get('user_id')
            ], ['state' => 1]);
        }

        foreach ($del as $item) {
            list($controller, $action) = explode('/', $item);
            $model::updateAll(['state' => 0], [
                'controller' => $controller,
                'action' => $action,
                'user_id' => Yii::$app->request->get('user_id')
            ]);
        }

        $this->success();
    }
}