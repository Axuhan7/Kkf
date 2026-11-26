<?php
/**
 * SECURITY: One-off migration endpoint disabled by default.
 * File retained. To run intentionally, create runtime/allow_migrate_keyword.lock
 * then open this URL once, then remove the lock file.
 */
$lock = dirname(__DIR__) . '/runtime/allow_migrate_keyword.lock';
if (!is_file($lock)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden: create runtime/allow_migrate_keyword.lock to enable this one-off migration.";
    exit;
}

require_once '../thinkphp/base.php';
use think\Db;

try {
    $sql = "ALTER TABLE wolive_business ADD COLUMN keyword_block TEXT NOT NULL DEFAULT '' COMMENT '关键词屏蔽，多个关键词以半角逗号隔开'";
    Db::execute($sql);
    echo "字段添加成功";
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "字段已存在，无需重复添加";
    } else {
        echo "错误: " . $e->getMessage();
    }
}

@unlink($lock);
