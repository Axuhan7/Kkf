<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
//include 'txprotect.php';
// 定义环境版本

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
define('VENDOR',__DIR__.'/../vendor/');

// 定义配置文件目录
define('CONF_PATH', __DIR__ . '/../config/'); 
$lkversion=include CONF_PATH.'version.php';

// 定义pusher密匙
define('app_key','aswhb5r3rbizutvw');
define('app_secret','7oi20q6shl3drdt6ye1uadcgd47t6pkl');
define('app_id',232);
define('whost','wss://kf3.yanshi.fun');
define('ahost','http://kf3.yanshi.fun');
define('wport',2081);
define('aport',2082);
define('registToken','1339983091');
define('YMWL_SALT','o6yk7llx2necxpe7t2');
define('LK_VERSION',$lkversion['LK_VERSION']);

// 自定义一个 入口 目录
define('PUBLIC_PATH',__DIR__);
// 定义 类的文件路径
define('EXTEND_PATH','../extend/');

// 定义微信配置
define('domain','https://kf3.yanshi.fun');

// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';