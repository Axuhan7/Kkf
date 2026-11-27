<?php
use Workerman\Worker;
use Workerman\Lib\Timer;

// composer autoload
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Pusher.php';
require_once __DIR__ . '/config.php';

// WSS 证书：优先读 .env 的 PUSHER_SSL_CERT / PUSHER_SSL_PK（方案 A）
// 宝塔默认路径示例：/www/server/panel/vhost/cert/你的域名/fullchain.pem
$ws_ssl_cert = trim((string)ymwl_env('PUSHER_SSL_CERT', ''));
$ws_ssl_pk = trim((string)ymwl_env('PUSHER_SSL_PK', ''));

if ($ws_ssl_cert !== '' && $ws_ssl_pk !== '') {
    if (!is_file($ws_ssl_cert)) {
        exit("file $ws_ssl_cert not exist\n");
    }
    if (!is_file($ws_ssl_pk)) {
        exit("file $ws_ssl_pk not exist\n");
    }
    $context = array(
        'ssl' => array(
            'local_cert'  => $ws_ssl_cert,
            'local_pk'    => $ws_ssl_pk,
            'verify_peer' => false,
        )
    );
} else {
    $context = [];
}

$pusher = new Pusher\Pusher("websocket://0.0.0.0:$websocket_port", $context);
$pusher->apiListen = "http://0.0.0.0:$api_port";
if ($ws_ssl_cert !== '' && $ws_ssl_pk !== '') {
    $pusher->transport = 'ssl';
}

$pusher->appInfo = array(
    $app_key => array(
        'channel_hook' => "{$domain}/admin/event",
        'app_secret'   => $app_secret,
    ),
);

// 只能是1
$pusher->count = 1;

Worker::runAll();
