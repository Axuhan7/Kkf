# Kkf 在线客服（加固版）

ThinkPHP 5.0.24 + Workerman 推送。本仓库已做漏洞加固，详见 [SECURITY_FIXES.md](SECURITY_FIXES.md)。

**不要删除任何原文件**；木马 `caches/lamp.php` 已就地清空为安全桩。

## 宝塔全新安装（推荐先看）

完整步骤（PHP / MySQL 版本、禁用函数、SSL、Pusher、网页安装）：

- **[install/安装说明.md](install/安装说明.md)** — 环境与细节  
- **[install/配置顺序.md](install/配置顺序.md)** — 从零到能聊的固定顺序  
- **[install/备用域名切换.md](install/备用域名切换.md)** — 防屏蔽：备用域名与接入链接全套改法  
- **[install/OPS.txt](install/OPS.txt)** — 运维速查  

**推荐环境：** Nginx + **MySQL 5.7/8.0** + **PHP 7.4**（不要 PHP 8），网站根目录指向 `public/`。

### 快速配置

```bash
# 全新网页安装前先删锁
rm -f install/install.lock

# 或手动配置
cp .env.example .env
# 编辑 .env 填库名/密码/域名/Pusher
```

新装数据库请导入 `install/data.sql`。  
网页安装：打开 `https://你的域名/安装.php`（装完请删除该文件）。

推送：

```bash
cd ymwl_pusher
/www/server/php/74/bin/php doctor.php
/www/server/php/74/bin/php start.php start -d
```

## App 用户资料同步

- 访客链接可带 `email`、`real_name`、`id_card`、`phone` 等参数  
- 或 `POST /api/user/sync`（签名或 `X-Api-Token`）  
- 客服后台「访客信息」显示 App 实名资料；说明见「接入方法」

若使用演示库包 `数据库.sql.zip`：平台管理员密码为 `ChangeMe123`（登录后立刻修改）。
