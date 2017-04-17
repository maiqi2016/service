<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "login_log".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $type
 * @property string  $ip
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class LoginLog extends General
{
    /**
     * @var array Field
     */
    public $_type = [
        0 => 'login',
        1 => 'register',
        2 => 'we-chat-login',
        3 => 'we-chat-bind',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'login_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'user_id',
                    'type',
                    'ip'
                ],
                'required'
            ],
            [
                [
                    'user_id',
                    'type'
                ],
                'integer'
            ],
            [
                ['ip'],
                'string',
                'max' => 15
            ]
        ], $this->_rule_state, $this->_rule_add_time, $this->_rule_update_time);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'user_id' => Yii::t('database', 'User ID'),
            'type' => Yii::t('database', 'Type'),
            'ip' => Yii::t('database', 'Ip'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
