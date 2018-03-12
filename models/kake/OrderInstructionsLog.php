<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "order_instructions_log".
 *
 * @property integer $id
 * @property integer $admin_user_id
 * @property integer $order_sub_id
 * @property string  $remark
 * @property integer $type
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class OrderInstructionsLog extends General
{
    /**
     * @var array Field
     */
    public $_type = [
        0 => '同意退款',
        1 => '拒绝退款',
        2 => '同意预约',
        3 => '拒绝预约',
        4 => '申请退款',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order_instructions_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'order_sub_id',
                    'type'
                ],
                'required'
            ],
            [
                [
                    'admin_user_id',
                    'order_sub_id',
                    'type'
                ],
                'integer'
            ],
            [
                ['remark'],
                'string'
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
            'admin_user_id' => Yii::t('database', 'Admin User ID'),
            'order_sub_id' => Yii::t('database', 'Order Sub ID'),
            'remark' => Yii::t('database', 'Remark'),
            'type' => Yii::t('database', 'Type'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}