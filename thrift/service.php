#!/usr/bin/env php
<?php
error_reporting(E_ALL);

$thriftPath = dirname(__DIR__) . '/vendor/packaged/thrift/src';
require_once $thriftPath . '/ClassLoader/ThriftClassLoader.php';

use Thrift\ClassLoader\ThriftClassLoader;

$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', $thriftPath);
$loader->registerDefinition('api', __DIR__ . '/gen-php');
$loader->register();

if ('cli' === php_sapi_name()) {
    ini_set('display_errors', 'stderr');
}

use Thrift\Exception\TException;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TPhpStream;
use Thrift\Transport\TBufferedTransport;

class CommonHandler implements \api\CommonIf
{

    /**
     * Run the service
     *
     * @access  public
     *
     * @param   string $params
     *
     * @return  string
     */
    public function run($params)
    {
        exec('php ../web/index.php "' . $params . '"', $result);

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}

try {

    header('Content-Type', 'application/x-thrift');
    if ('cli' === php_sapi_name()) {
        echo "\r\n";
    }

    $handler = new CommonHandler();
    $processor = new \api\CommonProcessor($handler);

    $transport = new TBufferedTransport(new TPhpStream(TPhpStream::MODE_R | TPhpStream::MODE_W));
    $protocol = new TBinaryProtocol($transport, true, true);

    $transport->open();
    $processor->process($protocol, $protocol);
    $transport->close();

} catch (TException $tx) {
    print $tx->getMessage();
}
