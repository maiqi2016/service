<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string  $username
 * @property string  $phone
 * @property integer $role
 * @property string  $openid
 * @property integer $sex
 * @property string  $country
 * @property string  $province
 * @property string  $city
 * @property string  $head_img_url
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class User extends General
{
    /**
     * @var array Field
     */
    public $_role = [
        0 => '普通用户',
        1 => '管理员',
    ];

    /**
     * @var array Field
     */
    public $_sex = [
        0 => '未知',
        1 => '男',
        2 => '女'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge($this->_rule_phone, $this->_rule_username, [
            [
                [
                    'role',
                    'sex'
                ],
                'integer'
            ],
            [
                ['role'],
                'default',
                'value' => 0
            ],
            [
                ['openid'],
                'string',
                'max' => 32
            ],
            [
                [
                    'country',
                    'province',
                    'city'
                ],
                'string',
                'max' => 64
            ],
            [
                ['head_img_url'],
                'string',
                'max' => 256
            ],
            [
                ['role'],
                'integer'
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
            'username' => Yii::t('database', 'Username'),
            'phone' => Yii::t('database', 'Phone'),
            'role' => Yii::t('database', 'Role'),
            'openid' => Yii::t('database', 'Openid'),
            'sex' => Yii::t('database', 'Sex'),
            'country' => Yii::t('database', 'Country'),
            'province' => Yii::t('database', 'Province'),
            'city' => Yii::t('database', 'City'),
            'head_img_url' => Yii::t('database', 'Head Img Url'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
