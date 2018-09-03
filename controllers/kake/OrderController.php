<?php

namespace service\controllers\kake;

use service\controllers\MainController;
use Oil\src\Helper;
use service\models\kake\Attachment;
use service\models\kake\OrderBill;
use service\models\kake\Order;
use service\models\kake\OrderContacts;
use service\models\kake\OrderInstructionsLog;
use service\models\kake\OrderSub;
use service\models\kake\PhoneCaptcha;
use service\models\kake\ProducerLog;
use service\models\kake\Product;
use service\models\kake\OrderSoldCode;
use service\models\kake\ProductPackage;
use service\models\kake\User;
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

        $result = $orderModel->trans(
            function () use ($orderModel, $params, $producerId) {
                if ($producerId) {
                    $ProducerLog = new ProducerLog();
                    $ProducerLog->attributes = [
                        'user_id'     => $params['user_id'],
                        'producer_id' => $producerId,
                        'product_id'  => $params['product_id'],
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
                            'order_id'           => $orderModel->id,
                            'product_package_id' => $item['id'],
                            'price'              => $item['price'],
                        ];
                        if (!$_model->save()) {
                            throw new yii\db\Exception(current($_model->getFirstErrors()));
                        }
                    }
                }

                return ['id' => $orderModel->id];
            },
            '添加主订单并根据套餐拆分出子订单'
        );

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
            'order.state'        => 1,
        ];

        $order = $model->first(
            function ($one) use ($where) {
                /**
                 * @var $one yii\db\Query
                 */
                $one->select(
                    [
                        'order.payment_state',
                        'order.id',
                        'order.product_id',
                        'order_contacts.real_name',
                        'order_contacts.phone',
                    ]
                );
                $one->where($where);
                $one->leftJoin('order_contacts', 'order.order_contacts_id = order_contacts.id');

                return $one;
            }
        );

        if (empty($order)) {
            $this->fail('order does not exist');
        }

        if ($order['payment_state'] == 1) {
            $this->success();
        }

        $editOrder = $model::findOne($where);
        $result = $model->trans(
            function () use ($editOrder, $order, $order_number, $paid_result) {

                if ($paid_result) {
                    $editOrder->payment_state = 1;

                    $orderSub = new OrderSub();
                    $sub = $orderSub->all(
                        function ($list) use ($orderSub, $order) {
                            return $orderSub->handleActiveRecord(
                                $list,
                                'order_sub',
                                [
                                    'join'   => [
                                        ['table' => 'product_package'],
                                        ['table' => 'order'],
                                    ],
                                    'where'  => [['order_sub.order_id' => $order['id']]],
                                    'select' => [
                                        'order_sub.*',
                                        'product_package.product_supplier_id',
                                        'order.user_id',
                                    ],
                                ]
                            );
                        }
                    );

                    // 更新销量
                    $number = count($sub);
                    (new Product())->edit(
                        ['id' => $order['product_id']],
                        [
                            'real_sales' => function ($num) use ($number) {
                                return $num + $number;
                            },
                        ]
                    );

                    // 生成子订单核销码
                    $code = [];
                    foreach ($sub as $item) {
                        if (empty($item['product_supplier_id'])) {
                            continue;
                        }
                        $code[] = [
                            $item['id'],
                            $item['product_supplier_id'],
                            Helper::createTicketNumber($item['product_supplier_id'], $item['user_id']),
                        ];
                    }

                    $soldCode = new OrderSoldCode();
                    $result = $soldCode->batchAdd(
                        [
                            'order_sub_id',
                            'product_supplier_id',
                            'code',
                        ],
                        $code
                    );

                    if (!$result['state']) {
                        Yii::error($result['info'] . ' : ' . json_encode($code));
                    }
                } else {
                    $editOrder->payment_state = 2;
                }

                if (!$editOrder->save()) {
                    throw new yii\db\Exception(current($editOrder->getFirstErrors()));
                }

                return true;
            },
            '支付后处理'
        );

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        // SMS
        $content = sprintf(Yii::$app->params['sms_tpl_order_success'], $order_number, Yii::$app->params['company_tel']);
        $this->callSmsApi($order['phone'], $content);

        // 获取 openid 用于发送模板信息
        $openid = (new Order())->first(
            function ($one) use ($where) {
                /**
                 * @var $one yii\db\Query
                 */

                $sub = (new yii\db\Query())->select('order_id, count(*) AS sub_total')->from('order_sub')->groupBy(
                    'order_id'
                );

                $one->where($where);
                $one->leftJoin('user', 'order.user_id = user.id');
                $one->leftJoin('producer_log', 'order.producer_log_id = producer_log.id');
                $one->leftJoin('user AS producer', 'producer_log.producer_id = producer.id');
                $one->leftJoin('product', 'order.product_id = product.id');
                $one->leftJoin('product_upstream', 'product.product_upstream_id = product_upstream.id');
                $one->leftJoin('order_sub', 'order.id = order_sub.order_id');
                $one->leftJoin(['sub' => $sub], 'sub.order_id = order.id');

                $one->select(
                    [
                        'order.*',
                        'sub.sub_total',
                        'producer_log.producer_id',
                        'product.title',
                        'product_upstream.name',
                        'user.username',
                        'user.openid AS user_openid',
                        'producer.openid AS producer_openid',
                    ]
                );

                return $one;
            }
        );

        $this->success($openid);
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
        $model = new Order();

        $record = $model::findOne(['id' => $id]);
        $record->order_number = $order_number;

        if (!$record->save()) {
            $this->fail(current($record->getFirstErrors()));
        }

        $this->success();
    }

    /**
     * 通过子订单 id 获取订单相关信息
     *
     * @access public
     *
     * @param integer $id
     * @param mixed   $state
     *
     * @return array
     */
    public function getOrderBySubId($id, $state = null)
    {
        $model = new OrderSub();
        $detail = $model->first(
            function ($one) use ($model, $id, $state) {

                $where = [['order_sub.id' => $id]];
                $state && $where[] = ['order_sub.state' => $state];

                return $model->handleActiveRecord(
                    $one,
                    'order_sub',
                    [
                        'join'   => [
                            ['table' => 'order'],
                            [
                                'left_table' => 'order',
                                'table'      => 'order_contacts',
                            ],
                            [
                                'left_table' => 'order',
                                'table'      => 'user',
                            ],
                            [
                                'left_table' => 'order',
                                'table'      => 'product',
                            ],
                            [
                                'left_table' => 'product',
                                'table'      => 'product_upstream',
                            ],
                        ],
                        'where'  => $where,
                        'select' => [
                            'order.*',
                            'order.price AS total_price',
                            'order_sub.*',
                            'user.username',
                            'user.openid',
                            'product_upstream.name',
                            'order_contacts.real_name order_user',
                            'order_contacts.phone AS order_phone',
                        ],
                    ]
                );
            },
            null,
            Yii::$app->params['use_cache']
        );

        return $detail;
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
        $result = $model->trans(
            function () use ($model, $order_sub_id, $user_id) {

                $result = $model->edit(
                    [
                        'id'    => $order_sub_id,
                        'state' => 1,
                    ],
                    ['state' => 2]
                );

                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                $result = (new OrderInstructionsLog())->add(
                    [
                        'order_sub_id'  => $order_sub_id,
                        'admin_user_id' => $user_id,
                        'type'          => 2,
                    ]
                );

                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                return true;
            },
            '同意预约事务逻辑'
        );

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        // SMS
        $sub = $this->getOrderBySubId($order_sub_id);
        $content = sprintf(
            Yii::$app->params['sms_tpl_apply_check_success'],
            ...[
                $sub['check_in_name'],
                $sub['check_in_time'],
                $sub['conformation_number'] ?: '暂无',
                Yii::$app->params['company_tel'],
            ]
        );
        $this->callSmsApi($sub['check_in_phone'], $content);

        $this->success($sub);
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
        $result = $model->trans(
            function () use ($model, $order_sub_id, $remark, $user_id) {

                $result = $model->edit(
                    [
                        'id'    => $order_sub_id,
                        'state' => 1,
                    ],
                    ['state' => 0]
                );

                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                $result = (new OrderInstructionsLog())->add(
                    [
                        'order_sub_id'  => $order_sub_id,
                        'remark'        => $remark,
                        'admin_user_id' => $user_id,
                        'type'          => 3,
                    ]
                );

                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                return true;
            },
            '拒绝预约事务逻辑'
        );

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        // SMS
        $sub = $this->getOrderBySubId($order_sub_id);
        $content = sprintf(
            Yii::$app->params['sms_tpl_apply_check_fail'],
            ...[
                $sub['check_in_name'],
                $sub['check_in_time'],
                $remark,
                Yii::$app->params['company_tel'],
            ]
        );
        $this->callSmsApi($sub['check_in_phone'], $content);

        $this->success($sub);
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
        $result = $model->trans(
            function () use ($model, $order_sub_id, $user_id) {

                $result = $model->edit(
                    [
                        'id'    => $order_sub_id,
                        'state' => 3,
                    ],
                    ['state' => 4]
                );

                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                // 核销码失效
                (new OrderSoldCode())->edit(
                    [
                        'order_sub_id' => $order_sub_id,
                        'state'        => 1,
                    ],
                    [
                        'state' => 0,
                    ]
                );

                $result = (new OrderInstructionsLog())->add(
                    [
                        'order_sub_id'  => $order_sub_id,
                        'admin_user_id' => $user_id,
                        'type'          => 0,
                    ]
                );

                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                return true;
            },
            '同意退款事务逻辑'
        );

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        // SMS
        $order = $this->getOrderBySubId($order_sub_id);
        $content = sprintf(
            Yii::$app->params['sms_tpl_apply_refund_success'],
            ...[
                $order['order_number'],
                $order['price'] / 100,
            ]
        );
        $this->callSmsApi($order['order_phone'], $content);

        $this->success($order);
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
        $result = $model->trans(
            function () use ($model, $order_sub_id, $remark, $user_id) {

                $result = $model->edit(
                    [
                        'id'    => $order_sub_id,
                        'state' => 3,
                    ],
                    ['state' => 0]
                );

                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                $result = (new OrderInstructionsLog())->add(
                    [
                        'order_sub_id'  => $order_sub_id,
                        'remark'        => $remark,
                        'admin_user_id' => $user_id,
                        'type'          => 1,
                    ]
                );

                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                return true;
            },
            '拒绝退款事务逻辑'
        );

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        // SMS
        $order = $this->getOrderBySubId($order_sub_id);
        $content = sprintf(
            Yii::$app->params['sms_tpl_apply_refund_fail'],
            ...[
                $order['order_number'],
                $order['price'] / 100,
                $remark,
            ]
        );
        $this->callSmsApi($order['order_phone'], $content);

        $this->success($order);
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
        $result = $model->all(
            function ($ar) use ($user_id, $package_ids) {
                /**
                 * @var $ar yii\db\Query
                 */
                $ar->select('product_package_id, COUNT(*) AS times');
                $ar->leftJoin('order', 'order_sub.order_id = order.id');
                $ar->where(['order.user_id' => $user_id]);
                $ar->andWhere(
                    [
                        '<>',
                        'order.state',
                        0,
                    ]
                );
                if ($package_ids) {
                    $ar->andWhere(['order_sub.product_package_id' => $package_ids]);
                }

                $ar->groupBy('order_sub.product_package_id');

                return $ar;
            },
            null,
            Yii::$app->params['use_cache']
        );

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
        $result = $model->first(
            function ($ar) use ($user_id, $order_sub_id) {
                /**
                 * @var $ar yii\db\Query
                 */
                $ar->leftJoin('order', 'order.id = order_sub.order_id');
                $ar->where(
                    [
                        'order_sub.id'        => $order_sub_id,
                        'order.user_id'       => $user_id,
                        'order.payment_state' => 1,
                        'order.state'         => 1,
                    ]
                );

                return $ar;
            },
            Yii::$app->params['use_cache']
        );

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
        $result = $model->trans(
            function () use ($model, $user_id, $order_sub_id, $remark) {

                $result = $model->edit(
                    [
                        'id'    => $order_sub_id,
                        'state' => 0,
                    ],
                    [
                        'state' => 3,
                    ]
                );

                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                $logModel = new OrderInstructionsLog();
                $result = $logModel->add(
                    [
                        'order_sub_id' => $order_sub_id,
                        'remark'       => $remark,
                        'type'         => 4,
                    ]
                );

                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                return true;
            },
            '退款申请事务操作'
        );

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
        $result = $model->edit(
            [
                'id'    => $order_sub_id,
                'state' => 0,
            ],
            [
                'check_in_name'  => $name,
                'check_in_phone' => $phone,
                'check_in_time'  => $time,
                'state'          => 1,
            ]
        );

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
        $result = $model->edit(
            [
                'id'    => $order_sub_id,
                'state' => 2,
            ],
            [
                'state' => 5,
            ]
        );

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
        $result = (new Order())->edit(
            [
                'user_id'       => $user_id,
                'order_number'  => $order_number,
                'payment_state' => 0,
                'state'         => 1,
            ],
            [
                'state' => 0,
            ]
        );

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
     * @param string  $tax_number
     *
     * @return void
     * @throws yii\db\Exception
     */
    public function actionApplyBill($user_id, $order_sub_id, $address, $invoice_title = null, $tax_number = null)
    {
        $this->validateOrderSubUser($user_id, $order_sub_id);

        $result = (new OrderBill())->add(compact('order_sub_id', 'invoice_title', 'tax_number', 'address'));
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
    public function actionPollOrder($order_number, $user_id)
    {
        $result = Order::find()->where(
            [
                'order_number'  => $order_number,
                'user_id'       => $user_id,
                'payment_state' => 1,
            ]
        )->count();

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
        $list = (new OrderSub())->all(
            function ($list) use ($order_id) {
                /**
                 * @var $list yii\db\Query
                 */
                $list->select(
                    [
                        'order_sub.id',
                        'order_sub.product_package_id',
                        'product_package.name',
                        'product_package.price',
                        'product_package.info',
                    ]
                );

                $list->where(['order_sub.order_id' => $order_id]);
                $list->leftJoin('product_package', 'order_sub.product_package_id = product_package.id');

                return $list;
            },
            null,
            Yii::$app->params['use_cache']
        );

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
     * 获取订单最后的联系人
     *
     * @access public
     *
     * @param integer $user_id
     *
     * @return void
     */
    public function actionGetLastOrderContact($user_id)
    {
        $model = new Order();
        $contact = $model->first(
            function ($one) use ($user_id) {
                /**
                 * @var $one yii\db\Query
                 */
                $one->select(
                    [
                        'order_contacts.real_name',
                        'order_contacts.phone',
                    ]
                );
                $one->leftJoin('order_contacts', 'order.order_contacts_id = order_contacts.id');
                $one->where(['order.user_id' => $user_id]);
                $one->orderBy('order.id DESC');

                return $one;
            }
        );

        $this->success($contact);
    }

    /**
     * 核销套餐
     *
     * @access public
     *
     * @param string $sold
     * @param array  $supplier
     *
     * @return  void
     */
    public function actionVerifySoldCode($sold, $supplier)
    {
        $model = new OrderSoldCode();
        $result = $model->trans(
            function () use ($model, $sold, $supplier) {

                $record = $model::findOne(
                    [
                        'code'                => $sold,
                        'product_supplier_id' => Helper::parseJsonString($supplier),
                    ]
                );

                if (empty($record)) {
                    throw new yii\db\Exception('sold code not exists');
                } elseif ($record->state != 1) {
                    throw new yii\db\Exception('sold code used');
                }

                $result = $model->edit(['code' => $sold], ['state' => 2]);
                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                $orderSubModel = new OrderSub();
                $result = $orderSubModel->edit(['id' => $record->order_sub_id], ['state' => 6]);
                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                return true;
            },
            '核销套餐'
        );

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }

    /**
     * 批量生成核销码
     */
    public function actionBuild()
    {
        $p = $this->getParams();

        $user_id = 4070;
        $base = [];
        for ($i = 0; $i < $p['total']; $i++) {
            $base[] = [
                'user_id'      => $user_id,
                'order_number' => Helper::createOrderNumber(2, $user_id),
                'code'         => Helper::createTicketNumber($p['product_supplier_id'], $user_id),
            ];
        }

        $this->handlerForBuildAndImport($base, $p['product_supplier_id'], $p['package_id'], $p['remark'], 'build');
    }

    /**
     * 批量导入核销码
     *
     * @throws
     */
    public function actionImport()
    {
        $p = $this->getParams();
        $file = (new Attachment())->first(['id' => $p['excel_id']], Yii::$app->params['use_cache']);

        $fileUrl = "https://kake-file.oss-cn-shanghai.aliyuncs.com/{$file['deep_path']}-{$file['filename']}";

        $filePath = "/tmp/{$file['filename']}";
        Helper::saveRemoteFile($fileUrl, $filePath);

        try {
            $data = $this->excelReader($filePath, ['用户名' => 'username', '手机号码' => 'phone', '核销码' => 'code']);
        } catch (\Exception $e) {
            $this->fail('请确保上传的文件为excel格式并且文件无损');
        }
        $base = [];

        $user = new User();
        $result = $user->trans(
            function () use ($user, $data, &$base) {
                foreach ($data as $item) {
                    $result = $user->add(['username' => $item['username'], 'phone' => strval($item['phone'])]);
                    if (!$result['state']) {
                        throw new yii\db\Exception($result['info']);
                    }
                    $base[] = [
                        'user_id'      => $result['data'],
                        'order_number' => Helper::createOrderNumber(2, $result['data']),
                        'code'         => $item['code'],
                    ];
                }
            },
            '导入核销码时自动建立用户'
        );
        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->handlerForBuildAndImport($base, $p['product_supplier_id'], $p['package_id'], $p['remark'], 'import');
    }

    /**
     * 处理批量导入和生成
     *
     * @param $base
     * @param $product_supplier_id
     * @param $package_id
     * @param $remark
     * @param $type
     */
    private function handlerForBuildAndImport($base, $product_supplier_id, $package_id, $remark, $type)
    {
        $packageModel = new ProductPackage();
        $package = $packageModel->first(['id' => $package_id], Yii::$app->params['use_cache']);

        $result = $packageModel->trans(
            function () use ($base, $product_supplier_id, $remark, $package, $type) {

                $type = ['build' => 1, 'import' => 2][$type];
                foreach ($base as $item) {

                    // 生成订单表数据
                    $result = (new Order())->add(
                        [
                            'order_number'      => $item['order_number'],
                            'product_id'        => $package['product_id'],
                            'user_id'           => $item['user_id'],
                            'price'             => $package['price'],
                            'order_contacts_id' => 0,
                            'payment_method'    => 9,
                            'payment_state'     => 1,

                        ]
                    );
                    if (!$result['state']) {
                        throw new yii\db\Exception($result['info']);
                    }

                    $orderId = $result['data'];

                    // 生成子订单表数据
                    $result = (new OrderSub())->add(
                        [
                            'order_id'           => $orderId,
                            'product_package_id' => $package['id'],
                            'price'              => $package['price'],
                        ]
                    );
                    if (!$result['state']) {
                        throw new yii\db\Exception($result['info']);
                    }

                    $orderSubId = $result['data'];


                    // 生成子订单核销码数据
                    $result = (new OrderSoldCode())->add(
                        [
                            'order_sub_id'        => $orderSubId,
                            'product_supplier_id' => $product_supplier_id,
                            'code'                => $item['code'],
                            'type'                => $type,
                            'remark'              => $remark,
                        ]
                    );
                    if (!$result['state']) {
                        throw new yii\db\Exception($result['info']);
                    }
                }

                return true;
            },
            '导入/生成核销码'
        );

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }
}