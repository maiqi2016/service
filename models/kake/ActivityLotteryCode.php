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
        -3 => '第三期',
        -2 => '第二期',
        -1 => '第一期',
        0 => '删除',
        1 => '本期',
    ];

    /**
     * @var array Fields
     */
    public $_company = [
        0 => '阿里巴巴',
        1 => '多乐米 (No.1)',
        2 => '保龄球 (No.1)',
        3 => '立秀宝 (No.1)',
        4 => '嘿姐 (No.1)',
        5 => '黄浦趴 (No.1)',
        6 => 'uyuan (No.1)',
        7 => 'kake (No.1)',
        8 => '广西大都 (No.1)',
        9 => '凯儿得乐 (No.1)',
        10 => '河马生活 (No.1)',
        11 => '小创客 (No.1)',
        12 => '带着屁孩去旅行 (No.1)',
        13 => '金宝贝 (No.1)',
        14 => '立秀宝 (No.2)',
        15 => 'ms.black (No.1)',
        16 => '马丁 (No.1)',
        17 => '花招 (No.1)',
        18 => '约麦 (No.1)',
        19 => '儿童科技营 (No.1)',
        20 => '灰姑娘 (No.1)',
        21 => '妈妈帮 (No.1)',
        22 => '爱代驾 (No.1)',
        23 => '小渔 (No.1)',
        24 => '汪正摄影 (No.1)',
        25 => 'uyuan',
    ];

    /**
     * @var array Fields
     */
    public $_company_en = [
        0 => 'ALI',
        1 => 'DLM',
        2 => 'BLQ',
        3 => 'LXB',
        4 => 'HJ',
        5 => 'HPP',
        6 => 'UY',
        7 => 'KK',
        8 => 'GXDD',
        9 => 'KEDL',
        10 => 'HM.SH',
        11 => 'XCK',
        12 => 'DPH.LX',
        13 => 'JBB',
        14 => 'LXB',
        15 => 'MS.B',
        16 => 'MD',
        17 => 'HZ',
        18 => 'YM',
        19 => 'ET.KJY',
        20 => 'HGN',
        21 => 'MMB',
        22 => 'ADJ',
        23 => 'XY',
        24 => 'WZ.SY',
        25 => 'UY',
    ];

    /**
     * @var array 对应活动时间
     */
    public $_activity_date = [
        25 => [
            'begin' => '2017-08-13 14:00:00',
            'end' => '2017-08-13 17:30:00'
        ],
    ];

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