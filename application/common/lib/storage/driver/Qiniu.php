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
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Qiniu extends Driver
{
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        parent::__construct();
    }

    public function put()
    {
        $uploadManager = new UploadManager();
        $auth = new Auth($this->options['access_key'], $this->options['secret_key']);
        $token = $auth->uploadToken($this->options['bucket']);

        $key = trim($this->saveFileFolder . '/' . $this->saveFileName, '/');
        list($result, $error) = $uploadManager->putFile(
            $token,
            $key,
            $this->file->getInfo('tmp_name')
        );
        $domain = rtrim($this->options['domain'], '/');
        // 如果已经填写了协议前缀则保留，否则用 https://（不再硬编码 http://，避免混合内容）
        if (stripos($domain, '://') !== false) {
            $this->url = $domain . '/' . ltrim($result['key'], '/');
        } else {
            $this->url = 'https://' . $domain . '/' . ltrim($result['key'], '/');
        }
        $this->thumbUrl = $this->url;

        return $this->save();
    }
}