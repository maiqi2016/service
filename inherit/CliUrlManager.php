<?php

namespace service\inherit;

use yii\web\UrlManager;

/**
 * Yii CLI parse
 *
 * @author    Leon <jiangxilee@gamil.com>
 * @copyright 2017-01-10 08:40:03
 */
class CliUrlManager extends UrlManager
{

    /**
     * @inheritDoc
     */
    public function parseRequest($request)
    {
        if (empty($_SERVER['argv']) || empty($_SERVER['argv'][1])) {
            return parent::parseRequest($request);
        }

        parse_str($_SERVER['argv'][1], $params);
        $api = !empty($params['app_api']) ? $params['app_api'] : 'main.error';

        $_GET[$this->routeParam] = strtr($api, '.', '/');
        $_GET = array_merge($_GET, $params);

        return parent::parseRequest($request);
    }
}