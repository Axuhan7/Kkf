<?php
/**
 * IP白名单检查行为类
 * 用于限制 /admin 和 /platform 路径的IP访问
 */
namespace app\platform\behavior;

use think\Db;
use think\Request;

class IpWhiteList
{
    /**
     * 检查当前访问IP是否在白名单中
     * @param $params
     * @return bool|void
     */
    public function run(&$params)
    {
        $request = Request::instance();
        $module = strtolower($request->module());

        // 只对 admin 和 platform 模块进行IP检查
        if (!in_array($module, ['admin', 'platform'])) {
            return true;
        }

        // 获取当前访问IP
        $clientIp = $request->ip();

        // 查询启用的白名单IP
        $whiteListIps = Db::name('admin_ip')
            ->where('status', 1)
            ->column('ip_address');

        // 如果白名单为空，允许所有IP访问（安全考虑：首次使用或未配置时放行）
        if (empty($whiteListIps)) {
            return true;
        }

        // 检查当前IP是否在白名单中（支持精确匹配和CIDR格式）
        $isAllowed = false;
        foreach ($whiteListIps as $whiteIp) {
            if ($this->ipMatch($clientIp, $whiteIp)) {
                $isAllowed = true;
                break;
            }
        }

        // 如果IP不在白名单中，输出禁止访问页面并终止
        if (!$isAllowed) {
            // 记录非法访问日志
            @Db::name('ip_access_log')->insert([
                'ip' => $clientIp,
                'url' => $request->url(),
                'module' => $module,
                'createtime' => time()
            ]);

            // 直接输出HTML并终止（避免TP5 Response对象在行为中的兼容问题）
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=utf-8');
            echo $this->getForbiddenHtml();
            exit();
        }

        return true;
    }

    /**
     * IP匹配（支持精确匹配和CIDR网段匹配）
     * @param string $ip 当前IP
     * @param string $whiteIp 白名单IP
     * @return bool
     */
    private function ipMatch($ip, $whiteIp)
    {
        // 精确匹配
        if ($ip === $whiteIp) {
            return true;
        }

        // CIDR网段匹配 (如 192.168.1.0/24)
        if (strpos($whiteIp, '/') !== false) {
            list($subnet, $maskBits) = explode('/', $whiteIp);
            if ($this->matchCidr($ip, $subnet, $maskBits)) {
                return true;
            }
        }

        // 通配符匹配 (如 192.168.1.*)
        if (strpos($whiteIp, '*') !== false) {
            $pattern = '/^' . str_replace(['.', '*'], ['\.', '[0-9]+'], $whiteIp) . '$/';
            if (preg_match($pattern, $ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * CIDR网段匹配
     */
    private function matchCidr($ip, $subnet, $bits)
    {
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        $mask = -1 << (32 - $bits);
        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    /**
     * 获取禁止访问的HTML页面
     */
    private function getForbiddenHtml()
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>访问被拒绝</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .container {
            text-align: center;
            padding: 40px;
            max-width: 600px;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.8;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 16px;
            color: #ff5c5c;
        }
        .message {
            font-size: 18px;
            color: #ccc;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .tip {
            font-size: 14px;
            color: #888;
            margin-bottom: 30px;
        }
        a {
            display: inline-block;
            padding: 12px 30px;
            background: #05202d;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        a:hover {
            background: #0a3050;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">&#9888;</div>
        <h1>非管理员IP禁止访问</h1>
        <p class="message">您的IP地址不在管理员白名单范围内，无法访问后台管理系统。</p>
        <p class="tip">如需访问，请联系管理员将您的IP添加到白名单中。</p>
        <a href="javascript:history.back()">返回上一页</a>
    </div>
</body>
</html>
HTML;
    }
}
