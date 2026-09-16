<?php

namespace api\wxapp\controller;

use api\wxapp\model\ShotReportModel;
use think\facade\Db;

class ShotReportController extends AuthController
{
    protected $model;

    public function initialize()
    {
        parent::initialize();
        $this->model = new ShotReportModel();
    }

    /**
     * 提交随手拍问题
     * POST /wxapp/shot_report/post
     * 参数：image, remark, location_type(1=小区内, 2=非小区, 默认1)
     *       小区内：community_id, community_name, point_id, point_name
     *       非小区：merchant_name, address
     */
    public function post()
    {
        $this->checkUser();

        $image        = $this->request->param('image', '');
        $remark       = $this->request->param('remark', '');
        $locationType = intval($this->request->param('location_type', 1));

        // 位置扩展字段
        $communityId   = intval($this->request->param('community_id', 0));
        $communityName = $this->request->param('community_name', '');
        $pointId       = intval($this->request->param('point_id', 0));
        $pointName     = $this->request->param('point_name', '');
        $merchantName  = $this->request->param('merchant_name', '');
        $address       = $this->request->param('address', '');

        if (empty($image)) {
            $this->error('请上传问题图片');
        }

        if (empty($remark)) {
            $this->error('请填写问题描述');
        }

        if (!in_array($locationType, [1, 2])) {
            $locationType = 1;
        }

        // 根据位置类型校验必填项
        if ($locationType == 1) {
            if (empty($communityName)) {
                $this->error('请选择小区');
            }
            if (empty($pointName)) {
                $this->error('请选择点位');
            }
        } else {
            if (empty($merchantName)) {
                $this->error('请填写商户名称');
            }
            if (empty($address)) {
                $this->error('请填写地址');
            }
        }

        $data = [
            'user_id'       => $this->user_id,
            'image'         => $image,
            'remark'        => $remark,
            'location_type' => $locationType,
            'community_id'  => $communityId,
            'community_name'=> $communityName,
            'point_id'      => $pointId,
            'point_name'    => $pointName,
            'merchant_name' => $merchantName,
            'address'       => $address,
            'status'        => 0,
            'create_time'   => time(),
        ];

        $this->model->save($data);

        $this->success('已提交，项目正在处理');
    }

    /**
     * 我的随手拍记录
     * GET /wxapp/shot_report/my_list
     */
    public function my_list()
    {
        $this->checkUser();
        $list = $this->model
            ->where('user_id', $this->user_id)
            ->order('id desc')
            ->paginate(15)
            ->each(function ($item) {
                if (!empty($item['image'])) {
                    $item['image'] = cmf_get_asset_url($item['image']);
                }
                $item['location_type_name'] = $item['location_type'] == 2 ? '非小区' : '小区内';
                // 拼接位置展示文本
                if ($item['location_type'] == 2) {
                    $item['location_text'] = $item['merchant_name'] . ' - ' . $item['address'];
                } else {
                    $item['location_text'] = $item['community_name'] . ' · ' . $item['point_name'];
                }
                return $item;
            });
        $this->success('获取成功', $list);
    }
}
