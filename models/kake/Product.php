<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product".
 *
 * @property integer $id
 * @property string  $title
 * @property integer $product_upstream_id
 * @property integer $sale_type
 * @property integer $sale_rate
 * @property string  $sale_from
 * @property string  $sale_to
 * @property integer $sort
 * @property integer $stock
 * @property integer $virtual_sales
 * @property integer $real_sales
 * @property integer $night_times
 * @property integer $manifestation
 * @property integer $attachment_cover
 * @property string  $attachment_ids
 * @property integer $product_description_id
 * @property integer $share_times
 * @property string  $referral_link
<<<<<<< HEAD
=======
 * @property integer $sell_out
>>>>>>> 3f7e0cbb1e9e9aa6d6ee2a18db7f54011886e0e5
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class Product extends General
{
    /**
     * @var array Field
     */
    public $_classify_model = 'kake\ProductUpstream';

    /**
     * @var array Field
     */
    public $_top = [
        0 => '否',
        1 => '是',
    ];

    /**
     * @var array Field
     */
<<<<<<< HEAD
=======
    public $_sell_out = [
        0 => '否',
        1 => '是'
    ];

    /**
     * @var array Field
     */
>>>>>>> 3f7e0cbb1e9e9aa6d6ee2a18db7f54011886e0e5
    public $_manifestation = [
        0 => '标准',
        1 => '焦点图',
        2 => '闪购模块'
    ];

    /**
     * @var array Field
     */
    public $_sale_type = [
        1 => '固定折扣',
        2 => '百分比折扣'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge([
            [
                [
                    'title',
                    'product_upstream_id',
                    'attachment_cover',
                    'attachment_ids',
                    'product_description_id'
                ],
                'required'
            ],
            [
                [
                    'product_upstream_id',
                    'sale_type',
                    'sale_rate',
                    'sort',
                    'stock',
                    'virtual_sales',
                    'real_sales',
                    'night_times',
                    'manifestation',
                    'attachment_cover',
                    'product_description_id',
<<<<<<< HEAD
                    'share_times'
=======
                    'share_times',
                    'sell_out'
>>>>>>> 3f7e0cbb1e9e9aa6d6ee2a18db7f54011886e0e5
                ],
                'integer'
            ],
            [
<<<<<<< HEAD
                ['share_times'],
=======
                ['share_times', 'real_sales', 'sell_out'],
>>>>>>> 3f7e0cbb1e9e9aa6d6ee2a18db7f54011886e0e5
                'default',
                'value' => 0
            ],
            [
                ['sale_rate'],
                'default',
                'value' => 1
            ],
            [
                [
                    'sale_from',
                    'sale_to'
                ],
                'safe'
            ],
            [
                ['title'],
                'string',
                'max' => 64
            ],
            [
<<<<<<< HEAD
                ['real_sales'],
                'default',
                'value' => 0
            ],
            [
=======
>>>>>>> 3f7e0cbb1e9e9aa6d6ee2a18db7f54011886e0e5
                [
                    'attachment_ids',
                    'referral_link'
                ],
                'string',
                'max' => 256
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
            'title' => Yii::t('database', 'Title'),
            'product_upstream_id' => Yii::t('database', 'Product Upstream ID'),
            'sale_type' => Yii::t('database', 'Sale Type'),
            'sale_rate' => Yii::t('database', 'Sale Rate'),
            'sale_from' => Yii::t('database', 'Sale From'),
            'sale_to' => Yii::t('database', 'Sale To'),
            'sort' => Yii::t('database', 'Sort'),
            'stock' => Yii::t('database', 'Stock'),
            'virtual_sales' => Yii::t('database', 'Virtual Sales'),
            'real_sales' => Yii::t('database', 'Real Sales'),
            'night_times' => Yii::t('database', 'Night Times'),
            'manifestation' => Yii::t('database', 'Manifestation'),
            'attachment_cover' => Yii::t('database', 'Attachment Cover'),
            'attachment_ids' => Yii::t('database', 'Attachment Ids'),
            'product_description_id' => Yii::t('database', 'Product Description ID'),
            'share_times' => Yii::t('database', 'Share Times'),
            'referral_link' => Yii::t('database', 'Referral Link'),
<<<<<<< HEAD
=======
            'sell_out' => Yii::t('database', 'Sell out'),
>>>>>>> 3f7e0cbb1e9e9aa6d6ee2a18db7f54011886e0e5
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}