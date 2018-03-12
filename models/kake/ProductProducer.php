<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product_producer".
 *
 * @property integer $id
 * @property integer $product_id
 * @property integer $from_sales
 * @property integer $to_sales
 * @property integer $type
 * @property integer $commission
 * @property integer $sort
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProductProducer extends General
{
    /**
     * @var array Field
     */
    public $_type = [
        0 => '固定额',
        1 => '百分比'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_producer';
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
                    'from_sales',
                    'type',
                    'commission'
                ],
                'required'
            ],
            [
                [
                    'product_id',
                    'from_sales',
                    'to_sales',
                    'type',
                    'commission',
                    'sort'
                ],
                'integer'
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
            'product_id' => Yii::t('database', 'Product ID'),
            'from_sales' => Yii::t('database', 'From Sales'),
            'to_sales' => Yii::t('database', 'To Sales'),
            'type' => Yii::t('database', 'Type'),
            'commission' => Yii::t('database', 'Commission'),
            'sort' => Yii::t('database', 'Sort'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}