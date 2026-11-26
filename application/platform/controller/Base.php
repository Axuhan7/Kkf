<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/1/28
 * Time: 16:34
 */
namespace app\platform\controller;

use app\platform\model\Option;
use app\platform\service\Auth;
use app\platform\service\Menu;
use app\platform\service\Permissions;
use think\Controller;
use think\Db;
use think\Hook;

/**
 * @property Auth $auth
 */
class Base extends Controller
{

    /**
     * 布局模板
     * @var string
     */
    protected $layout = 'default';

    protected $admin = null;

    protected $auth = null;

    protected $option = null;

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
                    try { Db::name('ip_access_log')->insert(['ip'=>$clientIp,'url'=>$this->request->url(),'module'=>'platform','createtime'=>time()]); } catch(\Exception $e){}
                    header('Content-Type: text/html; charset=utf-8');
                    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>访问被拒绝</title><style>body{font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f5f5f5}.box{background:#fff;padding:40px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);text-align:center}.box h2{color:#e74c3c;margin-bottom:15px}.box p{color:#666}</style></head><body><div class="box"><h2>
                   请停止非法入侵行为</h2><p>您的IP地址：<b>' . htmlspecialchars($clientIp) . '</b>已被记录</p><p><b>若正常访问，请联系管理员</p></div></body></html>';
                    die();
                }
            }
        } catch (\Exception $e) {}

        $this->auth = Auth::instance();
        $modulename = strtolower($this->request->module());
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());
        $route = $modulename.'/'.$controllername.'/'.$actionname;
        $token = $this->request->server('HTTP_TOKEN', $this->request->param('token', \think\Cookie::get('token')));
        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->auth->setRequestUri($path);

        Hook::add('action_begin', 'app\\platform\\behavior\\Permission');

        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin)) {
            //初始化
            $this->auth->init($token);
            //检测是否登录
            if (!$this->auth->isLogin()) {
                $this->redirect(url('passport/login'));
            }
            $this->admin = $this->auth->getUserinfo();
            $menu = Menu::getMenus();
            $title = array_column($menu,null,'route');
            $this->assign('title',isset($title[$route]['name']) ?$title[$route]['name']:null);
            $this->assign('admin',$this->admin);
            $this->assign('menu',$menu);
        } else {
            // 如果有传递token才验证是否登录状态
            if ($token) {
                $this->auth->init($token);
            }
        }
        $this->assign('route',$route);
        $this->view->engine->layout('layout/' . $this->layout);

        // 为所有平台页面准备 CSRF token（模板中通过 session('__token__') 读取）
        if (session('__token__')) {
            $this->assign('csrf_token', session('__token__'));
        }

        //设置seo
        $option = Option::getList('title,logo,copyright,max_login_error,passport_bg,open_register', 0, 'admin');
        $this->option = $option;
        $this->assign('option',$option);
        parent::_initialize();
    }

}
