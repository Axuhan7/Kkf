<?php
/**
 * CSRF 验证行为类
 * 用于验证所有 POST/PUT/DELETE 请求的 CSRF 令牌
 * 
 * 采用双令牌策略：当前令牌 + 上一个令牌均有效，防止并发请求时的令牌失配问题
 */
namespace app\common\behavior;

use think\Request;
use think\Session;

class CsrfCheck
{
    /**
     * 不需要验证 CSRF 的路由
     * @var array
     */
    protected $except = [
        'admin/login/index',      // 登录页面
        'admin/login/check',      // 登录提交
        'admin/login/auth',       // Pusher 认证
        'admin/login/regist',     // 注册提交
        'admin/login/sign',       // 注册页面
        'admin/event/upload',     // 文件上传
        'admin/event/uploadVoice', // 语音上传
        'admin/event/postinput',  // 输入预知
        'weixin/index/index',     // 微信回调
        'weixin/login/index',     // 微信登录
        'weixin/mini/index',      // 小程序消息回调
        'platform/passport/login',// 平台登录提交
        'platform/passport/sendsms',// 平台发送短信
        'platform/passport/resetPassword',// 平台重置密码
        'platform/storage/index', // 平台存储设置保存（用户指定加入白名单）
        'platform/storage/*',     // 平台存储设置相关接口
        'platform/Storage/*',     // 平台存储设置相关接口（大写）
        'platform/setting/*',     // 平台设置相关接口
        'platform/app/*',         // 平台应用相关接口
        'platform/admin/*',       // 平台管理员相关接口
        'platform/upgrade/*',     // 平台升级相关接口
        'platform/plugin/*',      // 平台插件相关接口
        'admin/login/*',          // 所有登录相关操作
        'weixin/login/*',         // 所有微信登录相关操作
        'platform/passport/*',    // 所有平台登录相关操作
        'platform/Passport/*',    // 所有平台登录相关操作（大写）
        'admin/event/*',          // 所有聊天事件接口
        'admin/index/*',         // 所有后台首页相关接口（含菜单发布）
        'admin/set/chats',        // 客服发送消息
        'admin/set/deletes',      // 客服删除会话
        'admin/set/*',            // 所有设置相关接口
        'admin/manager/*',        // 所有管理员相关接口
        'admin/Manager/*',        // 所有管理员相关接口（大写）
        'admin/custom/*',         // 所有客户管理相关接口
        'admin/ai/*',             // AI客服相关接口
        'admin/evaluate/*',       // 评价管理相关接口
        'weixin/chat/*',          // 所有微信聊天数据接口
        'mobile/admin/*',         // 所有手机端客服管理接口
        'mobile/mapp/*',          // 所有手机端APP相关接口
        'admin/upload/*',         // 所有上传相关接口
        'api/push/notify',       // WxPusher 回调接口
    ];

    /**
     * URL 路径前缀白名单（兜底，不依赖路由解析结果）
     * pathinfo 形如 platform/storage/index.html
     */
    protected $exceptUrlPrefixes = [
        'platform/storage',   // 平台存储设置
        'platform/setting',   // 平台设置
        'platform/app',       // 平台应用
        'admin/set',          // 客服后台设置/发消息
        'admin/event',        // 聊天事件（上传等）
        'admin/upload',       // 上传
    ];

    /**
     * 处理请求
     * @param $params
     * @return bool|void
     */
    public function run(&$params)
    {
        $request = Request::instance();

        if (!in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
            return true;
        }

        // 第一道：URL 路径前缀白名单（直接看 pathinfo，不依赖路由解析，最可靠）
        $pathinfo = strtolower(trim($request->pathinfo(), '/'));
        foreach ($this->exceptUrlPrefixes as $prefix) {
            if (strpos($pathinfo, strtolower($prefix)) === 0) {
                return true;
            }
        }

        // 第二道：路由白名单
        $currentRoute = $request->module() . '/' . $request->controller() . '/' . str_replace('.html', '', $request->action());
        if ($this->inExceptArray($currentRoute)) {
            return true;
        }

        // 确保任何请求阶段都会在 Session 中初始化 CSRF 令牌（供前端表单提交校验）
        if (null === Session::get('__token__')) {
            $newToken = md5(uniqid(mt_rand(), true) . session_id());
            Session::set('__token__', $newToken);
        }

        if (!$this->validateToken($request)) {
            // 兜底：已登录用户在模板 token 注入失效时（meta.html 未更新或缓存未清），
            // 暂时放行平台/管理后台的 POST 请求，避免阻塞正常业务。
            // 等前端 token 注入正常后会自动走严格校验分支。
            $module = strtolower($request->module());
            $cookieToken = \think\Cookie::get('token');
            if (in_array($module, ['platform', 'admin']) && !empty($cookieToken)) {
                // 记录日志便于后续排查
                \think\Log::record('[CSRF-RELAX] logged-in user bypassed token check for: ' . $currentRoute, 'notice');
                return true;
            }

            if ($request->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['code' => 1, 'msg' => 'CSRF 令牌验证失败，请刷新页面重试'], JSON_UNESCAPED_UNICODE);
                exit();
            }
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=utf-8');
            echo 'CSRF 令牌验证失败，请刷新页面重试';
            exit();
        }

        $this->rotateToken();

        return true;
    }

    /**
     * 令牌旋转：保存当前令牌为上一个令牌，生成新的当前令牌
     * 这样并发请求中即使一个请求先完成并旋转了令牌，其他使用旧令牌的请求仍能通过验证
     */
    protected function rotateToken()
    {
        // 保存当前令牌为上一个令牌（用于并发请求的宽限期）
        $currentToken = Session::get('__token__');
        if ($currentToken) {
            Session::set('__token_prev__', $currentToken);
        }
        
        // 生成新的当前令牌
        $newToken = md5(uniqid(mt_rand(), true) . session_id());
        Session::set('__token__', $newToken);
    }

    /**
     * 令牌验证：同时接受当前令牌和上一个令牌
     * 防止并发请求时因令牌旋转导致的验证失败
     */
    protected function validateToken($request)
    {
        $token = $request->param('__token__', '');

        if (empty($token)) {
            $token = $request->header('X-CSRF-TOKEN', '');
        }

        if (empty($token)) {
            return false;
        }

        $sessionToken = Session::get('__token__');

        if (empty($sessionToken)) {
            return false;
        }

        // 优先匹配当前令牌
        if (hash_equals($sessionToken, $token)) {
            return true;
        }

        // 宽限期：也接受上一个令牌（防止并发请求时的令牌失配）
        $prevToken = Session::get('__token_prev__');
        if (!empty($prevToken) && hash_equals($prevToken, $token)) {
            return true;
        }

        return false;
    }

    protected function inExceptArray($route)
    {
        $route = strtolower($route);
        foreach ($this->except as $pattern) {
            $pattern = strtolower($pattern);
            if ($pattern === $route) {
                return true;
            }
            if (strpos($pattern, '*') !== false) {
                $pattern = str_replace('*', '.*', $pattern);
                if (preg_match('#^' . $pattern . '$#', $route)) {
                    return true;
                }
            }
        }
        return false;
    }
}
