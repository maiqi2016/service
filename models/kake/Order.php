<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "order".
 *
 * @property integer $id
 * @property string  $order_number
 * @property integer $product_id
 * @property integer $user_id
 * @property integer $price
 * @property integer $order_contacts_id
 * @property integer $payment_method
 * @property integer $payment_state
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class Order extends General
{
    /**
     * @var array Field
     */
    public $_payment_method = [
        0 => '微信',
        1 => '支付宝'
    ];

    /**
     * @var array Field
     */
    public $_payment_state = [
        0 => '待支付',
        1 => '已支付',
        2 => '支付失败',
    ];

    /**
     * @var array Field
     */
    public $_state = [
        0 => '已取消',
        1 => '正常',
        2 => '已完成'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'order_number',
                    'product_id',
                    'user_id',
                    'price',
                    'order_contacts_id',
                    'payment_method'
                ],
                'required'
            ],
            [
                [
                    'product_id',
                    'user_id',
                    'price',
                    'order_contacts_id',
                    'payment_method',
                    'payment_state'
                ],
                'integer'
            ],
            [
                ['order_number'],
                'string',
                'max' => 14
            ],
            [
                ['order_number'],
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
            'order_number' => Yii::t('database', 'Order Number'),
            'product_id' => Yii::t('database', 'Product ID'),
            'user_id' => Yii::t('database', 'User ID'),
            'price' => Yii::t('database', 'Price'),
            'order_contacts_id' => Yii::t('database', 'Order Contacts ID'),
            'payment_method' => Yii::t('database', 'Payment Method'),
            'payment_state' => Yii::t('database', 'Payment State'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}