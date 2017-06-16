<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "producer_log".
 *
 * @property integer $id
 * @property integer $producer_id
 * @property integer $product_id
 * @property integer $state
 */
class ProducerLog extends General
{
    /**
     * @var array Field
     */
    public $_state = [
        0 => '已结算',
        1 => '未结算',
    ];

    /**
     * @var array Field
     */
    public $_type_model = 'kake\ProductProducer';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'producer_log';
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
                    'product_id'
                ],
                'required'
            ],
            [
                [
                    'producer_id',
                    'product_id'
                ],
                'integer'
            ],
        ], $this->_rule_state);
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
            'state' => Yii::t('database', 'State'),
        ];
    }
}