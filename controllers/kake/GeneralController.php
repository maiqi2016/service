<?php

namespace service\controllers\kake;

use service\components\Helper;
use service\controllers\MainController;
use service\models\kake\ActivityLotteryCode;
use service\models\kake\ActivityStory;
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
     *
     * @access public
     * @return void
     */
    public function actionConfigKvp()
    {
        $config = (new Config())->listConfigKVP([
            0,
            $this->user->app
        ]);
        unset($config['private']);

        $configFile = Yii::getAlias('@service/config/params.php');
        $file = $this->cache('list.file.params', function () use ($configFile) {
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
     * @access public
     *
     * @param string $ids
     *
     * @return void
     */
    public function actionListAttachmentByIds($ids)
    {
        $ids = explode(',', $ids);
        $list = (new Attachment())->all(function ($list) use ($ids) {
            /**
             * @var $list yii\db\Query
             */
            $list->where(['id' => $ids]);
            $list->andWhere([
                '<',
                'state',
                2
            ]);

            return $list;
        }, null, Yii::$app->params['use_cache']);
        $this->success($list);
    }

    /**
     * 获取广告
     *
     * @access public
     * @return void
     */
    public function actionListAd()
    {
        $option = $this->getParams();
        $model = new Ad();

        $list = $model->all(function ($ar) use ($model, $option) {
            return $model->handleActiveRecord($ar, 'ad', $option);
        }, null, Yii::$app->params['use_cache']);

        $this->success($list);
    }

    /**
     * 列表订单的所有套餐
     *
     * @access public
     *
     * @param integer $order_id
     *
     * @return void
     */
    public function actionListPackageByOrderId($order_id)
    {
        $list = (new OrderSub())->all(function ($list) use ($order_id) {
            /**
             * @var $list yii\db\Query
             */
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
        }, null, Yii::$app->params['use_cache']);

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
     * @access public
     * @return void
     */
    public function actionLogLotteryCode()
    {
        $params = $this->getParams();
        $model = new ActivityLotteryCode();

        $record = $model->first(function ($ar) use ($params) {
            /**
             * @var $ar yii\db\Query
             */
            $ar->where(['openid' => $params['openid']]);
            $ar->andWhere(['state' => 1]);

            return $ar;
        }, Yii::$app->params['use_cache']);

        if (!empty($record)) {
            $this->success([
                'code' => $record['code'],
                'exists' => true
            ]);
        }

        $result = $model->trans(function () use ($model, $params) {
            $sql = 'SELECT * FROM `activity_lottery_code` WHERE `company` = :company FOR UPDATE';
            $total = $model::findBySql($sql, [':company' => $params['company']])->count();

            $company = dechex($params['company'] + 500);
            $serial = Helper::integerEncode($total + 1, null);
            $params['code'] = $company . $serial;

            $result = $model->add($params);
            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            return ['code' => $params['code']];
        }, '生成抽奖码');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success([
            'code' => $result['data']['code'],
            'exists' => false
        ]);
    }

    /**
     * 添加活动故事
     *
     * @access public
     * @return void
     */
    public function actionAddActivityStory()
    {
        $params = $this->getParams();

        $result = (new ActivityStory())->updateOrInsert([
            'user_id' => $params['user_id']
        ], [
            'photo_attachment_id' => $params['attachment'],
            'story' => $params['story']
        ]);

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }
}