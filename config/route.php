<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/3/4
 * Time: 16:48
 */

use think\Route;

Route::get('api/group/:business_id', 'api/Group/getGroup');

// 管理员IP白名单管理路由（下划线格式，匹配AJAX硬编码URL）
Route::rule('platform/admin_ip/index', 'platform/AdminIp/index');
Route::rule('platform/admin_ip/add', 'platform/AdminIp/add');
Route::rule('platform/admin_ip/edit/id/:id', 'platform/AdminIp/edit');
Route::rule('platform/admin_ip/delete', 'platform/AdminIp/delete');
Route::rule('platform/admin_ip/toggleStatus', 'platform/AdminIp/toggleStatus');
Route::rule('platform/admin_ip/log', 'platform/AdminIp/log');
Route::rule('platform/admin_ip/clearLog', 'platform/AdminIp/clearLog');

// 驼峰格式（兼容页面链接）
Route::rule('platform/adminIp/index', 'platform/AdminIp/index');
Route::rule('platform/adminIp/add', 'platform/AdminIp/add');
Route::rule('platform/adminIp/edit/id/:id', 'platform/AdminIp/edit');
Route::rule('platform/adminIp/log', 'platform/AdminIp/log');

Route::rule('admin/ai/:action', 'admin/Ai/:action');
