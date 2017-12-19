<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "activity_producer_code".
 *
 * @property integer $id
 * @property integer $activity_producer_prize_id
 * @property integer $producer_id
 * @property integer $user_id
 * @property integer $from_user_id
 * @property string  $phone
 * @property integer $code
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ActivityProducerCode extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity_producer_code';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'activity_producer_prize_id',
                    'producer_id',
                    'user_id',
                    'phone',
                    'code'
                ],
                'required'
            ],
            [
                [
                    'activity_producer_prize_id',
                    'producer_id',
                    'user_id',
                    'from_user_id',
                    'code'
                ],
                'integer'
            ],
            [
                ['phone'],
                'string',
                'max' => 32
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
            'activity_producer_prize_id' => Yii::t('database', 'Activity Producer Prize ID'),
            'producer_id' => Yii::t('database', 'Producer ID'),
            'user_id' => Yii::t('database', 'User ID'),
            'from_user_id' => Yii::t('database', 'From User ID'),
            'phone' => Yii::t('database', 'Phone'),
            'code' => Yii::t('database', 'Code'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
