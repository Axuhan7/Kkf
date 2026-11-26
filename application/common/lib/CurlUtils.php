<?php
namespace app\common\lib;

/**
 * cURL请求工具类
 */
class CurlUtils {

    private $ch;//curl资源对象

    /**
     * 构造方法
     * @param string $url 请求的地址
     * @param int $responseHeader 是否需要响应头信息
     */
    public function __construct($url,$responseHeader = 0,$timeout = 5){
        $this->ch = curl_init($url);
        curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);//设置以文件流的形式返回
        curl_setopt($this->ch,CURLOPT_HEADER,$responseHeader);//设置响应头信息是否返回
        curl_setopt($this->ch,CURLOPT_TIMEOUT,$timeout);//设置响应头信息是否返回
        // 启用 SSL 证书校验，防止 MITM 攻击（修复 O1）
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
        $caBundle = self::findCaBundle();
        if ($caBundle) {
            curl_setopt($this->ch, CURLOPT_CAINFO, $caBundle);
        }
    }

    /**
     * 析构方法
     */
    public function __destruct(){
        $this->close();
    }

    /**
     * 添加请求头
     * @param array $value 请求头
     */
    public function addHeader($value){
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $value);
    }

    /**
     * 发送请求
     * @return string 返回的数据
     */
    private function exec(){
        return curl_exec($this->ch);
    }

    /**
     * 发送get请求
     * @return string 请求返回的数据
     */
    public function get(){
        return $this->exec();
    }

    /**
     * 发送post请求
     * @param  arr/string $value 准备发送post的数据
     * @param boolean $https 是否为https请求
     * @return string        请求返回的数据
     */
    public function post($value,$https=true){
        if($https){
            // HTTPS 请求同样启用证书校验（修复 O1）
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
            $caBundle = self::findCaBundle();
            if ($caBundle) {
                curl_setopt($this->ch, CURLOPT_CAINFO, $caBundle);
            }
        }
        curl_setopt($this->ch,CURLOPT_POST,1);//设置post请求
        curl_setopt($this->ch,CURLOPT_POSTFIELDS,$value);
        return $this->exec();
    }

    /**
     * 查找系统 CA Bundle
     * 自动适配 Linux / macOS / Windows 环境
     * @return string|false
     */
    public static function findCaBundle()
    {
        // 项目根目录下 cacert.pem（如果用户放置）
        $projectCa = dirname(dirname(dirname(dirname(__DIR__)))) . '/public/cacert.pem';
        if (is_file($projectCa)) {
            return $projectCa;
        }
        // ThinkPHP vendor
        $thinkphpCa = dirname(dirname(dirname(dirname(__DIR__)))) . '/thinkphp/library/vendor/cacert.pem';
        if (is_file($thinkphpCa)) {
            return $thinkphpCa;
        }
        // 系统级 CA bundle（Linux/macOS）
        $systemCandidates = [
            '/etc/pki/tls/certs/ca-bundle.crt',     // RHEL/CentOS/Fedora
            '/etc/ssl/certs/ca-certificates.crt',   // Debian/Ubuntu
            '/etc/ssl/ca-bundle.pem',               // OpenSUSE
            '/etc/ssl/cert.pem',                    // Alpine/BSD
            '/usr/local/share/cacert.pem',          // MacPorts
            '/usr/local/etc/openssl/cert.pem',      // macOS Homebrew
            '/opt/local/etc/openssl/cert.pem',      // macOS MacPorts
        ];
        foreach ($systemCandidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        // Composer ca-bundle
        $composerCa = dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/composer/ca-bundle/res/cacert.pem';
        if (is_file($composerCa)) {
            return $composerCa;
        }
        return false;
    }

    /**
     * 关闭curl句柄
     */
    private function close(){
        curl_close($this->ch);
    }

}
