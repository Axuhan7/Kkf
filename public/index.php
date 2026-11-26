<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// Application entry — secrets loaded from .env / runtime/local_secrets.php
// +----------------------------------------------------------------------

define('APP_PATH', __DIR__ . '/../application/');
define('VENDOR', __DIR__ . '/../vendor/');
define('CONF_PATH', __DIR__ . '/../config/');

require __DIR__ . '/bootstrap_env.php';

$lkversion = include CONF_PATH . 'version.php';

$appDomain = rtrim(ymwl_env('APP_DOMAIN', ''), '/');
if ($appDomain === '') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1';
    $appDomain = ($https ? 'https://' : 'http://') . $host;
}

define('app_key', ymwl_env('PUSHER_APP_KEY', ''));
define('app_secret', ymwl_env('PUSHER_APP_SECRET', ''));
define('app_id', (int)ymwl_env('PUSHER_APP_ID', '232'));
define('whost', ymwl_env('PUSHER_WHOST', 'wss://' . preg_replace('#^https?://#', '', $appDomain)));
define('ahost', ymwl_env('PUSHER_AHOST', 'http://127.0.0.1'));
define('wport', (int)ymwl_env('PUSHER_WPORT', '2081'));
define('aport', (int)ymwl_env('PUSHER_APORT', '2082'));
define('registToken', ymwl_env('REGIST_TOKEN', ''));
define('YMWL_SALT', ymwl_env('YMWL_SALT', ''));
define('LK_VERSION', $lkversion['LK_VERSION']);
define('PUBLIC_PATH', __DIR__);
define('EXTEND_PATH', '../extend/');
define('domain', $appDomain);

require __DIR__ . '/../thinkphp/start.php';
