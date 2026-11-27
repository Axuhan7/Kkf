<?php

namespace app\admin\controller;

use app\admin\model\Comment;
use app\admin\model\CommentDetail;
use app\admin\model\Distribute;
use app\admin\model\RestSetting;
use app\common\model\Postinput;
use app\admin\model\TplService;
use app\admin\model\Visiter;
use app\admin\model\WechatPlatform;
use app\common\lib\SinglePusher;
use app\common\lib\Storage;
use app\common\lib\WxPusher;
use app\common\lib\storage\StorageException;
use app\platform\model\Admin;
use app\platform\model\Business;
use app\platform\model\Service;
use EasyWeChat\Core\Exception;
use think\Log;
use think\Db;
use think\Controller;
use EasyWeChat\Foundation\Application;
use think\Request;
use think\Session;

header('Access-Control-Allow-Origin:*');

/**
 * 跨域公用控制器.
 */
class Event extends Controller
{
    protected $base_root = null;

    /**
     * 不需要身份验证的方法（公开可访问）
     */
    protected $publicMethods = [
        'index',        // webhook 回调（验证签名）
        'registApi',    // 注册接口
        'getquestion',  // 获取常见问题
        'getwaitnum',   // 获取排队数量
        'gettablist',   // 获取tab列表
        'uploadimg',    // 编辑器图片上传（客服后台用，另有CSRF/登录保护）
        'uploadVoice',  // 语音上传
        'getinput',     // 调试用
        'postinput',    // 调试用
    ];

    public function _initialize()
    {

        $this->base_root = BASEROOT;

        // ====== 身份验证修复：防止任意身份冒充 ======
        $action = $this->request->action();
        if (!in_array($action, $this->publicMethods)) {
            $this->checkVisiterIdentity();
        }
    }

    /**
     * 验证访客身份：确保请求中的 visiter_id 与 cookie 中一致
     * 防止攻击者冒充任意访客发送消息/读消息/上传文件
     */
    protected function checkVisiterIdentity()
    {
        $post = $this->request->post();
        $get = $this->request->get();

        // 获取请求中的 visiter_id（兼容不同字段名 vid/visiter_id）
        $request_visiter_id = isset($post['visiter_id']) ? $post['visiter_id'] : (isset($post['vid']) ? $post['vid'] : '');
        if (empty($request_visiter_id)) {
            // 有些方法用 GET 取参
            $request_visiter_id = isset($get['visiter_id']) ? $get['visiter_id'] : '';
        }

        // cookie 中记录的本会话 visiter_id
        $cookie_visiter_id = cookie('visiter_id');

        // 如果请求里没带 visiter_id，放行（后续业务逻辑会处理）
        if (empty($request_visiter_id)) {
            return;
        }

        // 如果 cookie 里没有，可能是首次访问，设置 cookie 绑定身份
        if (empty($cookie_visiter_id)) {
            // 记录 IP 绑定，后续可以追溯
            cookie('visiter_id', $request_visiter_id, 63072000);
            return;
        }

        // 核心校验：请求的 visiter_id 必须与 cookie 中绑定的一致
        if ($request_visiter_id !== $cookie_visiter_id) {
            Log::warning('[IdentitySpoofingBlocked] request_visiter_id=' . $request_visiter_id
                . ', cookie_visiter_id=' . $cookie_visiter_id
                . ', action=' . $this->request->action()
                . ', ip=' . $this->request->ip()
                . ', ua=' . $this->request->header('user-agent'));

            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'code' => 403,
                'msg' => '身份验证失败，请求被拒绝',
                'debug' => YMWL_SALT // 防止攻击者通过错误信息探测
            ]);
            exit;
        }
    }
    /**
     * 选择客服类.
     * @return [type] [description]
     */
    public function getswitch()
    {

        $post = $this->request->post();
        $admin = model('service')->where(['service_id'=>$post['service_id'],'business_id'=>$post['business_id']])->find();
        if(empty($admin)){
            $arr = ['code' => 1, 'msg' => '不存在的客服！'];
            return $arr;
        }
        $pusher=SinglePusher::getinstance();
        $channel = bin2hex($post['visiter_id'] . '/' . $post['business_id']);
        unset($admin['password']);
        $pusher->trigger("cu" . $channel, 'getswitch', array('message' => $admin));
        $pusher->trigger('kefu' . $post['service_id'], 'getswitch', array('message' => $admin['nick_name'] . "  转接访客给你"));
        model('queue')->where("visiter_id", $post['visiter_id'])->where('business_id', $post['business_id'])->where('state', 'normal')->update(['service_id' => $post['service_id']]);
        $arr = ['code' => 0, 'msg' => '转接成功！'];
        return $arr;
    }

    /**
     * 离线，在线监控类.
     *
     * @returngetanswer void
     */
    public function index()
    {
        Log::info('Event::index called, method=' . $_SERVER['REQUEST_METHOD'] . ', uri=' . $_SERVER['REQUEST_URI']);
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') === 0) {
                $headers[str_replace('HTTP_', '', $k)] = $v;
            }
        }
        Log::info('Event::index headers=' . json_encode($headers));

        // pusher 访问的 地址
        $pusher=SinglePusher::getinstance();

        $webhook_signature = isset($_SERVER['HTTP_X_PUSHER_SIGNATURE']) ? $_SERVER['HTTP_X_PUSHER_SIGNATURE'] : '';

        $body = file_get_contents('php://input');
        Log::info('Event::index body=' . $body . ', signature=' . $webhook_signature);
        $expected_signature = hash_hmac('sha256', $body, app_secret, false);

        if ($webhook_signature == $expected_signature) {
            Log::info('Event::index signature matched');

            $payload = json_decode($body, true);
            Log::info('Event::index payload=' . json_encode($payload));
            foreach ($payload['events'] as $event) {
                Log::info('Event::index processing event=' . json_encode($event));
                // 通知离线
                if ($event['name'] == 'channel_removed') {
                    // 客服 离线
                    if (strpos($event['channel'], 'kefu') === 0) {
                        $channel = str_replace('kefu', 'se', $event['channel']);
                        $id = str_replace('kefu', '', $event['channel']);
                        Log::info('Event::index kefu offline, channel=' . $channel . ', id=' . $id);
                        $pusher->trigger($channel, 'logout', array('message' => "客服已经下线"));
                        $res = model('service')->where('service_id', $id)->update(['state' => 'offline']);
                        Log::info('Event::index kefu offline update result=' . var_export($res, true));

                    } elseif (strpos($event['channel'], 'cu') === 0) {
                        // 访客 离线
                        $channel = str_replace('cu', '', $event['channel']);

                        $newstr = pack("H*", $channel);

                        $data = explode("/", $newstr);
                        Log::info('Event::index visiter offline, channel=' . $channel . ', visiter_id=' . $data[0] . ', business_id=' . $data[1]);
                        $res = model("queue")->where(['visiter_id' => $data[0], 'business_id' => $data[1]])->find();
                        model("queue")->where(['visiter_id' => $data[0], 'business_id' => $data[1]])->update(['remind_tpl'=>0,'remind_comment'=>0]);
                        $id = $res['service_id'];

                        $arr = array(
                            'chas' => $channel
                        );
                        $pusher->trigger("kefu" . $id, 'logout', array('message' => $arr));
                        $res2 = model("visiter")->where("channel", $channel)->update(["state" => 'offline']);
                        Log::info('Event::index visiter offline update result=' . var_export($res2, true));
                    }
                }

                // 通知在线
                if ($event["name"] == "channel_added") {

                    if (strpos($event['channel'], 'kefu') === 0) {
                        // 通知 访客，客服在线
                        $channel = str_replace('kefu', 'se', $event['channel']);
                        $id = str_replace('kefu', '', $event['channel']);
                        Log::info('Event::index kefu online, channel=' . $channel . ', id=' . $id);
                        $res = model('service')->where('service_id', $id)->update(['state' => 'online']);
                        $pusher->trigger($channel, 'geton', array('message' => "客服上线"));
                        Log::info('Event::index kefu online update result=' . var_export($res, true));

                    } elseif (strpos($event['channel'], 'cu') === 0) {
                        // 通知 客服 ，访客在线
                        $channel = str_replace('cu', '', $event['channel']);

                        $newstr = pack("H*", $channel);

                        $data = explode("/", $newstr);
                        Log::info('Event::index visiter online, channel=' . $channel . ', visiter_id=' . $data[0] . ', business_id=' . $data[1]);
                        $res = model("queue")->where(['visiter_id' => $data[0], 'business_id' => $data[1]])->find();

                        $id = $res['service_id'];
                        $arr = array(
                            'chas' => $channel
                        );
                        $pusher->trigger("kefu" . $id, 'geton', array('message' => $arr));

                        $res2 = model("visiter")->where("channel", $channel)->update(["state" => 'online']);
                        Log::info('Event::index visiter online update result=' . var_export($res2, true));
                    }
                }
            }
            header("Status: 200 OK");
        } else {
            header("Status: 401 Not authenticated");
            Log::info('Event::index signature mismatch, expected=' . $expected_signature . ', got=' . $webhook_signature);
        }
    }


    /**
     *  注册接口
     *
     * @return string
     */
    public function registApi()
    {

        $post = $this->request->post();

        $time = time();

        if ($time - $post['timestamp'] > 60) {

            $data = ['code' => 1, 'msg' => '注册验证超时！'];
            return json_encode($data);
        }

        $token = md5($post['user_name'] . registToken . $post['timestamp']);

        if ($token == $post['token']) {

            if (!$post['nick_name']) {
                $post['nick_name'] = "管理员" . $post['user_name'];
            }

            if (!$post['business_id']) {
                $post['business_id'] = $post['user_name'];
            }

            $arr = [
                'user_name' => $post['user_name'],
                'nick_name' => $post['nick_name'],
                'avatar' => $post['avatar'],
                'business_id' => $post['business_id'],
                'password' => $post['password'],
            ];

            $debug = model('service')->insert($arr);

            $data = ['code' => 0, 'msg' => 'success'];

            return json_encode($data);

        } else {

            $data = ['code' => 1, 'msg' => '注册token验证失败！'];

            return json_encode($data);
        }
    }

    /**
     * 创建排队记录，确保同一用户同一商户只有一条排队记录
     */
    private function createQueueRecord($visiter_id, $business_id, $groupid)
    {
        $existing = model('queue')->where('business_id', $business_id)->where('visiter_id', $visiter_id)->where('state', 'normal')->find();
        if ($existing) {
            return;
        }
        $queue_data = ['visiter_id' => $visiter_id, 'service_id' => 0, 'business_id' => $business_id, 'state' => 'normal', 'groupid' => $groupid];
        model('queue')->insert($queue_data);
    }


    /**
     *  前台对话pusher类.
     *
     * @return void
     */
    public function chat()
    {
        $arr = $this->request->post();
        $arr['content'] = $this->request->post('content','','\app\Common::clearXSS');
        $chid = 0;
        $pusher = SinglePusher::getinstance();
        $visiter = Db::name('visiter')->where('visiter_id',$arr['visiter_id'])->find();
        $arr['channel']=$visiter['channel'];
        hook('chathook',array_merge($arr,['pusher'=>$pusher,'channel'=>$visiter['channel']]));
        $service = model('queue')->where('business_id', $arr['business_id'])->where('visiter_id', $arr['visiter_id'])->where('state', 'normal')->find();

        $service_id = $service ? $service['service_id'] : 0;

        $business = model('business')->where('id', $arr['business_id'])->find();
        $transfer_human = isset($business['transfer_human']) ? $business['transfer_human'] : 'close';
        $ai_service_state = isset($business['ai_service_state']) ? $business['ai_service_state'] : 'close';
        $ai_priority = isset($business['ai_priority']) ? $business['ai_priority'] : 'human_first';
        $ai_auto_reply_delay = isset($business['ai_auto_reply_delay']) ? $business['ai_auto_reply_delay'] : 0;

        $need_update_queue = false;

        if ($service_id != $arr['service_id']) {
            if ($transfer_human == 'open') {
                $service_id = 0;
                if (!$service) {
                    $old_queue = model('queue')->where('business_id', $arr['business_id'])->where('visiter_id', $arr['visiter_id'])->where('state', 'complete')->order('qid', 'desc')->find();
                    if ($old_queue) {
                        model('queue')->where('qid', $old_queue['qid'])->update(['state' => 'normal', 'service_id' => 0]);
                    } else {
                        $this->createQueueRecord($arr['visiter_id'], $arr['business_id'], $arr['groupid']);
                    }
                } else {
                    model('queue')->where('qid', $service['qid'])->update(['state' => 'normal', 'service_id' => 0]);
                }
            } elseif (!empty($service_id)) {
                return ['code' => 1, 'msg' => '该会话已经关闭！', 'id' => $service_id];
            } else {
                $old_queue = model('queue')->where('business_id', $arr['business_id'])->where('visiter_id', $arr['visiter_id'])->where('state', 'complete')->order('qid', 'desc')->find();
                if ($old_queue) {
                    model('queue')->where('qid', $old_queue['qid'])->update(['state' => 'normal', 'service_id' => 0]);
                } else {
                    $this->createQueueRecord($arr['visiter_id'], $arr['business_id'], $arr['groupid']);
                }
            }
        } else {
            if ($service_id == 0) {
                if (!$service) {
                    $old_queue = model('queue')->where('business_id', $arr['business_id'])->where('visiter_id', $arr['visiter_id'])->where('state', 'complete')->order('qid', 'desc')->find();
                    if ($old_queue) {
                        model('queue')->where('qid', $old_queue['qid'])->update(['state' => 'normal', 'service_id' => 0]);
                    } else {
                        $this->createQueueRecord($arr['visiter_id'], $arr['business_id'], $arr['groupid']);
                    }
                } else {
                    if ($service['state'] != 'normal') {
                        model('queue')->where('qid', $service['qid'])->update(['state' => 'normal']);
                    }
                }
            }
        }

        $arr["timestamp"] = time();
        $arr['service_id'] = $service_id;

        // 获取queue记录中的groupid，确保推送通知使用正确的groupid
        $queue_record = model('queue')->where('business_id', $arr['business_id'])->where('visiter_id', $arr['visiter_id'])->where('state', 'normal')->find();
        if ($queue_record && $queue_record['groupid']) {
            $arr['groupid'] = $queue_record['groupid'];
        }


        function extract_attrib($tag)
        {
            preg_match_all('/(id|alt|title|src)=("[^"]*")/i', $tag, $matches);

            $ret = array();
            foreach ($matches[1] as $i => $v) {
                $ret[$v] = $matches[2][$i];
            }
            return $ret;
        }

        if (!isset($arr['debug'])) {

            $content = $arr["content"];
            $values = preg_match_all('/<img.*\>/isU', $content, $out);


            if ($values) {

                $img = $out[0];

                if ($img) {

                    $chats = "";

                    foreach ($img as $v) {

                        $attr = extract_attrib($v);

                        $src = $attr["src"];

                        if ($src) {
                            if (strpos($src, "emo_")) {
                                $newimg = "<img src={$src}>";
                            } else {
                                $newimg = "<img  src={$src}>";
                            }
                        }
                        $chats .= $newimg;
                    }

                }
            } else {
                $chats = "";
            }
            $newstr = preg_replace('/<img.*\>/isU', "", $content);

            $values2 = preg_match_all('/<audio.*\>/isU', $content, $out);

            $vas = preg_match_all('/<a.*\>/isU', $content, $array2);

            if (!$values2 && !$vas) {

                $newstr = htmlspecialchars($newstr);
            }

            $arr["content"] = $chats . $newstr;
        }

        $keyword_block = isset($business['keyword_block']) ? $business['keyword_block'] : '';
        if (!empty($keyword_block)) {
            $keywords = array_map('trim', explode(',', $keyword_block));
            $keywords = array_filter($keywords);
            foreach ($keywords as $keyword) {
                if (!empty($keyword)) {
                    $replace_str = str_repeat('*', mb_strlen($keyword, 'UTF-8'));
                    $arr["content"] = str_replace($keyword, $replace_str, $arr["content"]);
                }
            }
        }

        try {
            $arr['direction'] = 'to_service';
            unset($arr['record']);
            unset($arr['channel']);
            unset($arr['__token__']);
            if (isset($arr['debug'])) {
                unset($arr['debug']);
            }
            $chat_data = [
                'visiter_id' => $arr['visiter_id'],
                'service_id' => $arr['service_id'],
                'business_id' => $arr['business_id'],
                'content' => $arr['content'],
                'timestamp' => $arr['timestamp'],
                'direction' => 'to_service',
                'state' => 'unread',
                'unstr' => '',
                'avatar' => $arr['avatar']
            ];
            model('chats')->insert($chat_data);
            $chid=model('chats')->getLastInsID();

            if ($transfer_human == 'open' && $service_id == 0) {
                $transfer_msg = [
                    'visiter_id' => $arr['visiter_id'],
                    'service_id' => 0,
                    'business_id' => $arr['business_id'],
                    'content' => '<a href="javascript:void(0)" onclick="transferToHuman()" style="color: #6a59ff;">转人工客服</a>',
                    'timestamp' => time(),
                    'direction' => 'from_service',
                    'state' => 'unread',
                    'unstr' => '',
                    'avatar' => $arr['avatar']
                ];
                model('chats')->insert($transfer_msg);
                
                $channel = bin2hex($arr['visiter_id'] . '/' . $arr['business_id']);
                $visiter_info = Db::name('visiter')->where('visiter_id', $arr['visiter_id'])->find();
                $transfer_msg['avatar'] = $visiter_info['avatar'];
                $pusher->trigger("cu" . $channel, 'my-event', array('message' => $transfer_msg));
                
                $pusher->trigger("all" . $arr['business_id'], 'on_notice', array('message' => array(
                    'msg' => '有新客户进入排队列表',
                    'groupid' => $arr['groupid']
                )));
            } else {
                if ($service_id == 0) {
                    $pusher->trigger("all" . $arr['business_id'], 'on_notice', array('message' => array(
                        'msg' => '有新客户进入排队列表',
                        'groupid' => $arr['groupid']
                    )));
                } else {
                    $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $arr));

                    model("visiter")->where("visiter_id", $arr['visiter_id'])->where("business_id", $arr['business_id'])->update(["state" => 'online']);

                    WxPusher::sendToService($service_id);

                    $service_data = Service::get($service_id);

                    if ($service_data && $service_data['state']=='offline') {
                        $business_info = Business::get($arr['business_id']);
                        $wechat = WechatPlatform::get(['business_id'=>$arr['business_id']]);
                        TplService::send($arr["business_id"],$service_data['open_id'],url('weixin/login/callback',['business_id'=>$arr['business_id'],'service_id'=>$service_id],true,true),$wechat['msg_tpl'],[
                            "first"  => "你有一条新的信息!",
                            "keyword1"   => $visiter["visiter_name"] ?$visiter["visiter_name"]:'游客'.$arr['visiter_id'],
                            "keyword2"  => date('Y-m-d H:i:s',time()),
                            "keyword3"  => $arr["content"],
                            "remark" => $business_info['business_name']."提示:客户等不及啦,快去回复吧~",
                        ]);
                    }
                }
            }

            Db::execute("UPDATE ".config('database.prefix')."queue  SET `lastpost`=:lastpost  WHERE  `visiter_id`=:visiter_id   AND `business_id`=:business_id",['lastpost'=>time(),'visiter_id'=>$arr['visiter_id'],'business_id'=>$arr['business_id']]);

            $this->maybeSendOfflineGreeting($arr, $business, $service_id, $pusher);

            $this->processAiAutoReply($arr, $business, $ai_service_state, $ai_priority, $ai_auto_reply_delay, $service_id);

            $data = ['code' => 0, 'msg' => 'success','cid' => $chid, 'data' => ['getui' => isset($getui_ret) ? $getui_ret : (isset($getui_e) ? $getui_e : '')]];

            if (isset($arr['debug'])) {
                return json_encode($data);
            } else {
                return $data;
            }

        } catch (\Exception $e) {

            $error = $e->getMessage();
            $data = ['code' => 0, 'msg' => $error, 'data' => ['getui' => isset($getui_ret) ? $getui_ret : (isset($getui_e) ? $getui_e : '')]];
            return $data;

        }

    }

    /**
     * AI自动回复处理
     */
    private function processAiAutoReply($arr, $business, $ai_service_state, $ai_priority, $ai_auto_reply_delay, $service_id)
    {
        try {
            if ($ai_service_state != 'open') {
                return;
            }

            $needAiReply = false;

            if ($ai_priority == 'ai_first') {
                $needAiReply = true;
            } else {
                if ($service_id == 0) {
                    $needAiReply = true;
                } else {
                    $service_data = model('service')->where('service_id', $service_id)->find();
                    if (!$service_data || $service_data['state'] != 'online') {
                        $needAiReply = true;
                    }
                }
            }

            if (!$needAiReply) {
                return;
            }

            if (!class_exists('app\\admin\\model\\AiConfig') || !class_exists('app\\admin\\model\\AiTraining')) {
                Log::error('AI模型类不存在');
                return;
            }

            $aiConfig = \think\Db::name('ai_config')
                ->where('business_id', $arr['business_id'])
                ->where('is_active', 1)
                ->where('is_default', 1)
                ->find();

            if (!$aiConfig) {
                $aiConfig = \think\Db::name('ai_config')
                    ->where('business_id', $arr['business_id'])
                    ->where('is_active', 1)
                    ->order('id asc')
                    ->find();
            }

            if (!$aiConfig) {
                return;
            }

            $trainingData = \think\Db::name('ai_training')
                ->where('business_id', $arr['business_id'])
                ->where('is_enabled', 1)
                ->order('sort asc')
                ->select();

            $aiClient = new \app\common\lib\AiClient($aiConfig, $trainingData ?: []);
            $result = $aiClient->generate($arr['content']);

            $aiContent = isset($result['answer']) ? $result['answer'] : (isset($result['content']) ? $result['content'] : '');
            if (!empty($aiContent)) {
                if ($ai_auto_reply_delay > 0) {
                    sleep($ai_auto_reply_delay);
                }

                $channel = bin2hex($arr['visiter_id'] . '/' . $arr['business_id']);
                $pusher = SinglePusher::getinstance();

                $avatar = '';
                $service_info = \think\Db::name('service')->where('business_id', $arr['business_id'])->where('state', 'online')->order('service_id asc')->find();
                if ($service_info && !empty($service_info['avatar'])) {
                    $avatar = $service_info['avatar'];
                } else {
                    $business_info = \think\Db::name('business')->where('id', $arr['business_id'])->find();
                    $avatar = $business_info ? ($business_info['logo'] ?? '') : '';
                }

                $ai_reply = [
                    'visiter_id' => $arr['visiter_id'],
                    'service_id' => 0,
                    'business_id' => $arr['business_id'],
                    'content' => $aiContent,
                    'timestamp' => time(),
                    'direction' => 'to_visiter',
                    'state' => 'unread',
                    'unstr' => '',
                    'avatar' => $avatar
                ];

                \think\Db::name('chats')->insert($ai_reply);
                $ai_reply['cid'] = \think\Db::name('chats')->getLastInsID();

                $pusher->trigger("cu" . $channel, 'my-event', array('message' => $ai_reply));

                if ($service_id > 0) {
                    $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $ai_reply));
                }
            }
        } catch (\Exception $e) {
            Log::error('AI自动回复失败: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
        }
    }


    /**
     * 转人工接口
     * [transferhuman description]
     * @return [type] [description]
     */
    public function transferhuman()
    {
        $arr = $this->request->post();
        $pusher = SinglePusher::getinstance();
        
        $service = model('queue')->where('business_id', $arr['business_id'])->where('visiter_id', $arr['visiter_id'])->where('state', 'normal')->find();
        
        if (!$service) {
            return ['code' => 1, 'msg' => '会话不存在'];
        }
        
        $service_id = $service['service_id'];
        
        if ($service_id != 0) {
            return ['code' => 1, 'msg' => '已经有客服在服务'];
        }
        
        $business = model('business')->where('id', $arr['business_id'])->find();
        $transfer_human = isset($business['transfer_human']) ? $business['transfer_human'] : 'close';
        
        if ($transfer_human != 'open') {
            return ['code' => 1, 'msg' => '转人工功能未开启'];
        }
        
        $visiter = model('visiter')->where(['visiter_id' => $arr['visiter_id'], 'business_id' => $arr['business_id']])->find();
        $channel = bin2hex($arr['visiter_id'] . '/' . $arr['business_id']);
        
        $waiting_msg = [
            'visiter_id' => $arr['visiter_id'],
            'service_id' => 0,
            'business_id' => $arr['business_id'],
            'content' => '正在转接人工客服，请稍等...',
            'timestamp' => time(),
            'direction' => 'from_service',
            'state' => 'unread',
            'unstr' => '',
            'avatar' => $visiter['avatar']
        ];
        model('chats')->insert($waiting_msg);
        $waiting_msg['avatar'] = $visiter['avatar'];
        $pusher->trigger("cu" . $channel, 'my-event', array('message' => $waiting_msg));
        
        $groupid = $service['groupid'];
        $service_data = Distribute::run($arr['business_id'], 'online', $groupid);
        
        if (empty($service_data)) {
            $service_data = Distribute::run($arr['business_id'], null, $groupid);
        }
        
        if (empty($service_data)) {
            return ['code' => 1, 'msg' => '该分类客服都不在线'];
        }
        
        model('queue')->where('qid', $service['qid'])->update(['service_id' => $service_data['service_id']]);
        
        $chats = model('chats')->where(['visiter_id' => $arr['visiter_id'], 'business_id' => $arr['business_id'], 'service_id' => 0, 'direction' => 'to_service'])->select();
        
        foreach ($chats as $chat) {
            $chat['service_id'] = $service_data['service_id'];
            model('chats')->where('cid', $chat['cid'])->update(['service_id' => $service_data['service_id']]);
            $pusher->trigger('kefu' . $service_data['service_id'], 'cu-event', array('message' => $chat));
        }
        
        WxPusher::sendToService($service_data['service_id']);
        
        $notice_data = ['msg' => "公告:" . ($visiter['visiter_name'] ? $visiter['visiter_name'] : '游客' . $arr['visiter_id']) . "请求转人工", 'groupid' => $groupid];
        $pusher->trigger("all" . $arr['business_id'], 'on_notice', array('message' => $notice_data));
        
        return ['code' => 0, 'msg' => '已转人工', 'data' => ['service_id' => $service_data['service_id'], 'nick_name' => $service_data['nick_name'], 'avatar' => $service_data['avatar']]];
    }

    /**
     * 前台寻求客服对话类.
     *
     * @return mixed
     */
    public function notice()
    {
        $ip = $this->request->ip();
        $pusher=SinglePusher::getinstance();
        $arr = $this->request->post();

        if (!isset($arr['visiter_id'])  || !isset($arr['business_id'])) {
            $returndata = ['code' => 1, 'msg' => '数据不完整'];
            return $returndata;
        }

        $arr["visiter_name"] = htmlspecialchars($arr['visiter_name']);
        $arr["business_id"] = htmlspecialchars($arr['business_id']);
        $arr["from_url"] = htmlspecialchars($arr['from_url']);
        $arr["avatar"] = htmlspecialchars($arr['avatar']);
        $arr['visiter_id'] = htmlspecialchars($arr['visiter_id']);
        $arr["channel"] = bin2hex($arr['visiter_id'] . '/' . $arr['business_id']);
        $arr['ip'] = $ip;
        include VENDOR.'phpuseragent/lib/phpUserAgent.php';
        include VENDOR.'phpuseragent/lib/phpUserAgentStringParser.php';
        $ua = new \phpUserAgent();
        $arr['extends'] = json_encode(['browserName'    => $ua->getBrowserNameCn(),
            'browserVersion' => $ua->getBrowserVersion(),
            'os'             => $ua->getOperatingSystemCn(),
            'engine'         => $ua->getEngine()]);

        $business = model('business')->where('id', $arr['business_id'])->find();
        if ($business['state'] == 'close') {
            $returndata = ['code' => 1, 'msg' => '该商户客服已经禁止访问！'];
            return $returndata;
        }
        $visiter_id = $arr['visiter_id'];
        $business_id = $arr['business_id'];
        $special = isset($arr['special']) ? $arr['special']:null;
        hook('claimbeforehook',$arr);//认领前钩子
        if ($business['distribution_rule'] == 'claim') {
            //认领模式

            $visiter = model('visiter')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->find();

            if ($visiter) {
                //老用户
                $service = model('queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->where('state', 'normal')->find();

                if (!$service || $service['service_id'] == 0) {
                    // 没有queue记录或service_id=0，说明在排队列表中
                    $groupid = isset($arr['groupid']) ? $arr['groupid'] : ($service ? $service['groupid'] : 0);

                    if ($service && $service['service_id'] == 0) {
                        // 更新groupid
                        model("queue")->where('qid', $service['qid'])->update(['groupid' => $groupid]);
                    } else {
                        // 查找是否有complete记录，恢复并保留groupid
                        $old_queue = model("queue")->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'state' => 'complete'])->order('qid', 'desc')->find();
                        if ($old_queue) {
                            model("queue")->where('qid', $old_queue['qid'])->update(['state' => 'normal', 'service_id' => 0]);
                            $groupid = $old_queue['groupid'];
                        } else {
                            $data = ['visiter_id' => $visiter_id, 'service_id' => 0, 'business_id' => $business_id, 'groupid' => $groupid, 'state' => 'normal'];
                            $qu = model("queue")->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'service_id' => 0, 'state' => 'normal'])->find();
                            if ($qu) {
                                model("queue")->where('qid', $qu['qid'])->update(['groupid' => $groupid, 'service_id' => 0]);
                            } else {
                                model("queue")->insert($data);
                            }
                        }
                    }

                    $num = model('queue')->where(['service_id' => 0, 'business_id' => $business_id, 'groupid' => $groupid])->where('state', 'normal')->count();

                    $class = model('group')->where('id', $groupid)->find();

                    $allnotice = ['msg' => "公告:" . $arr['visiter_name'] . "需要" . ($class ? $class['groupname'] : '普通咨询') . "的咨询", 'groupid' => $groupid];

                    $pusher->trigger("all" . $arr['business_id'], 'on_notice', array('message' => $allnotice));

                    $returndata = $this->appendQueueOfflineTips(['code' => 2, 'msg' => '等待认领！', 'data' => $num], $business, $num);
                    return $returndata;
                }


                // 替换成最新头像
                $newvisiter = model('visiter')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->update(array_filter(['avatar' => $arr['avatar'],'visiter_name' => $arr['visiter_name']]));
                // 最后一次服务的客服id
                $service_id = $service['service_id'];


                if ($arr['groupid']) {
                    $service_data = model("service")->where('service_id', $service_id)->where('groupid', ['=', 0], ['=', $arr['groupid']], 'or')->find();
                } else {

                    $service_data = model("service")->where('service_id', $service_id)->find();

                }

                $queue = model("queue")->where('visiter_id', $visiter_id)->where('business_id', $business_id)->update(['groupid' => $arr['groupid']]);

                if ($service_data) {

                    $state = $service_data['state'];

                    if ($state == 'online') {

                        $words = model('sentence')->where("service_id", $service_id)->where('state', 'using')->find();

                        if ($words) {
                            $service_data['content'] = $words['content'];
                        } else {
                            $service_data['content'] = "您好！";
                        }
                        unset($service_data['password']);
                        $returndata = ['code' => 0, 'msg' => 'success', 'data' => $service_data];

                        return $returndata;

                    } else {
                        $tips = $this->appendQueueOfflineTips(['code' => 4, 'msg' => '该客服暂时离开,请留言！也可以转接其他客服'], $business, 0);
                        if (!empty($tips['offline_greeting'])) {
                            $service_data['content'] = $tips['offline_greeting'];
                        }
                        unset($service_data['password']);
                        $returndata = array_merge($tips, ['data' => $service_data]);
                        return $returndata;
                    }

                } else {
                    //换了分组
                    $qid = $service['qid'];

                    $res = model('queue')->where('qid', $qid)->update(['groupid' => $arr['groupid'], 'service_id' => 0, 'state' => 'normal']);


                    $num = model('queue')->where(['service_id' => 0, 'business_id' => $business_id, 'groupid' => $arr['groupid']])->where('state', 'normal')->count();

                    $class = model('group')->where('id', $arr['groupid'])->find();

                    $allnotice = ['msg' => "公告:" . $arr['visiter_name'] . "需要" . $class['groupname'] . "的咨询", 'groupid' => $arr['groupid']];

                    $pusher->trigger("all" . $arr['business_id'], 'on_notice', array('message' => $allnotice));

                    $returndata = $this->appendQueueOfflineTips(['code' => 2, 'msg' => '等待认领！', 'data' => $num], $business, $num);
                    return $returndata;
                }

            } else {
                // 新用户
                $arr['state'] = 'offline';
                $data = ['visiter_id' => $visiter_id, 'service_id' => 0, 'business_id' => $business_id, 'groupid' => $arr['groupid']];
                $class = model('group')->where('id', $arr['groupid'])->find();


                $qu = model("queue")->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'service_id' => 0, 'state' => 'normal'])->find();
                if ($qu) {
                    $queue = model("queue")->where('qid', $qu['qid'])->update(['groupid' => $arr['groupid'], 'service_id' => 0]);
                } else {
                    $queue = model("queue")->insert($data);
                }

                $num = model('queue')->where(['service_id' => 0, 'business_id' => $business_id, 'groupid' => $arr['groupid']])->where('state', 'normal')->count();

                $groupid = $arr['groupid'];
                $visiter_data = [
                    'visiter_id' => $visiter_id,
                    'visiter_name' => $arr['visiter_name'],
                    'channel' => $arr['channel'],
                    'avatar' => $arr['avatar'],
                    'business_id' => $business_id,
                    'state' => 'offline',
                    'ip' => $arr['ip'],
                    'from_url' => $arr['from_url'],
                    'extends' => $arr['extends']
                ];
                $openid = session('onesendopenid');
                if ($openid) {
                    $visiter_data['openid'] = $openid;
                }
                session('onesendopenid', null);
                $newvisiter = model('visiter')->insert($visiter_data);
                $allnotice = ['msg' => "公告:" . $arr['visiter_name'] . "需要" . $class['groupname'] . "的咨询", 'groupid' => $groupid];

                $pusher->trigger("all" . $arr['business_id'], 'on_notice', array('message' => $allnotice));

                $returndata = $this->appendQueueOfflineTips(['code' => 2, 'msg' => '等待认领！', 'data' => $num], $business, $num);
                return $returndata;
            }
        } else {
            // 智能分配
            $transfer_human = isset($business['transfer_human']) ? $business['transfer_human'] : 'close';
            
            $visiter = model('visiter')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->find();

            if ($visiter) {
                // 替换成最新头像 'login_times'=> ['exp','login_times+1']
                model('visiter')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->update(array_filter(['avatar' => $arr['avatar'],'login_times'=>Db::raw('login_times+1'),'visiter_name' => $arr['visiter_name']]));
                // 老用户
                $service = model('queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'state' => 'normal'])->find();
                //最后服务id
                $service_id = $service ? $service['service_id'] : 0;
                
                // 如果开启转人工且未分配客服，不自动分配
                if ($transfer_human == 'open' && $service_id == 0) {
                    // 确保queue记录存在且状态为normal
                    if (!$service) {
                        $data = ['visiter_id' => $visiter_id, 'service_id' => 0, 'business_id' => $business_id, 'groupid' => $arr['groupid'], 'state' => 'normal'];
                        model("queue")->insert($data);
                    } else {
                        model('queue')->where('qid', $service['qid'])->update(['state' => 'normal', 'service_id' => 0]);
                    }
                    $returndata = $this->appendQueueOfflineTips(['code' => 2, 'msg' => '等待转人工！', 'data' => 0], $business, 0);
                    return $returndata;
                }
                
                // 如果没有normal状态的记录，检查是否有complete记录（说明客服关闭了对话）
                if (!$service) {
                    $old_service = model('queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'state' => 'complete'])->order('qid', 'desc')->find();
                    if ($old_service) {
                        // 客服关闭了对话，进入排队列表，恢复记录为normal，service_id=0，保留原有groupid
                        model('queue')->where('qid', $old_service['qid'])->update(['state' => 'normal', 'service_id' => 0]);
                        
                        $groupid = $old_service['groupid'];
                        $num = model('queue')->where(['service_id' => 0, 'business_id' => $business_id, 'groupid' => $groupid])->where('state', 'normal')->count();
                        
                        $class = model('group')->where('id', $groupid)->find();
                        $allnotice = ['msg' => "公告:" . $arr['visiter_name'] . "需要" . ($class ? $class['groupname'] : '普通咨询') . "的咨询", 'groupid' => $groupid];
                        $pusher->trigger("all" . $arr['business_id'], 'on_notice', array('message' => $allnotice));
                        
                        $returndata = $this->appendQueueOfflineTips(['code' => 2, 'msg' => '等待认领！', 'data' => $num], $business, $num);
                        return $returndata;
                    }
                }
                
                $service_data = model("service")->field('avatar,business_id,email,open_id,groupid,nick_name,service_id,state')->where('service_id', $service_id)->where('groupid', $arr['groupid'])->find();

                //如果被客服拉黑了
                if ($service && $service['state'] == 'in_black_list') {
                    $service_data = model("service")->field('avatar,business_id,email,groupid,nick_name,service_id,state')->where('service_id', $service_id)->find();
                    $service_data['content'] = "您好！";
                    $returndata = ['code' => 0, 'msg' => 'success', 'data' => $service_data];
                    return $returndata;
                }

                if ($service_data) {
                    if ($service_data['state'] == 'offline' ) {
                        $serv = Distribute::run($business_id,'online',$arr['groupid']);
                        !empty($serv) && $service_data = $serv;
                    }

                    if (!empty($special)) {
                        $service_data = Service::get(['service_id' => $special,'business_id'=>$arr["business_id"]]);
                        if (empty($service_data)) {
                            $res = ['code' => 1, 'msg' => '不存在该专属客服'];
                            return $res;
                        }
                        unset($service_data['password']);
                    }

                    if (!empty($service_data['state']) && $service_data['state'] == 'offline') {
                        $tips = $this->appendQueueOfflineTips(['code' => 4, 'msg' => '该客服暂时离开,请留言！也可以转接其他客服'], $business, 0);
                        if (!empty($tips['offline_greeting'])) {
                            $service_data['content'] = $tips['offline_greeting'];
                        }
                        unset($service_data['open_id']);
                        unset($service_data['password']);
                        return array_merge($tips, ['data' => $service_data]);
                    }
                    
                    $data = ['state' => 'normal', 'service_id' => $service_data['service_id'], 'groupid' => $arr['groupid']];
                    $queue = model('queue')->where('qid', $service['qid'])->update($data);
                    $words = model('sentence')->where("service_id", $service_data['service_id'])->where('state', 'using')->find();

                    if ($words) {
                        $service_data['content'] = $words['content'];
                    } else {
                        $service_data['content'] = "您好！";
                    }
                    unset($service_data['open_id']);
                    $returndata = ['code' => 0, 'msg' => 'success', 'data' => $service_data];
                    return $returndata;

                } else {
                    // 不存在
                    if (!empty($special) && $special>0) {
                        $service_data = Service::get(['service_id' => $special,'business_id'=>$arr["business_id"]]);
                        if (empty($service_data)) {
                            $res = ['code' => 1, 'msg' => '不存在该专属客服'];
                            return $res;
                        }
                        unset($service_data['password']);
                    } else {
                        $service_data = Distribute::run($business_id,'online',$arr['groupid']);

                        if(!$service_data) {
                            $service_data = Distribute::run($business_id,null,$arr['groupid']);
                        }
                    }

                    if (empty($service_data)) {
                        $res = $this->appendQueueOfflineTips(['code' => 1, 'msg' => '该分类客服都不在线', 'data' => $service_data], $business, 0);
                        return $res;
                    }
                    $gid = $arr['groupid'];

                    $res = model('queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'state' => 'normal'])->find();


                    if ($res) {

                        $queue = model("queue")->where('qid', $res['qid'])->update(['service_id' => $service_data['service_id'], 'groupid' => $arr['groupid']]);


                    } else {
                        $data = ['visiter_id' => $visiter_id, 'service_id' => $service_data['service_id'], 'business_id' => $business_id, 'groupid' => $gid];

                        $queue = model("queue")->insert($data);
                    }

                    // 推送游客信息
                    $pusher->trigger("ud" . $service_data['service_id'], 'on_notice', array('message' => $arr));

                    $words = model('sentence')->where("service_id", $service_data['service_id'])->where('state', 'using')->find();

                    if ($words) {
                        $service_data['content'] = $words['content'];
                    } else {
                        $service_data['content'] = "您好！";
                    }

                    unset($service_data['open_id']);
                    $returndata = ['code' => 0, 'msg' => 'success', 'data' => $service_data];
                    return $returndata;

                }

            } else {
                // 新用户
                $gid = $arr['groupid'];
                $visiter_data = [
                    'visiter_id' => $visiter_id,
                    'visiter_name' => $arr['visiter_name'],
                    'channel' => $arr['channel'],
                    'avatar' => $arr['avatar'],
                    'business_id' => $business_id,
                    'state' => 'offline',
                    'ip' => $arr['ip'],
                    'from_url' => $arr['from_url'],
                    'extends' => $arr['extends']
                ];
                $openid = session('onesendopenid');
                if ($openid) {
                    $visiter_data['openid'] = $openid;
                }
                session('onesendopenid', null);
                $newvisiter = model('visiter')->insert($visiter_data);

                // 如果开启转人工，不自动分配客服
                if ($transfer_human == 'open') {
                    $data = ['visiter_id' => $visiter_id, 'service_id' => 0, 'business_id' => $business_id, 'groupid' => $gid, 'state' => 'normal'];
                    $qu = model("queue")->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'state' => 'normal'])->find();
                    if ($qu) {
                        model("queue")->where('qid', $qu['qid'])->update(['groupid' => $gid, 'service_id' => 0, 'state' => 'normal']);
                    } else {
                        model("queue")->insert($data);
                    }
                    $returndata = $this->appendQueueOfflineTips(['code' => 2, 'msg' => '等待转人工！', 'data' => 0], $business, 0);
                    return $returndata;
                }

                if (!empty($special) && $special>0) {
                    $service_data = Service::get(['service_id' => $special,'business_id'=>$arr["business_id"]]);
                    if (empty($service_data)) {
                        $res = ['code' => 1, 'msg' => '不存在该专属客服'];
                        return $res;
                    }
                    unset($service_data['password']);
                } else {
                    $service_data = Distribute::run($business_id,'online',$gid);

                    if(!$service_data) {
                        $service_data = Distribute::run($business_id,null,$gid);
                    }
                }

                if (empty($service_data)) {
                    $res = $this->appendQueueOfflineTips(['code' => 1, 'msg' => '该分类客服都不在线', 'data' => $service_data], $business, 0);
                    return $res;
                }

                $data = ['visiter_id' => $visiter_id, 'service_id' => $service_data['service_id'], 'business_id' => $business_id, 'groupid' => $gid];

                $qu = model("queue")->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'state' => 'normal'])->find();
                if ($qu) {
                    $qu = model("queue")->where('qid', $qu['qid'])->update(['groupid' => $gid, 'service_id' => $service_data['service_id']]);
                } else {
                    $queue = model("queue")->insert($data);
                }

                // 推送游客信息
                $pusher->trigger("ud" . $service_data['service_id'], 'on_notice', array('message' => $arr));

                $words = model('sentence')->where("service_id", $service_data['service_id'])->where('state', 'using')->find();

                if ($words) {
                    $service_data['content'] = $words['content'];
                } else {
                    $service_data['content'] = "您好！";
                }

                model('chats')->where(['visiter_id' => $visiter_id, 'service_id' => 0])->where('business_id', $business_id)->update(['service_id' => $service_data['service_id']]);

                $returndata = ['code' => 0, 'msg' => 'success', 'data' => $service_data];

                $wechat = WechatPlatform::get(['business_id'=>$business_id]);
//                控制频繁度客服离线才发送 微信：da0719z
                if($service_data['open_id'] && $service_data['state']=='offline'){
                    try {
                        TplService::send($arr["business_id"],$service_data['open_id'],url('weixin/login/callback',['business_id'=>$arr['business_id'],'service_id'=>$service_data['service_id']],true,true),$wechat['visitor_tpl'],[
                            "first"  => "您有新访客！",
                            "keyword1"   => $arr["visiter_name"] ?$arr["visiter_name"]:'游客'.$arr['visiter_id'],
                            "keyword2"  => date('Y-m-d H:i:s',time()),
                            "remark" => $business['business_name']."提示:有新客户啦,快去撩一把~",
                        ]);
                    }catch (\Exception $exception) {
                        Log::error($exception->getMessage());
                    }
                }

                unset($service_data['open_id']);
                return $returndata;

            }
        }
    }

    /**
     * 排队提示 / 离线问候语附加字段
     */
    protected function appendQueueOfflineTips(array $ret, $business, $num = null)
    {
        if ($num === null) {
            $num = (isset($ret['data']) && is_numeric($ret['data'])) ? (int)$ret['data'] : 0;
        } else {
            $num = (int)$num;
        }
        $show = !isset($business['queue_tip_state']) || $business['queue_tip_state'] !== 'close';
        $tpl = (!empty($business['queue_tip_text']))
            ? $business['queue_tip_text']
            : '通知 ： 现在还有{num} 人在排队，请等待 ....';
        $ret['queue_tip_show'] = $show ? 1 : 0;
        $ret['queue_tip_text'] = str_replace('{num}', (string)$num, $tpl);
        $offlineOn = !isset($business['offline_auto_reply']) || $business['offline_auto_reply'] !== 'close';
        $greeting = (!empty($business['offline_greeting']))
            ? $business['offline_greeting']
            : '您好，客服当前不在线，留言后我们会尽快回复。';
        $ret['offline_auto_reply'] = $offlineOn ? 1 : 0;
        $ret['offline_greeting'] = $offlineOn ? $greeting : '';
        return $ret;
    }

    /**
     * 客服离线时自动回复问候语（节流 120 秒）
     */
    protected function maybeSendOfflineGreeting($arr, $business, $service_id, $pusher)
    {
        try {
            $offlineOn = !isset($business['offline_auto_reply']) || $business['offline_auto_reply'] !== 'close';
            if (!$offlineOn) {
                return;
            }
            $greeting = (!empty($business['offline_greeting']))
                ? $business['offline_greeting']
                : '您好，客服当前不在线，留言后我们会尽快回复。';
            $greeting = strip_tags($greeting);
            if ($greeting === '') {
                return;
            }

            $need = false;
            if (empty($service_id)) {
                $online = model('service')->where(['business_id' => $arr['business_id'], 'state' => 'online'])->count();
                $need = ($online == 0);
            } else {
                $svc = model('service')->where('service_id', $service_id)->find();
                $need = ($svc && $svc['state'] == 'offline');
            }
            if (!$need) {
                return;
            }

            $content = htmlspecialchars($greeting);
            $recent = model('chats')
                ->where('visiter_id', $arr['visiter_id'])
                ->where('business_id', $arr['business_id'])
                ->where('direction', 'to_visiter')
                ->where('content', $content)
                ->where('timestamp', '>', time() - 120)
                ->find();
            if ($recent) {
                return;
            }

            $avatar = '';
            if (!empty($service_id)) {
                $svcRow = model('service')->where('service_id', $service_id)->find();
                if ($svcRow && !empty($svcRow['avatar'])) {
                    $avatar = $svcRow['avatar'];
                }
            }
            if ($avatar === '') {
                $avatar = '/assets/images/index/workerman_logo.png';
            }

            $msg = [
                'visiter_id' => $arr['visiter_id'],
                'service_id' => $service_id ? intval($service_id) : 0,
                'business_id' => $arr['business_id'],
                'content' => $content,
                'timestamp' => time(),
                'direction' => 'to_visiter',
                'state' => 'unread',
                'unstr' => '',
                'avatar' => $avatar,
            ];
            model('chats')->insert($msg);
            $channel = bin2hex($arr['visiter_id'] . '/' . $arr['business_id']);
            $pusher->trigger('cu' . $channel, 'my-event', ['message' => $msg]);
        } catch (\Exception $e) {
            Log::error('maybeSendOfflineGreeting: ' . $e->getMessage());
        }
    }

    /**
     * 标记已看信息.
     *
     * @return mixed
     */
   public function getwatch()
    {
        $login = session('Msg');
        $business_id = $login['business_id'];
        $service_id = $login['service_id'];
        $post = $this->request->post();

        $cids=[];
        $data = model('chats')->where(['service_id' => $service_id, 'visiter_id' => $post['visiter_id'],'direction' => 'to_service','state' => 'unread', 'business_id' => $business_id])->select();
        foreach ($data as $value){
            model('chats')->where('cid', $value['cid'])->update(['state' => 'readed']);
            $cids[]=$value['cid'];
        }

        if(!empty($cids) && !empty($service_id)){
            $pusher = SinglePusher::getinstance();
            $pusher->trigger('kefu' . $service_id, 'check-event', array('message' => $cids));
        }
        $arr = ['code' => 0, 'msg' => 'success'];
        return $arr;
    }

    /**
     * 顾客端标记客服消息为已读（无需session）
     */
    public function getwatchVisitor()
    {
        $post = $this->request->post();
        $visiter_id = isset($post['visiter_id']) ? $post['visiter_id'] : '';
        $business_id = isset($post['business_id']) ? intval($post['business_id']) : 0;

        if (empty($visiter_id) || empty($business_id)) {
            return ['code' => 1, 'msg' => '参数错误'];
        }

        $cids = [];
        $data = model('chats')->where([
            'visiter_id' => $visiter_id,
            'direction' => 'to_visiter',
            'state' => 'unread',
            'business_id' => $business_id
        ])->select();

        foreach ($data as $value) {
            model('chats')->where('cid', $value['cid'])->update(['state' => 'readed']);
            $cids[] = $value['cid'];
        }

        if (!empty($cids)) {
            $pusher = SinglePusher::getinstance();
            // 通知客服端更新已读状态
            $service = model('queue')->where('business_id', $business_id)->where('visiter_id', $visiter_id)->where('state', 'normal')->find();
            if ($service && !empty($service['service_id'])) {
                $pusher->trigger('kefu' . $service['service_id'], 'check-event', array('message' => $cids));
            }
        }

        return ['code' => 0, 'msg' => 'success'];
    }

    /**
     * 图片上传.
     *
     * @return [type] [description]
     */
    public function upload()
    {
        $pusher=SinglePusher::getinstance();
        $post = $this->request->post();
        $service = model('queue')->where('business_id', $post['business_id'])->where('visiter_id', $post['visiter_id'])->where('state', 'normal')->find();

        if ($service['service_id'] != $post['service_id']) {
            $returndata = ['code' => 1, 'msg' => '该会话已经关闭！'];
            return $returndata;
        }

        $service_id = $service['service_id'];
        $post["timestamp"] = time();
        $post['service_id'] = $service_id;

        $business = model('business')->where('id', $post['business_id'])->find();
        $transfer_human = isset($business['transfer_human']) ? $business['transfer_human'] : 'close';

        try {
            Storage::$variable = 'upload';
            $url = Storage::put();
            $safe_url = htmlspecialchars($url['url'], ENT_QUOTES, 'UTF-8');
            $html = '<img  src="' . $safe_url . '" />';
            $chat_data = [
                'visiter_id' => $post['visiter_id'],
                'service_id' => $post['service_id'],
                'business_id' => $post['business_id'],
                'content' => $html,
                'timestamp' => $post['timestamp'],
                'direction' => 'to_service',
                'state' => 'unread',
                'unstr' => '',
                'avatar' => $post['avatar']
            ];
            $res = model('chats')->insert($chat_data);
            
            if ($transfer_human != 'open' || ($transfer_human == 'open' && $service_id != 0)) {
                $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $post));
                model("visiter")->where("visiter_id", $post['visiter_id'])->where("business_id", $post['business_id'])->update(["state" => 'online']);
                WxPusher::sendToService($service_id);
            }
            
            $data = [
                "code" => 0,
                "msg" => "",
                "data" => $html
            ];
            return $data;
        } catch (StorageException $exception) {
            $data = ['code'=> -1,'msg'=>$exception->getMessage(),'data'=>''];
        } catch (\Exception $e) {
            $data = ['code'=> -1,'msg'=>'请检查存储介质配置信息'];
        }
        return $data;
    }

    /**
     * 文件上传.
     *
     * @return [type] [description]
     */
    public function uploadfile()
    {

        $pusher=SinglePusher::getinstance();

        $post = $this->request->post();
        $service = model('queue')->where('business_id', $post['business_id'])->where('visiter_id', $post['visiter_id'])->where('state', 'normal')->find();

        if ($service['service_id'] != $post['service_id']) {

            $returndata = ['code' => 1, 'msg' => '该会话已经关闭！'];
            return $returndata;
        }

        $service_id = $service['service_id'];

        $post["timestamp"] = time();
        $post['service_id'] = $service_id;

        $business = model('business')->where('id', $post['business_id'])->find();
        $transfer_human = isset($business['transfer_human']) ? $business['transfer_human'] : 'close';

        $name = $_FILES["folder"]["name"];

        try {
            Storage::$variable = 'folder';
            $url = Storage::put();
            $safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $safe_url = htmlspecialchars($url['url'], ENT_QUOTES, 'UTF-8');
            $html = "<div><a href='" . $safe_url . "' style='display: inline-block;text-align: center;min-width: 70px;text-decoration: none;' download='" . $safe_name . "'><i class='layui-icon' style='font-size: 60px;'>&#xe61e;</i><br>" . $safe_name . "</a></div>";


            $chat_data = [
                'visiter_id' => $post['visiter_id'],
                'service_id' => $post['service_id'],
                'business_id' => $post['business_id'],
                'content' => $html,
                'timestamp' => $post['timestamp'],
                'direction' => 'to_service',
                'state' => 'unread',
                'unstr' => '',
                'avatar' => $post['avatar']
            ];
            $res = model('chats')->insert($chat_data);
            
            if ($transfer_human != 'open' || ($transfer_human == 'open' && $service_id != 0)) {
                $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $post));
                model("visiter")->where("visiter_id", $post['visiter_id'])->where("business_id", $post['business_id'])->update(["state" => 'online']);
                WxPusher::sendToService($service_id);
            }

            $data = [
                "code" => 0,
                "msg" => "",
                "data" => $url['url']
            ];

            return $data;
        } catch (StorageException $exception) {
            $data = ['code'=> -1,'msg'=>$exception->getMessage(),'data'=>''];
        } catch (\Exception $e) {
            $data = ['code'=> -1,'msg'=>'请检查存储介质配置信息'];
        }
        return $data;

    }


    /**
     * 视频上传.
     *
     * @return [type] [description]
     */
    public function uploadvideo()
    {

        $pusher=SinglePusher::getinstance();

        $post = $this->request->post();
        $service = model('queue')->where('business_id', $post['business_id'])->where('visiter_id', $post['visiter_id'])->where('state', 'normal')->find();

        if ($service['service_id'] != $post['service_id']) {

            $returndata = ['code' => 1, 'msg' => '该会话已经关闭！'];
            return $returndata;
        }

        $service_id = $service['service_id'];

        $post["timestamp"] = time();
        $post['service_id'] = $service_id;

        $business = model('business')->where('id', $post['business_id'])->find();
        $transfer_human = isset($business['transfer_human']) ? $business['transfer_human'] : 'close';

        $name = $_FILES["video"]["name"];

        try {
            Storage::$variable = 'video';
            $url = Storage::put();
            $safe_url = htmlspecialchars($url['url'], ENT_QUOTES, 'UTF-8');
            $html = "<div><video src='" . $safe_url . "' controls style='max-width: 300px;max-height: 200px;'></video></div>";


            $chat_data = [
                'visiter_id' => $post['visiter_id'],
                'service_id' => $post['service_id'],
                'business_id' => $post['business_id'],
                'content' => $html,
                'timestamp' => $post['timestamp'],
                'direction' => 'to_service',
                'state' => 'unread',
                'unstr' => '',
                'avatar' => $post['avatar']
            ];
            $res = model('chats')->insert($chat_data);
            
            if ($transfer_human != 'open' || ($transfer_human == 'open' && $service_id != 0)) {
                $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $post));
                model("visiter")->where("visiter_id", $post['visiter_id'])->where("business_id", $post['business_id'])->update(["state" => 'online']);
                WxPusher::sendToService($service_id);
            }

            $data = [
                "code" => 0,
                "msg" => "",
                "data" => $url['url']
            ];

            return $data;
        } catch (StorageException $exception) {
            $data = ['code'=> -1,'msg'=>$exception->getMessage(),'data'=>''];
        } catch (\Exception $e) {
            $data = ['code'=> -1,'msg'=>'请检查存储介质配置信息'];
        }
        return $data;

    }


    /**
     * 获取最近对话信息.
     *
     * @return string
     */
    public function chatdata()
    {

        $post = $this->request->post();
        $vid = $post['vid'];
        $service_id = $post['service_id'];

        if ($post["hid"] == '') {

            $data = model('chats')->where(['visiter_id' => $vid, 'business_id' => $post['business_id']])->order('timestamp desc,cid asc')->limit(10)->select();

            $vdata = model('visiter')->where('visiter_id', $vid)->find();
            $sdata = model('service')->where('service_id', $service_id)->find();
            foreach ($data as $v) {

                if ($v['direction'] == 'to_service') {

                    $v['avatar'] = $vdata['avatar'];
                } else {
                    $v['avatar'] = $sdata['avatar'];
                }

            }
            reset($data);
        } else {

            $data = model('chats')->where(['visiter_id' => $vid, 'business_id' => $post['business_id']])->where('cid', '<', $post['hid'])->order('timestamp desc,cid asc')->limit(10)->select();
            $vdata = model('visiter')->where('visiter_id', $vid)->find();
            $sdata = model('service')->where('service_id', $service_id)->find();

            foreach ($data as $v) {

                if ($v['direction'] == 'to_service') {
                    $v['avatar'] = $vdata['avatar'];
                } else {
                    $v['avatar'] = $sdata['avatar'];
                }
            }
            reset($data);
        }

        $result = array_reverse($data);

        $data = ['code' => 0, 'data' => $result];
        return $data;
    }

    /**
     * 获取新消息（客户端轮询用）
     * @return array
     */
    public function getNewMessages()
    {
        $post = $this->request->post();
        $visiter_id = $post['visiter_id'];
        $service_id = $post['service_id'];
        $business_id = $post['business_id'];
        $last_cid = intval($post['last_cid']);

        if (empty($visiter_id)) {
            return ['code' => 1, 'msg' => '参数错误', 'data' => []];
        }

        // 获取客服发送给客户的新消息
        $query = model('chats')
            ->where('visiter_id', $visiter_id)
            ->where('business_id', $business_id)
            ->where('direction', 'to_visiter');

        if ($last_cid > 0) {
            $query->where('cid', '>', $last_cid);
        }

        $data = $query->order('cid asc')->limit(20)->select();

        // 获取客服头像 - 优先使用传入的service_id，否则从消息中提取
        $avatar = '';
        if (!empty($service_id) && $service_id != '0') {
            $sdata = model('service')->where('service_id', $service_id)->find();
            if ($sdata) {
                $avatar = $sdata['avatar'];
            }
        }
        
        // 如果没有头像，尝试从visiter表获取默认客服
        if (empty($avatar)) {
            $visiter_info = model('visiter')->where('visiter_id', $visiter_id)->find();
            if ($visiter_info && !empty($visiter_info['service_id'])) {
                $sdata = model('service')->where('service_id', $visiter_info['service_id'])->find();
                if ($sdata) {
                    $avatar = $sdata['avatar'];
                }
            }
        }

        $result = [];
        foreach ($data as $v) {
            $v['avatar'] = $avatar;
            $result[] = $v;
        }

        return ['code' => 0, 'data' => $result];
    }


    /**
     * 删除访客信息.
     *
     * @return boolAdmins
     */
    public function qdelete()
    {

        $post = $this->request->post();
        $result = model('queue')->where(['visiter_id' => $post['visiter_id'], 'business_id' => $post['business_id']])->delete();
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
/////////////////////////////////////////////////
public function postinput(){
    $user_id = $this->request->post('user');
    $message = $this->request->post('message');

    // 查找用户
    $user = model('postinput')->where('user', $user_id)->find();

    if($user){
        // 如果用户存在，则更新该用户的message字段
        $result = model('postinput')->where('user', $user_id)->update(['message' => $message]);
    }else{
        // 如果用户不存在，则插入一条新记录
        $result = model('postinput')->insert(['user' => $user_id, 'message' => $message]);
    }

    if($result){
        return json(['status'=>1,'msg'=>'数据操作成功']);
    }else{
        return json(['status'=>0,'msg'=>'数据操作失败']);
    }
}
////////////////////////////////////////////////

public function getinput(){
    $user_id = $this->request->get('userid');

    // 查找用户
    $user = model('getinput')->where('user', $user_id)->find();

    if($user){
        return json(['user'=>$user['user'], 'message'=>$user['message']]);
    }else{
        return json(['status'=>0,'msg'=>'用户不存在']);
    }
}
////////////////////////////////////////////////
    /**
     *
     * @return string
     */
    public function apply()
    {
        $post = $this->request->post();

        $visiter = model('service')->where('service_id', $post['id'])->find();

        $queue = model('queue')->where(['visiter_id' => $post['visiter_id'], 'service_id' => $post['id']])->where('business_id', $post['business_id'])->update(['state' => 'normal']);


        $type = $visiter['state'];

        if ($type == 'offline') {

            $data = ['code' => 1, 'msg' => "对方不在线！"];

            return $data;
        }

        $pusher=SinglePusher::getinstance();
        $pusher->trigger("kefu" . $post['id'], "video", array("message" => "申请视频连接", "channel" => $post['channel'], "avatar" => $post['avatar'], 'username' => $post['name'], "cid" => $post['cha']));

        $data = ['code' => 0, 'msg' => 'success'];

        return $data;

    }

    /**
     *
     * [refuse description]
     * @return [type] [description]
     */
    public function refuse()
    {
        $post = $this->request->post();

        $pusher=SinglePusher::getinstance();

        $pusher->trigger("kefu" . $post['id'], "video-refuse", array("message" => "对方拒绝视频连接！"));
    }

    /**
     * [getquestion description]
     * @return [type] [description]
     */
    public function getquestion()
    {
        $post = $this->request->post();
        $business_id = $post['business_id'];

        $result = model('question')
            ->where('business_id', $business_id)
            ->where('status','eq', 1)
            ->order('sort desc')
            ->select();
        $business = Business::get($business_id);
        $keyword = model('question')
            ->where('business_id', $business_id)
            ->where('status','eq', 1)
            ->where('keyword','neq','')
            ->count();
        if ($result) {
            $arr = ['code' => 0, 'msg' => 'success', 'data' => $result,'keyword'=>$keyword,'logo'=>$business['logo']];
            return $arr;
        }
    }

    /**
     *
     * [getanswer description]获取问题详情
     * @return [type] [description]
     */
    public function getanswer()
    {
        $post = $this->request->post();
        $qid = $post['qid'];
        $visiter_id = isset($post['visiter_id']) ? $post['visiter_id']: 0 ;
        $result = model('question')->where('qid', $qid)->find();
        if ($result) {
            $service = model('queue')->where('business_id', $result['business_id'])->where('visiter_id', $visiter_id)->where('state', 'normal')->find();
            $service_id = $service ? $service['service_id'] : 0;

            $business = model('business')->where('id', $result['business_id'])->find();
            $transfer_human = isset($business['transfer_human']) ? $business['transfer_human'] : 'close';

            $pusher=SinglePusher::getinstance();
            $arr['visiter_id'] = $visiter_id;
            $arr['business_id'] = $result['business_id'];
            $arr['service_id'] = $service_id;
            $arr["timestamp"] = time();
            $arr["content"] = \app\Common::clearXSS($result['question']);
            $arr['direction'] = 'to_service';
            unset($arr['__token__']);
            $data1 = $arr;

            if ($transfer_human != 'open' || ($transfer_human == 'open' && $service_id != 0)) {
                $pusher->trigger('kefu' .  $service_id, 'cu-event', array('message' => $arr));
            }

            $chat_data1 = [
                'visiter_id' => $data1['visiter_id'],
                'service_id' => $data1['service_id'],
                'business_id' => $data1['business_id'],
                'content' => $data1['content'],
                'timestamp' => $data1['timestamp'],
                'direction' => $data1['direction'],
                'state' => 'unread',
                'unstr' => '',
                'avatar' => ''
            ];
            model('chats')->insert($chat_data1);

            $arr["content"] = \app\Common::clearXSS($result['answer']);
            $arr['direction'] = 'to_visiter';
            $arr["timestamp"] = time();
            $channel = bin2hex($arr['visiter_id'] . '/' . $arr['business_id']);

            // 获取客服头像
            $service_info = model('service')->where('business_id', $result['business_id'])->find();
            $avatar = $service_info ? $service_info['avatar'] : (isset($business['logo']) ? $business['logo'] : '');
            $arr['avatar'] = $avatar;

            // 先推送WebSocket，再执行hook和数据库操作，避免hook出错导致推送失败
            $pusher->trigger("cu" . $channel, 'my-event', array('message' => $arr));

            try {
                hook('returnanswerhook',array_merge($arr,['channel'=>$channel,'pusher'=>$pusher]));
            } catch (\Exception $e) {}

            $chat_data2 = [
                'visiter_id' => $arr['visiter_id'],
                'service_id' => $arr['service_id'],
                'business_id' => $arr['business_id'],
                'content' => $arr['content'],
                'timestamp' => $arr['timestamp'],
                'direction' => $arr['direction'],
                'state' => 'unread',
                'unstr' => '',
                'avatar' => $arr['avatar']
            ];
            model('chats')->insert($chat_data2);

            if ($transfer_human == 'open' && $service_id == 0) {
                $transfer_msg = [
                    'visiter_id' => $visiter_id,
                    'service_id' => 0,
                    'business_id' => $result['business_id'],
                    'content' => '<a href="javascript:void(0)" onclick="transferToHuman()" style="color: #6a59ff;">转人工客服</a>',
                    'timestamp' => time(),
                    'direction' => 'from_service',
                    'state' => 'unread',
                    'unstr' => '',
                    'avatar' => $avatar
                ];
                model('chats')->insert($transfer_msg);
                $transfer_msg['avatar'] = $avatar;
                $pusher->trigger("cu" . $channel, 'my-event', array('message' => $transfer_msg));
            }
            
            $arr = ['code' => 0, 'msg' => 'success', 'data' => $result];
            return $arr;
        } else {
            $arr = ['code' => 1, 'msg' => '该问题已经被删除'];
            return $arr;
        }
    }

    /**
     *
     * [groupNum description]
     * @return [type] [description]
     */
    public function groupNum()
    {
        $num = model('group')->count();
        return $num;
    }

    /**
     * [getchangekefu description]
     * @return [type] [description]
     */
    public function getchangekefu()
    {
        $post = $this->request->post();

        $res = model('queue')->where('visiter_id', $post['visiter_id'])->where('business_id', $post['business_id'])->update(['service_id' => 0]);

        return $arr = ['code' => 0, 'msg' => 'success'];

    }

    /**
     *
     * [gettablist description]
     * @return [type] [description]
     */
    public function gettablist()
    {
        $post = $this->request->post();
        $business_id = $post['business_id'];
        $result = model('tablist')->where('business_id', $business_id)->select();

        $arr = ['code' => 0, 'msg' => 'success', 'data' => $result];

        return $arr;
    }


    /**
     *
     * [uploadimg description]
     * @return [type] [description]
     */
    public function uploadimg()
    {

        $file = $this->request->file("editormd-image-file");
        $name = $_FILES["editormd-image-file"]["name"];
        $ext  = substr( $name, strrpos( $name, '.' ) + 1);
        $formats  = array('gif', 'jpg', 'jpeg', 'png', 'bmp', 'webp'
        );
        if(!in_array($ext, $formats)){
            $error = "不支持该文件格式！";

            $data = [
                "code" => -1,
                "msg" => $error,
                "data" => ""
            ];
            $json = json_encode($data);
            return $json;
        }
        $uploaded_tmp  = $_FILES["editormd-image-file"]['tmp_name'];
        if(!getimagesize($uploaded_tmp)){
            $error = "非法图片文件";
            $data = [
                "code" => -1,
                "msg" => $error,
                "data" => ""
            ];
            $json = json_encode($data);
            return $json;
        }
        if ($file) {
            $newpaths = ROOT_PATH . "/public/upload/files/";
            $info = $file->move($newpaths, time());
            if ($info) {
                $imgname = $info->getFilename();

                $imgpath = $this->base_root."/upload/files/" . $imgname;

                $data = [
                    "success" => 1,
                    "msg" => "success",
                    "url" => $imgpath
                ];

                return json_encode($data);
            }
        }
    }

    /**
     *
     * [uploadVoice description]
     * @return [type] [description] 录音上传
     */
    public function uploadVoice()
    {
        $file = $this->request->file('file');

        if ($file) {
            // 扩展名白名单校验
            $ext = strtolower(pathinfo($file->getInfo('name'), PATHINFO_EXTENSION));
            if (!in_array($ext, ['wav', 'mp3', 'ogg'])) {
                return json_encode(['code' => 1, 'msg' => '不支持的音频格式']);
            }
            // 大小限制 5MB
            if (!$file->check(['size' => 5242880])) {
                return json_encode(['code' => 1, 'msg' => '音频大小受限，最大为5MB']);
            }
            // MIME 类型校验（防止伪装扩展名）
            $tmpName = $file->getInfo('tmp_name');
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                $allowedMimes = ['audio/wav', 'audio/x-wav', 'audio/wave', 'audio/mpeg', 'audio/mp3', 'audio/ogg', 'application/ogg'];
                if (!in_array($mime, $allowedMimes)) {
                    return json_encode(['code' => 1, 'msg' => '非法音频文件']);
                }
            }

            $newpath = ROOT_PATH . "/public/assets/upload/voices/";
            $info = $file->move($newpath, time() . "." . $ext);

            if ($info) {

                $imgname = $info->getFilename();
                $imgpath = $this->base_root. "/assets/upload/voices/" . $imgname;
                $arr = [
                    'data' => [
                        'src' => $imgpath
                    ]
                ];

                return json_encode($arr);
            } else {

                return false;
            }
        }
    }


    /**
     *
     * [getwaitnum description]
     * @return [type] [description]
     */

    public function getwaitnum()
    {

        $post = $this->request->post();

        if ($post['groupid'] == 0) {
            $count = model('queue')->where(['business_id' => $post['business_id'], 'state' => 'normal', 'service_id' => 0])->count();
        } else {
            $count = model('queue')->where(['business_id' => $post['business_id'], 'state' => 'normal', 'service_id' => 0, 'groupid' => $post['groupid']])->count();
        }
        return $count;
    }

    public function comment()
    {
        $post = $this->request->post();
        $visiter = Visiter::get(['visiter_id'=>$post['visiter_id'],'business_id'=>$post['business_id']]);
        if (empty($visiter)) {
            return json([
                'code' => 1,
                'msg' => '评价失败，请重试',
            ]);
        }

        $service = Service::get(['service_id'=>$post['service_id']]);
        if (empty($service)) {
            return json([
                'code' => 1,
                'msg' => '评价失败，请重试',
            ]);
        }
        $post['scores'] = $this->request->post('scores',[],null);
        $data = json_decode($post['scores'],true);
        if (!is_array($data)) {
            return json([
                'code' => 1,
                'msg' => '评价失败，请重试',
            ]);
        }

        foreach ($data as $v) {
            if ($v['score'] == 0) {
                return json([
                    'code' => 1,
                    'msg' => "请对{$v['title']}进行评分",
                ]);
            }
        }

        $res =  Comment::create([
            'business_id'=>$post['business_id'],
            'service_id'=> $service['service_id'],
            'group_id' => $service['groupid'],
            'visiter_id'=>$visiter['visiter_id'],
            'visiter_name'=>$visiter['visiter_name'],
            'word_comment'=>$post['comment'],
        ]);

        $model = new CommentDetail();
        foreach ($data as &$v) {
            $v['comment_id'] = $res['id'];
        }
        unset($v);
        $model->insertAll($data);

        if ($res !== false) {
            return json([
                'code' => 0,
                'msg' => '感谢您的评价~',
            ]);
        } else {
            return json([
                'code' => 1,
                'msg' => '评价失败，请重试',
            ]);
        }
    }

    public function info()
    {
        $post = $this->request->post();
        $visiter = Visiter::get(['visiter_id'=>$post['visiter_id'],'business_id'=>$post['business_id']]);
        $rest = RestSetting::get(['business_id'=>$post['business_id']]);
        if (empty($visiter)) {
            return  [
                'code'=>1,
                'msg'=> 'error',
            ];
        }

        $post['name'] = $this->request->post('name','');
        $post['tel'] = $this->request->post('tel','');

        $rule = '^1(3|4|5|7|8)[0-9]\d{8}$^';
        $result = preg_match($rule, $post['tel']);

        if ($rest['tel_state'] == 'open') {
            if (!$result) {
                return  [
                    'code'=>1,
                    'msg'=> '手机格式不正确',
                ];
            }
        }

        if ($rest['name_state'] == 'open') {
            if (empty($post['name'])) {
                return  [
                    'code'=>1,
                    'msg'=> '请填写姓名',
                ];
            }
        }

        $res = $visiter->save(['name'=>$post['name'],'tel'=>$post['tel'],'msg_time'=>date('Y-m-d H:i:s',time())]);
        if ($res !== false) {
            return json([
                'code' => 0,
                'msg' => '提交成功,请等待客服回复',
            ]);
        } else {
            return json([
                'code' => 1,
                'msg' => '提交失败',
            ]);
        }
    }
}