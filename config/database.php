<?php
/**
 * Database config — prefer environment variables over hardcoded credentials.
 */
if (!function_exists('ymwl_db_env')) {
    function ymwl_db_env($key, $default = '')
    {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        if (function_exists('getenv')) {
            $v = @getenv($key);
            if ($v !== false && $v !== '') {
                return $v;
            }
        }
        return $default;
    }
}

return [
    'debug'          => false,
    'fields_strict'  => true,
    'auto_timestamp' => false,
    'sql_explain'    => false,

    'type'           => 'mysql',
    'hostname'       => ymwl_db_env('DB_HOST', '127.0.0.1'),
    'database'       => ymwl_db_env('DB_DATABASE', 'your_database'),
    'username'       => ymwl_db_env('DB_USERNAME', 'your_user'),
    'password'       => ymwl_db_env('DB_PASSWORD', 'change_me'),
    'hostport'       => ymwl_db_env('DB_PORT', '3306'),
    'prefix'         => 'wolive_',
    'charset'        => 'utf8mb4',
    'params'         => [],
];
