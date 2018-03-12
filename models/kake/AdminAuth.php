<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "admin_auth".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string  $controller
 * @property string  $action
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class AdminAuth extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'admin_auth';
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
                    'controller',
                    'action'
                ],
                'required'
            ],
            [
                ['user_id'],
                'integer'
            ],
            [
                [
                    'controller',
                    'action'
                ],
                'string',
                'max' => 64
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
            'user_id' => Yii::t('database', 'User ID'),
            'controller' => Yii::t('database', 'Controller'),
            'action' => Yii::t('database', 'Action'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
