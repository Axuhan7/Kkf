<?php
/**
 * Ops bootstrap: load .env + ensure runtime secrets exist.
 * Safe to include from web entry and ymwl_pusher.
 */
if (defined('YMWL_ENV_BOOTSTRAPPED')) {
    return;
}
define('YMWL_ENV_BOOTSTRAPPED', true);

if (!function_exists('ymwl_putenv_safe')) {
    function ymwl_putenv_safe($name, $value)
    {
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        if (!function_exists('putenv')) {
            return;
        }
        $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        if (in_array('putenv', $disabled, true)) {
            return;
        }
        @putenv($name . '=' . $value);
    }
}

if (!function_exists('ymwl_load_env_file')) {
    function ymwl_load_env_file($file)
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }
        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';' || strpos($line, '=') === false) {
                continue;
            }
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ($name === '') {
                continue;
            }
            if ((isset($value[0]) && ($value[0] === '"' || $value[0] === "'"))
                && substr($value, -1) === $value[0]) {
                $value = substr($value, 1, -1);
            }
            if (ymwl_env($name, '') !== '') {
                continue;
            }
            ymwl_putenv_safe($name, $value);
        }
    }
}

if (!function_exists('ymwl_env')) {
    function ymwl_env($key, $default = '')
    {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        if (function_exists('getenv')) {
            $value = @getenv($key);
            if ($value !== false && $value !== '') {
                return $value;
            }
        }
        return $default;
    }
}

if (!function_exists('ymwl_ensure_secrets')) {
    function ymwl_ensure_secrets($projectRoot)
    {
        $defs = [
            'PUSHER_APP_KEY' => 32,
            'PUSHER_APP_SECRET' => 48,
            'YMWL_SALT' => 32,
            'REGIST_TOKEN' => 20,
            'API_ACCESS_TOKEN' => 32,
        ];
        $runtimeFile = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'local_secrets.php';
        $stored = [];
        if (is_file($runtimeFile)) {
            $loaded = include $runtimeFile;
            if (is_array($loaded)) {
                $stored = $loaded;
            }
        }
        $changed = false;
        foreach ($defs as $name => $len) {
            $current = ymwl_env($name, '');
            if ($current !== '') {
                continue;
            }
            if (!empty($stored[$name])) {
                $value = $stored[$name];
            } else {
                try {
                    $value = bin2hex(random_bytes((int)ceil($len / 2)));
                } catch (Exception $e) {
                    $value = md5(uniqid((string)mt_rand(), true) . microtime(true));
                }
                if (strlen($value) > $len) {
                    $value = substr($value, 0, $len);
                }
                $stored[$name] = $value;
                $changed = true;
            }
            ymwl_putenv_safe($name, $value);
        }
        if ($changed) {
            $dir = dirname($runtimeFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $export = "<?php\n// Auto-generated. Prefer .env. Do not commit.\nreturn "
                . var_export($stored, true) . ";\n";
            @file_put_contents($runtimeFile, $export, LOCK_EX);
            @chmod($runtimeFile, 0600);
        }
    }
}

$ymwlProjectRoot = dirname(__DIR__);
ymwl_load_env_file($ymwlProjectRoot . DIRECTORY_SEPARATOR . '.env');
ymwl_ensure_secrets($ymwlProjectRoot);
