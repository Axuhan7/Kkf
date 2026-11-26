<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/2/18
 * Time: 9:58
 */
namespace app\admin\model;

use think\Model;

class WechatPlatform extends Model
{
    protected $field = true;

    protected $table = 'wolive_wechat_platform';

    public static function edit($post)
    {
        $model = self::get(['business_id'=>$post['business_id']]);
        if (empty($model)) {
            $res = self::create($post,true);
        } else {
            $model->wx_id = $post['wx_id'];
            $model->wx_token = $post['wx_token'];
            $model->wx_aeskey = $post['wx_aeskey'];
            $model->app_id = $post['app_id'];
            $model->app_secret = $post['app_secret'];
            $model->msg_tpl = $post['msg_tpl'];
            $model->customer_tpl = $post['customer_tpl'];
            $model->visitor_tpl = $post['visitor_tpl'];
            
            $model->subscribe_reply = isset($post['subscribe_reply']) ? $post['subscribe_reply'] : '';
            $model->msg_reply = isset($post['msg_reply']) ? $post['msg_reply'] : '';
            $model->keyword_reply = isset($post['keyword_reply']) ? $post['keyword_reply'] : '';

            $model->desc = $post['desc'];
            $model->business_id = $post['business_id'];
            $model->isscribe = isset($post['isscribe'])?$post['isscribe']:0;
            
            $model->mini_app_id = isset($post['mini_app_id']) ? $post['mini_app_id'] : '';
            $model->mini_app_secret = isset($post['mini_app_secret']) ? $post['mini_app_secret'] : '';
            $model->mini_wx_token = isset($post['mini_wx_token']) ? $post['mini_wx_token'] : '';
            $model->mini_wx_aeskey = isset($post['mini_wx_aeskey']) ? $post['mini_wx_aeskey'] : '';
            if (isset($post['menu_config'])) {
                $model->menu_config = $post['menu_config'];
            }
            $res = $model->save();
        }
        return $res;
    }
}