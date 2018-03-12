<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product_plate".
 *
 * @property integer $id
 * @property string  $name
 * @property integer $attachment_id
 * @property integer $sort
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProductPlate extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_plate';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'name',
                    'attachment_id'
                ],
                'required'
            ],
            [
                [
                    'attachment_id',
                    'sort'
                ],
                'integer'
            ],
            [
                ['name'],
                'string',
                'max' => 32
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
            'name' => Yii::t('database', 'Name'),
            'attachment_id' => Yii::t('database', 'Attachment ID'),
            'sort' => Yii::t('database', 'Sort'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}