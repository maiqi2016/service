<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "producer_log".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $producer_id
 * @property integer $product_id
 * @property integer $log_amount_in
 * @property integer $log_amount_out
 * @property integer $log_sub_counter
 * @property string  $log_commission_quota
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProducerLog extends General
{
    /**
     * @var array Field
     */
    public $_state = [
        0 => '已结算',
        1 => '未结算',
    ];

    /**
     * @var array Field
     */
    public $_type_model = 'kake\ProductProducer';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'producer_log';
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
                    'producer_id',
                    'product_id'
                ],
                'required'
            ],
            [
                [
                    'user_id',
                    'producer_id',
                    'product_id',
                    'log_amount_in',
                    'log_amount_out',
                    'log_sub_counter',
                ],
                'integer'
            ],
            [
                ['log_commission_quota'],
                'string',
                'max' => 16
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
            'producer_id' => Yii::t('database', 'Producer ID'),
            'product_id' => Yii::t('database', 'Product ID'),
            'log_amount_in' => Yii::t('database', 'Log Amount In'),
            'log_amount_out' => Yii::t('database', 'Log Amount Out'),
            'log_sub_counter' => Yii::t('database', 'Log Sub Counter'),
            'log_commission_quota' => Yii::t('database', 'Log Commission Quota'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}