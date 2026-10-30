<?php

namespace api\wxapp\controller;

use api\wxapp\service\MemberActiveService;
use api\wxapp\service\TabHomeService;
use app\admin\model\active\ActiveModel;

class MemberActiveController extends AuthController
{
    /** 发布页元数据 */
    public function meta()
    {
        $this->checkUser();
        $this->success('获取成功', [
            'themes' => MemberActiveService::themeOptions(),
            'block_types' => MemberActiveService::blockTypeMeta(),
            'tips' => [
                'headline' => '发一场小而美的邻里活动',
                'sub' => '谁参加、做什么，都由你来定！',
            ],
        ]);
    }

    /** 活动 Tab 瀑布流（「附近」chip：按七贤岭基准点距离排序） */
    public function list()
    {
        $page = max(1, (int)$this->request->param('page', 1));
        $pageSize = min(30, max(1, (int)$this->request->param('page_size', 10)));
        $service = new TabHomeService($this);
        $data = MemberActiveService::paginateForFeed((int)$this->user_id, $page, $pageSize, $service);
        $this->success('获取成功', $data);
    }

    /** 居民发布活动 */
    public function publish()
    {
        $this->checkUser();
        try {
            $activeId = MemberActiveService::publish((int)$this->user_id, [
                'name' => $this->request->param('name', ''),
                'summary' => $this->request->param('summary', ''),
                'thumb' => $this->request->param('thumb', ''),
                'activity_date' => $this->request->param('activity_date', ''),
                'start_time' => $this->request->param('start_time', ''),
                'end_time' => $this->request->param('end_time', ''),
                'address' => $this->request->param('address', ''),
                'place_name' => $this->request->param('place_name', ''),
                'lat' => $this->request->param('lat', ''),
                'lng' => $this->request->param('lng', ''),
                'contact' => $this->request->param('contact', ''),
                'theme_id' => $this->request->param('theme_id', 0),
                'custom_blocks' => $this->request->param('custom_blocks', ''),
                'allow_signup' => $this->request->param('allow_signup', 1),
                'require_checkin' => $this->request->param('require_checkin', 1),
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        } catch (\Throwable $e) {
            \think\facade\Log::error('[member_active publish] ' . $e->getMessage());
            $msg = '发布失败，请稍后再试';
            $raw = $e->getMessage();
            if (stripos($raw, 'publish_user_id') !== false || stripos($raw, 'publish_audit') !== false) {
                $msg = '服务器尚未升级居民活动字段，请执行 update.sql 后重试';
            } elseif (stripos($raw, 'SQLSTATE') !== false && (bool) config('app.app_debug')) {
                $msg = $raw;
            }
            $this->error($msg);
        }
        $this->success('发布成功', ['active_id' => $activeId]);
    }

    /** 我发布的活动（简版，兼容旧客户端） */
    public function my_list()
    {
        $this->checkUser();
        try {
            $data = MemberActiveService::myPublishDashboard((int)$this->user_id, 'all');
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('获取成功', ['list' => $data['list'] ?? []]);
    }

    /** 发布者维护页：统计 + 分 tab 列表 */
    public function my_publish()
    {
        $this->checkUser();
        $filter = (string)$this->request->param('filter', 'all');
        try {
            $data = MemberActiveService::myPublishDashboard((int)$this->user_id, $filter);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('获取成功', $data);
    }

    /** 撤回审核中的居民活动 */
    public function withdraw_publish()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            MemberActiveService::withdrawPublish((int)$this->user_id, $activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('已撤回');
    }

    /** 发布者：报名名单 */
    public function publish_sign_list()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $page = max(1, (int)$this->request->param('page', 1));
        $pageSize = min(50, max(1, (int)$this->request->param('page_size', 20)));
        try {
            $data = MemberActiveService::publishSignList((int)$this->user_id, $activeId, $page, $pageSize);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('获取成功', $data);
    }

    /** 发布者：签到名单 */
    public function publish_checkin_list()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $page = max(1, (int)$this->request->param('page', 1));
        $pageSize = min(50, max(1, (int)$this->request->param('page_size', 20)));
        try {
            $data = MemberActiveService::publishCheckinList((int)$this->user_id, $activeId, $page, $pageSize);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('获取成功', $data);
    }
}
