<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "activity_lottery_code".
 *
 * @property integer $id
 * @property string  $openid
 * @property integer $company
 * @property string  $nickname
 * @property string  $real_name
 * @property string  $phone
 * @property string  $code
 * @property integer $subscribe
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ActivityLotteryCode extends General
{
    /**
     * @var array Field
     */
    public $_state = [
        -2 => '第二期',
        -1 => '第一期',
        0 => '删除',
        1 => '本期',
    ];

    /**
     * @var array Fields
     */
    public $_company = [
        1 => '多乐米',
        2 => '保龄球',
        3 => '立秀宝(No.1)',
        4 => '嘿姐',
        5 => '黄浦趴',
        6 => 'uyuan',
        7 => 'kake',
        8 => '广西大都',
        9 => '凯儿得乐',
        10 => '河马生活',
        11 => '小创客',
        12 => '带着屁孩去旅行',
        13 => '金宝贝',
        14 => '立秀宝(No.2)',
        15 => 'ms.black',
        16 => '马丁',
        17 => '花招',
        18 => '约麦',
        19 => '儿童科技营',
        20 => '灰姑娘',
        21 => '妈妈帮',
        22 => '爱代驾',
        23 => '小渔',
        24 => '汪正摄影'
    ];

    /**
     * @var array 对应活动时间
     */
    public $_activity_date = [];

    /**
     * @var array Fields
     */
    public $_subscribe = [
        0 => '已取关',
        1 => '关注中'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity_lottery_code';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'openid',
                    'company',
                    'nickname',
                    'code'
                ],
                'required'
            ],
            [
                [
                    'company',
                    'subscribe'
                ],
                'integer'
            ],
            [
                [
                    'openid',
                    'nickname',
                    'real_name',
                    'phone',
                    'code'
                ],
                'string',
                'max' => 32
            ],
            [
                ['subscribe'],
                'default',
                'value' => 1
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
        ], $this->_rule_state, $this->_rule_add_time, $this->_rule_update_time);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'openid' => Yii::t('database', 'Openid'),
            'company' => Yii::t('database', 'Company'),
            'nickname' => Yii::t('database', 'Nickname'),
            'real_name' => Yii::t('database', 'Real Name'),
            'phone' => Yii::t('database', 'Phone'),
            'code' => Yii::t('database', 'Code'),
            'subscribe' => Yii::t('database', 'Subscribe'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}