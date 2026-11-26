<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/3/20
 * Time: 16:49
 */

namespace app\api\controller;

use think\Log;

class Test extends Base
{
    public function index()
    {
        echo '测试更新';
        Log::info('测试更新123321');
    }

    public function notify()
    {
        $data = $this->request->post();
        Log::info($data);
    }
}