<?php

namespace service\controllers\kake;

use service\controllers\MainController;
use Oil\src\Helper;
use service\models\kake\Attachment;
use service\models\kake\Product;
use service\models\kake\ProductDescription;
use service\models\kake\ProductPackage;
use service\models\kake\ProductPackageBind;
use service\models\kake\ProductProducer;
use yii;

/**
 * Product controller
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-02-16 14:11:58
 */
class ProductController extends MainController
{
    /**
     * 列表套餐详情
     *
     * @access public
     *
     * @param integer $product_id
     *
     * @return void
     */
    public function actionPackageList($product_id)
    {
        $list = (new ProductPackage())->all(function ($list) use ($product_id) {
            /**
             * @var $list yii\db\Query
             */
            $list->from('product_package AS package');
            $list->select([
                'package.id',
                'package.name',
                'package.price',
                'package.purchase_limit',
                'package.info',
                'package.bidding',
                'product.sale_type',
                'product.sale_rate',
                'product.sale_from',
                'product.sale_to',
            ]);
            $list->leftJoin('product', 'package.product_id = product.id');
            $list->where([
                'package.product_id' => $product_id,
                'package.state' => 1
            ]);
            $list->orderBy('package.bidding DESC, package.update_time DESC');

            return $list;
        }, null, Yii::$app->params['use_cache']);

        $this->success($list);
    }

    /**
     * 列表套餐打包详情
     *
     * @access public
     *
     * @param integer $product_id
     *
     * @return void
     */
    public function actionPackageBindList($product_id)
    {
        $list = (new ProductPackageBind())->all(function ($list) use ($product_id) {
            /**
             * @var $list yii\db\Query
             */
            $list->select([
                'min',
                'max',
            ]);
            $list->where([
                'product_package_bind.product_id' => $product_id,
                'product_package_bind.state' => 1
            ]);

            return $list;
        }, null, Yii::$app->params['use_cache']);

        $this->success($list);
    }

    /**
     * 列表分销详情
     *
     * @access public
     *
     * @param integer $product_id
     *
     * @return void
     */
    public function actionProducerList($product_id)
    {
        $params = $this->getParams();

        $where = ['producer.product_id' => $product_id];
        if (isset($params['where']) && is_array($params['where'])) {
            $where = array_merge($params['where'], $where);
        } else {
            $where['producer.state'] = 1;
        }

        $list = (new ProductProducer())->all(function ($list) use ($where) {
            /**
             * @var $list yii\db\Query
             */
            $list->from('product_producer AS producer');
            $list->select([
                'producer.from_sales',
                'producer.to_sales',
                'producer.type',
                'producer.commission'
            ]);
            $list->where($where);
            $list->orderBy('producer.from_sales ASC');

            return $list;
        }, null, Yii::$app->params['use_cache']);

        $this->success($list);
    }

    /**
     * 新增/编辑酒店产品前置操作
     *
     * @access private
     * @return array
     */
    private function validateProductParams()
    {
        $baseModel = new Product();
        $descriptionModel = new ProductDescription();

        list($data, $attachment, $tagsRecord) = $this->getData();
        $textField = array_keys($descriptionModel->attributeLabels());
        Helper::popSome($textField, [
            'id',
            'add_time',
            'update_time',
            'state'
        ], true);

        $descriptionModel->attributes = Helper::pullSome($data, $textField);

        Helper::popSome($data, $textField);
        $baseModel->attributes = $data;

        return [
            $baseModel,
            $descriptionModel,
            $attachment,
            $tagsRecord
        ];
    }

    /**
     * 新增酒店产品
     *
     * @access public
     * @return void
     * @throws yii\db\Exception
     */
    public function actionAddProduct()
    {
        list($baseModel, $descriptionModel, $attachment, $tagsRecord) = $this->validateProductParams();
        $result = $baseModel->trans(function () use ($baseModel, $descriptionModel, $attachment, $tagsRecord) {
            if (!$descriptionModel->save()) {
                throw new yii\db\Exception(current($descriptionModel->getFirstErrors()));
            }

            $baseModel->product_description_id = $descriptionModel->id;
            if (!$baseModel->validate()) {
                throw new yii\db\Exception(current($baseModel->getFirstErrors()));
            }
            $baseModel->insert();

            if (!empty($attachment['add'])) {
                (new Attachment())->updateStateByIds($attachment['add'], $baseModel->state);
            }

            $this->orderTagsRecord($tagsRecord, $baseModel->id);

            return $baseModel->id;
        }, '新增酒店产品');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success(['id' => $result['data']]);
    }

    /**
     * 编辑酒店产品
     *
     * @access public
     *
     * @param integer $id
     *
     * @return void
     * @throws yii\db\Exception
     */
    public function actionUpdateProduct($id)
    {
        list($baseModel, $descriptionModel, $attachment, $tagsRecord) = $this->validateProductParams();
        $result = $baseModel->trans(function () use ($id, $baseModel, $descriptionModel, $attachment, $tagsRecord) {

            $baseRecord = $baseModel::findOne(['id' => $id]);
            if (empty($baseRecord->product_description_id)) {
                throw new yii\db\Exception('abnormal operation');
            }

            $descriptionRecord = $descriptionModel::findOne(['id' => $baseRecord->product_description_id]);
            foreach ($descriptionModel->attributes as $field => $value) {
                if ($field == 'id') {
                    continue;
                }
                $descriptionRecord->{$field} = $value;
            }
            if (!$descriptionRecord->save()) {
                throw new yii\db\Exception(current($descriptionRecord->getFirstErrors()));
            }

            foreach ($baseModel->attributes as $field => $value) {
                if (in_array($field, [
                    'id',
                    'product_description_id'
                ])) {
                    continue;
                }
                $baseRecord->{$field} = $value;
            }
            if (!$baseRecord->validate()) {
                throw new yii\db\Exception(current($baseRecord->getFirstErrors()));
            }
            $baseRecord->update();

            if (!empty($attachment['add']) || !empty($attachment['del'])) {
                $attachmentModel = new Attachment();

                $attachmentModel->updateStateByIds($attachment['add'], $baseModel->state);
                $attachmentModel->updateStateByIds($attachment['del'], 0);
            }

            $this->orderTagsRecord($tagsRecord, $id);

            return true;
        }, '编辑酒店产品');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success(['id' => $result['data']]);
    }
}