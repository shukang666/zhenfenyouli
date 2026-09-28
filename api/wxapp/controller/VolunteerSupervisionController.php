<?php

namespace api\wxapp\controller;

use api\wxapp\model\CommonModel;
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

        $config = cmf_get_option('set_config');
        $rewardPoint = max(0, intval($config['supervision_point'] ?? 100));

        if (!empty($record) && !empty($record['image'])) {
            $record['image_urls'] = CommonModel::getArrImageFullUrl($record['image']);
            $record['image']      = implode(',', $record['image_urls']);
        }

        $this->success('获取成功', [
            'today_signed' => !empty($record),
            'record'       => $record ?: null,
            'reward_point' => $rewardPoint,
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
                    $item['image_urls'] = CommonModel::getArrImageFullUrl($item['image']);
                    $item['image']      = implode(',', $item['image_urls']);
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
        $imageParam    = $this->request->param('image', '');
        $image         = CommonModel::getStringImageUrl($imageParam);
        $content       = trim($this->request->param('content', ''));

        if (empty($communityId)) {
            $this->error('请选择督导小区');
        }
        if ($image === '') {
            $this->error('请上传督导照片');
        }
        $imageCount = count(array_filter(explode(',', $image)));
        if ($imageCount > 9) {
            $this->error('现场照片最多 9 张');
        }
        if (empty($content)) {
            $this->error('请填写督导内容');
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
                'status'           => 0,    // 待审核，审核通过后由后台发放积分
                'create_time'      => time(),
            ]);
        } catch (\Exception $e) {
            $this->error('提交失败：' . $e->getMessage());
        }

        $this->success('督导签到已提交，请等待审核', ['point' => $point, 'id' => intval($id)]);
    }

}
