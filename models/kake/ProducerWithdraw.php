<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "producer_withdraw".
 *
 * @property integer $id
 * @property integer $producer_id
 * @property integer $withdraw
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ProducerWithdraw extends General
{
    /**
     * @var array Field
     */
    public $_state = [
        0 => '已关闭',
        1 => '申请中',
        2 => '已完成'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'producer_withdraw';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $state = $this->_rule_state;
        $state['state_range']['range'] = range(0, 2);

        return array_merge([
            [
                [
                    'producer_id',
                    'withdraw'
                ],
                'required'
            ],
            [
                [
                    'producer_id',
                    'withdraw'
                ],
                'integer'
            ],
        ], $state, $this->_rule_add_time, $this->_rule_update_time);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'producer_id' => Yii::t('database', 'Producer ID'),
            'withdraw' => Yii::t('database', 'Withdraw'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}