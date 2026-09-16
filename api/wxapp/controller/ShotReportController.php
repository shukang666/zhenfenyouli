<?php

namespace api\wxapp\controller;

use api\wxapp\model\ShotReportModel;
use think\facade\Db;

class ShotReportController extends AuthController
{
    protected $model;

    protected function initialize()
    {
        $this->model = new ShotReportModel();
    }

    /**
     * 提交随手拍问题
     */
    public function post()
    {
        $this->checkUser();

        $image = $this->request->param('image', '');
        $remark = $this->request->param('remark', '');

        if (empty($image)) {
            $this->error('请上传问题图片');
        }

        if (empty($remark)) {
            $this->error('请填写备注');
        }

        $data = [
            'user_id' => $this->user_id,
            'image' => $image,
            'remark' => $remark,
            'status' => 0,
            'create_time' => time(),
        ];

        $this->model->insert($data);

        $this->success('已提交，项目正在处理');
    }
}
