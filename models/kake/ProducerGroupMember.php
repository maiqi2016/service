<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "producer_group_member".
 *
 * @property integer $id
 * @property integer $producer_id
 * @property integer $producer_group_id
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProducerGroupMember extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'producer_group_member';
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
                    'producer_group_id'
                ],
                'required'
            ],
            [
                [
                    'producer_id',
                    'producer_group_id'
                ],
                'integer'
            ],
            [
                [
                    'producer_id',
                    'producer_group_id'
                ],
                'unique',
                'targetAttribute' => [
                    'producer_id',
                    'producer_group_id'
                ]
            ],
        ], $this->_rule_add_time, $this->_rule_update_time, $this->_rule_state);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'producer_id' => Yii::t('database', 'Producer ID'),
            'producer_group_id' => Yii::t('database', 'Producer Group ID'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
