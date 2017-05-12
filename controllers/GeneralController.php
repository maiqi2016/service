<?php

namespace service\controllers;

use service\models\kake\ActivityLotteryCode;
use service\models\kake\Attachment;
use service\models\kake\Ad;
use service\models\kake\Config;
use service\models\kake\OrderSub;
use yii;

/**
 * General controller
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-01-07 13:16:10
 */
class GeneralController extends MainController
{
    /**
     * 列表配置 - 键值对
     */
    public function actionConfigKvp()
    {
        $config = (new Config())->listConfigKVP([
            0,
            $this->user->app
        ]);
        unset($config['private']);

        $configFile = Yii::getAlias('@service/config/params.php');
        $file = $this->cache('file.get.params', function () use ($configFile) {
            return require $configFile;
        }, null, new yii\caching\FileDependency([
            'fileName' => $configFile
        ]));

        $this->success([
            'db' => $config,
            'file' => $file
        ]);
    }

    /**
     * 列表附件
     *
     * @param string $ids
     */
    public function actionListAttachmentByIds($ids)
    {
        $ids = explode(',', $ids);
        $list = (new Attachment())->all(function ($list) use ($ids) {
            $list->where(['id' => $ids]);
            $list->andWhere([
                '<',
                'state',
                2
            ]);

            return $list;
        });
        $this->success($list);
    }

    /**
     * 获取广告
     */
    public function actionListAd()
    {
        $option = $this->getParams();
        $model = new Ad();

        $list = $model->all(function ($ar) use ($model, $option) {
            return $model->handleActiveRecord($ar, 'ad', $option);
        });

        $this->success($list);
    }

    /**
     * 列表订单的所有套餐
     *
     * @param integer $order_id
     */
    public function actionListPackageByOrderId($order_id)
    {
        $list = (new OrderSub())->all(function ($list) use ($order_id) {
            $list->select([
                'order_sub.id',
                'order_sub.product_package_id',
                'product_package.name',
                'product_package.price',
                'product_package.info',
            ]);

            $list->where(['order_sub.order_id' => $order_id]);
            $list->leftJoin('product_package', 'order_sub.product_package_id = product_package.id');

            return $list;
        });

        $package = [];
        foreach ($list as $item) {
            $id = $item['product_package_id'];
            if (!isset($package[$id])) {
                $item['number'] = 1;
                $package[$id] = $item;
            } else {
                $package[$id]['number'] += 1;
            }
        }

        $this->success($package);
    }

    /**
     * 生成抽奖码
     *
     * @param string $openid
     * @param string $nickname
     * @param integer $company
     */
    public function actionLogLotteryCode($openid, $nickname, $company)
    {
        $model = new ActivityLotteryCode();
        $result = $model->trans(function () use ($model, $openid, $nickname, $company) {
            $sql = 'SELECT * FROM `activity_lottery_code` WHERE `company` = :company FOR UPDATE';
            $total = $model::findBySql($sql, [':company' => $company])->count();

            $code = dechex($total + 666666 + 1);
            $code = str_pad($code, 6, 0, STR_PAD_LEFT);
            $code = strtoupper($company . $code);

            $result = $model->add(compact('openid', 'nickname', 'company', 'code'));
            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            return $code;
        }, '生成抽奖码');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }
}