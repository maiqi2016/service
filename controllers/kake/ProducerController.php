<?php

namespace service\controllers\kake;

use service\components\Helper;
use service\controllers\MainController;
use service\models\kake\ProducerLog;
use yii;

/**
 * Producer controller
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-06-23 13:54:21
 */
class ProducerController extends MainController
{
    /**
     * 结算
     *
     * @access public
     *
     * @param array   $log
     * @param float   $volume
     * @param integer $user_id
     *
     * @return void
     */
    public function actionSettlement($log, $volume, $user_id)
    {
        $producerLog = new ProducerLog();
        $log = Helper::parseJsonString($log);

        $this->fail('ing...');
        $producerLog->trans(function () use($producerLog, $log, $volume, $user_id) {
            foreach ($log as $id => $item) {
                $item['log_commission'] = substr($item['log_commission'], 0, 16);
                $producerLog->edit([
                    'id' => $id,
                    'producer_id' => $user_id
                ], $item);
            }
        }, '分销结算');
    }
}