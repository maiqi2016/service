<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "producer_quota".
 *
 * @property integer $id
 * @property integer $producer_id
 * @property integer $quota
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProducerQuota extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'producer_quota';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'producer_id',
                    'quota'
                ],
                'required'
            ],
            [
                [
                    'producer_id',
                    'quota'
                ],
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
            'producer_id' => Yii::t('database', 'Producer ID'),
            'quota' => Yii::t('database', 'Quota'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}