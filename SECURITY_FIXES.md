# Kkf 安全加固说明（未删除任何原文件）

更新日期：2026-11-26（Asia/Shanghai）

## 发现与处理

| 问题 | 处理方式 |
|------|----------|
| `public/caches.rar` → `caches/lamp.php` 混淆 WebShell（`eval`） | **保留文件名/路径**，内容替换为 404 安全桩；并重建 `caches.rar` |
| `public/index.php` 硬编码 `app_key/app_secret/域名/盐` | 改为读取 `.env` / `runtime/local_secrets.php` |
| `config/database.php` 含真实库账号密码 | 改为环境变量，默认占位符 |
| `config/config.php` `app_debug=true` | 改为 `false` |
| `ymwl_pusher/config.php` 硬编码密钥与演示域名 | 改为与 Web 共用 env |
| `public/migrate_keyword.php` 未鉴权可改库 | 默认 403，需手动放行锁文件才可执行一次 |
| `数据库.sql.zip` 含会话 Token、推送密钥、旧管理员哈希 | 清洗 Token/密钥；平台管理员密码重置为 `ChangeMe123`（务必立刻修改） |
| 前端 `ymwl_*.js` 写死 `kf3.yanshi.fun` | 改为当前站点 `location.origin` |
| 根目录空 `start.php` | 保留文件，写入安全提示 |

## 部署注意

1. 复制 `.env.example` 为项目根目录 `.env` 并填写真实库与域名。  
2. **新装优先用** `install/data.sql`（干净结构）。演示库仅用已清洗的 `数据库.sql.zip` / `install/demo_dump_sanitized.sql`。  
3. 若仍解压旧版 `application.rar` 等，请使用本仓库已重建的 rar（与解压目录内容一致）。  
4. 登录后立刻修改平台管理员与客服管理员密码。  
5. 生产环境确认 `app_debug=false`，并删除/锁定安装入口（`install/install.lock` 已存在时安装页应不可重装）。

## 未删除

所有原有 `.rar` / 资源 / 目录均保留；后门仅清空或改写内容，不删路径。
