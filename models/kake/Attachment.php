<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "attachment".
 *
 * @property integer $id
 * @property string  $deep_path
 * @property string  $filename
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class Attachment extends General
{
    /**
     * @var array Fields
     */
    public $_state = [
        0 => '未使用',
        1 => '使用中',
        2 => '删除'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'attachment';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $state = $this->_rule_state;
        $state['state_default']['value'] = 0;

        return array_merge([
            [
                [
                    'deep_path',
                    'filename'
                ],
                'required'
            ],
            [
                ['deep_path'],
                'string',
                'max' => 256
            ],
            [
                ['filename'],
                'string',
                'max' => 64
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
            'deep_path' => Yii::t('database', 'Deep Path'),
            'filename' => Yii::t('database', 'Filename'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }

    /**
     * 更新附件状态
     *
     * @param string  $ids
     * @param integer $state
     *
     * @return mixed
     */
    public function updateStateByIds($ids, $state)
    {
        if (empty($ids)) {
            return $this->result(true);
        }

        if (!is_array($ids) && !is_numeric($ids)) {
            $ids = explode(',', $ids);
        }

        $count = static::updateAll(['state' => $state], ['id' => $ids]);
        if ($count) {
            return $this->result(['count' => $count]);
        }

        return $this->result(current($this->getFirstErrors()));
    }
}
