<?php

namespace service\controllers\kake;

use service\controllers\MainController;
use Oil\src\Helper;
use service\models\kake\PhoneCaptcha;
use yii;

/**
 * PhoneCaptcha controller
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-01-12 14:00:57
 */
class PhoneCaptchaController extends MainController
{
    /**
     * 发送短信验证码
     *
     * @access public
     *
     * @param string $phone
     * @param string $type
     *
     * @return void
     */
    public function actionSend($phone, $type)
    {
        if (empty($phone)) {
            $this->fail([
                'param illegal',
                'param' => 'phone'
            ]);
        }

        $phoneCaptchaModel = new PhoneCaptcha();
        if (!isset($phoneCaptchaModel->_type[$type])) {
            $this->fail([
                'param illegal',
                'param' => 'type'
            ]);
        }

        $rulePhone = $phoneCaptchaModel->_rule_phone;
        unset($rulePhone['phone_unique']);

        $this->validate([
            'phone' => $phone
        ], $rulePhone);

        $length = $phoneCaptchaModel->_type_captcha_length[$type];

        $captcha = Helper::randString($length, 'number');
        $result = $phoneCaptchaModel->validCaptcha($phone, $captcha, $type, Yii::$app->params['captcha_send_again']);

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        // Call DH3T SMS
        $tpl = Yii::$app->params['sms_tpl_' . str_replace('-', '_', $type)];
        $content = sprintf($tpl, $captcha, ceil(Yii::$app->params['captcha_timeout'] / 60));
        $response = $this->callSmsApi($phone, $content);

        if (!empty($response['result'])) {
            $this->fail('burst error, please contact the administrator');
        }

        $this->success();
    }

    /**
     * Boom phone number
     *
     * @access public
     *
     * @param string $phone
     *
     * @return void
     */
    public function actionBoom($phone)
    {
        // Call DH3T SMS
        $tpl = Yii::$app->params['sms_tpl_1'];

        $captcha = Helper::randString(4, 'number');
        $content = sprintf($tpl, $captcha, 10);
        $response = $this->callSmsApi($phone, $content);

        if ($response['result']) {
            $this->fail('burst error, please contact the administrator');
        }

        $this->success();
    }
}