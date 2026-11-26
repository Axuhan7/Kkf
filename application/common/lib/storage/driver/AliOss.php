<?php
/**
 * @copyright Powered by 在线客服系统
 * Created by PhpStorm.
 * User: www.yanshi.fun
 * Date: 2020/6/17
 * Time: 15:51
 */
namespace app\common\lib\storage\driver;

use app\common\lib\storage\Driver;
use OSS\OssClient;

class AliOss extends Driver
{
    /**
     * 架构函数
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        parent::__construct();
    }

    public function put()
    {
        $isCName = (!empty($this->options['cname']) && $this->options['cname'] == 1) ? true : false;
        // 补齐 endpoint 协议前缀（缺省时使用 https），保证 OssClient 和 URL 拼接都正确
        $domain = $this->options['domain'];
        if (stripos($domain, '://') === false) {
            $endpointForClient = 'https://' . $domain;
        } else {
            $endpointForClient = $domain;
        }
        $client = new OssClient($this->options['access_key'], $this->options['secret_key'], $endpointForClient, $isCName);

        $object = trim($this->saveFileFolder . '/' . $this->saveFileName, '/');
        $client->uploadFile($this->options['bucket'], $object, $this->file->getInfo('tmp_name'));

        if (!$isCName) {
            // 标准 endpoint：拆分协议和 host，拼接成 scheme://bucket.host 形式
            if (stripos($domain, '://') !== false) {
                list($scheme, $host) = explode('://', $domain, 2);
            } else {
                $scheme = 'https';
                $host = $domain;
            }
            $urlPrefix = $scheme . '://' . $this->options['bucket'] . '.' . rtrim($host, '/');
        } else {
            // 自定义域名：如果没有协议补齐 https
            if (stripos($domain, '://') === false) {
                $urlPrefix = 'https://' . rtrim($domain, '/');
            } else {
                $urlPrefix = rtrim($domain, '/');
            }
        }
        $this->url = $urlPrefix . '/' . ltrim($this->saveFileFolder . '/' . $this->saveFileName, '/');
        $this->thumbUrl = $this->url;

        return $this->save();
    }
}