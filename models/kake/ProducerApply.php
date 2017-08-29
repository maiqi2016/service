<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "producer_apply".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string  $phone
 * @property string  $name
 * @property integer $attachment_id
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProducerApply extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'producer_apply';
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
                    'phone',
                    'name',
                    // 'attachment_id'
                ],
                'required'
            ],
            [
                [
                    'user_id',
                    'attachment_id'
                ],
                'integer'
            ],
            [
                [
                    'phone',
                    'name'
                ],
                'string',
                'max' => 32
            ],
            [
                ['user_id'],
                'unique'
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
            'phone' => Yii::t('database', 'Phone'),
            'name' => Yii::t('database', 'Name'),
            'attachment_id' => Yii::t('database', 'Attachment ID'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}