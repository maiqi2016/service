<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "order_sold_code".
 *
 * @property integer $id
 * @property integer $order_sub_id
 * @property integer $product_supplier_id
 * @property string  $code
 * @property integer $type
 * @property string  $remark
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class OrderSoldCode extends General
{
    /**
     * @var array Field
     */
    public $_type = [
        0 => '常规',
        1 => '批量生成',
        2 => '批量导入',
    ];

    /**
     * @var array Field
     */
    public $_state = [
        0 => '已失效',
        1 => '未核销',
        2 => '已核销',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order_sold_code';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $state = $this->_rule_state;
        $state['state_range']['range'] = range(0, 2);

        return array_merge([
            [
                [
                    'order_sub_id',
                    'product_supplier_id',
                    'code',
                    'remark'
                ],
                'required'
            ],
            [
                [
                    'order_sub_id',
                    'product_supplier_id',
                    'type'
                ],
                'integer'
            ],
            [
                ['code'],
                'string',
                'max' => 12
            ],
            [
                ['order_sub_id'],
                'unique'
            ],
            [
                ['code'],
                'unique'
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
            'order_sub_id' => Yii::t('database', 'Order Sub ID'),
            'product_supplier_id' => Yii::t('database', 'Product Supplier ID'),
            'code' => Yii::t('database', 'Code'),
            'type' => Yii::t('database', 'Type'),
            'remark' => Yii::t('database', 'Remark'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
