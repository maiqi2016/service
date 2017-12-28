<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "activity_producer_prize".
 *
 * @property integer $id
 * @property string  $from
 * @property string  $to
 * @property integer $product_id
 * @property integer $standard_code_number
 * @property string  $description
 * @property integer $win_code
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ActivityProducerPrize extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity_producer_prize';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'from',
                    'to',
                    'product_id',
                    'description'
                ],
                'required'
            ],
            [
                [
                    'from',
                    'to'
                ],
                'safe'
            ],
            [
                [
                    'product_id',
                    'standard_code_number',
                    'win_code'
                ],
                'integer'
            ],
            [
                ['description'],
                'string'
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
            'from' => Yii::t('database', 'From'),
            'to' => Yii::t('database', 'To'),
            'product_id' => Yii::t('database', 'Product ID'),
            'standard_code_number' => Yii::t('database', 'Standard Code Number'),
            'description' => Yii::t('database', 'Description'),
            'win_code' => Yii::t('database', 'Win Code'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
