<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product_supplier".
 *
 * @property integer $id
 * @property string  $name
 * @property string  $contact
 * @property string  $address
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProductSupplier extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_supplier';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                ['name'],
                'required'
            ],
            [
                [
                    'name',
                    'contact'
                ],
                'string',
                'max' => 32
            ],
            [
                ['address'],
                'string',
                'max' => 64
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
            'contact' => Yii::t('database', 'Contact'),
            'address' => Yii::t('database', 'Address'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}