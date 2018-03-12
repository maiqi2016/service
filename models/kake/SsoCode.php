<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "sso_code".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string  $code
 * @property string  $sign
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class SsoCode extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sso_code';
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
                    'code',
                    'sign'
                ],
                'required'
            ],
            [
                ['user_id'],
                'integer'
            ],
            [
                [
                    'code',
                    'sign'
                ],
                'string',
                'max' => 32
            ],
            [
                ['user_id'],
                'unique'
            ],
            [
                ['code'],
                'unique'
            ],
        ], $this->_rule_state, $this->_rule_state, $this->_rule_update_time);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'user_id' => Yii::t('database', 'User ID'),
            'code' => Yii::t('database', 'Sso Code'),
            'sign' => Yii::t('database', 'Sign'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}