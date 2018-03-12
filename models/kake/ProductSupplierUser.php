<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product_supplier_user".
 *
 * @property integer $id
 * @property integer $product_supplier_id
 * @property integer $user_id
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProductSupplierUser extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_supplier_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'product_supplier_id',
                    'user_id'
                ],
                'required'
            ],
            [
                [
                    'product_supplier_id',
                    'user_id'
                ],
                'integer'
            ],
            [
                [
                    'product_supplier_id',
                    'user_id'
                ],
                'unique',
                'targetAttribute' => [
                    'product_supplier_id',
                    'user_id'
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
            'product_supplier_id' => Yii::t('database', 'Product Supplier ID'),
            'user_id' => Yii::t('database', 'User ID'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}