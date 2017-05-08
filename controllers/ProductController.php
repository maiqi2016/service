<?php

namespace service\controllers;

use service\components\Helper;
use service\models\kake\Attachment;
use service\models\kake\Product;
use service\models\kake\ProductDescription;
use service\models\kake\ProductPackage;
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
     * @param integer $product_id
     */
    public function actionPackageList($product_id)
    {
        $list = (new ProductPackage())->all(function ($list) use ($product_id) {
            $list->from('product_package AS package');
            $list->select([
                'package.id',
                'package.name',
                'package.price',
                'package.purchase_limit',
                'package.info',
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

            return $list;
        });

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
                $result = (new Attachment())->updateStateByIds($attachment['add'], $baseModel->state);
                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }
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
     * @param integer $id
     *
     * @throws yii\db\Exception
     */
    public function actionEditProduct($id)
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

                $result = $attachmentModel->updateStateByIds($attachment['add'], $baseModel->state);
                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }

                $result = $attachmentModel->updateStateByIds($attachment['del'], 0);
                if (!$result['state']) {
                    throw new yii\db\Exception($result['info']);
                }
            }

            $this->orderTagsRecord($tagsRecord, $id);

            return true;
        }, '新增酒店产品');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success(['id' => $result['data']]);
    }
}