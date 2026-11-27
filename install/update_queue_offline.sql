-- 排队提示 + 离线自动回复（可重复执行）
SET NAMES utf8mb4;

-- business: 排队提示开关与文案
SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wolive_business' AND COLUMN_NAME = 'queue_tip_state'),
    'SELECT 1',
    'ALTER TABLE `wolive_business` ADD COLUMN `queue_tip_state` enum(''open'',''close'') NOT NULL DEFAULT ''open'' COMMENT ''是否向访客显示排队人数提示'' AFTER `ai_auto_reply_delay`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wolive_business' AND COLUMN_NAME = 'queue_tip_text'),
    'SELECT 1',
    'ALTER TABLE `wolive_business` ADD COLUMN `queue_tip_text` varchar(500) NOT NULL DEFAULT ''通知 ： 现在还有{num} 人在排队，请等待 ....'' COMMENT ''排队提示文案，{num}为排队人数'' AFTER `queue_tip_state`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wolive_business' AND COLUMN_NAME = 'offline_auto_reply'),
    'SELECT 1',
    'ALTER TABLE `wolive_business` ADD COLUMN `offline_auto_reply` enum(''open'',''close'') NOT NULL DEFAULT ''open'' COMMENT ''客服离线时是否自动回复问候语'' AFTER `queue_tip_text`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wolive_business' AND COLUMN_NAME = 'offline_greeting'),
    'SELECT 1',
    'ALTER TABLE `wolive_business` ADD COLUMN `offline_greeting` varchar(500) NOT NULL DEFAULT ''您好，客服当前不在线，留言后我们会尽快回复。'' COMMENT ''离线自动回复问候语'' AFTER `offline_auto_reply`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
