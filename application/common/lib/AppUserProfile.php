<?php
namespace app\common\lib;

use think\Db;

/**
 * Sync and read App real-name / profile data onto wolive_visiter.connect
 */
class AppUserProfile
{
    const MAX_CONNECT_BYTES = 8192;
    const MAX_EXTRA_KEYS = 20;

    public static function allowedFields()
    {
        return [
            'email',
            'real_name',
            'id_card',
            'phone',
            'tel',
            'gender',
            'nickname',
            'visiter_name',
            'avatar',
            'birthday',
            'address',
            'company',
            'extra',
        ];
    }

    /**
     * Fields that identify a real person — require signed / token sync to write.
     * Unsigned URL params must not overwrite these (anti-tamper).
     */
    public static function sensitiveFields()
    {
        return [
            'email',
            'real_name',
            'id_card',
            'phone',
            'tel',
            'birthday',
            'address',
            'company',
            'extra',
        ];
    }

    /**
     * Drop identity fields unless the caller is trusted (valid sign / API token).
     */
    public static function stripSensitive(array $profile)
    {
        foreach (self::sensitiveFields() as $key) {
            unset($profile[$key]);
        }
        return $profile;
    }

    /**
     * Extract profile for visitor entry URLs.
     * Returns [profile, trusted]. Sensitive fields require valid sign.
     */
    public static function extractForVisitorEntry(array $input, $businessId, $visiterId)
    {
        $sign = isset($input['sign']) ? (string)$input['sign'] : '';
        $timestamp = isset($input['timestamp']) ? (string)$input['timestamp'] : '';
        $trusted = self::verifySign($businessId, $visiterId, $timestamp, $sign);
        $profile = self::extractFromInput($input);
        if (!$trusted) {
            $profile = self::stripSensitive($profile);
        }
        return [$profile, $trusted];
    }

    private static function fieldLimits()
    {
        return [
            'email' => 128,
            'real_name' => 64,
            'id_card' => 32,
            'phone' => 32,
            'tel' => 32,
            'gender' => 16,
            'nickname' => 64,
            'visiter_name' => 64,
            'avatar' => 1024,
            'birthday' => 32,
            'address' => 255,
            'company' => 128,
        ];
    }

    public static function extractFromInput(array $input)
    {
        $profile = [];
        $limits = self::fieldLimits();

        foreach (self::allowedFields() as $key) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $value = $input[$key];
            if ($key === 'extra') {
                $profile['extra'] = self::sanitizeExtra($value);
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $text = self::clip(trim((string)$value), isset($limits[$key]) ? $limits[$key] : 255);
            if ($key === 'avatar' && !self::isSafeAvatar($text)) {
                continue;
            }
            if ($key === 'email' && !filter_var($text, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $profile[$key] = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }

        $extraCount = isset($profile['extra']) && is_array($profile['extra']) ? count($profile['extra']) : 0;
        foreach ($input as $key => $value) {
            if (strpos($key, 'app_') !== 0) {
                continue;
            }
            if ($extraCount >= self::MAX_EXTRA_KEYS) {
                break;
            }
            if (!preg_match('/^app_[A-Za-z0-9_]{1,40}$/', $key)) {
                continue;
            }
            if (!isset($profile['extra']) || !is_array($profile['extra'])) {
                $profile['extra'] = [];
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $profile['extra'][$key] = htmlspecialchars(self::clip(trim((string)$value), 500), ENT_QUOTES, 'UTF-8');
            $extraCount++;
        }

        return $profile;
    }

    private static function sanitizeExtra($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                return ['raw' => htmlspecialchars(self::clip($value, 500), ENT_QUOTES, 'UTF-8')];
            }
            $value = $decoded;
        }
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        $i = 0;
        foreach ($value as $k => $v) {
            if ($i >= self::MAX_EXTRA_KEYS) {
                break;
            }
            $key = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$k);
            if ($key === '' || strlen($key) > 40) {
                continue;
            }
            if (is_array($v) || is_object($v)) {
                continue;
            }
            $out[$key] = htmlspecialchars(self::clip(trim((string)$v), 500), ENT_QUOTES, 'UTF-8');
            $i++;
        }
        return $out;
    }

    private static function clip($text, $max)
    {
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $max, 'UTF-8');
        }
        return substr($text, 0, $max);
    }

    private static function isSafeAvatar($url)
    {
        if ($url === '' || strpos($url, 'javascript:') === 0 || strpos($url, 'data:') === 0) {
            return false;
        }
        if (isset($url[0]) && $url[0] === '/') {
            return true;
        }
        $parts = parse_url($url);
        if (empty($parts['scheme'])) {
            return false;
        }
        $scheme = strtolower($parts['scheme']);
        return $scheme === 'http' || $scheme === 'https';
    }

    /**
     * Upsert visitor and merge App profile into connect JSON.
     * (No wolive_visiter.source column required.)
     */
    public static function sync($businessId, $visiterId, array $profile, array $meta = [])
    {
        $businessId = (int)$businessId;
        $visiterId = self::clip(trim((string)$visiterId), 200);
        if ($businessId <= 0 || $visiterId === '') {
            return [];
        }
        if (!preg_match('/^[A-Za-z0-9_\-\.@]{1,200}$/', $visiterId)) {
            return [];
        }

        $existing = Db::name('visiter')
            ->where(['visiter_id' => $visiterId, 'business_id' => $businessId])
            ->find();

        $connect = [];
        if ($existing && !empty($existing['connect'])) {
            $decoded = json_decode($existing['connect'], true);
            if (is_array($decoded)) {
                $connect = $decoded;
            }
        }

        if (!empty($profile['extra']) && is_array($profile['extra'])) {
            $prevExtra = isset($connect['extra']) && is_array($connect['extra']) ? $connect['extra'] : [];
            $profile['extra'] = array_merge($prevExtra, $profile['extra']);
            if (count($profile['extra']) > self::MAX_EXTRA_KEYS) {
                $profile['extra'] = array_slice($profile['extra'], 0, self::MAX_EXTRA_KEYS, true);
            }
        }

        $trusted = !empty($meta['trusted']);
        // Defense in depth: unsigned callers never overwrite existing identity fields
        if (!$trusted) {
            foreach (self::sensitiveFields() as $sf) {
                if ($sf === 'extra') {
                    continue;
                }
                if (!empty($connect[$sf])) {
                    unset($profile[$sf]);
                }
            }
            unset($profile['extra']);
        }

        $connect = array_merge($connect, $profile);
        $connect['synced_at'] = date('Y-m-d H:i:s');
        $connect['channel'] = 'app';
        if (!empty($meta)) {
            $safeMeta = [];
            foreach ($meta as $mk => $mv) {
                if ($mk === 'trusted') {
                    continue;
                }
                if (is_scalar($mv)) {
                    $safeMeta[self::clip((string)$mk, 32)] = self::clip((string)$mv, 255);
                }
            }
            $connect['meta'] = array_merge(
                isset($connect['meta']) && is_array($connect['meta']) ? $connect['meta'] : [],
                $safeMeta
            );
        }

        $connectJson = json_encode($connect, JSON_UNESCAPED_UNICODE);
        if ($connectJson === false) {
            return [];
        }
        if (strlen($connectJson) > self::MAX_CONNECT_BYTES) {
            unset($connect['extra'], $connect['meta']);
            $connectJson = json_encode($connect, JSON_UNESCAPED_UNICODE);
            if ($connectJson === false || strlen($connectJson) > self::MAX_CONNECT_BYTES) {
                return [];
            }
        }

        $update = [
            'connect' => $connectJson,
        ];

        $displayName = '';
        if (!empty($profile['real_name'])) {
            $displayName = $profile['real_name'];
        } elseif (!empty($profile['nickname'])) {
            $displayName = $profile['nickname'];
        } elseif (!empty($profile['visiter_name'])) {
            $displayName = $profile['visiter_name'];
        }
        if ($displayName !== '') {
            $update['visiter_name'] = $displayName;
            $update['name'] = $displayName;
        }
        if (!empty($profile['avatar'])) {
            $update['avatar'] = $profile['avatar'];
        }
        $phone = '';
        if (!empty($profile['phone'])) {
            $phone = $profile['phone'];
        } elseif (!empty($profile['tel'])) {
            $phone = $profile['tel'];
        }
        if ($phone !== '') {
            $update['tel'] = $phone;
        }

        $extends = ['source' => 'app_sync'];
        if ($existing && !empty($existing['extends'])) {
            $prevExt = json_decode($existing['extends'], true);
            if (is_array($prevExt)) {
                $extends = array_merge($prevExt, $extends);
            }
        }
        $update['extends'] = json_encode($extends, JSON_UNESCAPED_UNICODE);

        if ($existing) {
            Db::name('visiter')
                ->where(['visiter_id' => $visiterId, 'business_id' => $businessId])
                ->update($update);
        } else {
            $channel = bin2hex($visiterId . '/' . $businessId);
            $insert = array_merge([
                'visiter_id' => $visiterId,
                'visiter_name' => $displayName !== '' ? $displayName : ('用户' . $visiterId),
                'channel' => $channel,
                'avatar' => !empty($profile['avatar']) ? $profile['avatar'] : '/assets/images/index/avatar-red2.png',
                'name' => $displayName,
                'tel' => $phone,
                'ip' => isset($meta['ip']) ? self::clip((string)$meta['ip'], 64) : '',
                'from_url' => isset($meta['from_url']) ? self::clip((string)$meta['from_url'], 255) : 'app_sync',
                'business_id' => $businessId,
                'state' => 'offline',
            ], $update);
            Db::name('visiter')->insert($insert);
        }

        $row = Db::name('visiter')
            ->where(['visiter_id' => $visiterId, 'business_id' => $businessId])
            ->find();
        return $row ? $row : [];
    }

    public static function decodeConnect($connectRaw)
    {
        if (empty($connectRaw)) {
            return [];
        }
        if (is_array($connectRaw)) {
            return $connectRaw;
        }
        $decoded = json_decode($connectRaw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * sign = md5(business_id#visiter_id#timestamp#app_secret)
     */
    public static function verifySign($businessId, $visiterId, $timestamp, $sign)
    {
        if ($sign === '' || $timestamp === '') {
            return false;
        }
        if (!ctype_digit((string)$timestamp)) {
            return false;
        }
        if (abs(time() - (int)$timestamp) > 300) {
            return false;
        }
        if (!preg_match('/^[a-f0-9]{32}$/i', $sign)) {
            return false;
        }
        $secret = defined('app_secret') ? app_secret : '';
        if ($secret === '' && function_exists('ymwl_env')) {
            $secret = ymwl_env('PUSHER_APP_SECRET', '');
        }
        if ($secret === '') {
            return false;
        }
        $expected = md5($businessId . '#' . $visiterId . '#' . $timestamp . '#' . $secret);
        return hash_equals($expected, $sign);
    }

    public static function apiAccessToken()
    {
        if (isset($_ENV['API_ACCESS_TOKEN']) && $_ENV['API_ACCESS_TOKEN'] !== '') {
            return $_ENV['API_ACCESS_TOKEN'];
        }
        if (isset($_SERVER['API_ACCESS_TOKEN']) && $_SERVER['API_ACCESS_TOKEN'] !== '') {
            return $_SERVER['API_ACCESS_TOKEN'];
        }
        if (function_exists('ymwl_env')) {
            $v = ymwl_env('API_ACCESS_TOKEN', '');
            if ($v !== '') {
                return $v;
            }
        }
        $v = getenv('API_ACCESS_TOKEN');
        return ($v !== false && $v !== '') ? $v : '';
    }
}
