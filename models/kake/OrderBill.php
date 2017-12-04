<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "order_bill".
 *
 * @property integer $id
 * @property integer $order_sub_id
 * @property string  $courier_number
 * @property string  $courier_company
 * @property string  $invoice_title
 * @property string  $tax_number
 * @property string  $address
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class OrderBill extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order_bill';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'order_sub_id',
                    'address'
                ],
                'required'
            ],
            [
                ['order_sub_id'],
                'unique'
            ],
            [
                ['order_sub_id'],
                'integer'
            ],
            [
                [
                    'courier_number',
                    'courier_company',
                    'address'
                ],
                'string',
                'max' => 64
            ],
            [
                ['invoice_title'],
                'string',
                'max' => 128
            ],
            [
                ['tax_number'],
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
            'order_sub_id' => Yii::t('database', 'Order Sub ID'),
            'courier_number' => Yii::t('database', 'Courier Number'),
            'courier_company' => Yii::t('database', 'Courier Company'),
            'invoice_title' => Yii::t('database', 'Invoice Title'),
            'tax_number' => Yii::t('database', 'Tax Number'),
            'address' => Yii::t('database', 'Address'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}