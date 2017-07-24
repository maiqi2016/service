<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "hotel_region".
 *
 * @property integer $id
 * @property integer $hotel_plate_id
 * @property string  $name
 * @property integer $attachment_id
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class HotelRegion extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hotel_region';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'hotel_plate_id',
                    'name',
                    'attachment_id'
                ],
                'required'
            ],
            [
                [
                    'hotel_plate_id',
                    'attachment_id',
                    'state'
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
            'hotel_plate_id' => Yii::t('database', 'Hotel Plate ID'),
            'name' => Yii::t('database', 'Name'),
            'attachment_id' => Yii::t('database', 'Attachment ID'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}