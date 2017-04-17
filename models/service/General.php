<?php

namespace service\models\service;

use yii;
use service\models\Main;

/**
 * Main model
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-02-09 11:34:46
 */
class General extends Main
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->setDb(DB_SERVICE);
        $this->db = Yii::$app->{DB_SERVICE};
    }
}