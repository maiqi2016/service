<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "config".
 *
 * @property integer $id
 * @property integer $app
 * @property string  $key
 * @property string  $value
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class Config extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'config';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                ['app'],
                'integer'
            ],
            [
                ['key'],
                'required'
            ],
            [
                ['key'],
                'string',
                'max' => 64
            ],
            [
                ['value'],
                'string',
                'max' => 128
            ],
            [
                [
                    'key',
                    'app'
                ],
                'unique',
                'targetAttribute' => [
                    'app',
                    'key'
                ]
            ],
        ], $this->_rule_remark, $this->_rule_state, $this->_rule_add_time, $this->_rule_update_time);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('database', 'ID'),
            'app' => Yii::t('database', 'App'),
            'key' => Yii::t('database', 'Key'),
            'value' => Yii::t('database', 'Value'),
            'remark' => Yii::t('database', 'Remark'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }

    /**
     * 获取配置列表
     *
     * @access public
     *
     * @param mixed   $app
     * @param boolean $useCache
     *
     * @return array
     */
    public function listConfigKVP($app = null, $useCache = false)
    {
        return $this->cache([
            'list.config.kvp',
            func_get_args()
        ], function () use ($app) {

            $where = ['state' => 1];
            if ($app) {
                $where['app'] = $app;
            }

            $config = static::find()->select([
                'key',
                'value'
            ])->where($where)->asArray()->all();

            return array_column($config, 'value', 'key');
        }, null, $this->cacheDbDependent(self::tableName()), $useCache);
    }
}
