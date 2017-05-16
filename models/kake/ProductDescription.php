<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product_description".
 *
 * @property integer $id
 * @property string  $cost
 * @property string  $recommend
 * @property string  $use
 * @property string  $back
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
                    'cost',
                    'recommend',
                    'use',
                    'back'
                ],
                'required'
            ],
            [
                [
                    'cost',
                    'recommend',
                    'use',
                    'back'
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
            'cost' => Yii::t('database', 'Cost'),
            'recommend' => Yii::t('database', 'Recommend'),
            'use' => Yii::t('database', 'Use'),
            'back' => Yii::t('database', 'Back'),
        ];
    }
}