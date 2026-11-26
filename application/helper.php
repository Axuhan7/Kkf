<?php
// +----------------------------------------------------------------------
// | 助手函数文件
// +----------------------------------------------------------------------

use think\Session;

/**
 * 生成CSRF令牌
 * 保留上一个令牌以支持并发请求的宽限期
 * @return string
 */
function csrf_token()
{
    // 保留当前令牌为上一个令牌（宽限期）
    $currentToken = Session::get('__token__');
    if ($currentToken) {
        Session::set('__token_prev__', $currentToken);
    }
    
    $token = bin2hex(random_bytes(32));
    Session::set('__token__', $token);
    return $token;
}

/**
 * 获取CSRF令牌字段HTML
 * @return string
 */
function csrf_field()
{
    $token = csrf_token();
    return '<input type="hidden" name="__token__" value="' . $token . '" />';
}

/**
 * 验证CSRF令牌
 * @param string $token
 * @return bool
 */
function csrf_check($token)
{
    $sessionToken = Session::get('__token__');
    if (empty($token) || empty($sessionToken)) {
        return false;
    }
    return hash_equals($sessionToken, $token);
}
