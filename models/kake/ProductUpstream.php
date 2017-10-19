<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product_upstream".
 *
 * @property integer $id
 * @property integer $classify
 * @property string  $name
 * @property integer $product_region_id
 * @property string  $principal
 * @property string  $contact
 * @property string  $address
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProductUpstream extends General
{
    /**
     * @var array Field
     */
    public $_classify = [
        0 => '酒店',
        1 => '餐饮',
        2 => '娱乐',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_upstream';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'classify',
                    'name',
                    'product_region_id',
                    'address'
                ],
                'required'
            ],
            [
                [
                    'classify',
                    'product_region_id'
                ],
                'integer'
            ],
            [
                [
                    'name',
                    'address'
                ],
                'string',
                'max' => 64
            ],
            [
                [
                    'principal',
                    'contact'
                ],
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
            'classify' => Yii::t('database', 'Classify'),
            'name' => Yii::t('database', 'Name'),
            'product_region_id' => Yii::t('database', 'Product Region ID'),
            'principal' => Yii::t('database', 'Principal'),
            'contact' => Yii::t('database', 'Contact'),
            'address' => Yii::t('database', 'Address'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}