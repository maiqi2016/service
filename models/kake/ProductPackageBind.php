<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product_package_bind".
 *
 * @property integer $id
 * @property integer $product_id
 * @property integer $min
 * @property integer $max
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProductPackageBind extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_package_bind';
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
                    'min',
                    'max'
                ],
                'required'
            ],
            [
                [
                    'product_id',
                    'min',
                    'max'
                ],
                'integer'
            ],
            [
                [
                    'min',
                    'max'
                ],
                'unique',
                'targetAttribute' => [
                    'min',
                    'max'
                ],
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
            'min' => Yii::t('database', 'Left Package'),
            'max' => Yii::t('database', 'Right Package'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
