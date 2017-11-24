<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "activity_story".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $photo_attachment_id
 * @property string  $story
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ActivityStory extends General
{
    /**
     * @var array Field
     */
    public $_state = [
        -1 => '第一期',
        0 => '删除',
        1 => '本期',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity_story';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'user_id',
                    'photo_attachment_id',
                    'story'
                ],
                'required'
            ],
            [
                [
                    'user_id',
                    'photo_attachment_id'
                ],
                'integer'
            ],
            [
                ['story'],
                'string',
                'max' => 100
            ],
            [
                [
                    'user_id',
                    'state'
                ],
                'unique',
                'targetAttribute' => [
                    'user_id',
                    'state'
                ]
            ]
        ], $this->_rule_add_time, $this->_rule_update_time, $this->_rule_state);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'user_id' => Yii::t('database', 'User ID'),
            'photo_attachment_id' => Yii::t('database', 'Photo Attachment ID'),
            'story' => Yii::t('database', 'Story'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}