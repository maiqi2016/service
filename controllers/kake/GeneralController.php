<?php

namespace service\controllers\kake;

use Oil\src\Helper;
use service\controllers\MainController;
use service\models\kake\Attachment;
use service\models\kake\Ad;
use service\models\kake\Config;
use service\models\kake\ShortUrl;
use service\models\kake\SsoCode;
use service\models\kake\SsoToken;
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
        $config = (new Config())->listConfigKVP(($this->user->app == 2) ? null : [
            0,
            $this->user->app
        ], Yii::$app->params['use_cache']);
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
     * 新增令牌并失效授权码
     *
     * @access public
     * @return  void
     */
    public function actionNewlySsoToken()
    {
        $model = new SsoToken();
        $data = current($this->getData());

        $result = $model->trans(function () use ($model, $data) {

            $result = $model->add($data);
            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            $result = (new SsoCode())->edit([
                'id' => $data['sso_code_id']
            ], ['state' => 0]);
            if (!$result['state']) {
                throw new yii\db\Exception($result['info']);
            }

            return true;
        }, '新增令牌并失效授权码');

        if (!$result['state']) {
            $this->fail($result['info']);
        }

        $this->success();
    }

    /**
     * 获取短连接
     *
     * @param string $original_url
     */
    public function actionShortUrl($original_url)
    {
        $model = new ShortUrl();

        $uri = md5($original_url);
        $has = $model->first([
            'uri' => $uri,
            'state' => 1
        ]);

        if (empty($has)) {
            $data = $model->add([
                'uri' => $uri,
                'url' => $original_url
            ]);
            $id = $data['data'];
        } else {
            $id = $has['id'];
        }

        $shortUrl = Yii::$app->params['short_url_host'] . '/' . Helper::hexDecimal2n($id);

        $this->success($shortUrl);
    }
}