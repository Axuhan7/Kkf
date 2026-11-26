<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/3/20
 * Time: 16:49
 */

namespace app\api\controller;

use app\common\lib\WxPusher;
use think\Db;
use think\Log;

class Push extends Base
{
    const TOKEN = '';
    const APP_ID = '';
    const UID = 'UID_Xd1TBu305tK8sCuL0jI70OH16VSV';

    public function index()
    {
        $result = $this->push();

        return json(['code' => 0, 'data' => $result]);
    }

    /**
     * 测试发送（uids 仍为示例常量，实际业务请改为读库 push_uid）
     */
    private function push()
    {
        $url = 'https://wxpusher.zjiecode.com/api/send/message';
        $data = [
            'appToken' => WxPusher::getAppToken(),
            'content' => WxPusher::getNoticeContent(),
            'contentType' => 1,
            'uids' => [self::UID],
        ];
        $result = WxPusher::curlPostJson($url, $data);
        $result = json_decode($result, true);

        return $result;
    }

    /**
     * 扫码绑定回调：将 WxPusher uid 写入客服表 push_uid(extra 为创建二维码时传入的 service_id)
     */
    public function notify()
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = $this->request->post();
        }
        Log::info('[wxpusher notify] raw: ' . (is_string($raw) ? $raw : json_encode($payload)));

        if (empty($payload['action']) || $payload['action'] !== 'app_subscribe') {
            Log::info('[wxpusher notify] ignored, action: ' . (isset($payload['action']) ? $payload['action'] : 'empty'));
            return json(['code' => 0, 'msg' => 'ignored']);
        }
        $data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : [];
        $uid = isset($data['uid']) ? trim((string)$data['uid']) : '';
        $extra = isset($data['extra']) ? trim((string)$data['extra']) : '';
        $appId = isset($data['appId']) ? (string)$data['appId'] : '';

        Log::info('[wxpusher notify] uid: ' . $uid . ', extra: ' . $extra . ', appId: ' . $appId);

        if ($uid === '' || $extra === '' || !ctype_digit($extra)) {
            Log::error('[wxpusher notify] invalid payload');
            return json(['code' => 1, 'msg' => 'invalid payload']);
        }

        $cfgAppId = WxPusher::getAppId();
        Log::info('[wxpusher notify] cfgAppId: ' . $cfgAppId);
        if ($cfgAppId !== '' && $appId !== '' && $appId !== $cfgAppId) {
            Log::error('[wxpusher notify] appId mismatch: expected ' . $cfgAppId . ', got ' . $appId);
            return json(['code' => 1, 'msg' => 'appId mismatch']);
        }

        $serviceId = (int)$extra;
        Log::info('[wxpusher notify] updating service_id: ' . $serviceId . ' with push_uid: ' . $uid);
        $n = Db::name('service')->where('service_id', $serviceId)->update(['push_uid' => $uid]);
        if ($n === false) {
            Log::error('[wxpusher notify] db error');
            return json(['code' => 1, 'msg' => 'db error']);
        }
        Log::info('[wxpusher notify] success, affected rows: ' . $n);

        return json(['code' => 0, 'msg' => 'ok']);
    }
}
