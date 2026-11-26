-- AI客服配置表
CREATE TABLE IF NOT EXISTS `wolive_ai_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID',
  `model_type` enum('baidu_wenxin','doubao','alibaba_tongyi','deepseek','openai_chatgpt','google_gemini','flash_thinking') NOT NULL COMMENT '模型类型',
  `api_key` varchar(500) NOT NULL DEFAULT '' COMMENT 'API密钥',
  `secret_key` varchar(500) NOT NULL DEFAULT '' COMMENT 'Secret Key（部分模型需要）',
  `api_url` varchar(500) NOT NULL DEFAULT '' COMMENT '自定义API地址',
  `model_name` varchar(255) NOT NULL DEFAULT '' COMMENT '模型名称',
  `temperature` decimal(3,2) NOT NULL DEFAULT '0.7' COMMENT '温度参数',
  `max_tokens` int(11) NOT NULL DEFAULT '2000' COMMENT '最大token数',
  `is_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否设为默认',
  `prompt_template` text COMMENT '提示词模板',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `model_type` (`model_type`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='AI客服配置表';

-- AI训练数据表（用于喂数据）
CREATE TABLE IF NOT EXISTS `wolive_ai_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '数据标题',
  `content` longtext NOT NULL COMMENT '训练内容（产品参数、话术等）',
  `type` enum('product','faq','sales','other') NOT NULL DEFAULT 'other' COMMENT '数据类型',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `type` (`type`),
  KEY `is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='AI训练数据表';

-- 在wolive_business表中添加AI客服相关字段
ALTER TABLE `wolive_business` 
ADD COLUMN `ai_service_state` enum('open','close') NOT NULL DEFAULT 'close' COMMENT 'AI客服开关' AFTER `keyword_block`,
ADD COLUMN `ai_auto_reply_delay` int(11) NOT NULL DEFAULT '3' COMMENT 'AI自动回复延迟（秒）' AFTER `ai_service_state`,
ADD COLUMN `ai_priority` enum('ai_first','human_first') NOT NULL DEFAULT 'human_first' COMMENT '优先级：ai_first优先AI回复，human_first优先人工' AFTER `ai_auto_reply_delay`;

-- AI对话记录表
CREATE TABLE IF NOT EXISTS `wolive_ai_chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID',
  `visiter_id` varchar(200) NOT NULL COMMENT '访客ID',
  `service_id` int(11) NOT NULL DEFAULT '0' COMMENT '客服ID',
  `question` text NOT NULL COMMENT '用户问题',
  `answer` text NOT NULL COMMENT 'AI回答',
  `model_type` varchar(50) NOT NULL DEFAULT '' COMMENT '使用的模型类型',
  `token_usage` int(11) NOT NULL DEFAULT '0' COMMENT 'token消耗',
  `response_time` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '响应时间(秒)',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `visiter_id` (`visiter_id`),
  KEY `create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='AI对话记录表';