<?php

namespace api\wxapp\controller;

use api\wxapp\service\ActiveWebLifeNavigatorService;

/**
 * Web 式活动「绿动生活领航员」
 */
class ActiveWebLifeNavigatorController extends AuthController
{
    public function intro()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            $data = ActiveWebLifeNavigatorService::introPayload(
                (int)$this->user_id,
                $activeId,
                (array)$this->user_info
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('请求成功', $data);
    }

    public function apply()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            $data = ActiveWebLifeNavigatorService::apply((int)$this->user_id, $activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('申请已提交', $data);
    }

    public function zone_status()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            $data = ActiveWebLifeNavigatorService::zoneStatus(
                (int)$this->user_id,
                $activeId,
                (array)$this->user_info
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        } catch (\Throwable $e) {
            $this->error('行动区加载失败，请稍后再试');
        }
        $this->success('请求成功', $data);
    }

    public function claim_base()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            $data = ActiveWebLifeNavigatorService::claimBaseReward((int)$this->user_id, $activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('领取成功', $data);
    }

    public function invite_ack()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            $data = ActiveWebLifeNavigatorService::ackInviteShare((int)$this->user_id, $activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('已记录', $data);
    }
}
