<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "order_sub".
 *
 * @property integer $id
 * @property integer $order_id
 * @property integer $product_package_id
 * @property integer $price
 * @property string  $check_in_name
 * @property string  $check_in_phone
 * @property string  $check_in_time
 * @property string  $conformation_number
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class OrderSub extends General
{
    /**
     * @var array Field
     */
    public $_state = [
        0 => '未使用',
        1 => '预约中',
        2 => '待入住',
        3 => '退款申请中',
        4 => '已操作退款',
        5 => '已入住',
        6 => '已完成',
    ];

    /**
     * @var array Field
     */
    public $_payment_state_model = 'kake\Order';

    /**
     * @var array Field
     */
    public $_sold_state_model = 'kake\OrderSoldCode::state';

    /**
     * @var array Field
     */
    public $_type_model = 'kake\OrderSoldCode';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order_sub';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $state = $this->_rule_state;
        $state['state_default']['value'] = 0;
        $state['state_range']['range'] = range(0, 6);

        return array_merge([
            [
                [
                    'order_id',
                    'product_package_id',
                    'price'
                ],
                'required'
            ],
            [
                [
                    'order_id',
                    'product_package_id',
                    'price'
                ],
                'integer'
            ],
            [
                ['check_in_time'],
                'safe'
            ],
            [
                [
                    'check_in_name',
                    'check_in_phone',
                    'conformation_number'
                ],
                'string',
                'max' => 32
            ],
        ], $state, $this->_rule_add_time, $this->_rule_update_time);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'order_id' => Yii::t('database', 'Order ID'),
            'product_package_id' => Yii::t('database', 'Product Package ID'),
            'price' => Yii::t('database', 'Price'),
            'check_in_name' => Yii::t('database', 'Check In Name'),
            'check_in_phone' => Yii::t('database', 'Check In Phone'),
            'check_in_time' => Yii::t('database', 'Check In Time'),
            'conformation_number' => Yii::t('database', 'Conformation Number'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}