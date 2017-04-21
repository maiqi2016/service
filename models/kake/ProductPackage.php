<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product_package".
 *
 * @property integer $id
 * @property integer $product_id
 * @property string  $name
 * @property integer $price
 * @property integer $purchase_limit
 * @property string  $info
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProductPackage extends General
{
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
                    'purchase_limit',
                    'info'
                ],
                'required'
            ],
            [
                [
                    'product_id',
                    'price',
                    'purchase_limit'
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
            'price' => Yii::t('database', 'Price'),
            'purchase_limit' => Yii::t('database', 'Purchase Limit'),
            'info' => Yii::t('database', 'Info'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}