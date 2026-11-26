<?php
namespace app\admin\model;

use think\Model;

class AiTraining extends Model
{
    protected $name = 'ai_training';

    protected $autoWriteTimestamp = false;

    public static function getTypeList()
    {
        return [
            'product' => '产品参数',
            'faq' => '常见问题',
            'sales' => '销售话术',
            'other' => '其他',
        ];
    }

    public static function getEnabledData($business_id)
    {
        return self::where('business_id', $business_id)
            ->where('is_enabled', 1)
            ->order('sort desc')
            ->select();
    }
}