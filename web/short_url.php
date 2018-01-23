<?php

include '../vendor/jtleon/oil/dispatcher/AutoLoader.php';

use Oil\dispatcher\AutoLoader;
use Oil\src\Helper;
use Oil\src\SimplePdo;
use Oil\dispatcher\ServiceLocator;

class ShortUrl
{
    /**
     * @var SimplePdo
     */
    private $db;

    /**
     * ShortUrl constructor.
     */
    public function __construct()
    {
        @$config = require '../config/main.php';
        $config = $config['components']['kake'];

        $dsn = current(current($config['slaves']));

        $oil = new ServiceLocator([
            'pdo' => [
                'class' => 'Oil\src\SimplePdo',
                'config' => [
                    'dsn' => $dsn,
                    'username' => $config['slaveConfig']['username'],
                    'password' => $config['slaveConfig']['password']
                ]
            ]
        ]);

        $this->db = $oil->pdo;
    }

    /**
     * Show message
     *
     * @param string $message
     */
    public function error($message)
    {
        exit('Error: ' . $message);
    }

    /**
     * Get short url id
     *
     * @return integer
     */
    public function getShortId()
    {
        if (empty($id = trim($_SERVER['REQUEST_URI'], '/'))) {
            $this->error('without short url id');
        }

        if (empty($id = Helper::hexN2Decimal($id))) {
            $this->error('short url id illegal');
        }

        return $id;
    }

    /**
     * Resolve the short url
     */
    public function resolve()
    {
        $id = $this->getShortId();
        $record = $this->db->fetchOne("SELECT * FROM short_url where id = ?", [$id]);

        if (empty($record)) {
            $this->error('not found url with the id');
        }

        if (empty($record['state'])) {
            $this->error('the url already invalidated');
        }

        // redirect
        header('Location: ' . $record['url']);
    }
}

AutoLoader::register();

$short = new ShortUrl();
$short->resolve();