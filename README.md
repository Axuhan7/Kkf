# Kkf 在线客服

## 安全说明

本仓库已做漏洞加固，详见 [SECURITY_FIXES.md](SECURITY_FIXES.md)。

**不要删除任何原文件**；木马 `caches/lamp.php` 已就地清空为安全桩。

### 快速配置

```bash
cp .env.example .env
# 编辑 .env 填库名/密码/域名
```

新装数据库请导入 `install/data.sql`。  
若使用演示库包 `数据库.sql.zip`：平台管理员 `admin` / `ChangeMe123`（登录后立刻修改）。

网站根目录指向 `public/`。推送：

```bash
cd ymwl_pusher && php start.php start -d
```
