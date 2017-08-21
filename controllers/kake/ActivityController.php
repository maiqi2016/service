<?php

namespace service\controllers\kake;

use service\controllers\MainController;
use service\components\Helper;
use service\models\kake\ActivityLotteryCode;
use service\models\kake\ActivityStory;
use service\models\kake\ActivityWinningCode;
use yii;

/**
 * Activity controller
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-01-12 14:00:57
 */
class ActivityController extends MainController
{

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

            $company = $params['company'];
            $total = $model::findBySql($sql, [':company' => $company])->count();

            if (in_array($company, [25])) { // 有序抽奖码
                $serial = str_pad($total + 1, 3, 0, STR_PAD_LEFT);
                $params['code'] = $model->_company_en[$company] . $serial;
            } else {
                $company = dechex($company + 500);
                $serial = Helper::integerEncode($total + 1, null);
                $params['code'] = $company . $serial;
            }

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

    /**
     * 批量生成抽奖码
     *
     * @access public
     *
     * @param integer $total
     * @param integer $winning
     *
     * @return void
     */
    public function actionGenerateWinningCode($total, $winning)
    {
        $code = Helper::generateCode(8, $total);
        $winning = Helper::generateRandMultipleNum(0, $total - 1, $winning);

        $model = new ActivityWinningCode();

        $items = [];
        foreach ($code as $key => $item) {
            $data = [
                $item,
                null
            ];
            if (in_array($key, $winning)) {
                $data[1] = 1;
            }
            $items[] = $data;
        }

        $result = $model->batchAdd([
            'code',
            'winning'
        ], $items);

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success(['effect' => $result['data']]);
    }

    /**
     * 核实是否中奖
     *
     * @access public
     * @return void
     */
    public function actionLogWinningCode()
    {
        $params = $this->getParams();
        $model = new ActivityWinningCode();

        // 核对该用户是否领取过
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
                'winning' => $record['winning'],
                'error' => 'user_already_receive'
            ]);
        }

        // 核对该抽奖码是否被领取
        $record = $model->first(function ($ar) use ($params) {
            /**
             * @var $ar yii\db\Query
             */
            $ar->where(['code' => $params['code']]);
            $ar->andWhere(['state' => 1]);

            return $ar;
        }, Yii::$app->params['use_cache']);

        if (empty($record)) {
            $this->success(['error' => 'code_error']);
        }

        if (!empty($record['openid'])) {
            $this->success(['error' => 'code_already_received']);
        }

        // 领取动作
        $result = $model->edit([
            'code' => $params['code'],
            'state' => 1
        ], [
            'openid' => $params['openid'],
            'nickname' => $params['nickname']
        ]);

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success([
            'winning' => $record['winning'],
            'error' => null
        ]);
    }
}