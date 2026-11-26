<?php


namespace app\admin\controller;

use app\Common;
use app\platform\model\Admin;
use app\platform\model\Business;
use app\weixin\model\Weixin;
use think\Controller;
use think\Cookie;
use think\Loader;
use think\Response;
use think\Session;
use app\admin\model\Admins;
use think\Db;

/**
 * 基础验证是否登录.
 */
class Base extends Controller
{

    protected $base_root = null;
    public $wechat_platform=null;
    public $open_id='';
    /**
     * 验证session.
     *
     * @return void
     */
    public function _initialize()
    {
        // IP白名单检查（必须在最前面）
        try {
            $clientIp = $this->request->ip();
            $whiteList = Db::name('admin_ip')->where('status', 1)->column('ip_address');
            if (!empty($whiteList)) {
                $allowed = false;
                foreach ($whiteList as $wIp) {
                    if ($clientIp === $wIp) { $allowed = true; break; }
                    if (strpos($wIp, '/') !== false) {
                        list($sub,$bits)=explode('/',$wIp);
                        $mL=-1<<(32-$bits);
                        if ((ip2long($clientIp)&$mL)===(ip2long($sub)&$mL)) { $allowed=true; break; }
                    }
                    if (strpos($wIp, '*') !== false) {
                        if (preg_match('/^'.str_replace(['.'],['\.'],$wIp).'$/',$clientIp)) { $allowed=true; break; }
                    }
                }
                if (!$allowed) {
                    try { Db::name('ip_access_log')->insert(['ip'=>$clientIp,'url'=>$this->request->url(),'module'=>'admin','createtime'=>time()]); } catch(\Exception $e){}
                    header('Content-Type: text/html; charset=utf-8');
                    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>访问被拒绝</title><style>body{font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f5f5f5}.box{background:#fff;padding:40px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);text-align:center}.box h2{color:#e74c3c;margin-bottom:15px}.box p{color:#666}</style></head><body><div class="box"><h2>请停止非法入侵行为</h2><p>您的IP地址：<b>' . htmlspecialchars($clientIp) . '</b>已被记录</p><p>若正常访问，请联系管理员</p></div></body></html>';
                    die();
                }
            }
        } catch (\Exception $e) {}

        parent::_initialize();

        // 防止浏览器缓存后台页面，避免使用过期的 CSRF token
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');

        if (!session('Msg')) {
            $token = Cookie::get('service_token');
            if (!$token) {
                $this->redirect('admin/login/index');
            }
            $common = new Common();
            $user_name = $common->cpDecode($token,YMWL_SALT);
            if(!$user_name){
                // Cookie 无效，删除它防止重定向循环
                Cookie::delete('service_token');
                $this->redirect('admin/login/index');
            }
            $data = model("service")
                ->where('user_name', $user_name)
                ->find();
            if ($data) {
                session('Msg',$data->getData());
                $business = Business::get(session('Msg.business_id'));
                session('Msg.business',$business->getData());
                $this->open_id= session('Msg.open_id');
            } else {
                // 用户不存在，删除无效 cookie
                Cookie::delete('service_token');
            }
        }else{
            $serviceInfo=Db::name('service')->field('open_id')->where(['service_id'=>session('Msg.service_id')])->find();
            if($serviceInfo){
                $this->open_id=$serviceInfo['open_id'];
            }

        }
        if (!session('Msg')) {
            $this->redirect('admin/login/index');
        }

        $login = session('Msg');
        $res =model('business')->where('id',$login['business_id'])->find();
        if ($res['is_recycle'] || $res['is_delete']) {
            session('Msg',null);
            $this->error('系统已被回收或封禁');
        }
        if ($res['expire_time'] < time() && $res['expire_time'] != 0) {
            session('Msg',null);
            $this->error('系统已过期');
        }

        $group =model('group')->where('business_id',$login['business_id'])->select();

        $groupjson =json_encode($group);
        $temp = $login;
        $temp['open_id']= $this->open_id;
        unset($temp['copyright']);
        unset($temp['business']['copyright']);
        unset($temp['password']);
        $data = json_encode($temp);
        $app_key = app_key;

        $arr = parse_url(whost);

        if ($arr['scheme'] == 'ws') {
            $value = 'false';
            $port = 'wsPort';
        } else {
            $value = 'true';
            $port = 'wssPort';
        }
        $this->assign('baseroot',BASEROOT);
//        $service = Weixin::get(['service_id'=>session('Msg.service_id')]);
        $this->assign('referer',session('Platform.referer'));

        $this->wechat_platform=Db::name('wechat_platform')->where(['business_id'=>$login['business_id']])->find();
        $is_bind_wechat=0;
        if(!$this->open_id &&$this->wechat_platform && $this->wechat_platform['app_id'] && $this->wechat_platform['app_secret'] && $this->wechat_platform['visitor_tpl']){
            $is_bind_wechat=1;
        }
        $this->assign('is_bind_wechat',$is_bind_wechat);

        if (!session('Msg.business')) {
            $business = Business::get(session('Msg.business_id'));
            if ($business) {
                session('Msg.business',$business->getData());
            }
        }
        $business_data = session('Msg.business');
        $business_name = isset($business_data['business_name']) ? $business_data['business_name'] : '在线客服';
        $theme = isset($business_data['theme']) ? trim($business_data['theme'], '#') : '05202d';
        $this->assign('seo', $business_data ?: []);
        $this->assign('business_name', $business_name);
        $this->assign('theme', $theme);
        $this->assign('app_key', $app_key);
        $this->assign('whost',$arr['host']);
        $this->assign('value', $value);
        $this->assign('wport', wport);
        $this->assign('arr', $login);
        $this->assign('data', $data);
        $this->assign('port', $port);
        $this->assign('group',$groupjson);
        $this->assign('voice',$res['voice_state']);
        $this->assign('voice_address',$res['voice_address']);
        $this->assign('visiter', '');
        $this->assign('from_url', '');
        $this->assign('avatar', '');
        $this->assign('channel', '');
        $this->assign('visiter_id', '');
        $this->assign('special', '');
        $this->assign('url', '');
        $this->assign('business_id', $login['business_id']);
        $this->assign('groupid', $login['groupid']);
        $this->assign('code', '');
        $this->assign('reststate', false);
        $this->assign('restsetting', [
            'reply' => '',
            'name_state' => 'close',
            'tel_state' => 'close',
        ]);
        $this->assign('chat_notice1', isset($res['chat_notice1']) ? $res['chat_notice1'] : '');
        $this->assign('chat_notice2', isset($res['chat_notice2']) ? $res['chat_notice2'] : '');
        $this->assign('transfer_human', isset($res['transfer_human']) ? $res['transfer_human'] : 'close');
        $this->assign('type', isset($res['video_state']) ? $res['video_state'] : 'close');
        $this->assign('atype', isset($res['audio_state']) ? $res['audio_state'] : 'close');
        $this->assign('info', $res);

    }

}