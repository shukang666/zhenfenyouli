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
     * 参数：image, remark, location_type(1=小区内, 2=沿街商铺, 默认1)
     */
    public function post()
    {
        $this->checkUser();

        $image       = $this->request->param('image', '');
        $remark      = $this->request->param('remark', '');
        $locationType = intval($this->request->param('location_type', 1));

        if (empty($image)) {
            $this->error('请上传问题图片');
        }

        if (empty($remark)) {
            $this->error('请填写备注');
        }

        if (!in_array($locationType, [1, 2])) {
            $locationType = 1;
        }

        $data = [
            'user_id'      => $this->user_id,
            'image'        => $image,
            'remark'       => $remark,
            'location_type'=> $locationType,
            'status'       => 0,
            'create_time'  => time(),
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
                $item['location_type_name'] = $item['location_type'] == 2 ? '沿街商铺' : '小区内';
                return $item;
            });
        $this->success('获取成功', $list);
    }
}
