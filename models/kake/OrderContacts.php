<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "order_contacts".
 *
 * @property integer $id
 * @property string  $real_name
 * @property string  $phone
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class OrderContacts extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order_contacts';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'real_name',
                    'phone'
                ],
                'required'
            ],
            [
                [
                    'real_name',
                    'phone'
                ],
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
            'real_name' => Yii::t('database', 'Real Name'),
            'phone' => Yii::t('database', 'Phone'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}