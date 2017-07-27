<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product".
 *
 * @property integer $id
 * @property string  $title
 * @property integer $hotel_id
 * @property integer $classify
 * @property integer $sale_type
 * @property integer $sale_rate
 * @property string  $sale_from
 * @property string  $sale_to
 * @property integer $top
 * @property integer $stock
 * @property integer $virtual_sales
 * @property integer $real_sales
 * @property integer $night_times
 * @property integer $manifestation
 * @property integer $attachment_cover
 * @property string  $attachment_ids
 * @property integer $product_description_id
 * @property integer $share_times
 * @property string  $add_time
 * @property string  $update_time
 * @property integer $state
 */
class Product extends General
{
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
    public $_manifestation = [
        0 => '标准',
        1 => '焦点图',
        2 => '闪购模块'
    ];

    /**
     * @var array Field
     */
    public $_classify = [
        1 => '食慧酒廊',
        2 => '亲子游',
        3 => 'KAKE置换',
        4 => '精品酒店',
        5 => '周末游',
        6 => '国内长线',
        7 => '海外度假'
    ];

    /**
     * @var array Field en
     */
    public $_classify_en = [
        1 => 'Lounge',
        2 => 'Parenting',
        3 => 'Trade',
        4 => 'Boutique',
        5 => 'Weekend',
        6 => 'Domestic',
        7 => 'Overseas',
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
                    'hotel_id',
                    'classify',
                    'attachment_cover',
                    'attachment_ids',
                    'product_description_id'
                ],
                'required'
            ],
            [
                [
                    'classify',
                    'hotel_id',
                    'sale_type',
                    'sale_rate',
                    'top',
                    'stock',
                    'virtual_sales',
                    'real_sales',
                    'night_times',
                    'manifestation',
                    'attachment_cover',
                    'product_description_id',
                    'share_times'
                ],
                'integer'
            ],
            [
                ['share_times'],
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
                ['real_sales'],
                'default',
                'value' => 0
            ],
            [
                ['attachment_ids'],
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
            'hotel_id' => Yii::t('database', 'Hotel ID'),
            'classify' => Yii::t('database', 'Classify'),
            'sale_type' => Yii::t('database', 'Sale Type'),
            'sale_rate' => Yii::t('database', 'Sale Rate'),
            'sale_from' => Yii::t('database', 'Sale From'),
            'sale_to' => Yii::t('database', 'Sale To'),
            'top' => Yii::t('database', 'Top'),
            'stock' => Yii::t('database', 'Stock'),
            'virtual_sales' => Yii::t('database', 'Virtual Sales'),
            'real_sales' => Yii::t('database', 'Real Sales'),
            'night_times' => Yii::t('database', 'Night Times'),
            'manifestation' => Yii::t('database', 'Manifestation'),
            'attachment_cover' => Yii::t('database', 'Attachment Cover'),
            'attachment_ids' => Yii::t('database', 'Attachment Ids'),
            'product_description_id' => Yii::t('database', 'Product Description ID'),
            'share_times' => Yii::t('database', 'Share Times'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }
}