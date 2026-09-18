<?php

namespace api\wxapp\controller;

use think\facade\Db;

/**
 * 督导监测（指导员厨余垃圾检查表单）
 * 对应小程序 pages/dtsh/supervision-monitor
 * 后台：垃圾分类活动菜单下的"督导监测"页面
 */
class SupervisionMonitorController extends AuthController
{
    /**
     * 提交督导监测表单
     * POST /wxapp/supervision_monitor/post
     */
    public function post()
    {
        $this->checkUser();

        $communityId   = intval($this->request->param('community_id', 0));
        $communityName = trim($this->request->param('community_name', ''));
        $unitName      = trim($this->request->param('unit_name', ''));
        $unitType      = intval($this->request->param('unit_type', 0));
        $checkDate     = trim($this->request->param('check_date', ''));
        $address       = trim($this->request->param('address', ''));
        $contact       = trim($this->request->param('contact', ''));
        $contactPhone  = trim($this->request->param('contact_phone', '')); // 联系电话（选填）

        // 必填校验
        if (empty($communityName)) {
            $this->error('请选择所属社区');
        }
        if (empty($unitName)) {
            $this->error('请填写单位名称');
        }
        if (!in_array($unitType, [1, 2, 3, 4])) {
            $this->error('请选择单位类型');
        }
        if (empty($checkDate)) {
            $this->error('请选择日期');
        }
        if (empty($address)) {
            $this->error('请填写地址');
        }

        // 是否类字段（1=是 0=否）
        $hasWaste       = $this->request->param('has_waste', 0) == 1 ? 1 : 0;
        $wasteCorrect   = $this->request->param('waste_correct', 0) == 1 ? 1 : 0;
        $innerQualified = $this->request->param('inner_qualified', 0) == 1 ? 1 : 0;
        $needRectify    = $this->request->param('need_rectify', 0) == 1 ? 1 : 0;
        $pointQualified = $this->request->param('point_qualified', 0) == 1 ? 1 : 0;
        $manageQualified= $this->request->param('manage_qualified', 0) == 1 ? 1 : 0;

        $data = [
            'user_id'              => $this->user_id,
            'community_id'         => $communityId,
            'community_name'       => $communityName,
            'unit_name'            => $unitName,
            'unit_type'            => $unitType,
            'check_date'           => $checkDate,
            'address'              => $address,
            'contact'              => $contact,
            'contact_phone'        => $contactPhone,
            'has_waste'            => $hasWaste,
            'waste_correct'        => $wasteCorrect,
            'waste_flow'           => trim($this->request->param('waste_flow', '')),
            'door_photo'           => trim($this->request->param('door_photo', '')),
            'inner_photo'          => trim($this->request->param('inner_photo', '')),
            'publicity_photo'      => trim($this->request->param('publicity_photo', '')),
            'inner_qualified'      => $innerQualified,
            'need_rectify'         => $needRectify,
            'inner_rectify_photo'  => trim($this->request->param('inner_rectify_photo', '')),
            'point_photo'          => trim($this->request->param('point_photo', '')),
            'point_qualified'      => $pointQualified,
            'point_rectify_photo'  => trim($this->request->param('point_rectify_photo', '')),
            'manage_photo'         => trim($this->request->param('manage_photo', '')),
            'manage_qualified'     => $manageQualified,
            'manage_rectify_photo' => trim($this->request->param('manage_rectify_photo', '')),
            'transport_photo'      => trim($this->request->param('transport_photo', '')),
            'catering_photo'       => trim($this->request->param('catering_photo', '')),
            'accuracy_photo'       => trim($this->request->param('accuracy_photo', '')),
            'overall_evaluation'   => trim($this->request->param('overall_evaluation', '')),
            'create_time'          => time(),
        ];

        Db::name('supervision_monitor')->insert($data);

        $this->success('已提交');
    }
}