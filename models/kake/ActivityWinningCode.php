<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "activity_winning_code".
 *
 * @property integer $id
 * @property string  $code
 * @property string  $openid
 * @property string  $nickname
 * @property integer $winning
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ActivityWinningCode extends General
{
    /**
     * @var array Field
     */
    public $_state = [
        0 => '删除',
        1 => '本期',
    ];
    
    /**
     * @var array Field
     */
    public $_winning = [
        0 => '未中奖',
        1 => '中奖'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity_winning_code';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                ['code'],
                'required'
            ],
            [
                ['winning'],
                'integer'
            ],
            [
                ['code'],
                'string',
                'max' => 8
            ],
            [
                [
                    'openid',
                    'nickname'
                ],
                'string',
                'max' => 32
            ],
            [
                [
                    'openid',
                    'state'
                ],
                'unique',
                'targetAttribute' => [
                    'openid',
                    'state'
                ]
            ],
            [
                [
                    'code',
                    'state'
                ],
                'unique',
                'targetAttribute' => [
                    'code',
                    'state'
                ]
            ]
        ], $this->_rule_state, $this->_rule_add_time, $this->_rule_update_time);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'code' => Yii::t('database', 'Code'),
            'openid' => Yii::t('database', 'Openid'),
            'nickname' => Yii::t('database', 'Nickname'),
            'winning' => Yii::t('database', 'Winning'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}