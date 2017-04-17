<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "app_log".
 *
 * @property integer $id
 * @property integer $level
 * @property string  $category
 * @property double  $log_time
 * @property string  $prefix
 * @property string  $message
 */
class AppLog extends General
{
    /**
     * @var array Level
     */
    public $_level = [
        1 => 'Error',
        2 => 'Warning',
        4 => 'Info',
        8 => 'Trace'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'app_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                ['level'],
                'integer'
            ],
            [
                ['log_time'],
                'number'
            ],
            [
                [
                    'prefix',
                    'message'
                ],
                'string'
            ],
            [
                ['category'],
                'string',
                'max' => 255
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
            'level' => Yii::t('database', 'Level'),
            'category' => Yii::t('database', 'Category'),
            'log_time' => Yii::t('database', 'Log Time'),
            'prefix' => Yii::t('database', 'Prefix'),
            'message' => Yii::t('database', 'Message'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
