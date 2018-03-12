<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "producer_setting".
 *
 * @property integer $id
 * @property integer $producer_id
 * @property integer $theme
 * @property integer $name
 * @property integer $logo_attachment_id
 * @property integer $account_type
 * @property string  $account_number
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProducerSetting extends General
{
    /**
     * @var array Field
     */
    public $_theme = [
        1 => 'default'
    ];

    /**
     * @var array Field
     */
    public $_account_type = [
        0 => '微信',
        1 => '支付宝'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'producer_setting';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'producer_id',
                    'name',
                    // 'logo_attachment_id',
                    'account_type',
                    'account_number'
                ],
                'required'
            ],
            [
                [
                    'producer_id',
                    'theme',
                    'logo_attachment_id',
                    'account_type',
                ],
                'integer'
            ],
            [
                ['name'],
                'string',
                'max' => 32
            ],
            [
                ['account_number'],
                'string',
                'max' => 64
            ],
            [
                ['producer_id'],
                'unique'
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
            'producer_id' => Yii::t('database', 'Producer ID'),
            'theme' => Yii::t('database', 'Theme'),
            'name' => Yii::t('database', 'Name'),
            'logo_attachment_id' => Yii::t('database', 'Logo Attachment ID'),
            'account_type' => Yii::t('database', 'Account Type'),
            'account_number' => Yii::t('database', 'Account Number'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}