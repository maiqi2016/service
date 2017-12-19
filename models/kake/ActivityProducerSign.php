<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "activity_producer_sign".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ActivityProducerSign extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity_producer_sign';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                ['user_id'],
                'required'
            ],
            [
                ['user_id'],
                'integer'
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
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
