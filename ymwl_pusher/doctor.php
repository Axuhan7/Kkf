<?php
/**
 * Pusher 启动诊断（宝塔 CLI 运行）：
 *   /www/server/php/74/bin/php doctor.php
 */
echo "=== ymwl_pusher doctor ===\n";
echo "PHP: " . PHP_VERSION . " (" . PHP_BINARY . ")\n";
echo "SAPI: " . PHP_SAPI . "\n";

$need = ['pcntl', 'posix', 'openssl', 'sockets'];
foreach ($need as $ext) {
    echo "ext $ext: " . (extension_loaded($ext) ? 'OK' : 'MISSING') . "\n";
}

$disabled = (string)ini_get('disable_functions');
echo "disable_functions: " . ($disabled !== '' ? $disabled : '(empty)') . "\n";
foreach (['putenv', 'pcntl_fork', 'pcntl_signal', 'pcntl_wait'] as $fn) {
    $blocked = $disabled !== '' && in_array($fn, array_map('trim', explode(',', $disabled)), true);
    echo "fn $fn: " . ($blocked ? 'DISABLED (bad for pusher)' : 'ok') . "\n";
}

require_once dirname(__DIR__) . '/public/bootstrap_env.php';
$cert = trim((string)ymwl_env('PUSHER_SSL_CERT', ''));
$pk = trim((string)ymwl_env('PUSHER_SSL_PK', ''));
$wport = (int)ymwl_env('PUSHER_WPORT', '2081');
$aport = (int)ymwl_env('PUSHER_APORT', '2082');
$domain = ymwl_env('APP_DOMAIN', '');
$key = ymwl_env('PUSHER_APP_KEY', '');
$secret = ymwl_env('PUSHER_APP_SECRET', '');

echo "APP_DOMAIN: " . ($domain !== '' ? $domain : '(empty)') . "\n";
echo "PUSHER_WPORT: $wport  PUSHER_APORT: $aport\n";
echo "PUSHER_APP_KEY: " . ($key !== '' ? '(set)' : '(empty → local_secrets)') . "\n";
echo "PUSHER_APP_SECRET: " . ($secret !== '' ? '(set)' : '(empty → local_secrets)') . "\n";
echo "PUSHER_SSL_CERT: " . ($cert !== '' ? $cert : '(empty)') . "\n";
if ($cert !== '') {
    echo "  cert exists: " . (is_file($cert) ? 'YES' : 'NO') . "\n";
}
echo "PUSHER_SSL_PK: " . ($pk !== '' ? $pk : '(empty)') . "\n";
if ($pk !== '') {
    echo "  pk exists: " . (is_file($pk) ? 'YES' : 'NO') . "\n";
}

echo "Listen check 127.0.0.1:$wport: ";
$c = @fsockopen('127.0.0.1', $wport, $errno, $errstr, 1);
if ($c) {
    fclose($c);
    echo "OPEN\n";
} else {
    echo "closed ($errno $errstr)\n";
}

echo "Done. Foreground start for real errors:\n";
echo "  /www/server/php/74/bin/php start.php start\n";
