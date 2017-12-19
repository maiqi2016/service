<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "phone_captcha".
 *
 * @property integer $id
 * @property string  $phone
 * @property integer $captcha
 * @property integer $type
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class PhoneCaptcha extends General
{
    /**
     * @var array Field
     */
    public $_type = [
        1 => '后台登录',
        2 => '填写订单联系人',
        3 => 'SSO',
        4 => '分销商抽奖参与',
    ];

    /**
     * @var array Type 类型对应的验证码长度
     */
    public $_type_captcha_length = [
        1 => 6,
        2 => 4,
        3 => 4,
        4 => 4,
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'phone_captcha';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                ['type'],
                'integer'
            ],
            [
                [
                    'phone',
                    'type'
                ],
                'unique',
                'targetAttribute' => [
                    'phone',
                    'type'
                ]
            ],
        ], $this->_rule_phone, $this->_rule_captcha, $this->_rule_state, $this->_rule_add_time, $this->_rule_update_time);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'phone' => Yii::t('database', 'Phone'),
            'captcha' => Yii::t('database', 'Captcha'),
            'type' => Yii::t('database', 'Type'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }

    /**
     * 使验证码有效
     *
     * @access public
     *
     * @param string  $phone
     * @param integer $captcha
     * @param string  $type
     * @param integer $timeout
     *
     * @return mixed
     */
    public function validCaptcha($phone, $captcha, $type, $timeout)
    {
        $result = $this->updateOrInsert([
            'phone' => $phone,
            'type' => $type
        ], [
            'captcha' => $captcha,
            'state' => 1
        ], function ($record) use ($timeout) {

            Yii::trace('判断上次发送短信至今是否超过冷却时间');

            $timeLong = TIME - strtotime($record->update_time);
            if ($timeLong < $timeout) {
                $second = $timeout - $timeLong;

                return $this->result(Yii::t('common', 'try again after moment', ['second' => $second]));
            }

            return true;
        });

        return $result;
    }

    /**
     * 校验验证码是否有效
     *
     * @access public
     *
     * @param string  $phone
     * @param string  $captcha
     * @param string  $type
     * @param integer $timeout
     *
     * @return boolean
     */
    public function checkCaptcha($phone, $captcha, $type, $timeout)
    {
        $record = static::find()->where([
            'phone' => $phone,
            'captcha' => $captcha,
            'type' => $type,
            'state' => 1,
        ])->andWhere([
            '>=',
            'update_time',
            date('Y-m-d H:i:s', TIME - $timeout)
        ])->exists();

        return $record ? true : false;
    }
}
