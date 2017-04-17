<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "hotel".
 *
 * @property integer $id
 * @property string  $name
 * @property string  $principal
 * @property string  $contact
 * @property string  $address
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class Hotel extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hotel';
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
                    'principal',
                    'contact',
                    'address'
                ],
                'required'
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
            'name' => Yii::t('database', 'Name'),
            'principal' => Yii::t('database', 'Principal'),
            'contact' => Yii::t('database', 'Contact'),
            'address' => Yii::t('database', 'Address'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}