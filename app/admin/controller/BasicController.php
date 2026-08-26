<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use api\user\model\UserModel;
use api\wxapp\model\ActivityModel;
use api\wxapp\model\FishAreaModel;
use cmf\controller\AdminBaseController;
use cmf\model\OptionModel;
use think\facade\Db;
use think\Model;

class BasicController extends AdminBaseController
{
    /**
        * 列表
    */
    public function index(){
        $Model = new ActivityModel();
        $keyword = $this->request->param('keyword','','trim');
        $map = [];
        $map[] = ['status','in',[1,2]];
        if(!empty($keyword)) $map[] = ['title','like',"%$keyword%"];

        $list = $Model->getInfo($map);

        $list->appends(['keyword' => $keyword]);
        // 获取分页显示
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        return $this->fetch();

    }

    /**
        * 添加
     */
    public function add(){
        if($this->request->isPost()){
            $param = $this->request->param();

            $Model = new ActivityModel();
            $param['create_time'] = time();
            $param['status'] = 2;
            $re = $Model->save($param);
            if($re){
                $this->success('添加成功', url("index"));
            }else{
                $this->error('添加失败');
            }

        }else{
            return $this->fetch();
        }
    }

    /**
        * 编辑
     */
    public function edit(){
        $id = $this->request->param('id');
        $Model = new ActivityModel();
        $data = $Model->getInfo(['a.id'=>$id],1,$id);
        if($this->request->isPost()){
            $param = $this->request->param();
            $re = $data->save($param);
            if($re){
                $this->success('更新成功');
            }else{
                $this->error('更新失败');
            }
        }else{
            $this->assign('data',$data);
            return $this->fetch();
        }
    }

    /**
        * 详情
     */
    public function detail(){
        $id = $this->request->param('id');
        $Model = new ActivityModel();
        $data = $Model->getInfo(['a.id'=>$id],1,$id);
        $this->assign('data',$data);
        return $this->fetch();
    }
    /**
        * 删除
     */
    public function delete(){
        if($this->request->isPost()){
            $id = $this->request->param('id');
            $Model = new ActivityModel();
            $re  = $Model->where(['id'=>$id])->save(['status'=>0]);
            if($re){
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }else{
            $this->error('非法操作');
        }
    }
    /**
        * 设置
     */
    public function setStatus(){
        $id = $this->request->param('id');
        $status= $this->request->param('status');
        $field= $this->request->param('field');
        $data = ActivityModel::find($id);
        $data->save([$field=>$status]);
        $this->success('设置成功');
    }

    /**
     * 审核
     */
    public function check(){
        $id = $this->request->param('id');
        $check_status = $this->request->param('check_status');
        $check_reason = $this->request->param('check_reason');
        $data = ActivityModel::find($id);
        $data->save(['check_status'=>$check_status,'check_reason'=>$check_reason,'check_time'=>time()]);
        $this->success('审核成功');
    }

    public function listOrder(){
        $Model = new ActivityModel();
        parent::listOrders($Model);
        $this->success("排序更新成功！");
    }


    /**
         * 富文本 协议等设置
         * @return mixed
     */
    public function richTextOption(){
        $field = $this->request->param('field');

        if(empty($field)){
            $field = Db::name('option_field')->where(['status'=>1,'type'=>1])->value('field');
        }

        if($this->request->isPost()){
            $data = $this->request->post();
            cmf_set_option($field,$data,true);
            $this->success('保存成功');
        }else{

            $data = cmf_get_new_option($field);
            $data['content'] = htmlspecialchars_decode($data['content']);

            $this->assign('data',$data['content']);
            $field_list = Db::name('option_field')->where(['status'=>1,'type'=>1])->select();
            $this->assign('field_list',$field_list);

            $field_data = Db::name('option_field')->where(['field'=>$field])->find();
            $this->assign('field_data',$field_data);

            return $this->fetch();
        }
    }

    //参数配置
    public function fieldDataOption(){
        $field = $this->request->param('field');

        if(empty($field)){
            $field = Db::name('option_field')->where(['status'=>1,'type'=>2])->value('field');
        }

        if($this->request->isPost()){
            $data = $this->request->post();
            cmf_set_option($field,$data,true);
            $this->success('保存成功');
        }else{

            $data = cmf_get_new_option($field);
            $this->assign('data',$data);

            $field_list = Db::name('option_field')->where(['status'=>1,'type'=>2])->select();
            $this->assign('field_list',$field_list);

            $field_data = Db::name('option_field')->where(['field'=>$field])->find();
            $this->assign('field_data',$field_data);

            $this->assign('field',$field);
            return $this->fetch();
        }
    }







}
