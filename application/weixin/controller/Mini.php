<?php

namespace app\weixin\controller;

use app\admin\model\WechatPlatform;
use app\common\lib\SinglePusher;
use think\Controller;
use EasyWeChat\Factory;
use think\Log;
use think\Db;

class Mini extends Controller
{
    public function index()
    {
        $business_id = $this->request->param('business_id', null);
        !$business_id && abort(500, '参数错误');
        
        $wechat = WechatPlatform::get(['business_id' => $business_id]);
        if (empty($wechat) || empty($wechat['mini_app_id']) || empty($wechat['mini_app_secret'])) {
            Log::warning('小程序未配置: business_id=' . $business_id);
            echo '该商户尚未配置小程序AppID和AppSecret';
            exit;
        }

        $options = [
            'app_id' => $wechat['mini_app_id'],
            'secret' => $wechat['mini_app_secret'],
            'token' => $wechat['mini_wx_token'],
            'aes_key' => $wechat['mini_wx_aeskey'],
            'response_type' => 'object',
            'log' => [
                'default' => 'prod',
                'channels' => [
                    'prod' => [
                        'driver' => 'daily',
                        'path' => RUNTIME_PATH . 'log/easywechat_mini.log',
                        'level' => 'info',
                    ],
                ],
            ],
        ];

        $app = Factory::miniProgram($options);
        $server = $app->server;

        $server->push(function ($message) use ($business_id, $wechat) {
            Log::info('小程序消息: ' . json_encode($message));
            
            if ($message['MsgType'] == 'text') {
                $this->handleTextMessage($message, $business_id);
            } elseif ($message['MsgType'] == 'image') {
                $this->handleImageMessage($message, $business_id);
            } elseif ($message['MsgType'] == 'event') {
                $this->handleEventMessage($message, $business_id);
            }
            
            return 'success';
        });

        if ($this->request->isGet()) {
            $response = $server->serve();
            $response->send();
        } else {
            $response = $server->serve();
            $response->send();
        }
    }

    private function handleTextMessage($message, $business_id)
    {
        $openid = $message['FromUserName'];
        $content = $message['Content'];
        
        $visiter = $this->getOrCreateVisiter($openid, $business_id);
        
        $this->saveMessage($visiter['visiter_id'], $content, 'to_service', $business_id, 'miniprogram');
        
        $this->notifyService($visiter['visiter_id'], $business_id, $content);
    }

    private function handleImageMessage($message, $business_id)
    {
        $openid = $message['FromUserName'];
        $picUrl = $message['PicUrl'];
        
        $visiter = $this->getOrCreateVisiter($openid, $business_id);
        
        $content = '<img src="' . $picUrl . '" style="max-width: 200px;" />';
        $this->saveMessage($visiter['visiter_id'], $content, 'to_service', $business_id, 'miniprogram');
        
        $this->notifyService($visiter['visiter_id'], $business_id, '[图片消息]');
    }

    private function handleEventMessage($message, $business_id)
    {
        $openid = $message['FromUserName'];
        
        if ($message['Event'] == 'user_enter_tempsession') {
            $visiter = $this->getOrCreateVisiter($openid, $business_id);
            
            Db::name('visiter')
                ->where('visiter_id', $visiter['visiter_id'])
                ->where('business_id', $business_id)
                ->update(['state' => 'online']);
        }
    }

    private function getOrCreateVisiter($openid, $business_id)
    {
        $visiter = Db::name('visiter')
            ->where('openid', $openid)
            ->where('business_id', $business_id)
            ->find();

        if ($visiter) {
            return $visiter;
        }

        $visiter_id = md5($openid . time() . rand(1000, 9999));
        
        Db::name('visiter')->insert([
            'visiter_id' => $visiter_id,
            'visiter_name' => '小程序用户',
            'channel' => bin2hex($visiter_id . '/' . $business_id),
            'avatar' => '',
            'openid' => $openid,
            'ip' => '',
            'from_url' => 'miniprogram',
            'business_id' => $business_id,
            'state' => 'online',
            'source' => 'miniprogram',
            'timestamp' => time(),
        ]);

        Db::name('queue')->insert([
            'visiter_id' => $visiter_id,
            'service_id' => 0,
            'groupid' => 0,
            'business_id' => $business_id,
            'state' => 'normal',
            'timestamp' => time(),
            'lastpost' => time(),
        ]);

        return ['visiter_id' => $visiter_id];
    }

    private function saveMessage($visiter_id, $content, $direction, $business_id, $source)
    {
        Db::name('chats')->insert([
            'visiter_id' => $visiter_id,
            'service_id' => 0,
            'business_id' => $business_id,
            'content' => $content,
            'timestamp' => time(),
            'state' => 'unread',
            'direction' => $direction,
            'source' => $source,
            'avatar' => '',
        ]);
    }

    private function notifyService($visiter_id, $business_id, $content)
    {
        $pusher = SinglePusher::getinstance();
        $channel = bin2hex($visiter_id . '/' . $business_id);
        
        $pusher->trigger('ud', 'on_chat', array('message' => ''));
        
        $services = Db::name('service')
            ->where('business_id', $business_id)
            ->where('state', 'online')
            ->select();
        
        foreach ($services as $service) {
            $pusher->trigger('kefu' . $service['service_id'], 'on_chat', array(
                'message' => array(
                    'visiter_id' => $visiter_id,
                    'content' => $content,
                    'business_id' => $business_id,
                )
            ));
        }
    }

    public static function sendMessage($business_id, $openid, $content)
    {
        $wechat = WechatPlatform::get(['business_id' => $business_id]);
        if (empty($wechat) || empty($wechat['mini_app_id']) || empty($wechat['mini_app_secret'])) {
            Log::warning('小程序未配置，无法发送消息: business_id=' . $business_id);
            return false;
        }

        try {
            $options = [
                'app_id' => $wechat['mini_app_id'],
                'secret' => $wechat['mini_app_secret'],
            ];
            
            $app = Factory::miniProgram($options);
            $result = $app->customer_service_message->send([
                'touser' => $openid,
                'msgtype' => 'text',
                'text' => [
                    'content' => strip_tags($content),
                ],
            ]);
            
            Log::info('小程序消息发送结果: ' . json_encode($result));
            return true;
        } catch (\Exception $e) {
            Log::error('小程序消息发送失败: ' . $e->getMessage());
            return false;
        }
    }
}