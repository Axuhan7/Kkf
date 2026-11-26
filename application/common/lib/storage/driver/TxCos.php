<?php
/**
 * @copyright Powered by 在线客服系统
 * Created by PhpStorm.
 */
namespace app\common\lib\storage\driver;
use app\common\lib\storage\Driver;
use Qcloud\Cos\Client;
class TxCos extends Driver
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
        $client = new Client([
            'region' => $this->options['region'],
            'credentials' => [
                'secretId' => $this->options['secret_id'],
                'secretKey' => $this->options['secret_key'],
            ],
        ]);
        $key = $this->saveFileFolder . '/' . $this->saveFileName;
//        $result = $client->putObject([
        $result = $client->putObject([
            'Bucket' => $this->options['bucket'],
            'Key' => $key,
            'Body' => fopen($this->file->getInfo('tmp_name'), 'rb'),
        ]);
        $this->thumbUrl = rtrim($this->options['domain'], '/') . '/' . ltrim($result['Key'], '/');
        // 如果域名中没有协议前缀，补齐 https://，避免被前端当相对路径拼接本地域名
        if (stripos($this->thumbUrl, '://') === false) {
            $this->thumbUrl = 'https://' . $this->thumbUrl;
        }
        $this->url = $this->thumbUrl;
        return $this->save();
    }
}