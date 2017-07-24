<?php

namespace service\models\kake;

use yii;
use service\models\Main;

/**
 * Main model
 *
 * @author    Leon <jiangxilee@gmail.com>
 * @copyright 2017-02-09 11:33:07
 */
class General extends Main
{
    public static $dbIdentity;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->setDb(DB_KAKE);
        self::$dbIdentity = DB_KAKE;
        $this->db = Yii::$app->{DB_KAKE};
    }
}