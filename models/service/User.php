<?php

namespace service\models\service;

use service\components\Helper;
use Yii;

/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string  $app_id
 * @property string  $app_secret
 * @property integer $type
 * @property integer $app
 * @property string  $remark
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class User extends General
{
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
        return array_merge([
            [
                [
                    'app_id',
                    'app_secret'
                ],
                'required'
            ],
            [
                [
                    'type',
                    'app'
                ],
                'integer'
            ],
            [
                ['app_id'],
                'string',
                'max' => 18
            ],
            [
                ['app_secret'],
                'string',
                'max' => 32
            ],
            [
                ['remark'],
                'string',
                'max' => 128
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
            'app_id' => Yii::t('database', 'App ID'),
            'app_secret' => Yii::t('database', 'App Secret'),
            'type' => Yii::t('database', 'Type'),
            'app' => Yii::t('database', 'app'),
            'remark' => Yii::t('database', 'Remark'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }

    /**
     * 新增 Service 调用者
     *
     * @access  public
     *
     * @param   string $remark 备注
     * @param integer  $type   0对外 1对内
     * @param integer  $app    对内时的项目id
     *
     * @return  array
     */
    public function addUser($remark = null, $type = 1, $app = null)
    {

        // 生成id和密钥
        $app_secret = strrev(md5(Helper::randString(8) . TIME));
        $app_id = 'kk_' . substr(md5(TIME . $app_secret), 8, 15);

        $caller = new static();

        $caller->attributes = $user = [
            'app_id' => $app_id,
            'app_secret' => $app_secret,
            'type' => $type,
            'app' => $app,
            'remark' => $remark
        ];
        $caller->save();

        return $user;
    }
}
