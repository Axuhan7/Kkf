<?php
/**
 * 管理员IP白名单管理控制器
 * 用于添加、修改、删除管理员IP
 */
namespace app\platform\controller;

use think\Controller;
use think\Db;

class AdminIp extends Base
{
    protected $noNeedLogin = [];

    /**
     * IP白名单管理首页
     * @return mixed
     */
    public function index()
    {
        $list = Db::name('admin_ip')->order('id', 'desc')->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 添加IP
     */
    public function add()
    {
        if ($this->request->isPost()) {
            header('Content-Type: application/json; charset=utf-8');
            $ipAddress = $this->request->post('ip_address', '');
            $remark = $this->request->post('remark', '');

            if (empty($ipAddress)) {
                exit(json_encode(['code' => 1, 'msg' => 'IP地址不能为空']));
            }

            if (!$this->validateIp($ipAddress)) {
                exit(json_encode(['code' => 1, 'msg' => 'IP地址格式不正确']));
            }

            $exists = Db::name('admin_ip')->where('ip_address', $ipAddress)->find();
            if ($exists) {
                exit(json_encode(['code' => 1, 'msg' => '该IP已存在']));
            }

            $result = Db::name('admin_ip')->insert([
                'ip_address' => $ipAddress,
                'remark' => $remark,
                'status' => 1,
                'createtime' => time()
            ]);

            if ($result) {
                exit(json_encode(['code' => 0, 'msg' => '添加成功']));
            } else {
                exit(json_encode(['code' => 1, 'msg' => '添加失败']));
            }
        }

        return $this->fetch();
    }

    /**
     * 编辑IP
     */
    public function edit()
    {
        $id = $this->request->param('id', 0);

        if ($this->request->isPost()) {
            header('Content-Type: application/json; charset=utf-8');
            $id = intval($this->request->post('id', 0));
            $ipAddress = $this->request->post('ip_address', '');
            $remark = $this->request->post('remark', '');

            if (empty($ipAddress)) {
                exit(json_encode(['code' => 1, 'msg' => 'IP地址不能为空']));
            }

            if (!$this->validateIp($ipAddress)) {
                exit(json_encode(['code' => 1, 'msg' => 'IP地址格式不正确']));
            }

            $exists = Db::name('admin_ip')
                ->where('ip_address', $ipAddress)
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                exit(json_encode(['code' => 1, 'msg' => '该IP已被其他记录使用']));
            }

            $result = Db::name('admin_ip')
                ->where('id', $id)
                ->update([
                    'ip_address' => $ipAddress,
                    'remark' => $remark
                ]);

            if ($result !== false) {
                exit(json_encode(['code' => 0, 'msg' => '修改成功']));
            } else {
                exit(json_encode(['code' => 1, 'msg' => '修改失败']));
            }
        }

        $data = Db::name('admin_ip')->where('id', $id)->find();
        if (!$data) {
            $this->error('记录不存在');
        }

        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 删除IP
     */
    public function delete()
    {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($this->request->post('id', 0));

        if (empty($id)) {
            exit(json_encode(['code' => 1, 'msg' => '参数错误']));
        }

        $result = Db::name('admin_ip')->where('id', $id)->delete();

        if ($result) {
            exit(json_encode(['code' => 0, 'msg' => '删除成功']));
        } else {
            exit(json_encode(['code' => 1, 'msg' => '删除失败']));
        }
    }

    /**
     * 切换状态（启用/禁用）
     */
    public function toggleStatus()
    {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($this->request->post('id', 0));

        if (empty($id)) {
            exit(json_encode(['code' => 1, 'msg' => '参数错误']));
        }

        $data = Db::name('admin_ip')->where('id', $id)->find();
        if (!$data) {
            exit(json_encode(['code' => 1, 'msg' => '记录不存在']));
        }

        $newStatus = $data['status'] == 1 ? 0 : 1;
        $result = Db::name('admin_ip')
            ->where('id', $id)
            ->update(['status' => $newStatus]);

        if ($result !== false) {
            exit(json_encode([
                'code' => 0,
                'msg' => $newStatus == 1 ? '已启用' : '已禁用',
                'status' => $newStatus
            ]));
        } else {
            exit(json_encode(['code' => 1, 'msg' => '操作失败']));
        }
    }

    /**
     * IP访问日志
     */
    public function log()
    {
        $list = Db::name('ip_access_log')
            ->order('id', 'desc')
            ->limit(100)
            ->select();

        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 清空访问日志
     */
    public function clearLog()
    {
        header('Content-Type: application/json; charset=utf-8');
        $result = Db::name('ip_access_log')->delete(true);

        if ($result !== false) {
            exit(json_encode(['code' => 0, 'msg' => '清空成功']));
        } else {
            exit(json_encode(['code' => 1, 'msg' => '清空失败']));
        }
    }

    /**
     * 验证IP地址格式
     */
    private function validateIp($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }
        if (preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $ip)) {
            list($subnet, $mask) = explode('/', $ip);
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $mask >= 0 && $mask <= 32) {
                return true;
            }
        }
        if (preg_match('/^(\d{1,3}\.|\*){3}(\d{1,3}|\*)$/', $ip)) {
            return true;
        }
        return false;
    }
}
