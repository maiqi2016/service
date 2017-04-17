<?php

namespace service\models\kake;

use Yii;

/**
 * This is the model class for table "product".
 *
 * @property integer $id
 * @property string  $title
 * @property string  $info
 * @property string  $destination
 * @property string  $hotel_id
 * @property integer $classify
 * @property integer $sale_type
 * @property integer $sale_rate
 * @property string  $sale_from
 * @property string  $sale_to
 * @property integer $top
 * @property integer $stock
 * @property integer $min_night
 * @property integer $manifestation
 * @property integer $attachment_cover
 * @property string  $attachment_ids
 * @property integer $product_description_id
 * @property integer $purchase_times
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
                    'info',
                    'destination',
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
                    'min_night',
                    'manifestation',
                    'attachment_cover',
                    'product_description_id',
                    'purchase_times',
                    'share_times'
                ],
                'integer'
            ],
            [
                [
                    'purchase_times',
                    'share_times'
                ],
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
                [
                    'info',
                    'attachment_ids'
                ],
                'string',
                'max' => 256
            ],
            [
                ['destination'],
                'string',
                'max' => 32
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
            'info' => Yii::t('database', 'Info'),
            'destination' => Yii::t('database', 'Destination'),
            'hotel_id' => Yii::t('database', 'Hotel ID'),
            'classify' => Yii::t('database', 'Classify'),
            'sale_type' => Yii::t('database', 'Sale Type'),
            'sale_rate' => Yii::t('database', 'Sale Rate'),
            'sale_from' => Yii::t('database', 'Sale From'),
            'sale_to' => Yii::t('database', 'Sale To'),
            'top' => Yii::t('database', 'Top'),
            'stock' => Yii::t('database', 'Stock'),
            'min_night' => Yii::t('database', 'Min Night'),
            'manifestation' => Yii::t('database', 'Manifestation'),
            'attachment_cover' => Yii::t('database', 'Attachment Cover'),
            'attachment_ids' => Yii::t('database', 'Attachment Ids'),
            'product_description_id' => Yii::t('database', 'Product Description ID'),
            'purchase_times' => Yii::t('database', 'Purchase Times'),
            'share_times' => Yii::t('database', 'Share Times'),
            'add_time' => Yii::t('database', 'Add Time'),
            'update_time' => Yii::t('database', 'Update Time'),
            'state' => Yii::t('database', 'State'),
        ];
    }

    /**
     * 列表简单版本产品
     *
     * @access public
     *
     * @param array $params
     *
     * @return array
     */
    public function simpleList($params)
    {
        $where = null;

        if (isset($params['classify'])) {
            $sql = 'AND `product`.`classify` = %d';
            $where .= sprintf($sql, $params['classify']);
        }

        if (isset($params['manifestation'])) {
            $sql = 'AND `product`.`manifestation` = %d';
            $where .= sprintf($sql, $params['manifestation']);
        }

        if (isset($params['keyword'])) {
            $sql = "AND (`product`.`title` LIKE '%%s%' OR `product`.`info` LIKE '%%s%' OR `product`.`destination` LIKE '%%s%')";
            $where .= sprintf($sql, $params['keyword'], $params['keyword'], $params['keyword']);
        }

        $now = date('Y-m-d H:i:s', TIME);
        if (isset($params['sale'])) {
            if ($params['sale']) {
                $sql = "AND (`product`.`sale_rate` > 0 AND `product`.`sale_from` < '%s' AND `product`.`sale_to` > '%s')";
                $where .= sprintf($sql, $now, $now);
            } else {
                $sql = "AND (`product`.`sale_rate` IS NULL OR `product`.`sale_rate` = 0 OR `product`.`sale_from` > '%s' AND `product`.`sale_to` > '%s')";
                $where .= sprintf($sql, $now, $now);
            }
        }

        $sql = 'SELECT 
            `product`.`id`, 
            `product`.`title`, 
            `product`.`attachment_cover`, 
            `package`.`price`, 
            `cover`.`deep_path` AS `cover_deep_path`, 
            `cover`.`filename` AS `cover_filename`, 
            `hotel`.`name` 
        FROM `product` 
        LEFT JOIN (
            SELECT `product_id`, min(price) AS `price` 
            FROM `product_package` 
            GROUP BY `product_id` 
        ) AS `package` ON `product`.`id` = `package`.`product_id` 
        LEFT JOIN `attachment` AS `cover` ON `product`.`attachment_cover` = `cover`.`id` 
        LEFT JOIN `hotel` AS `hotel` ON `product`.`hotel_id` = `hotel`.`id` 
        WHERE `product`.`state`=1 ' . $where . '
        ORDER BY `product`.`top` DESC, `product`.`update_time` DESC 
        LIMIT %d OFFSET %d';

        if (empty($params['limit'])) {
            return $this->result('lack of necessary parameters');
        }
        $offset = empty($params['offset']) ? 0 : (int) $params['offset'];

        $sql = sprintf($sql, (int) $params['limit'], $offset);
        $command = $this->db->createCommand($sql);

        return $command->queryAll();
    }
}