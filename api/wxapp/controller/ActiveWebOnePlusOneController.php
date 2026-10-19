<?php

namespace api\wxapp\controller;

use api\wxapp\service\ActiveWebOnePlusOneService;

/**
 * Web 式活动「1+1」七日挑战
 */
class ActiveWebOnePlusOneController extends AuthController
{
    public function status()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            ActiveWebOnePlusOneService::loadWebActive($activeId);
            $data = ActiveWebOnePlusOneService::syncAndBuildStatus((int)$this->user_id, $activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('请求成功', $data);
    }

    public function day()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $day = (int)$this->request->param('day');
        try {
            ActiveWebOnePlusOneService::loadWebActive($activeId);
            $data = ActiveWebOnePlusOneService::dayDetail((int)$this->user_id, $activeId, $day);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('请求成功', $data);
    }

    /** 第 2 天：用户在 H5 点击「去分享」后标记（可与积分分享记录二选一满足） */
    public function share_ack()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            ActiveWebOnePlusOneService::loadWebActive($activeId);
            ActiveWebOnePlusOneService::markShareAck((int)$this->user_id, $activeId);
            $data = ActiveWebOnePlusOneService::syncAndBuildStatus((int)$this->user_id, $activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('已记录分享', $data);
    }
}
