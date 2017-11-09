<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "producer_group".
 *
 * @property integer $id
 * @property string  $name
 * @property string  $remark
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProducerGroup extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'producer_group';
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
                ['remark'],
                'string'
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
            'remark' => Yii::t('database', 'Remark'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
