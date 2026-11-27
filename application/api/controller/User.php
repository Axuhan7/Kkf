<?php
namespace app\api\controller;

use app\common\lib\AppUserProfile;
use think\Db;

/**
 * App user profile sync API for customer-service visitor cards.
 */
class User extends Base
{
    /**
     * Sync App user real-name / profile into visitor record.
     *
     * POST /api/user/sync
     * Required: business_id, visiter_id
     * Auth: sign = md5(business_id#visiter_id#timestamp#app_secret)
     *    or header X-Api-Token = API_ACCESS_TOKEN
     * Profile: email, real_name, id_card, phone, tel, gender,
     * nickname, visiter_name, avatar, birthday, address, company, extra(JSON),
     * or any app_* custom fields.
     */
    public function sync()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请使用 POST 提交']);
        }

        $input = $this->request->post();
        if (empty($input)) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && strlen($raw) > 65536) {
                return json(['code' => 1, 'msg' => '请求体过大']);
            }
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $input = $json;
            }
        }

        $businessId = isset($input['business_id']) ? (int)$input['business_id'] : 0;
        $visiterId = isset($input['visiter_id']) ? trim((string)$input['visiter_id']) : '';
        $timestamp = isset($input['timestamp']) ? (string)$input['timestamp'] : '';
        $sign = isset($input['sign']) ? (string)$input['sign'] : '';

        if ($businessId <= 0 || $visiterId === '' || strlen($visiterId) > 200) {
            return json(['code' => 1, 'msg' => 'business_id 与 visiter_id 必填']);
        }

        $business = Db::name('business')->where('id', $businessId)->find();
        if (!$business || !empty($business['is_delete'])) {
            return json(['code' => 1, 'msg' => '客服应用不存在']);
        }
        if (isset($business['state']) && $business['state'] === 'close') {
            return json(['code' => 1, 'msg' => '客服应用已关闭']);
        }

        $apiToken = AppUserProfile::apiAccessToken();
        $headerToken = '';
        $headers = $this->request->header();
        if (!empty($headers['x-api-token'])) {
            $headerToken = $headers['x-api-token'];
        } elseif (!empty($headers['X-Api-Token'])) {
            $headerToken = $headers['X-Api-Token'];
        }

        $authed = false;
        if ($apiToken !== '' && $headerToken !== '' && hash_equals($apiToken, $headerToken)) {
            $authed = true;
        }
        if (!$authed && AppUserProfile::verifySign($businessId, $visiterId, $timestamp, $sign)) {
            $authed = true;
        }
        if (!$authed) {
            return json(['code' => 1, 'msg' => '签名校验失败或已过期']);
        }

        $profile = AppUserProfile::extractFromInput($input);
        if (empty($profile)) {
            return json(['code' => 1, 'msg' => '未提供任何可同步的用户资料字段']);
        }

        $row = AppUserProfile::sync($businessId, $visiterId, $profile, [
            'ip' => $this->request->ip(),
            'from_url' => 'api/user/sync',
            'trusted' => 1,
        ]);
        if (empty($row)) {
            return json(['code' => 1, 'msg' => '同步失败，请检查参数']);
        }

        return json([
            'code' => 0,
            'msg' => '同步成功',
            'data' => [
                'visiter_id' => $visiterId,
                'business_id' => $businessId,
                'profile' => AppUserProfile::decodeConnect(isset($row['connect']) ? $row['connect'] : ''),
            ],
        ]);
    }
}
