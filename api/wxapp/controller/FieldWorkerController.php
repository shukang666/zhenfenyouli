<?php

namespace api\wxapp\controller;

use think\facade\Db;

/**
 * 外勤工作台 - 简易网页版（无需登录，用户写死）
 * 供外勤人员临时通过网页提交点位巡查
 */
class FieldWorkerController extends AuthController
{
    /**
     * 写死的外勤用户ID（提交时关联到此用户）
     * 可在 cmf_user 表中找一个外勤人员的 id
     */
    protected $hardcodedUserId = 2;

    /**
     * 提交点位巡查（无需登录，用户写死）
     * POST /wxapp/field_worker/submit
     */
    public function submit()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }

        $communityId   = intval($this->request->param('community_id', 0));
        $communityName = trim($this->request->param('community_name', ''));
        $unitName      = trim($this->request->param('unit_name', ''));
        $doorPhoto     = trim($this->request->param('door_photo', ''));
        $innerPhoto    = trim($this->request->param('inner_photo', ''));
        $publicityPhoto= trim($this->request->param('publicity_photo', ''));

        if ($communityId <= 0) {
            $this->error('请选择所属社区');
        }
        if (empty($unitName)) {
            $this->error('请选择点位');
        }
        if (empty($doorPhoto) || empty($innerPhoto) || empty($publicityPhoto)) {
            $this->error('请上传全部三张照片');
        }

        $data = [
            'user_id'         => $this->hardcodedUserId,
            'community_id'    => $communityId,
            'community_name'  => $communityName,
            'unit_name'       => $unitName,
            'unit_type'       => 1,
            'check_date'      => date('Y-m-d'),
            'address'         => '',
            'contact'         => '',
            'contact_phone'   => '',
            'has_waste'       => 1,
            'waste_correct'   => 1,
            'waste_flow'      => '',
            'door_photo'      => $doorPhoto,
            'inner_photo'     => $innerPhoto,
            'publicity_photo' => $publicityPhoto,
            'inner_qualified' => 1,
            'need_rectify'    => 0,
            'inner_rectify_photo' => '',
            'point_photo'     => '',
            'point_qualified' => 1,
            'point_rectify_photo' => '',
            'manage_photo'    => '',
            'manage_qualified'=> 1,
            'manage_rectify_photo' => '',
            'transport_photo' => '',
            'catering_photo'  => '',
            'accuracy_photo'  => '',
            'overall_evaluation' => '',
            'create_time'     => time(),
        ];

        Db::name('supervision_monitor')->insert($data);

        $this->success('提交成功');
    }
}
