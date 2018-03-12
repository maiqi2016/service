<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "short_url".
 *
 * @property integer $id
 * @property string  $uri
 * @property string  $url
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class ShortUrl extends General
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'short_url';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'uri',
                    'url'
                ],
                'required'
            ],
            [
                ['uri'],
                'string',
                'max' => 32
            ],
            [
                ['url'],
                'string',
                'max' => 256
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
            'uri' => Yii::t('database', 'Uri'),
            'url' => Yii::t('database', 'Url'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}
