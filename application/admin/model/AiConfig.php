<?php
namespace app\admin\model;

use think\Model;

class AiConfig extends Model
{
    protected $name = 'ai_config';

    protected $autoWriteTimestamp = false;

    public static function getModelTypeList()
    {
        return [
            'baidu_wenxin' => '文心一言',
            'doubao' => '豆包',
            'alibaba_tongyi' => '通义千问',
            'deepseek' => 'DeepSeek',
            'openai_chatgpt' => 'ChatGPT',
            'google_gemini' => 'Gemini',
            'flash_thinking' => 'Flash-Thinking',
        ];
    }

    public static function getDefaultModel($business_id)
    {
        return self::where('business_id', $business_id)
            ->where('is_active', 1)
            ->where('is_default', 1)
            ->find();
    }

    public static function getActiveModels($business_id)
    {
        return self::where('business_id', $business_id)
            ->where('is_active', 1)
            ->select();
    }
}