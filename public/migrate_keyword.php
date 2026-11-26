<?php
require_once '../thinkphp/base.php';
use think\Db;

try {
    $sql = "ALTER TABLE wolive_business ADD COLUMN keyword_block TEXT NOT NULL DEFAULT '' COMMENT '关键词屏蔽，多个关键词以半角逗号隔开'";
    $result = Db::execute($sql);
    echo "字段添加成功";
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "字段已存在，无需重复添加";
    } else {
        echo "错误: " . $e->getMessage();
    }
}
?>