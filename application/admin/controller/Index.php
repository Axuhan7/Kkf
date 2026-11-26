<?php


namespace app\admin\controller;

use app\admin\model\Admins;
use app\admin\model\WechatPlatform;
use app\admin\model\WechatService;
use app\common\lib\WxPusher;
use think\Db;
use think\Paginator;
use app\Common;
/**
 *
 * 后台页面控制器.
 */
class Index extends Base
{

    /**
     * 后台首页
     *
     * @return mixed
     */
    public function index()
    {
        $common = new Common();
        if ($common->isMobile()) {
            $this->redirect('mobile/admin/index');
        }
        $login = session('Msg');
        $time = date('Y-m-d', time());
        $t = strtotime(date('Y-m-d'));
        $times = date('Y-m-d H:i', time());
        $ftime = date('Y-m-d', time());
        $frtime = strtotime($ftime);

        if ($login['level'] != 'super_manager') {
        // 接入总量
            $sql = "select count(distinct(visiter_id)) as total from wolive_chats where business_id=:business_id and service_id=:service_id";
            $res = Db::query($sql, ['business_id' => $login['business_id'], 'service_id' => $login['service_id']]);
            $getinall = isset($res[0]['total'])?$res[0]['total']:0;
        } else {
            // 接入用户量
            $sql = "select count(distinct(visiter_id)) as total from ".config('database.prefix')."chats where business_id=:business_id";
            $res = Db::query($sql, ['business_id' => $login['business_id']]);
            $getinall = isset($res[0]['total'])?$res[0]['total']:0;
        }
        if ($login['level'] != 'super_manager') {
            // 获取总会话量
            $chatsall = model("chats")->where('business_id', $login['business_id'])->where('service_id', $login['service_id'])->count();
        } else {
        // 获取总会话量
        $chatsall = model("chats")->where('business_id', $login['business_id'])->count();
        }
        // 正在排队人数
        $waiter = model("queue")->where(['business_id' => $login['business_id'], 'state' => 'normal'])->where("service_id", 0)->count();
        if ($login['level'] != 'super_manager') {
            // 正在咨询的人
            $talking = model('queue')->where(['business_id' => $login['business_id']])->where('service_id', $login['service_id'])->where('state', 'normal')->where("service_id", '<>', 0)->count();
        } else {
        // 正在咨询的人
        $talking = model('queue')->where(['business_id' => $login['business_id']])->where('state', 'normal')->where("service_id", '<>', 0)->count();
        }
        if ($login['level'] != 'super_manager') {
            //在线用户数
            $sql = "select count(distinct(a.visiter_id)) as total from ".config('database.prefix')."visiter a left join wolive_chats b";
            $sql .= " on a.visiter_id=b.visiter_id";
            $sql .= " WHERE a.business_id=:business_id and b.service_id=:service_id";
            $sql .= " and a.state='online'";
            $res = Db::query($sql, ['business_id' => $login['business_id'], 'service_id' => $login['service_id']]);
            $visiter_online = isset($res[0]['total'])?$res[0]['total']:0;;
        } else {
            //在线用户数
            $visiter_online = model('visiter')->where(['business_id' => $login['business_id']])->where('state', 'online')->count();
        }
        // 在线客服人数
        $services = model("service")->where(['business_id' => $login['business_id'], 'state' => 'online'])->count();
        if ($login['level'] != 'super_manager') {
        // 今日会话量
            $nowchats = model("chats")->where('business_id', $login['business_id'])->where('service_id', $login['service_id'])->where('timestamp', '>', "{$t}")->where('timestamp', '<=', time())->count();
        } else {
            // 今日会话量
            $nowchats = model("chats")->where('business_id', $login['business_id'])->where('timestamp',
                '>', "{$t}")->where('timestamp', '<=', time())->count();
        }
        if ($login['level'] != 'super_manager') {
            //今日评价人数
            $nowcomments = model("comment")->where('business_id', $login['business_id'])->where('service_id', $login['service_id'])->where('add_time', '>', "{$time}")->where('add_time', '<=', $times)->count();
        } else {
        //今日评价人数
            $nowcomments = model("comment")->where('business_id',
                $login['business_id'])->where('add_time', '>', "{$time}")->where('add_time', '<=', $times)->count();
        }

        if ($login['level'] != 'super_manager') {
            //评价总数
            $allcomments = model("comment")->where('business_id', $login['business_id'])->where('service_id', $login['service_id'])->count();
        } else {
        //评价总数
        $allcomments = model("comment")->where('business_id', $login['business_id'])->count();
        }

        // 今日留言量
        $message = model('message')->where('business_id', $login['business_id'])->where('timestamp', '>', $time)->where('timestamp', '<=', $times)->count();
        // 留言总量
        $messageall = model('message')->where('business_id', $login['business_id'])->count();


        if ($times > $cutime = $time . " 08:00") {
            $time8 = strtotime($cutime);
            $chats8 = model('chats')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $chats8 = $chats8->where('service_id', $login['service_id']);
            }
            $chats8 = $chats8->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time8}%")->count();
            $chatsdata[] = $chats8;

            $message8 = model("message")->where('business_id', $login['business_id'])->where('timestamp', '>', "{$ftime}")->where('timestamp', '<', "{$cutime}")->count();
            $messagedata[] = $message8;

            $getin8 = model("chats")->distinct(true)->field('visiter_id')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $getin8 = $getin8->where('service_id', $login['service_id']);
            }
            $getin8 = $getin8->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time8}%")->count();

            $getindata[] = $getin8;

        } else {
            $chatsdata[] = "";
            $messagedata[] = "";
            $getindata[] = "";

        }

        if ($times > $cutime = $time . " 10:00") {

            $time10 = strtotime($cutime);

            $chats10 = model('chats')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $chats10 = $chats10->where('service_id', $login['service_id']);
            }
            $chats10 = $chats10->where('timestamp', '>', "{$frtime}")->where('timestamp', '<', "{$time10}")->count();
            $chatsdata[] = $chats10;

            $message10 = model("message")->where('business_id', $login['business_id'])->where('timestamp', '>', "{$ftime}")->where('timestamp', '<', "{$cutime}")->count();

            $messagedata[] = $message10;

            $getin10 = model("chats")->distinct(true)->field('visiter_id')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $getin10 = $getin10->where('service_id', $login['service_id']);
            }
            $getin10 = $getin10->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time10}%")->count();


            $getindata[] = $getin10;

        } else {
            $chatsdata[] = "";
            $messagedata[] = "";
            $getindata[] = "";

        }

        if ($times > $cutime = $time . " 12:00") {
            $time12 = strtotime($cutime);
            $chats12 = model('chats')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $chats12 = $chats12->where('service_id', $login['service_id']);
            }
            $chats12 = $chats12->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time12}%")->count();
            $chatsdata[] = $chats12;

            $message12 = model("message")->where('business_id', $login['business_id'])->where('timestamp', '>', "{$ftime}")->where('timestamp', '<', "{$cutime}")->count();

            $messagedata[] = $message12;

            $getin12 = model("chats")->distinct(true)->field('visiter_id')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $getin12 = $getin12->where('service_id', $login['service_id']);
            }
            $getin12 = $getin12->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time12}%")->count();


            $getindata[] = $getin12;

        } else {
            $chatsdata[] = "";
            $messagedata[] = "";
            $getindata[] = "";

        }


        if ($times > $cutime = $time . " 14:00") {
            $time14 = strtotime($cutime);

            $chats14 = model('chats')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $chats14 = $chats14->where('service_id', $login['service_id']);
            }
            $chats14 = $chats14->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time14}%")->count();
            $chatsdata[] = $chats14;

            $message14 = model("message")->where('business_id', $login['business_id'])->where('timestamp', '>', "{$ftime}")->where('timestamp', '<', "{$cutime}")->count();

            $messagedata[] = $message14;

            $getin14 = model("chats")->distinct(true)->field('visiter_id')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $getin14 = $getin14->where('service_id', $login['service_id']);
            }
            $getin14 = $getin14->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time14}%")->count();

            $getindata[] = $getin14;

        } else {
            $chatsdata[] = "";
            $messagedata[] = "";
            $getindata[] = "";

        }

        if ($times > $cutime = $time . " 16:00") {
            $time16 = strtotime($cutime);

            $chats16 = model('chats')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $chats16 = $chats16->where('service_id', $login['service_id']);
            }
            $chats16 = $chats16->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time16}%")->count();
            $chatsdata[] = $chats16;

            $message16 = model("message")->where('business_id', $login['business_id'])->where('timestamp', '>', "{$ftime}")->where('timestamp', '<', "{$cutime}")->count();

            $messagedata[] = $message16;

            $getin16 = model("chats")->distinct(true)->field('visiter_id')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $getin16 = $getin16->where('service_id', $login['service_id']);
            }
            $getin16 = $getin16->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time16}%")->count();


            $getindata[] = $getin16;

        } else {
            $chatsdata[] = "";
            $messagedata[] = "";
            $getindata[] = "";

        }

        if ($times > $cutime = $time . " 18:00") {
            $time18 = strtotime($cutime);

            $chats18 = model('chats')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $chats18 = $chats18->where('service_id', $login['service_id']);
            }
            $chats18 = $chats18->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time18}%")->count();
            $chatsdata[] = $chats18;

            $message18 = model("message")->where('business_id', $login['business_id'])->where('timestamp', '>', "{$ftime}")->where('timestamp', '<', "{$cutime}")->count();

            $messagedata[] = $message18;

            $getin18 = model("chats")->distinct(true)->field('visiter_id')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $getin18 = $getin18->where('service_id', $login['service_id']);
            }
            $getin18 = $getin18->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time18}%")->count();


            $getindata[] = $getin18;

        } else {
            $chatsdata[] = "";
            $messagedata[] = "";
            $getindata[] = "";
        }

        if ($times > $cutime = $time . " 20:00") {
            $time20 = strtotime($cutime);
            $chats20 = model('chats')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $chats20 = $chats20->where('service_id', $login['service_id']);
            }
            $chats20 = $chats20->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time20}%")->count();
            $chatsdata[] = $chats20;

            $message20 = model("message")->where('business_id', $login['business_id'])->where('timestamp', '>', "{$ftime}")->where('timestamp', '<', "{$cutime}")->count();

            $messagedata[] = $message20;

            $getin20 = model("chats")->distinct(true)->field('visiter_id')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $getin20 = $getin20->where('service_id', $login['service_id']);
            }
            $getin20 = $getin20->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time20}%")->count();


            $getindata[] = $getin20;

        } else {
            $chatsdata[] = "";
            $messagedata[] = "";
            $getindata[] = "";

        }

        if ($times > $cutime = $time . " 22:00") {
            $time22 = strtotime($cutime);
            $chats22 = model('chats')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $chats22 = $chats22->where('service_id', $login['service_id']);
            }
            $chats22 = $chats22->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time22}%")->count();
            $chatsdata[] = $chats22;

            $message22 = model("message")->where('business_id', $login['business_id'])->where('timestamp', '>', "{$ftime}%")->where('timestamp', '<', "{$cutime}")->count();

            $messagedata[] = $message22;

            $getin22 = model("chats")->distinct(true)->field('visiter_id')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $getin22 = $getin22->where('service_id', $login['service_id']);
            }
            $getin22 = $getin22->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time22}%")->count();


            $getindata[] = $getin22;

        } else {
            $chatsdata[] = "";
            $messagedata[] = "";
            $getindata[] = "";

        }

        if ($times > $cutime = $time . " 00:00") {
            $time00 = strtotime($cutime);
            $chats00 = model('chats')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $chats00 = $chats00->where('service_id', $login['service_id']);
            }
            $chats00 = $chats00->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time00}%")->count();
            $chatsdata[] = $chats00;

            $message00 = model("message")->where('business_id', $login['business_id'])->where('timestamp', '>', "{$ftime}%")->where('timestamp', '<', "{$cutime}")->count();

            $messagedata[] = $message00;

            $getin00 = model("chats")->distinct(true)->field('visiter_id')->where('business_id', $login['business_id']);
            if ($login['level'] != 'super_manager') {
                $getin00 = $getin00->where('service_id', $login['service_id']);
            }
            $getin00 = $getin00->where('timestamp', '>', "{$frtime}%")->where('timestamp', '<', "{$time00}%")->count();


            $getindata[] = $getin00;

        } else {
            $chatsdata[] = "";
            $messagedata[] = "";
            $getindata[] = "";

        }

        $this->assign('nowcomments',$nowcomments);
        $this->assign('allcomments',$allcomments);

        $this->assign('chatsdata', $chatsdata);
        $this->assign('messagedata', $messagedata);
        $this->assign('getindata', $getindata);

        $this->assign('getinall', $getinall);
        $this->assign('waiter', $waiter);
        $this->assign('chatsall', $chatsall);
        $this->assign('talking', $talking);
        $this->assign('visiter_online', $visiter_online);
        $this->assign('services', $services);
        $this->assign('nowchats', $nowchats);
        $this->assign('message', $message);
        $this->assign('messageall', $messageall);
        $this->assign('admins', $login);
        $this->assign("part", "首页");
        $this->assign('title', '首页');

        return $this->fetch();
    }

    /**
     * 获取客服5分钟内回复率统计
     */
    public function responseRate()
    {
        $login = session('Msg');
        $todayStart = strtotime(date('Y-m-d'));
        $now = time();
        $prefix = config('database.prefix');

        // 获取今天每个访客的第一条消息
        $sql = "SELECT visiter_id, service_id, MIN(timestamp) as first_msg_time 
                FROM {$prefix}chats 
                WHERE business_id = :business_id 
                AND direction = 'to_service' 
                AND timestamp >= :today_start AND timestamp <= :now_time
                GROUP BY visiter_id, service_id";
        $customerMsgs = Db::query($sql, ['business_id' => $login['business_id'], 'today_start' => $todayStart, 'now_time' => $now]);

        $serviceStats = [];
        foreach ($customerMsgs as $msg) {
            $sid = $msg['service_id'];
            if (!isset($serviceStats[$sid])) {
                $serviceStats[$sid] = ['total' => 0, 'within5min' => 0];
            }
            $serviceStats[$sid]['total']++;

            $deadline = $msg['first_msg_time'] + 300;
            $replySql = "SELECT cid FROM {$prefix}chats 
                         WHERE business_id = :business_id 
                         AND direction = 'to_visiter' 
                         AND service_id = :service_id
                         AND visiter_id = :visiter_id
                         AND timestamp >= :first_msg_time AND timestamp <= :deadline
                         LIMIT 1";
            $reply = Db::query($replySql, [
                'business_id' => $login['business_id'],
                'service_id' => $sid,
                'visiter_id' => $msg['visiter_id'],
                'first_msg_time' => $msg['first_msg_time'],
                'deadline' => $deadline,
            ]);
            if (!empty($reply)) {
                $serviceStats[$sid]['within5min']++;
            }
        }

        $result = [];
        foreach ($serviceStats as $sid => $stats) {
            $rate = $stats['total'] > 0 ? round($stats['within5min'] / $stats['total'] * 100, 2) : 0;
            $service = model('service')->where('service_id', $sid)->find();
            $result[] = [
                'service_name' => $service ? $service['nick_name'] : '未知',
                'service_id' => $sid,
                'total_customers' => $stats['total'],
                'within_5min' => $stats['within5min'],
                'rate' => $rate
            ];
        }

        return json(['code' => 0, 'data' => $result]);
    }

    /**
     * 获取客服接待客户量统计（日/周/月）
     */
    public function customerStats()
    {
        $login = session('Msg');
        $prefix = config('database.prefix');

        $todayStart = strtotime(date('Y-m-d'));
        $now = time();

        // 今日：每个客服接待的客户数（客服回复了才算）
        $sqlToday = "SELECT c.service_id, COUNT(DISTINCT c.visiter_id) as customer_count 
                     FROM {$prefix}chats c 
                     WHERE c.business_id = :business_id 
                     AND c.direction = 'to_visiter'
                     AND c.timestamp >= :today_start AND c.timestamp <= :now_time
                     GROUP BY c.service_id";
        $todayData = Db::query($sqlToday, ['business_id' => $login['business_id'], 'today_start' => $todayStart, 'now_time' => $now]);

        // 本周（本周一开始）
        $weekStart = strtotime('monday this week');
        $sqlWeek = "SELECT c.service_id, COUNT(DISTINCT c.visiter_id) as customer_count 
                    FROM {$prefix}chats c 
                    WHERE c.business_id = :business_id 
                    AND c.direction = 'to_visiter'
                    AND c.timestamp >= :week_start AND c.timestamp <= :now_time
                    GROUP BY c.service_id";
        $weekData = Db::query($sqlWeek, ['business_id' => $login['business_id'], 'week_start' => $weekStart, 'now_time' => $now]);

        // 本月（本月1号开始）
        $monthStart = strtotime(date('Y-m-01'));
        $sqlMonth = "SELECT c.service_id, COUNT(DISTINCT c.visiter_id) as customer_count 
                     FROM {$prefix}chats c 
                     WHERE c.business_id = :business_id 
                     AND c.direction = 'to_visiter'
                     AND c.timestamp >= :month_start AND c.timestamp <= :now_time
                     GROUP BY c.service_id";
        $monthData = Db::query($sqlMonth, ['business_id' => $login['business_id'], 'month_start' => $monthStart, 'now_time' => $now]);

        $formatData = function($data) {
            $result = [];
            foreach ($data as $item) {
                $service = model('service')->where('service_id', $item['service_id'])->find();
                $result[] = [
                    'service_name' => $service ? $service['nick_name'] : '未知',
                    'customer_count' => $item['customer_count']
                ];
            }
            return $result;
        };

        return json([
            'code' => 0,
            'data' => [
                'today' => $formatData($todayData),
                'week' => $formatData($weekData),
                'month' => $formatData($monthData)
            ]
        ]);
    }

    /**
     * 后台对话页面.
     *
     * @return mixed
     */
    public function chats()
    {
        $login = session('Msg');
        $res = model('business')->where('id', $login['business_id'])->find();
        $this->assign("type", $res['video_state']);
        $this->assign('atype', $res['audio_state']);
        $this->assign('ftype', $res['file_state']);
        $this->assign("title", "客户咨询");
        $this->assign('part', '客户咨询');
        return $this->fetch();
    }


    /**
     * 常用语页面.
     *
     * @return mixed
     */
    public function custom()
    {
        $login = session('Msg');
        $data = model("sentence")->where('service_id', $login['service_id'])->paginate(9);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('lister', $data);
        $this->assign('title', "问候语设置");
        $this->assign('part', "设置");
        return $this->fetch();
    }

    /**
     * 常见问题设置.
     *
     * @return mixed
     */
    public function question()
    {
        $login = session('Msg');
        if ($login['level'] == 'service') {
            $this->redirect('admin/index/index');
        }
        $data = model("question")
            ->where('business_id', $login['business_id'])
            ->order('sort desc')
            ->paginate();
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('lister', $data);
        $this->assign('title', "常见问题设置");
        $this->assign('part', "设置");
        return $this->fetch();
    }


    /**
     * 生成前台文件页面.
     *
     * @return mixed
     */
    public function front()
    {
        $action = BASEROOT;
        $login = session('Msg');
        if(isset($login['business']['domain_entry']) && !empty($login['business']['domain_entry'])){
            $action=$login['business']['domain_entry'];
        }
        $class = model('group')->where('business_id', $login['business_id'])->select();

        $this->assign('class', $class);
        $this->assign('business', $login['business_id']);
        $this->assign('login', $login);
        $this->assign('action', $action);
        $this->assign("title", "接入方法");
        $this->assign("part", "接入方法");

        return $this->fetch();
    }


    /**
     * 所有聊天记录页面。
     * [history description]
     * @return [type] [description]
     */
    public function history()
    {
        $visiter_id = $this->request->param('visiter_id');
        $this->assign('visiter_id',$visiter_id);
        return $this->fetch();
    }

    /**
     * 留言页面.
     *
     * @return mixed
     */
    public function message()
    {
        $login = session('Msg');
        $post = $this->request->get();
        $userAdmin = model('message');
        $pageParam = ['query' => []];
        unset($post['page']);
        if ($post) {
            $pushtime = $post['pushtime'];

            if ($pushtime) {
                if ($pushtime == 1) {
                    $timetoday = date("Y-m-d", time());
                    $userAdmin->where('timestamp', 'like', $timetoday . "%");
                    $this->assign('pushtime', $pushtime);
                    $pageParam['query']['timestamp'] = $pushtime;
                } elseif ($pushtime == 7) {
                    $timechou = strtotime("-1 week");
                    $times = date("Y-m-d", $timechou);
                    $userAdmin->where('timestamp', ">", $times);
                    $this->assign('pushtime', $pushtime);
                    $pageParam['query']['timestamp'] = $pushtime;
                }
            }
        }

        $data = $userAdmin->where('business_id', $login['business_id'])->paginate(8, false, $pageParam);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('msgdata', $data);
        $this->assign('title', "留言查看");
        $this->assign('part', "留言查看");

        return $this->fetch();
    }

    /**
     * 转接客服页面
     * @return [type] [description]
     */
    public function service()
    {
        $visiter_id = $this->request->get('visiter_id', '', 'htmlspecialchars');
        $name = $this->request->get('name', '', 'htmlspecialchars');

        $login = session('Msg');

        $business_id = $login['business_id'];

        $res = model('service')->where('business_id', "{$business_id}")->where('service_id', '<>', $login['service_id'])->select();

        $this->assign('service', $res);
        $this->assign('visiter_id', $visiter_id);
        $this->assign('name', $name);

        return $this->fetch();
    }

    public function servicejson()
    {
        $visiter_id = $this->request->get('visiter_id', '', 'htmlspecialchars');
        $name = $this->request->get('name', '', 'htmlspecialchars');

        $login = session('Msg');

        $business_id = $login['business_id'];

        $res = model('service')->where('business_id', "{$business_id}")->where('service_id', '<>', $login['service_id'])->select();
        unset($res['password']);
        return json(['code'=>0,'data'=>['visiter_id'=>$visiter_id,'name'=>$name,'service'=>$res]]);
    }

    /**
     * 常见问题编辑页面
     * [editer description]
     * @return [type] [description]
     */
    public function editer()
    {
        $login = session('Msg');
        if ($login['level'] == 'service') {
            $this->redirect('admin/index/index');
        }

        $get = $this->request->get();

        $res = model('question')
            ->where('qid', $get['qid'])
            ->order('sort desc')
            ->find();

        $this->assign('question', $res['question']);
        $this->assign('keyword',$res['keyword']);
        $this->assign('answer', $res['answer']);
        $this->assign('qid', $get['qid']);
        $this->assign('sort', $res['sort']);
        $this->assign('status', $res['status']);

        return $this->fetch();
    }
    /**
     * 常见问题编辑页面
     * [editer description]
     * @return [type] [description]
     */
    public function custom_editer()
    {
        $login = session('Msg');
        if ($login['level'] == 'service') {
            $this->redirect('admin/index/index');
        }

        $get = $this->request->get();
        $id=isset($get['id'])?$get['id']:0;

        $res = model('question')
            ->where('id', $get['id'])
            ->order('sort desc')
            ->find();

        $this->assign('question', $res['question']);
        $this->assign('keyword',$res['keyword']);
        $this->assign('answer', $res['answer_read']);
        $this->assign('qid', $get['qid']);
        $this->assign('sort', $res['sort']);
        $this->assign('status', $res['status']);

        return $this->fetch();
    }


    /**
     * 编辑tab页面
     * [editertab description]
     * @return [type] [description]
     */
    public function editertab()
    {

        $login = session('Msg');
        if ($login['level'] == 'service') {
            $this->redirect('admin/index/index');
        }

        $get = $this->request->get();

        $res = model('tablist')->where('tid', $get['tid'])->find();

        $this->assign('title', $res['title']);
        $this->assign('content', $res['content_read']);
        $this->assign('tid', $get['tid']);

        return $this->fetch();
    }

    public function editercustom()
    {
        $login = session('Msg');
        $get = $this->request->get();
$content='';
        $sid=0;
        if($get['sid']>0){
            $res = model('sentence')
                ->where('sid', $get['sid'])
                ->where('service_id',$login['service_id'])
                ->find();
            $content=$res['content'];
            $sid=$res['sid'];
        }
        $this->assign('content', $content);
        $this->assign('sid', $sid);

        return $this->fetch();
    }

    /**
     * 设置页面
     * [set description]
     */
    public function set()
    {

        $this->assign('user',session('Msg'));
        $this->assign('title', '系统设置');
        $this->assign('part', '系统设置');
        return $this->fetch();
    }

    /**
     * 获取当前客服的 WxPusher 绑定二维码（extra=service_id，扫码回调写入 push_uid）
     */
    public function pushBindQrcode()
    {
        $login = session('Msg');
        $serviceId = (int)$login['service_id'];
        
        Db::name('service')->where('service_id', $serviceId)->update(['push_uid' => '']);
        
        $result = WxPusher::createQrcode((string)$serviceId);

        $qrcodeUrl = '';
        if (is_array($result)) {
            if (!empty($result['url'])) {
                $qrcodeUrl = $result['url'];
            } elseif (!empty($result['data']['url'])) {
                $qrcodeUrl = $result['data']['url'];
            }
        }

        $pushUid = '';

        $code = 0;
        $msg = 'ok';
        if ($qrcodeUrl === '') {
            $code = 1;
            $msg = is_array($result) && isset($result['msg']) ? (string)$result['msg'] : '获取二维码失败，请检查平台端推送配置';
            if (is_array($result) && isset($result['code']) && $result['code'] == -1 && isset($result['msg'])) {
                $msg = (string)$result['msg'];
            }
        }

        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => [
                'qrcode_url' => $qrcodeUrl,
                'push_uid' => $pushUid,
            ],
        ]);
    }

    /**
     * 检查当前客服的 WxPusher 绑定状态
     */
    public function checkPushBind()
    {
        $login = session('Msg');
        $serviceId = (int)$login['service_id'];
        $pushUid = Db::name('service')->where('service_id', $serviceId)->value('push_uid');
        if ($pushUid === null) {
            $pushUid = '';
        }
        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'push_uid' => $pushUid,
            ],
        ]);
    }


    public function setup()
    {

        $login = session('Msg');
        if ($login['level'] == 'service') {
            $this->redirect('admin/index/index');
        }
        $res = model("business")->where('id', $login['business_id'])->find();

        $this->assign('video', $res['video_state']);
        $this->assign('info', $res);
        $this->assign('audio', $res['audio_state']);
        $this->assign('file', $res['file_state']);
        $this->assign('voice', $res['voice_state']);
        $this->assign('voice_addr', $res['voice_address']);
        $this->assign('template', $res['template_state']);
        $this->assign('method', $res['distribution_rule']);
        $this->assign('transfer_human', $res['transfer_human']);
        $this->assign('push_url',$res['push_url']);
        $this->assign('title', '通用设置');
        $this->assign('part', '设置');

        return $this->fetch();
    }

    /**
     * tab面版页面。
     * [tablist description]
     * @return [type] [description]
     */
    public function tablist()
    {


        if (session('Msg.level') == 'service') {
            $this->redirect('admin/index/index');
        }

        $business_id = session('Msg.business_id');

        $res = model('tablist')->where('business_id', $business_id)->select();

        $this->assign('tablist', $res);

        $this->assign('title', '编辑前端tab面版');
        $this->assign('part', '设置');

        return $this->fetch();
    }


    /**
     *
     * [replylist description]
     * @return [type] [description]
     */
    public function replylist()
    {

        $id = session('Msg.service_id');
        $res = model('reply')->where('service_id', $id)->paginate(8);
        $page = $res->render();
        $this->assign('page', $page);
        $this->assign('replyword', $res);

        return $this->fetch();
    }

    public function template()
    {
        $common = new Common();
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['business_id'] = session('Msg.business_id');
            $post=$common->deep_array_map_trim($post);
            $res = WechatPlatform::edit($post);

            $arr = $res!== false ? ['code' => 0, 'msg' => '成功']: ['code' => 1, 'msg' => '失败'];
            return $arr;
        } else {
            $template = WechatPlatform::get(['business_id'=>session('Msg.business_id')]);

            $protocol=isHTTPS()?'https://':'http://';
            $this->assign('template',$template);
            $this->assign('business_id',session('Msg.business_id'));
            $this->assign('protocol',$protocol);
            $this->assign('title', '公众号与模板消息设置');
            $this->assign('part', "设置");
            $menuConfig = !empty($template['menu_config']) ? json_decode($template['menu_config'], true) : [];
            $this->assign('menu_json', json_encode($menuConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
            return $this->fetch();
        }
    }

    /**
     * 保存菜单配置
     */
    public function savemenu()
    {
        $login = session('Msg');
        if($login['level'] == 'service'){
            return ['code' => 1, 'msg' => '没有权限'];
        }
        $menuConfig = $this->request->post('menu_config', '');
        $res = Db::name('wechat_platform')->where('business_id', $login['business_id'])->update(['menu_config' => $menuConfig]);
        return $res !== false ? ['code' => 0, 'msg' => '保存成功'] : ['code' => 1, 'msg' => '保存失败'];
    }

    /**
     * 发布菜单到微信
     */
    public function publishmenu()
    {
        $login = session('Msg');
        if($login['level'] == 'service'){
            return ['code' => 1, 'msg' => '没有权限'];
        }
        $businessId = $login['business_id'];
        $wechat = WechatPlatform::get(['business_id' => $businessId]);
        if (empty($wechat) || empty($wechat['app_id']) || empty($wechat['app_secret'])) {
            return ['code' => 1, 'msg' => '请先配置公众号AppID和AppSecret'];
        }
        $menuConfig = !empty($wechat['menu_config']) ? json_decode($wechat['menu_config'], true) : [];
        if (empty($menuConfig)) {
            return ['code' => 1, 'msg' => '请先保存菜单配置'];
        }
        try {
            $options = [
                'app_id' => $wechat['app_id'],
                'secret' => $wechat['app_secret'],
                'token'  => $wechat['wx_token'],
                'aes_key' => $wechat['wx_aeskey'],
            ];
            $app = \EasyWeChat\Factory::officialAccount($options);
            $app->menu->create($menuConfig);
            return ['code' => 0, 'msg' => '发布成功'];
        } catch (\Exception $e) {
            return ['code' => 1, 'msg' => '发布失败：' . $e->getMessage()];
        }
    }

    public function qrcode()
    {
        $qrcode = WechatService::get()->qrcode;
//        fangke
        $result = $qrcode->temporary('kefu_'.session('Msg.service_id'), 6 * 24 * 3600);
        $ticket = $result['ticket'];// 或者 $result['ticket']
        $url = $qrcode->url($ticket);
        return json(['code'=>0,'data'=>$url]);
    }

    public function test(){
        var_dump(\app\Common::clearXSS('121313&&&&156479'));
    }
}