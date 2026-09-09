<?php

namespace app\admin\controller;

use app\admin\model\member\MemberModel;
use app\admin\model\member\MemberShopModel;
use think\App;

class MemberShopController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new MemberShopModel();
    }


    //审核
    public function fix_status()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $msg = $this->request->param('text');
        if ($status == 1) {
            $save = ['status' => 2];
            $this->model->where('id', $id)->save($save);
            //把关联的用户改为商家
            $user_id = $this->model->where('id', $id)->value('user_id');
            MemberModel::where('id',$user_id)->update(['is_merchant' => 1]);


            $this->success('审核通过！');
        } else {
            $save = ['msg' => $msg, 'status' => 3];
            $this->model->where('id', $id)->save($save);
            $this->success('保存成功！');
        }

    }


    /**删除
     * @return void
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        //把关联的用户改为非商家
        $user_id = $this->model->where('id', $id)->value('user_id');
        MemberModel::where('id',$user_id)->update(['is_merchant' => 0]);

        $result = $this->model->find($id)->delete();
        $this->getMsg($result, '删除');
    }
}