<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "producer_product".
 *
 * @property integer $id
 * @property integer $producer_id
 * @property integer $product_id
 * @property integer $type
 * @property integer $sort
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProducerProduct extends General
{
    /**
     * @var array Field
     */
    public $_type_model = 'kake\ProductProducer';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'producer_product';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'producer_id',
                    'product_id',
                    'type'
                ],
                'required'
            ],
            [
                [
                    'producer_id',
                    'product_id',
                    'type',
                    'sort'
                ],
                'integer'
            ],
            [
                [
                    'producer_id',
                    'product_id'
                ],
                'unique',
                'targetAttribute' => [
                    'producer_id',
                    'product_id'
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
            'producer_id' => Yii::t('database', 'Producer ID'),
            'product_id' => Yii::t('database', 'Product ID'),
            'type' => Yii::t('database', 'Type'),
            'sort' => Yii::t('database', 'Sort'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}