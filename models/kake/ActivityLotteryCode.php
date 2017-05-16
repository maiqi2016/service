<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "activity_lottery_code".
 *
 * @property integer $id
 * @property string  $openid
 * @property integer $company
 * @property string  $nickname
 * @property string  $real_name
 * @property string  $phone
 * @property string  $code
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ActivityLotteryCode extends General
{
    /**
     * @var array Fields
     */
    public $_company = [
        1 => '迈骐',
        2 => '喀客'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity_lottery_code';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'openid',
                    'company',
                    'nickname',
                    'code'
                ],
                'required'
            ],
            [
                ['company'],
                'integer'
            ],
            [
                [
                    'openid',
                    'nickname',
                    'real_name',
                    'phone'
                ],
                'string',
                'max' => 32
            ],
            [
                ['code'],
                'string',
                'max' => 16
            ],
        ], $this->_rule_state, $this->_rule_add_time, $this->_rule_update_time);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'openid' => Yii::t('database', 'Openid'),
            'company' => Yii::t('database', 'Company'),
            'nickname' => Yii::t('database', 'Nickname'),
            'real_name' => Yii::t('database', 'Real Name'),
            'phone' => Yii::t('database', 'Phone'),
            'code' => Yii::t('database', 'Code'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}