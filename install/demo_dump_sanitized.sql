-- SECURITY HARDENED DUMP
-- Live tokens cleared; platform admin password reset to ChangeMe123 (change immediately).
-- Prefer install/data.sql for clean installs.

-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: kf3_yanshi_fun
-- ------------------------------------------------------
-- Server version	5.7.44-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `wolive_admin`
--

DROP TABLE IF EXISTS `wolive_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `addtime` int(11) NOT NULL DEFAULT '0',
  `is_delete` smallint(6) NOT NULL DEFAULT '0',
  `app_max_count` int(11) NOT NULL DEFAULT '0',
  `permission` longtext,
  `remark` varchar(255) NOT NULL DEFAULT '',
  `expire_time` int(11) NOT NULL DEFAULT '0' COMMENT '账户有效期至，0表示永久',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '手机号',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_admin`
--

LOCK TABLES `wolive_admin` WRITE;
/*!40000 ALTER TABLE `wolive_admin` DISABLE KEYS */;
INSERT INTO `wolive_admin` VALUES (1,'admin','c4f554cc6ec01aae6bfa3b2ab6684ed4',1784597397,0,999,'','平台管理员',0,'');
/*!40000 ALTER TABLE `wolive_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_admin_ip`
--

DROP TABLE IF EXISTS `wolive_admin_ip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_admin_ip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL COMMENT 'IP地址，支持IPv4和IPv6',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注说明',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `createtime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='管理员IP白名单表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_admin_ip`
--

LOCK TABLES `wolive_admin_ip` WRITE;
/*!40000 ALTER TABLE `wolive_admin_ip` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_admin_ip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_admin_token`
--

DROP TABLE IF EXISTS `wolive_admin_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_admin_token` (
  `token` varchar(50) NOT NULL COMMENT 'Token',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `createtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `expiretime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间',
  PRIMARY KEY (`token`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Token表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_admin_token`
--

LOCK TABLES `wolive_admin_token` WRITE;
/*!40000 ALTER TABLE `wolive_admin_token` DISABLE KEYS */;
INSERT INTO `wolive_admin_token` VALUES ();
-- SECURITY: live session tokens removed
/*!40000 ALTER TABLE `wolive_admin_token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_ai_config`
--

DROP TABLE IF EXISTS `wolive_ai_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_ai_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) DEFAULT NULL COMMENT '商户ID',
  `model_type` varchar(50) NOT NULL COMMENT '模型类型:baidu_wenxin/doubao/alibaba_tongyi/deepseek/openai_chatgpt/google_gemini/flash_thinking',
  `model_name` varchar(100) NOT NULL COMMENT '模型名称',
  `api_key` text NOT NULL COMMENT 'API密钥',
  `secret_key` text COMMENT 'Secret Key',
  `api_url` varchar(500) DEFAULT NULL COMMENT '自定义API地址',
  `temperature` decimal(3,1) DEFAULT '0.7' COMMENT '温度参数',
  `max_tokens` int(11) DEFAULT '2000' COMMENT '最大Token数',
  `prompt_template` text COMMENT '提示词模板',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '是否默认',
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='AI配置表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_ai_config`
--

LOCK TABLES `wolive_ai_config` WRITE;
/*!40000 ALTER TABLE `wolive_ai_config` DISABLE KEYS */;
INSERT INTO `wolive_ai_config` VALUES (1,1,'deepseek','deepseek-v4-pro','','','',0.7,2000,'',1785238372,1785238372,1,1,NULL,NULL);
/*!40000 ALTER TABLE `wolive_ai_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_ai_training`
--

DROP TABLE IF EXISTS `wolive_ai_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_ai_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) DEFAULT NULL COMMENT '商户ID',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `type` varchar(50) DEFAULT 'product' COMMENT '类型:product(产品参数)/faq(常见问题)/script(话术)/other(其他)',
  `content` text NOT NULL COMMENT '内容',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='AI训练数据表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_ai_training`
--

LOCK TABLES `wolive_ai_training` WRITE;
/*!40000 ALTER TABLE `wolive_ai_training` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_ai_training` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_attachment_data`
--

DROP TABLE IF EXISTS `wolive_attachment_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_attachment_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '附件id',
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT '原文件名',
  `fileext` varchar(20) NOT NULL COMMENT '文件扩展名',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `url` varchar(600) NOT NULL DEFAULT '',
  `filemd5` varchar(64) NOT NULL DEFAULT '',
  `inputtime` int(10) unsigned NOT NULL COMMENT '入库时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `inputtime` (`inputtime`) USING BTREE,
  KEY `fileext` (`fileext`) USING BTREE,
  KEY `uid` (`service_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='附件归档表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_attachment_data`
--

LOCK TABLES `wolive_attachment_data` WRITE;
/*!40000 ALTER TABLE `wolive_attachment_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_attachment_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_business`
--

DROP TABLE IF EXISTS `wolive_business`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_business` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_name` varchar(100) NOT NULL COMMENT '商家标识符',
  `logo` varchar(255) NOT NULL DEFAULT '',
  `copyright` varchar(255) NOT NULL DEFAULT '' COMMENT '底部版权信息',
  `admin_id` int(11) NOT NULL DEFAULT '0',
  `video_state` enum('close','open') NOT NULL DEFAULT 'open' COMMENT '是否开启视频',
  `voice_state` enum('close','open') NOT NULL DEFAULT 'open' COMMENT '是否开启提示音',
  `audio_state` enum('close','open') NOT NULL DEFAULT 'open' COMMENT '是否开启音频',
  `file_state` enum('close','open') NOT NULL DEFAULT 'open' COMMENT '是否开启文件',
  `template_state` enum('close','open') NOT NULL DEFAULT 'close' COMMENT '是否开启模板消息',
  `distribution_rule` enum('auto','claim') DEFAULT 'auto' COMMENT 'claim:认领，auto:自动分配',
  `voice_address` varchar(255) NOT NULL DEFAULT '/upload/voice/default.mp3' COMMENT '提示音文件地址',
  `domain_entry` varchar(200) NOT NULL DEFAULT '' COMMENT '入口域名',
  `domain_landing` varchar(200) NOT NULL DEFAULT '' COMMENT '落地域名',
  `chat_notice1` varchar(500) NOT NULL DEFAULT '' COMMENT '聊天框轮播公告1',
  `chat_notice2` varchar(500) NOT NULL DEFAULT '' COMMENT '聊天框轮播公告2',
  `announcement` varchar(500) NOT NULL DEFAULT '' COMMENT '公告文字（聊天窗口顶部轮播公告）',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `expire_time` int(11) NOT NULL DEFAULT '0',
  `max_count` int(11) NOT NULL DEFAULT '0',
  `push_url` varchar(255) NOT NULL DEFAULT '' COMMENT '推送url',
  `state` enum('close','open') NOT NULL DEFAULT 'open' COMMENT '''open'': 打开该商户 ，''close''：禁止该商户',
  `is_recycle` tinyint(2) NOT NULL DEFAULT '0',
  `is_delete` tinyint(2) NOT NULL DEFAULT '0',
  `transfer_human` enum('open','close') DEFAULT 'close' COMMENT '是否开启转人工',
  `keyword_block` varchar(500) DEFAULT NULL COMMENT '关键词屏蔽，多个关键词以逗号分隔',
  `ai_service_state` varchar(20) DEFAULT 'close' COMMENT 'AI客服开关:open/close',
  `ai_priority` varchar(20) DEFAULT 'human_first' COMMENT '回复优先级:human_first/ai_first',
  `ai_auto_reply_delay` int(11) DEFAULT '0' COMMENT '自动回复延迟(秒)',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `bussiness` (`business_name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='商家表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_business`
--

LOCK TABLES `wolive_business` WRITE;
/*!40000 ALTER TABLE `wolive_business` DISABLE KEYS */;
INSERT INTO `wolive_business` VALUES (1,'默认商户','','',1,'open','open','open','open','open','auto','/upload/voice/default.mp3','','','','','','',0,999,'','open',0,0,'close','','close','ai_first',0);
/*!40000 ALTER TABLE `wolive_business` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_chats`
--

DROP TABLE IF EXISTS `wolive_chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_chats` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `visiter_id` varchar(200) NOT NULL COMMENT '访客id',
  `service_id` int(11) NOT NULL COMMENT '客服id',
  `business_id` int(11) NOT NULL DEFAULT '0' COMMENT '商家id',
  `content` mediumtext NOT NULL COMMENT '内容',
  `timestamp` int(11) NOT NULL,
  `state` enum('readed','unread') NOT NULL DEFAULT 'unread' COMMENT 'unread 未读；readed 已读',
  `direction` enum('to_visiter','to_service') DEFAULT NULL,
  `unstr` varchar(60) NOT NULL DEFAULT '' COMMENT '前端唯一字符串用于撤销使用',
  `avatar` varchar(1024) NOT NULL DEFAULT '' COMMENT '发送者头像',
  PRIMARY KEY (`cid`) USING BTREE,
  KEY `visiter_id` (`visiter_id`) USING BTREE,
  KEY `service_id` (`service_id`) USING BTREE,
  KEY `business_id` (`business_id`) USING BTREE,
  KEY `unstr` (`unstr`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=313 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='消息表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_chats`
--

LOCK TABLES `wolive_chats` WRITE;
/*!40000 ALTER TABLE `wolive_chats` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_chats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_comment`
--

DROP TABLE IF EXISTS `wolive_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_comment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT '0',
  `service_id` int(11) NOT NULL DEFAULT '0',
  `group_id` int(11) NOT NULL DEFAULT '0',
  `visiter_id` varchar(200) NOT NULL DEFAULT '',
  `visiter_name` varchar(255) NOT NULL DEFAULT '',
  `word_comment` text NOT NULL COMMENT '文字评价',
  `add_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_comment`
--

LOCK TABLES `wolive_comment` WRITE;
/*!40000 ALTER TABLE `wolive_comment` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_comment_detail`
--

DROP TABLE IF EXISTS `wolive_comment_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_comment_detail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) unsigned NOT NULL,
  `title` varchar(32) NOT NULL DEFAULT '',
  `score` tinyint(4) NOT NULL DEFAULT '1' COMMENT '分数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_comment_detail`
--

LOCK TABLES `wolive_comment_detail` WRITE;
/*!40000 ALTER TABLE `wolive_comment_detail` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_comment_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_comment_setting`
--

DROP TABLE IF EXISTS `wolive_comment_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_comment_setting` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(128) NOT NULL DEFAULT '' COMMENT '评价说明',
  `comments` text NOT NULL COMMENT '评价条目',
  `word_switch` enum('close','open') NOT NULL DEFAULT 'close',
  `word_title` varchar(32) NOT NULL DEFAULT '',
  `add_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_comment_setting`
--

LOCK TABLES `wolive_comment_setting` WRITE;
/*!40000 ALTER TABLE `wolive_comment_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_comment_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_group`
--

DROP TABLE IF EXISTS `wolive_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupname` varchar(255) DEFAULT NULL,
  `business_id` int(11) unsigned NOT NULL DEFAULT '0',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_group`
--

LOCK TABLES `wolive_group` WRITE;
/*!40000 ALTER TABLE `wolive_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_input`
--

DROP TABLE IF EXISTS `wolive_input`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_input` (
  `id` int(99) NOT NULL AUTO_INCREMENT,
  `user` varchar(99) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_input`
--

LOCK TABLES `wolive_input` WRITE;
/*!40000 ALTER TABLE `wolive_input` DISABLE KEYS */;
INSERT INTO `wolive_input` VALUES (144,'4f54061019a17c86a51d13dd4fa44abb',''),(145,'5506a466f6f177abc4cd977d808eff92',''),(146,'26fd2e6fcfca361cddb69c918642f54e','');
/*!40000 ALTER TABLE `wolive_input` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_ip_access_log`
--

DROP TABLE IF EXISTS `wolive_ip_access_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_ip_access_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL COMMENT '访问者IP',
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '访问的URL',
  `module` varchar(20) NOT NULL DEFAULT '' COMMENT '访问的模块(admin/platform)',
  `createtime` int(11) NOT NULL DEFAULT '0' COMMENT '访问时间',
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `createtime` (`createtime`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='IP访问日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_ip_access_log`
--

LOCK TABLES `wolive_ip_access_log` WRITE;
/*!40000 ALTER TABLE `wolive_ip_access_log` DISABLE KEYS */;
INSERT INTO `wolive_ip_access_log` VALUES (1,'106.110.79.69','/platform/adminIp/index','platform',1781281938),(2,'106.110.79.69','/platform/admin_ip/index','platform',1781281942),(3,'106.110.79.69','/platform/admin_ip/index','platform',1781281943),(4,'106.110.79.69','/platform/admin_ip/index','platform',1781281954),(5,'106.110.79.69','/platform/admin_ip/index','platform',1781282325),(6,'106.110.79.69','/platform/admin_ip/index','platform',1781282441),(7,'106.110.79.69','/platform/admin_ip/index','platform',1781282537),(8,'106.110.79.69','/platform/admin_ip/index','platform',1781282605),(9,'106.110.79.69','/platform/admin_ip/index','platform',1781282607),(10,'106.110.79.69','/platform/admin_ip/index','platform',1781282608),(11,'106.110.79.69','/platform/admin_ip/index','platform',1781282609),(12,'106.110.79.69','/platform/admin_ip/index','platform',1781282620),(13,'106.110.79.69','/platform/admin_ip/index','platform',1781282672),(14,'106.110.79.69','/platform/admin_ip/index','platform',1781282681),(15,'106.110.79.69','/platform/admin_ip/index','platform',1781282681),(16,'106.110.79.69','/platform/admin_ip/index','platform',1781282682),(17,'106.110.79.69','/platform/admin_ip/index','platform',1781282714),(18,'106.110.79.69','/platform/admin_ip/index','platform',1781282734),(19,'106.110.79.69','/platform/admin_ip/index','platform',1781282741),(20,'106.110.79.69','/platform/admin_ip/index','platform',1781282760),(21,'106.110.79.69','/platform/admin_ip/index','platform',1781282782),(22,'106.110.79.69','/platform/admin_ip/index','platform',1781282860),(23,'106.110.79.69','/platform/admin_ip/index','platform',1781282861),(24,'106.110.79.69','/admin','admin',1781282871),(25,'106.110.79.69','/admin','admin',1781282888),(26,'106.110.79.69','/admin','admin',1781282987),(27,'106.110.79.69','/admin','admin',1781282988),(28,'106.110.79.69','/admin','admin',1781282990),(29,'106.110.79.69','/platform/admin_ip/index.html','platform',1781287225),(30,'106.110.79.69','/platform/admin_ip/add.html','platform',1781287236),(31,'106.110.79.69','/platform/admin_ip/index.html','platform',1781288914),(32,'106.110.79.69','/platform/admin_ip/index.html','platform',1781288973),(33,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289001),(34,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289043),(35,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289060),(36,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289122),(37,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289140),(38,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289173),(39,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289248),(40,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289255),(41,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289271),(42,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289311),(43,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289495),(44,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289587),(45,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289695),(46,'106.110.79.69','/platform/admin_ip/index.html','platform',1781289697),(47,'203.160.68.148','/platform/setting/index.html','platform',1781313255),(48,'0.0.0.0','/platform/setting/index.html','platform',1781313270),(49,'0.0.0.0','/platform/setting/index.html','platform',1781313279),(50,'106.110.79.69','/admin/index/index.html','admin',1781338479),(51,'106.110.79.69','/admin/index/index.html','admin',1781338490),(52,'106.110.79.69','/admin/index/index.html','admin',1781338506);
/*!40000 ALTER TABLE `wolive_ip_access_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_login_log`
--

DROP TABLE IF EXISTS `wolive_login_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_login_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登陆者ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '登陆者账号',
  `ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登陆者IP',
  `area` varchar(60) NOT NULL DEFAULT '' COMMENT '地址',
  `login_side` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0未知登陆方式1电脑登录2手机登录方式',
  `source` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1管理员2客服人员',
  `createtime` int(10) DEFAULT '0' COMMENT '登录时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `source` (`source`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=81 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='登录日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_login_log`
--

LOCK TABLES `wolive_login_log` WRITE;
/*!40000 ALTER TABLE `wolive_login_log` DISABLE KEYS */;
INSERT INTO `wolive_login_log` VALUES (80,1,'admin','171.113.16.7','中国湖北武汉',1,2,1787836255);
/*!40000 ALTER TABLE `wolive_login_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_message`
--

DROP TABLE IF EXISTS `wolive_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_message` (
  `mid` int(11) NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL COMMENT '留言内容',
  `name` varchar(255) NOT NULL COMMENT '留言人姓名',
  `moblie` varchar(255) NOT NULL COMMENT '留言人电话',
  `email` varchar(255) NOT NULL COMMENT '留言人邮箱',
  `business_id` int(11) DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mid`) USING BTREE,
  KEY `timestamp` (`timestamp`) USING BTREE,
  KEY `web` (`business_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_message`
--

LOCK TABLES `wolive_message` WRITE;
/*!40000 ALTER TABLE `wolive_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_option`
--

DROP TABLE IF EXISTS `wolive_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_option` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT '0',
  `group` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `business_id` (`business_id`) USING BTREE,
  KEY `group` (`group`) USING BTREE,
  KEY `name` (`title`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_option`
--

LOCK TABLES `wolive_option` WRITE;
/*!40000 ALTER TABLE `wolive_option` DISABLE KEYS */;
INSERT INTO `wolive_option` VALUES (1,0,'admin','title','\"\"'),(2,0,'admin','logo','\"\"'),(3,0,'admin','login_logo','null'),(4,0,'admin','copyright','\"\"'),(5,0,'admin','max_login_error','0'),(6,0,'admin','passport_bg','\"\"'),(7,0,'admin','open_register','null'),(8,0,'admin','mp_verify','\"\"'),(9,0,'admin','regist','\"0\"'),(10,0,'admin','regist_expire','\"7\"'),(11,0,'admin','regist_crnum','\"1\"'),(12,0,'admin','ind_sms','{\"aliyun\":{\"access_key_id\":\"\",\"access_key_secret\":\"\",\"sign\":\"\",\"tpl_id\":\"\"}}'),(13,0,'admin','push_app_id','\"132442\"'),(14,0,'admin','push_app_token','\"\"'),(15,0,'admin','push_notice_content','\"\\u60a8\\u597d, \\u60a8\\u6709\\u65b0\\u6d88\\u606f, \\u8bf7\\u53ca\\u65f6\\u67e5\\u770b\"'),(16,0,'admin','image_size','\"\"'),(17,0,'admin','file_size','\"\"'),(18,0,'admin','video_size','\"\"'),(19,0,'admin','audio_size','\"\"');
/*!40000 ALTER TABLE `wolive_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_question`
--

DROP TABLE IF EXISTS `wolive_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_question` (
  `qid` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT '0',
  `question` longtext NOT NULL,
  `keyword` varchar(12) NOT NULL DEFAULT '' COMMENT '关键词',
  `sort` int(11) NOT NULL DEFAULT '0',
  `answer` longtext NOT NULL,
  `answer_read` longtext NOT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1显示 0不显示',
  PRIMARY KEY (`qid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='常见问题表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_question`
--

LOCK TABLES `wolive_question` WRITE;
/*!40000 ALTER TABLE `wolive_question` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_queue`
--

DROP TABLE IF EXISTS `wolive_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_queue` (
  `qid` int(11) NOT NULL AUTO_INCREMENT,
  `visiter_id` varchar(200) NOT NULL DEFAULT '' COMMENT '访客id',
  `service_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '客服id',
  `groupid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '客服分类id',
  `business_id` int(11) unsigned NOT NULL DEFAULT '0',
  `state` enum('normal','complete','in_black_list') NOT NULL DEFAULT 'normal' COMMENT 'normal：正常接入,‘complete’:已经解决，‘in_black_list’:黑名单',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `remind_tpl` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否已发送模板消息',
  `remind_comment` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否已推送评价',
  `lastpost` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后收发信息时间',
  PRIMARY KEY (`qid`) USING BTREE,
  KEY `se` (`service_id`) USING BTREE,
  KEY `vi` (`visiter_id`) USING BTREE,
  KEY `business` (`business_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='会话表(排队表)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_queue`
--

LOCK TABLES `wolive_queue` WRITE;
/*!40000 ALTER TABLE `wolive_queue` DISABLE KEYS */;
INSERT INTO `wolive_queue` VALUES (143,'5506a466f6f177abc4cd977d808eff92',1,0,1,'normal','2026-08-27 09:50:35',0,0,0),(144,'26fd2e6fcfca361cddb69c918642f54e',1,10000,1,'normal','2026-08-27 13:10:00',0,0,0);
/*!40000 ALTER TABLE `wolive_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_reply`
--

DROP TABLE IF EXISTS `wolive_reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_reply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `word` text NOT NULL COMMENT '快捷用语',
  `service_id` int(11) unsigned NOT NULL DEFAULT '0',
  `tag` varchar(255) DEFAULT NULL,
  `sort` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_reply`
--

LOCK TABLES `wolive_reply` WRITE;
/*!40000 ALTER TABLE `wolive_reply` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_reply` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_report`
--

DROP TABLE IF EXISTS `wolive_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '投诉类型',
  `content` varchar(1200) NOT NULL DEFAULT '' COMMENT '投诉内容',
  `contact` varchar(60) NOT NULL DEFAULT '' COMMENT '联系方式',
  `ip` varchar(15) NOT NULL DEFAULT '' COMMENT '投诉者IP',
  `business_id` int(10) NOT NULL DEFAULT '0' COMMENT '商家ID',
  `createtime` int(10) NOT NULL DEFAULT '0' COMMENT '登录时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='访客投诉表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_report`
--

LOCK TABLES `wolive_report` WRITE;
/*!40000 ALTER TABLE `wolive_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_rest_setting`
--

DROP TABLE IF EXISTS `wolive_rest_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_rest_setting` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT '0',
  `state` enum('open','close') NOT NULL DEFAULT 'open',
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `week` varchar(32) NOT NULL DEFAULT '',
  `reply` varchar(255) NOT NULL DEFAULT '',
  `name_state` enum('open','close') NOT NULL DEFAULT 'open',
  `tel_state` enum('open','close') NOT NULL DEFAULT 'open',
  `add_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_rest_setting`
--

LOCK TABLES `wolive_rest_setting` WRITE;
/*!40000 ALTER TABLE `wolive_rest_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_rest_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_sentence`
--

DROP TABLE IF EXISTS `wolive_sentence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_sentence` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL COMMENT '内容',
  `service_id` int(11) NOT NULL COMMENT '所属客服id',
  `state` enum('using','unuse') DEFAULT 'unuse' COMMENT 'unuse: 未使用 ，using：使用中',
  PRIMARY KEY (`sid`) USING BTREE,
  KEY `se` (`service_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_sentence`
--

LOCK TABLES `wolive_sentence` WRITE;
/*!40000 ALTER TABLE `wolive_sentence` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_sentence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_service`
--

DROP TABLE IF EXISTS `wolive_service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_service` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) NOT NULL COMMENT '用户名',
  `nick_name` varchar(255) NOT NULL COMMENT '昵称',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `groupid` varchar(225) DEFAULT '0' COMMENT '客服分类id',
  `groupid2` varchar(255) DEFAULT '0' COMMENT '在线离线',
  `phone` varchar(255) DEFAULT '' COMMENT '手机',
  `open_id` varchar(255) NOT NULL DEFAULT '',
  `push_uid` varchar(255) NOT NULL DEFAULT '' COMMENT 'WxPusher 推送 UID',
  `email` varchar(255) DEFAULT '' COMMENT '邮箱',
  `business_id` int(11) NOT NULL DEFAULT '0',
  `avatar` varchar(1024) NOT NULL DEFAULT '/assets/images/admin/avatar-admin2.png' COMMENT '头像',
  `level` enum('super_manager','manager','service') NOT NULL DEFAULT 'service' COMMENT 'super_manager: 超级管理员，manager：商家管理员 ，service：普通客服',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属商家管理员id',
  `offline_first` tinyint(2) NOT NULL DEFAULT '0',
  `state` enum('online','offline') NOT NULL DEFAULT 'offline' COMMENT 'online：在线，offline：离线',
  PRIMARY KEY (`service_id`) USING BTREE,
  UNIQUE KEY `user_name` (`user_name`) USING BTREE,
  KEY `pid` (`parent_id`) USING BTREE,
  KEY `web` (`business_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='后台客服表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_service`
--

LOCK TABLES `wolive_service` WRITE;
/*!40000 ALTER TABLE `wolive_service` DISABLE KEYS */;
INSERT INTO `wolive_service` VALUES (1,'admin','在线客服','ea69c6be4a8bfe81d3c3a36bacbac4f1','0','0','','','UID_7QvkdQuW16zwvu8r10DRNJsaRBbJ','',1,'/upload/images/1/1785944043.png','super_manager',0,0,'offline');
/*!40000 ALTER TABLE `wolive_service` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_storage`
--

DROP TABLE IF EXISTS `wolive_storage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_storage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '存储类型：1=本地，2=阿里云，3=腾讯云，4=七牛',
  `config` text CHARACTER SET utf8mb4 NOT NULL,
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_storage`
--

LOCK TABLES `wolive_storage` WRITE;
/*!40000 ALTER TABLE `wolive_storage` DISABLE KEYS */;
INSERT INTO `wolive_storage` VALUES (1,1,2,'{\"bucket\":\"kf3\",\"domain\":\"https:\\/\\/oss-cn-shanghai.aliyuncs.com\",\"cname\":\"0\",\"access_key\":\"\",\"secret_key\":\"xXehhjuZrb8PmJAewzWof8maI66Gxq\"}',1);
/*!40000 ALTER TABLE `wolive_storage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_tablist`
--

DROP TABLE IF EXISTS `wolive_tablist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_tablist` (
  `tid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'tab的名称',
  `content_read` text,
  `content` text NOT NULL,
  `business_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_tablist`
--

LOCK TABLES `wolive_tablist` WRITE;
/*!40000 ALTER TABLE `wolive_tablist` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_tablist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_vgroup`
--

DROP TABLE IF EXISTS `wolive_vgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_vgroup` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT '0',
  `service_id` int(11) NOT NULL DEFAULT '0',
  `group_name` varchar(128) NOT NULL DEFAULT '',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `bgcolor` char(7) NOT NULL DEFAULT '#707070',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_vgroup`
--

LOCK TABLES `wolive_vgroup` WRITE;
/*!40000 ALTER TABLE `wolive_vgroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_vgroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_visiter`
--

DROP TABLE IF EXISTS `wolive_visiter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_visiter` (
  `vid` int(11) NOT NULL AUTO_INCREMENT,
  `visiter_id` varchar(200) NOT NULL COMMENT '访客id',
  `visiter_name` varchar(255) NOT NULL COMMENT '访客名称',
  `channel` varchar(255) NOT NULL COMMENT '用户游客频道',
  `avatar` varchar(1024) NOT NULL COMMENT '头像',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '用户自己填写的姓名',
  `openid` varchar(128) NOT NULL DEFAULT '',
  `tel` varchar(32) NOT NULL DEFAULT '' COMMENT '用户自己填写的电话',
  `login_times` int(11) NOT NULL DEFAULT '1' COMMENT '登录次数',
  `connect` text COMMENT '联系方式',
  `comment` text COMMENT '备注',
  `extends` text COMMENT '浏览器扩展',
  `ip` varchar(255) NOT NULL COMMENT '访客ip',
  `from_url` varchar(255) NOT NULL COMMENT '访客浏览地址',
  `msg_time` timestamp NULL DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '访问时间',
  `business_id` int(11) NOT NULL DEFAULT '0',
  `state` enum('online','offline') NOT NULL DEFAULT 'offline' COMMENT 'offline：离线，online：在线',
  `istop` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1置顶展示0未置顶',
  PRIMARY KEY (`vid`) USING BTREE,
  UNIQUE KEY `id` (`visiter_id`,`business_id`) USING BTREE,
  KEY `visiter` (`visiter_id`) USING BTREE,
  KEY `time` (`timestamp`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_visiter`
--

LOCK TABLES `wolive_visiter` WRITE;
/*!40000 ALTER TABLE `wolive_visiter` DISABLE KEYS */;
INSERT INTO `wolive_visiter` VALUES (143,'5506a466f6f177abc4cd977d808eff92','游客5506a466f6f177abc4cd977d808eff92','35353036613436366636663137376162633463643937376438303865666639322f31','/assets/images/index/avatar-red2.png','','','',1,NULL,NULL,'{\"browserName\":\"\\u5fae\\u4fe1\\u5185\\u6d4f\\u89c8\\u5668\",\"browserVersion\":\"8.0\",\"os\":null,\"engine\":\"webkit\"}','58.244.98.176','https://weixin110.qq.com/',NULL,'2026-08-27 09:50:39',1,'offline',0),(144,'26fd2e6fcfca361cddb69c918642f54e','游客26fd2e6fcfca361cddb69c918642f54e','32366664326536666366636133363163646462363963393138363432663534652f31','/assets/images/index/avatar-red2.png','','','',1,NULL,NULL,'{\"browserName\":\"\\u8c37\\u6b4c\\u6d4f\\u89c8\\u5668\",\"browserVersion\":\"151.0\",\"os\":\"\\u5fae\\u8f6fWindows\\u7cfb\\u7edf\",\"engine\":\"webkit\"}','171.113.16.7','https://www.yanshi.fun/',NULL,'2026-08-27 13:10:00',1,'offline',0);
/*!40000 ALTER TABLE `wolive_visiter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_visiter_vgroup`
--

DROP TABLE IF EXISTS `wolive_visiter_vgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_visiter_vgroup` (
  `vid` int(11) NOT NULL,
  `business_id` int(11) NOT NULL DEFAULT '0',
  `service_id` int(11) NOT NULL DEFAULT '0',
  `group_id` int(11) NOT NULL DEFAULT '0',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`vid`,`group_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_visiter_vgroup`
--

LOCK TABLES `wolive_visiter_vgroup` WRITE;
/*!40000 ALTER TABLE `wolive_visiter_vgroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_visiter_vgroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_wechat_platform`
--

DROP TABLE IF EXISTS `wolive_wechat_platform`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_wechat_platform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT '0' COMMENT '客服系统id',
  `wx_id` varchar(60) NOT NULL DEFAULT '' COMMENT '公众号原始id',
  `app_id` varchar(255) NOT NULL DEFAULT '' COMMENT '公众号appid',
  `app_secret` varchar(255) NOT NULL DEFAULT '' COMMENT '公众号appsecret',
  `wx_token` varchar(120) NOT NULL DEFAULT '' COMMENT '公众号token',
  `wx_aeskey` varchar(120) NOT NULL DEFAULT '' COMMENT '消息加解密密钥(EncodingAESKey)',
  `visitor_tpl` varchar(255) NOT NULL DEFAULT '' COMMENT '新访客模板消息',
  `msg_tpl` varchar(255) NOT NULL DEFAULT '' COMMENT '新消息提示模板消息',
  `customer_tpl` varchar(255) NOT NULL DEFAULT '' COMMENT '访客模板消息',
  `onesubtpl_id` varchar(120) NOT NULL DEFAULT '' COMMENT '订阅消息ID',
  `onesubtpl_title` varchar(120) NOT NULL DEFAULT '' COMMENT '订阅消息标题',
  `onesubtpl_desc` varchar(300) NOT NULL DEFAULT '' COMMENT '订阅消息内容',
  `isscribe` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启引导关注1开启0关闭',
  `subscribe_reply` text COMMENT '关注回复',
  `msg_reply` text COMMENT '消息回复',
  `keyword_reply` text COMMENT '关键词回复',
  `menu_config` text COMMENT '菜单配置',
  `mini_app_id` varchar(255) NOT NULL DEFAULT '' COMMENT '小程序appid',
  `mini_app_secret` varchar(255) NOT NULL DEFAULT '' COMMENT '小程序appsecret',
  `mini_wx_token` varchar(120) NOT NULL DEFAULT '' COMMENT '小程序token',
  `mini_wx_aeskey` varchar(120) NOT NULL DEFAULT '' COMMENT '小程序消息加解密密钥',
  `desc` varchar(255) NOT NULL COMMENT '公共号说明、备注',
  `addtime` int(11) NOT NULL DEFAULT '0',
  `is_delete` smallint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `business_id` (`business_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='微信公众号';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_wechat_platform`
--

LOCK TABLES `wolive_wechat_platform` WRITE;
/*!40000 ALTER TABLE `wolive_wechat_platform` DISABLE KEYS */;
INSERT INTO `wolive_wechat_platform` VALUES (1,1,'','','','','','','','','','','',0,NULL,NULL,NULL,NULL,'','','','','',0,0);
/*!40000 ALTER TABLE `wolive_wechat_platform` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wolive_weixin`
--

DROP TABLE IF EXISTS `wolive_weixin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wolive_weixin` (
  `wid` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL COMMENT '商户ID',
  `service_id` int(11) NOT NULL COMMENT '客服ID',
  `app_id` varchar(64) NOT NULL DEFAULT '' COMMENT '公众号appid',
  `open_id` varchar(255) NOT NULL COMMENT '微信用户id',
  `subscribe` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否关注微信0未关注1已关注',
  `subscribe_time` int(11) NOT NULL DEFAULT '0' COMMENT '关注时间',
  PRIMARY KEY (`wid`) USING BTREE,
  KEY `business_id` (`business_id`) USING BTREE,
  KEY `app_id` (`app_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wolive_weixin`
--

LOCK TABLES `wolive_weixin` WRITE;
/*!40000 ALTER TABLE `wolive_weixin` DISABLE KEYS */;
/*!40000 ALTER TABLE `wolive_weixin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'kf3_yanshi_fun'
--

--
-- Dumping routines for database 'kf3_yanshi_fun'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-28 16:22:25
