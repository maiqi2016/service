<?php

namespace service\controllers\kake;

use service\controllers\MainController;
use service\components\Helper;
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
     * 调用发送短信接口
     *
     * @access private
     *
     * @param string $phone
     * @param string $content
     *
     * @return mixed
     */
    private function callSmsApi($phone, $content)
    {
        $conf = Yii::$app->params;
        $response = Yii::$app->api->fields('account', 'password')->auth($conf['sms_id'], md5($conf['sms_secret']))->host($conf['sms_host'])->service('json/sms/Submit')->params([
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

        return $response;
    }

    /**
     * 发送短信验证码
     *
     * @param string $phone
     * @param string $type
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

        if ($response['result']) {
            Yii::error(json_encode($response, JSON_UNESCAPED_UNICODE));
            $this->fail('burst error, please contact the administrator');
        }

        $this->success();
    }
}