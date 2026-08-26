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

use api\wxapp\model\AdviceModel;
use api\wxapp\model\CommonModel;
use api\wxapp\model\MemberModel;
use cmf\controller\AdminBaseController;
use think\Model;

class AdviceController extends AdminBaseController
{
    /*
     *
     */
    public function index(){
        $Model = new AdviceModel();
        $keyword = $this->request->param('keyword','','trim');
        $map = [];
        $map[] = ['a.status','in',[1,2]];
        if(!empty($keyword)){
            $map[] = ['a.content','like',"%$keyword%"];
        }
        $list = $Model->alias('a')
            ->field('a.*,b.nickname,b.true_name,b.mobile')
            ->join('member b','a.user_id=b.id')
            ->where($map)->order('id desc')->paginate(10);

        $list=  CommonModel::getMany($list, ['image']);
        $list->appends(['keyword' => $keyword]);
        // 获取分页显示
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        return $this->fetch();
    }
}
