<?php

namespace service\controllers\kake;

use service\controllers\MainController;
use service\components\Helper;
use service\models\kake\Bill;
use service\models\kake\Order;
use service\models\kake\OrderContacts;
use service\models\kake\OrderInstructionsLog;
use service\models\kake\OrderSub;
use service\models\kake\PhoneCaptcha;
use service\models\kake\ProducerLog;
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
     * @access public
     * @return void
     * @throws yii\db\Exception
     */
    public function actionAdd()
    {
        $params = $this->getParams();
        $orderModel = new Order();

        $producerId = Helper::popOne($params, 'producer_id');
        if ($producerId) {
            $controller = $this->controller('producer');
            $producerProductIds = $controller->listProductIds($producerId);
            $producerId = in_array($params['product_id'], $producerProductIds) ? $producerId : null;
        }

        $result = $orderModel->trans(function () use ($orderModel, $params, $producerId) {
            if ($producerId) {
                $ProducerLog = new ProducerLog();
                $ProducerLog->attributes = [
                    'user_id' => $params['user_id'],
                    'producer_id' => $producerId,
                    'product_id' => $params['product_id']
                ];
                if (!$ProducerLog->save()) {
                    throw new yii\db\Exception(current($ProducerLog->getFirstErrors()));
                }
                $params['producer_log_id'] = $ProducerLog->id;
            }

            $package = Helper::popOne($params, 'package');
            $orderModel->attributes = $params;
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
     * @access public
     *
     * @param string  $order_number
     * @param boolean $paid_result
     *
     * @return void
     * @throws yii\db\Exception
     */
    public function actionPayHandler($order_number, $paid_result)
    {
        $model = new Order();
        $where = [
            'order.order_number' => $order_number,
            'order.state' => 1
        ];

        $order = $model->first(function ($one) use ($where) {
            /**
             * @var $one yii\db\Query
             */
            $one->select([
                'order.payment_state',
                'order.id',
                'order.product_id',
                'order_contacts.real_name',
                'order_contacts.phone'
            ]);
            $one->where($where);
            $one->leftJoin('order_contacts', 'order.order_contacts_id = order_contacts.id');

            return $one;
        });

        if (empty($order)) {
            $this->fail('order does not exist');
        }

        if ($order['payment_state'] == 1) {
            $this->success();
        }

        $editOrder = $model::findOne($where);
        $result = $model->trans(function () use ($editOrder, $order, $order_number, $paid_result) {

            if ($paid_result) {
                $editOrder->payment_state = 1;
                $number = OrderSub::find()->where([
                    'order_id' => $order['id']
                ])->count();

                (new Product())->edit(['id' => $order['product_id']], [
                    'real_sales' => function ($num) use ($number) {
                        return $num + $number;
                    }
                ]);
            } else {
                $editOrder->payment_state = 2;
            }

            if (!$editOrder->save()) {
                throw new yii\db\Exception(current($editOrder->getFirstErrors()));
            }

            return true;
        }, '支付后处理');

        // SMS
        $content = sprintf(Yii::$app->params['sms_tpl_4'], $order_number, Yii::$app->params['company_tel']);
        $result = $this->callSmsApi($order['phone'], $content);

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }

    /**
     * 更新订单编号
     *
     * @access public
     *
     * @param integer $id
     * @param string  $order_number
     *
     * @return void
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
     * @access public
     *
     * @param integer $order_sub_id
     * @param integer $user_id
     *
     * @return void
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

        // SMS
        $sub = $model->first([
            'id' => $order_sub_id,
            'state' => 2
        ]);
        $params = [
            $sub['check_in_name'],
            $sub['check_in_time'],
            $sub['conformation_number'] ?: '暂无',
            Yii::$app->params['company_tel']
        ];
        $content = sprintf(Yii::$app->params['sms_tpl_5'], ...$params);
        $this->callSmsApi($sub['check_in_phone'], $content);

        $this->success($result['data']);
    }

    /**
     * 拒绝预约
     *
     * @access public
     *
     * @param integer $order_sub_id
     * @param string  $remark
     * @param integer $user_id
     *
     * @return void
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

        // SMS
        $sub = $model->first([
            'id' => $order_sub_id,
            'state' => 2
        ]);
        $params = [
            $sub['check_in_name'],
            $sub['check_in_time'],
            $remark,
            Yii::$app->params['company_tel']
        ];
        $content = sprintf(Yii::$app->params['sms_tpl_6'], ...$params);
        $this->callSmsApi($sub['check_in_phone'], $content);

        $this->success();
    }

    /**
     * 同意退款
     *
     * @access public
     *
     * @param integer $order_sub_id
     * @param integer $user_id
     *
     * @return void
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
     * @access public
     *
     * @param integer $order_sub_id
     * @param string  $remark
     * @param integer $user_id
     *
     * @return void
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
     * @access public
     *
     * @param integer $user_id
     * @param string  $package_ids
     *
     * @return void
     */
    public function actionPurchaseTimes($user_id, $package_ids = null)
    {
        $model = new OrderSub();

        $package_ids = Helper::parseJsonString($package_ids);
        $result = $model->all(function ($ar) use ($user_id, $package_ids) {
            /**
             * @var $ar yii\db\Query
             */
            $ar->select('product_package_id, COUNT(*) AS times');
            $ar->leftJoin('order', 'order_sub.order_id = order.id');
            $ar->where(['order.user_id' => $user_id]);
            $ar->andWhere([
                '<>',
                'order.state',
                0
            ]);
            if ($package_ids) {
                $ar->andWhere(['order_sub.product_package_id' => $package_ids]);
            }

            $ar->groupBy('order_sub.product_package_id');

            return $ar;
        }, null, Yii::$app->params['use_cache']);

        $result = array_column($result, 'times', 'product_package_id');
        $this->success($result);
    }

    /**
     * 添加联系人
     *
     * @access public
     *
     * @param string $real_name
     * @param string $phone
     * @param string $captcha
     *
     * @return void
     */
    public function actionAddContacts($real_name, $phone, $captcha)
    {
        $captcha = (new PhoneCaptcha())->checkCaptcha($phone, $captcha, 2, Yii::$app->params['captcha_timeout']);
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
     * 验证子订单的真实性
     *
     * @access public
     *
     * @param integer $user_id
     * @param integer $order_sub_id
     *
     * @return object
     */
    private function validateOrderSubUser($user_id, $order_sub_id)
    {
        $model = new OrderSub();
        $result = $model->first(function ($ar) use ($user_id, $order_sub_id) {
            /**
             * @var $ar yii\db\Query
             */
            $ar->leftJoin('order', 'order.id = order_sub.order_id');
            $ar->where([
                'order_sub.id' => $order_sub_id,
                'order.user_id' => $user_id,
                'order.payment_state' => 1,
                'order.state' => 1
            ]);

            return $ar;
        }, Yii::$app->params['use_cache']);

        if (empty($result)) {
            $this->fail('abnormal operation');
        }

        return $model;
    }

    /**
     * 退款申请
     *
     * @access public
     *
     * @param integer $user_id
     * @param integer $order_sub_id
     * @param string  $remark
     *
     * @return void
     */
    public function actionApplyRefund($user_id, $order_sub_id, $remark)
    {
        $model = $this->validateOrderSubUser($user_id, $order_sub_id);
        $result = $model->trans(function () use ($model, $user_id, $order_sub_id, $remark) {

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
     * 预约申请
     *
     * @access public
     *
     * @param integer $user_id
     * @param integer $order_sub_id
     * @param string  $name
     * @param string  $phone
     * @param string  $time
     *
     * @return void
     */
    public function actionApplyOrder($user_id, $order_sub_id, $name, $phone, $time)
    {
        $model = $this->validateOrderSubUser($user_id, $order_sub_id);
        $result = $model->edit([
            'id' => $order_sub_id,
            'state' => 0
        ], [
            'check_in_name' => $name,
            'check_in_phone' => $phone,
            'check_in_time' => $time,
            'state' => 1
        ]);

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }

    /**
     * 我已入住
     *
     * @access public
     *
     * @param integer $user_id
     * @param integer $order_sub_id
     *
     * @return void
     */
    public function actionCompleted($user_id, $order_sub_id)
    {
        $model = $this->validateOrderSubUser($user_id, $order_sub_id);
        $result = $model->edit([
            'id' => $order_sub_id,
            'state' => 2
        ], [
            'state' => 5
        ]);

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }

    /**
     * 取消订单
     *
     * @access public
     *
     * @param integer $user_id
     * @param string  $order_number
     *
     * @return void
     */
    public function actionCancelOrder($user_id, $order_number)
    {
        $result = (new Order())->edit([
            'user_id' => $user_id,
            'order_number' => $order_number,
            'payment_state' => 0,
            'state' => 1
        ], [
            'state' => 0
        ]);

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }

    /**
     * 发票申请
     *
     * @access public
     *
     * @param integer $user_id
     * @param integer $order_sub_id
     * @param string  $address
     * @param string  $invoice_title
     *
     * @return void
     * @throws yii\db\Exception
     */
    public function actionApplyBill($user_id, $order_sub_id, $address, $invoice_title = null)
    {
        $this->validateOrderSubUser($user_id, $order_sub_id);

        $result = (new Bill())->add(compact('order_sub_id', 'invoice_title', 'address'));
        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success($result['data']);
    }

    /**
     * 轮询订单支付状态
     *
     * @access public
     *
     * @param string  $order_number
     * @param integer $user_id
     * @param number  $time
     *
     * @return void
     */
    public function actionPollOrder($order_number, $user_id, $time)
    {
        $result = Order::find()->where([
            'order_number' => $order_number,
            'user_id' => $user_id,
            'payment_state' => 1
        ])->andWhere([
            '<=',
            'update_time',
            $time
        ])->count();

        $this->success($result);
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
}