<?php

namespace service\controllers;

use service\components\Helper;
use service\models\kake\Bill;
use service\models\kake\Order;
use service\models\kake\OrderContacts;
use service\models\kake\OrderInstructionsLog;
use service\models\kake\OrderSub;
use service\models\kake\PhoneCaptcha;
use service\models\kake\Product;
use yii;

/**
 * Order controller
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-02-16 14:11:58
 */
class OrderController extends MainController
{
    /**
     * 新增订单
     *
     * @throws yii\db\Exception
     */
    public function actionAdd()
    {
        $params = $this->getParams();
        $package = Helper::popOne($params, 'package');

        $orderModel = new Order();
        $orderModel->attributes = $params;

        $result = $orderModel->trans(function () use ($orderModel, $package) {
            if (!$orderModel->save()) {
                throw new yii\db\Exception(current($orderModel->getFirstErrors()));
            }

            $orderSubModel = new OrderSub();
            $package = Helper::parseJsonString($package);

            if (empty($package)) {
                throw new yii\db\Exception('order package required');
            }

            foreach ($package as $item) {
                for ($i = 0; $i < $item['number']; $i++) {
                    $_model = clone $orderSubModel;
                    $_model->attributes = [
                        'order_id' => $orderModel->id,
                        'product_package_id' => $item['id'],
                        'price' => $item['price']
                    ];
                    if (!$_model->save()) {
                        throw new yii\db\Exception(current($_model->getFirstErrors()));
                    }
                }
            }

            return ['id' => $orderModel->id];
        }, '添加主订单并根据套餐拆分出子订单');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }

    /**
     * 订单支付后处理
     *
     * @param string $order_number
     * @param boolean $paid_result
     */
    public function actionPayHandler($order_number, $paid_result)
    {
        $model = new Order();
        $order = $model::findOne([
            'order_number' => $order_number,
            'state' => 1
        ]);

        if (empty($order)) {
            $this->fail('order does not exist');
        }

        if ($order['payment_state'] == 1) {
            $this->success();
        }

        if ($paid_result) {
            $order->payment_state = 1;

            (new Product())->edit(['id' => $order->product_id], [
                'real_sales' => function ($num) {
                    return ++$num;
                }
            ]);

        } else {
            $order->payment_state = 2;
        }

        if (!$order->save()) {
            $this->fail(current($order->getFirstErrors()));
        }

        $this->success();
    }

    /**
     * 更新订单编号
     *
     * @param integer $id
     * @param string  $order_number
     */
    public function actionUpdateOrderNumber($id, $order_number)
    {
        $model = $this->model('order');

        $record = $model::findOne(['id' => $id]);
        $record->order_number = $order_number;

        if (!$record->save()) {
            $this->fail(current($record->getFirstErrors()));
        }

        $this->success();
    }

    /**
     * 同意预约
     *
     * @param integer $order_sub_id
     * @param integer $user_id
     *
     * @throws yii\db\Exception
     */
    public function actionAgreeOrder($order_sub_id, $user_id)
    {
        $model = new OrderSub();
        $result = $model->trans(function () use ($model, $order_sub_id, $user_id) {

            $result = $model->edit([
                'id' => $order_sub_id,
                'state' => 1
            ], ['state' => 2]);

            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            $result = (new OrderInstructionsLog())->add([
                'order_sub_id' => $order_sub_id,
                'admin_user_id' => $user_id,
                'type' => 2
            ]);

            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            return true;
        }, '同意预约事务逻辑');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }

    /**
     * 拒绝预约
     *
     * @param integer $order_sub_id
     * @param string  $remark
     * @param integer $user_id
     *
     * @throws yii\db\Exception
     */
    public function actionRefuseOrder($order_sub_id, $remark, $user_id)
    {
        $model = new OrderSub();
        $result = $model->trans(function () use ($model, $order_sub_id, $remark, $user_id) {

            $result = $model->edit([
                'id' => $order_sub_id,
                'state' => 1
            ], ['state' => 0]);

            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            $result = (new OrderInstructionsLog())->add([
                'order_sub_id' => $order_sub_id,
                'remark' => $remark,
                'admin_user_id' => $user_id,
                'type' => 3
            ]);

            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            return true;
        }, '拒绝预约事务逻辑');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }

    /**
     * 同意退款
     *
     * @param integer $order_sub_id
     * @param integer $user_id
     *
     * @throws yii\db\Exception
     */
    public function actionAgreeRefund($order_sub_id, $user_id)
    {
        $model = new OrderSub();
        $result = $model->trans(function () use ($model, $order_sub_id, $user_id) {

            $result = $model->edit([
                'id' => $order_sub_id,
                'state' => 3
            ], ['state' => 4]);

            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            $result = (new OrderInstructionsLog())->add([
                'order_sub_id' => $order_sub_id,
                'admin_user_id' => $user_id,
                'type' => 0
            ]);

            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            return true;
        }, '同意退款事务逻辑');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }

    /**
     * 拒绝退款
     *
     * @param integer $order_sub_id
     * @param string  $remark
     * @param integer $user_id
     *
     * @throws yii\db\Exception
     */
    public function actionRefuseRefund($order_sub_id, $remark, $user_id)
    {
        $model = new OrderSub();
        $result = $model->trans(function () use ($model, $order_sub_id, $remark, $user_id) {

            $result = $model->edit([
                'id' => $order_sub_id,
                'state' => 3
            ], ['state' => 0]);

            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            $result = (new OrderInstructionsLog())->add([
                'order_sub_id' => $order_sub_id,
                'remark' => $remark,
                'admin_user_id' => $user_id,
                'type' => 1
            ]);

            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            return true;
        }, '拒绝退款事务逻辑');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }

    /**
     * 统计指定用户的套餐购买次数
     *
     * @param integer $user_id
     * @param string  $package_ids
     */
    public function actionPurchaseTimes($user_id, $package_ids = null)
    {
        $model = new OrderSub();

        $package_ids = Helper::parseJsonString($package_ids);
        $result = $model->all(function ($ar) use ($user_id, $package_ids) {
            $ar->select('product_package_id, COUNT(*) AS times');
            $ar->leftJoin('order', 'order_sub.order_id = order.id');
            $ar->where(['order.user_id' => $user_id]);
            if ($package_ids) {
                $ar->andWhere(['order_sub.product_package_id' => $package_ids]);
            }

            $ar->groupBy('order_sub.product_package_id');

            return $ar;
        });

        $result = array_column($result, 'times', 'product_package_id');
        $this->success($result);
    }

    /**
     * 添加联系人
     *
     * @param string  $real_name
     * @param string  $phone
     * @param string  $captcha
     */
    public function actionAddContacts($real_name, $phone, $captcha)
    {
        $captcha = (new PhoneCaptcha())->checkCaptcha($phone, $captcha, 2);
        if (!$captcha) {
            Yii::info('验证码错误, phone:' . $phone . ', captcha:' . $captcha);
            $this->fail('phone captcha error');
        }

        $result = (new OrderContacts())->add(compact('real_name', 'phone'));
        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }

    /**
     * 退款申请
     *
     * @param integer $user_id
     * @param integer $order_sub_id
     * @param string  $remark
     */
    public function actionApplyRefund($user_id, $order_sub_id, $remark)
    {
        $model = new OrderSub();
        $result = $model->trans(function () use ($model, $user_id, $order_sub_id, $remark) {

            $result = $model->first(function($ar) use ($user_id, $order_sub_id) {
                $ar->leftJoin('order', 'order.id = order_sub.order_id');
                $ar->where([
                    'order_sub.id' => $order_sub_id,
                    'order.user_id' => $user_id,
                    'order.payment_state' => 1,
                    'order.state' => 1
                ]);

                return $ar;
            });

            if (empty($result)) {
                throw new yii\db\Exception('abnormal operation');
            }

            $result = $model->edit([
                'id' => $order_sub_id,
                'state' => 0
            ], [
                'state' => 3
            ]);

            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            $logModel = new OrderInstructionsLog();
            $result = $logModel->add([
                'order_sub_id' => $order_sub_id,
                'remark' => $remark,
                'type' => 4
            ]);

            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            return true;
        }, '退款申请事务操作');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }

    /**
     * 发票申请
     *
     * @param integer $order_sub_id
     * @param string  $address
     * @param string  $invoice_title
     *
     * @throws yii\db\Exception
     */
    public function actionApplyBill($order_sub_id, $address, $invoice_title = null)
    {
        $result = (new Bill())->add(compact('order_sub_id', 'invoice_title', 'address'));

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }
}