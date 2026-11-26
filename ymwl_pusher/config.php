<?php
// Keep in sync with public/index.php via the same .env / secrets.
require_once dirname(__DIR__) . '/public/bootstrap_env.php';

$domainEnv = ymwl_env('APP_DOMAIN', '');
$domain = $domainEnv !== '' ? (rtrim($domainEnv, '/') . '/') : 'http://127.0.0.1/';

$app_key = ymwl_env('PUSHER_APP_KEY', '');
$app_secret = ymwl_env('PUSHER_APP_SECRET', '');
$app_id = (int)ymwl_env('PUSHER_APP_ID', '232');
$websocket_port = (int)ymwl_env('PUSHER_WPORT', '2081');
$api_port = (int)ymwl_env('PUSHER_APORT', '2082');
