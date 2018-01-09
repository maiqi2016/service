<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product_package".
 *
 * @property integer $id
 * @property integer $product_id
 * @property string  $name
 * @property integer $base_price
 * @property integer $price
 * @property integer $bidding
 * @property integer $sort
 * @property integer $purchase_limit
 * @property string  $info
 * @property integer $product_supplier_id
 * @property string  $supplier_contact
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProductPackage extends General
{
    /**
     * @var array Fields
     */
    public $_bidding = [
        0 => '不参与',
        1 => '参与'
    ];

    /**
     * @var array Field
     */
    public $_status_model = 'kake\Product::state';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_package';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'product_id',
                    'name',
                    'price',
                    'bidding',
                    'purchase_limit',
                    'info'
                ],
                'required'
            ],
            [
                [
                    'product_id',
                    'base_price',
                    'price',
                    'bidding',
                    'sort',
                    'purchase_limit',
                    'product_supplier_id'
                ],
                'integer'
            ],
            [
                ['info'],
                'string'
            ],
            [
                ['name'],
                'string',
                'max' => 64
            ],
            [
                ['supplier_contact'],
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
            'product_id' => Yii::t('database', 'Product ID'),
            'name' => Yii::t('database', 'Name'),
            'base_price' => Yii::t('database', 'Base Price'),
            'price' => Yii::t('database', 'Price'),
            'bidding' => Yii::t('database', 'Bidding'),
            'sort' => Yii::t('database', 'Sort'),
            'purchase_limit' => Yii::t('database', 'Purchase Limit'),
            'info' => Yii::t('database', 'Info'),
            'product_supplier_id' => Yii::t('database', 'Product Supplier ID'),
            'supplier_contact' => Yii::t('database', 'Supplier Contact'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}