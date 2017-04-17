<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product_description".
 *
 * @property integer $id
 * @property string  $enjoy
 * @property string  $characteristic
 * @property string  $use
 */
class ProductDescription extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_description';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                [
                    'enjoy',
                    'use'
                ],
                'required'
            ],
            [
                [
                    'enjoy',
                    'characteristic',
                    'use'
                ],
                'string'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'enjoy' => Yii::t('database', 'Enjoy'),
            'characteristic' => Yii::t('database', 'Characteristic'),
            'use' => Yii::t('database', 'Use'),
        ];
    }
}