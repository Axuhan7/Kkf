<?php

namespace app\common\lib;

use app\platform\model\Option;
use think\Db;
use think\Log;

/**
 * WxPusher 开放接口（创建二维码、发消息等）
 */
class WxPusher
{
    const DEFAULT_TOKEN = '';
    const DEFAULT_APP_ID = '';

    /** 访客发消息后推送客服的默认文案（与平台端表单默认值一致） */
    const DEFAULT_NOTICE_CONTENT = '您好, 您有新消息, 请及时查看';

    public static function getAppToken()
    {
        $v = Option::gets('push_app_token', 0, 'admin', null);
        return ($v !== null && $v !== '') ? (string)$v : self::DEFAULT_TOKEN;
    }

    public static function getAppId()
    {
        $v = Option::gets('push_app_id', 0, 'admin', null);
        return ($v !== null && $v !== '') ? (string)$v : self::DEFAULT_APP_ID;
    }

    /**
     * 访客发消息后通知客服的推送正文（平台端可配置）
     */
    public static function getNoticeContent()
    {
        $v = Option::gets('push_notice_content', 0, 'admin', null);
        if ($v !== null && trim((string)$v) !== '') {
            return (string)$v;
        }
        return self::DEFAULT_NOTICE_CONTENT;
    }

    /**
     * 向指定客服发送 WxPusher 文本通知（需已绑定 push_uid）
     *
     * @param int $serviceId
     * @return bool 是否已发起请求（未配置 token/uid 时返回 false）
     */
    public static function sendToService($serviceId)
    {
        $serviceId = (int)$serviceId;
        if ($serviceId <= 0) {
            return false;
        }
        $token = self::getAppToken();
        if ($token === '') {
            return false;
        }
        $pushUid = Db::name('service')->where('service_id', $serviceId)->value('push_uid');
        if ($pushUid === null || $pushUid === '') {
            return false;
        }
        $url = 'https://wxpusher.zjiecode.com/api/send/message';
        $data = [
            'appToken' => $token,
            'content' => self::getNoticeContent(),
            'contentType' => 1,
            'uids' => [(string)$pushUid],
        ];
        $raw = self::curlPostJson($url, $data);
        $result = json_decode($raw, true);
        if (is_array($result) && isset($result['code']) && (int)$result['code'] !== 1000) {
            Log::warning('[wxpusher send] ' . $raw);
        }
        return true;
    }

    /**
     * 创建绑定二维码，extra 会原样出现在用户扫码后的回调里（用于关联客服 id）
     *
     * @param string $extra
     * @return array|null
     */
    public static function createQrcode($extra)
    {
        $url = 'https://wxpusher.zjiecode.com/api/fun/create/qrcode';
        $token = self::getAppToken();
        if ($token === '') {
            return ['code' => -1, 'msg' => '未在平台端配置推送 APP_TOKEN'];
        }
        $data = [
            'appToken' => $token,
            'extra' => (string)$extra,
        ];
        $raw = self::curlPostJson($url, $data);
        $result = json_decode($raw, true);
        if (!is_array($result)) {
            return ['code' => -1, 'msg' => 'WxPusher 返回无效', 'raw' => $raw];
        }
        return $result;
    }

    public static function curlPostJson($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
