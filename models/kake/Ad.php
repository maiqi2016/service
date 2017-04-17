<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "ad".
 *
 * @property integer $id
 * @property integer $attachment_id
 * @property integer $target
 * @property string  $url
 * @property string  $remark
 * @property string  $from
 * @property string  $to
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class Ad extends General
{
    /**
     * @var array Field
     */
    public $_target = [
        0 => '_blank',
        1 => '_self'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ad';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'attachment_id',
                    'target',
                    'url',
                    'remark'
                ],
                'required'
            ],
            [
                [
                    'attachment_id',
                    'target'
                ],
                'integer'
            ],
            [
                [
                    'from',
                    'to'
                ],
                'safe'
            ],
            [
                ['url'],
                'string',
                'max' => 256
            ],
            [
                ['remark'],
                'string',
                'max' => 128
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
            'attachment_id' => Yii::t('database', 'Attachment ID'),
            'target' => Yii::t('database', 'Target'),
            'url' => Yii::t('database', 'Url'),
            'remark' => Yii::t('database', 'Remark'),
            'from' => Yii::t('database', 'From'),
            'to' => Yii::t('database', 'To'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}