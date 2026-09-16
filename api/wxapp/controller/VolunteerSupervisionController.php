<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use think\facade\Db;

/**
 * 志愿督导（小程序端）
 * 路径：/wxapp/volunteer_supervision/*
 */
class VolunteerSupervisionController extends AuthController
{
    /**
     * 当天签到状态
     * GET /wxapp/volunteer_supervision/today
     */
    public function today()
    {
        $this->checkUser();
        $today = date('Y-m-d');
        $record = Db::name('volunteer_supervision')
            ->where('user_id', $this->user_id)
            ->where('supervision_date', $today)
            ->find();

        // 当前是否处于高峰时段
        $inPeak = $this->isInPeakHour();

        $this->success('获取成功', [
            'today_signed' => !empty($record),
            'record'       => $record ?: null,
            'in_peak_hour' => $inPeak,
            'peak_periods' => $this->getPeakPeriods(),
        ]);
    }

    /**
     * 我的志愿督导历史记录
     * GET /wxapp/volunteer_supervision/my_list
     */
    public function my_list()
    {
        $this->checkUser();
        $list = Db::name('volunteer_supervision')
            ->where('user_id', $this->user_id)
            ->order('id desc')
            ->paginate(15)
            ->each(function ($item) {
                if (!empty($item['image'])) {
                    $item['image'] = cmf_get_asset_url($item['image']);
                }
                return $item;
            });
        $this->success('获取成功', $list);
    }

    /**
     * 提交志愿督导签到
     * POST /wxapp/volunteer_supervision/sign_in
     * 参数：community_id, community_name, point_id, point_name, image(字符串), content
     */
    public function sign_in()
    {
        $this->checkUser();

        $communityId   = intval($this->request->param('community_id', 0));
        $communityName = trim($this->request->param('community_name', ''));
        $pointId       = intval($this->request->param('point_id', 0));
        $pointName     = trim($this->request->param('point_name', ''));
        $image         = trim($this->request->param('image', ''));
        $content       = trim($this->request->param('content', ''));

        if (empty($communityId)) {
            $this->error('请选择督导小区');
        }
        if (empty($image)) {
            $this->error('请上传督导照片');
        }
        if (empty($content)) {
            $this->error('请填写督导内容');
        }

        // 高峰时段校验
        if (!$this->isInPeakHour()) {
            $this->error('当前不是督导高峰时间段，请在配置的时段内签到');
        }

        // 当天是否已签到
        $today = date('Y-m-d');
        $exist = Db::name('volunteer_supervision')
            ->where('user_id', $this->user_id)
            ->where('supervision_date', $today)
            ->find();
        if ($exist) {
            $this->error('今日已签到，请勿重复提交');
        }

        $config = cmf_get_option('set_config');
        $point  = max(0, intval($config['supervision_point'] ?? 100));

        Db::startTrans();
        try {
            $id = Db::name('volunteer_supervision')->insertGetId([
                'user_id'          => $this->user_id,
                'community_id'     => $communityId,
                'community_name'   => $communityName,
                'point_id'         => $pointId,
                'point_name'       => $pointName,
                'image'            => $image,
                'content'          => $content,
                'supervision_date' => $today,
                'point'            => $point,
                'status'           => 1,    // 志愿督导默认已通过
                'create_time'      => time(),
            ]);

            if ($point > 0) {
                AccountLogModel::add_account_log(
                    $this->user_id, 2, 226, $point,
                    ['supervision_id' => $id, 'remark' => '志愿督导签到']
                );
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('提交失败：' . $e->getMessage());
        }

        $this->success('督导签到成功', ['point' => $point, 'id' => intval($id)]);
    }

    /**
     * 获取高峰时段配置
     */
    private function getPeakPeriods()
    {
        $config = cmf_get_option('set_config');
        $periods = json_decode($config['supervision_peak_periods'] ?? '', true);
        if (empty($periods) || (!is_array($periods))) {
            $periods = ['am' => ['07:30', '09:30'], 'pm' => ['16:30', '18:30']];
        }
        return $periods;
    }

    /**
     * 判断当前是否在高峰时段
     */
    private function isInPeakHour()
    {
        $periods = $this->getPeakPeriods();
        $nowTime = date('H:i');

        if (!empty($periods['am']) && is_array($periods['am']) && count($periods['am']) >= 2) {
            if ($nowTime >= $periods['am'][0] && $nowTime <= $periods['am'][1]) {
                return true;
            }
        }
        if (!empty($periods['pm']) && is_array($periods['pm']) && count($periods['pm']) >= 2) {
            if ($nowTime >= $periods['pm'][0] && $nowTime <= $periods['pm'][1]) {
                return true;
            }
        }
        return false;
    }
}
