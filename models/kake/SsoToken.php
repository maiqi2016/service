<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "sso_token".
 *
 * @property integer $id
 * @property integer $sso_code_id
 * @property string  $token
 * @property string  $sign
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class SsoToken extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sso_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'sso_code_id',
                    'token',
                    'sign'
                ],
                'required'
            ],
            [
                ['sso_code_id'],
                'integer'
            ],
            [
                [
                    'add_time',
                    'update_time'
                ],
                'safe'
            ],
            [
                ['token'],
                'string',
                'max' => 256
            ],
            [
                ['sign'],
                'string',
                'max' => 32
            ],
            [
                ['token'],
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
            'sso_code_id' => Yii::t('database', 'Sso Code ID'),
            'token' => Yii::t('database', 'Token'),
            'sign' => Yii::t('database', 'Sign'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}