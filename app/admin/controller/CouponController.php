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

use api\wxapp\model\CategoryModel;
use api\wxapp\model\CouponModel;
use api\wxapp\model\GoodsModel;
use api\wxapp\model\ShopModel;
use api\wxapp\model\UserCouponModel;
use cmf\controller\AdminBaseController;
use think\Model;

class CouponController extends AdminBaseController
{
    /*
     *优惠券列表
     */
    public function index()
    {
        $Model = new CouponModel();
        $keyword = $this->request->param('keyword');
        $type = $this->request->param('type');
        $discount_type = $this->request->param('discount_type');
        $map = null;
        $map[] = ['a.status', 'in', [1, 2]];

        if (!empty($type)) {
            $map[] = ['a.type', '=', $type];
        }
        if (!empty($discount_type)) {
            $map[] = ['a.discount_type', '=', $discount_type];
        }

        $list = $Model->getInfo($map);

        // 获取分页显示
        $list->appends(['keyword' => $keyword, 'type' => $type, 'discount_type' => $discount_type]);
        $page = $list->render();

        $this->assign('type', $type);
        $this->assign('discount_type', $discount_type);
        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('type_list', $Model->getType());
        $this->assign('discount_type_list', $Model->getDiscountType());
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $data = $this->checkData($data);
            $CouponModel = new CouponModel();
            $data['select_count'] = 0;
            $data['create_time'] = time();
            $data['add_admin_id'] = $this->admin_id;

            $res = $CouponModel->strict(false)->save($data);
            if ($res) {
                $this->success("添加成功!", url('index'));
            } else {
                $this->error('添加失败');
            }
        } else {
            $CouponModel = new CouponModel();
//            $this->assign('goods_list',$this->getGoodsList());
//            $this->assign('cate_list',$this->getCateList());
            $this->assign('type_list', $CouponModel->getType());
            $this->assign('discount_type_list', $CouponModel->getDiscountType());
            return $this->fetch();
        }
    }

    public function checkData($data)
    {
//        if($data['discount_type'] == 2){
//            //现金券
//            $data['amount'] = $data['money_amount'];
//            $data['full_amount'] = $data['amount'];
//        }elseif($data['discount_type'] == 3){
//            if($data['discount'] > 10) $this->error('折扣比例错误');
//        }
//        if($data['full_amount'] < $data['amount']){
//            $this->error('金额错误');
//        }
        if ($data['indate_cate'] == 1) {
            if (empty($data['indate_day'])) $this->error('请输入有效期天数');
            $data['start_time'] = '';
            $data['end_time'] = '';
        } else {
            if (empty($data['start_time']) || empty($data['end_time'])) $this->error('请选择有效期时间');
            $data['indate_day'] = null;
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']) + 86399;
        }
        return $data;
    }

    public function getGoodsList()
    {
        $GoodsModel = new GoodsModel();
        return $GoodsModel->field('id,goods_name')->where(['status' => 1])->select();
    }
//    public function getCateList(){
//        $CategoryModel = new CategoryModel();
//        return $CategoryModel->field('id,cate_name')->where(['status'=>1,'parent_id'=>0])->select();
//    }

    public function edit()
    {
        $CouponModel = new CouponModel();
        $id = $this->request->param('id');
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data = $this->checkData($data);
            $data['update_time'] = time();

            $res = $CouponModel->where(['id' => $id])->strict(false)->save($data);
            if ($res) {
                $this->success("编辑成功!");
            } else {
                $this->error('编辑失败');
            }
        } else {

            $data = $CouponModel->where(['id' => $id])->find();
            if ($data['indate_cate'] == 1) {
                $data['start_time'] = '';
                $data['end_time'] = '';
            } else {
                $data['start_time'] = date('Y-m-d', $data['start_time']);
                $data['end_time'] = date('Y-m-d', $data['end_time']);
            }

            $this->assign('data', $data);
//            $this->assign('goods_list',$this->getGoodsList());
//            $this->assign('cate_list',$this->getCateList());
            $this->assign('type_list', $CouponModel->getType());
            $this->assign('discount_type_list', $CouponModel->getDiscountType());
            return $this->fetch();
        }
    }

    public function delete()
    {
        $id = $this->request->param('id');

        $Model = CouponModel::find($id);
        if (empty($Model)) {
            $this->error('优惠券不存在');
        } else {
            $Model->delete();
        }
        $this->success("删除成功!");
    }

    public function select_delete()
    {
        $id = $this->request->param('id');

        $Model = UserCouponModel::find($id);
        if (empty($Model)) {
            $this->error('优惠券不存在');
        } else {
            $Model->delete();
        }
        $this->success("删除成功!");
    }  public function delete_all()
{
    $ids = $this->request->param('ids');
    UserCouponModel::whereIn('id', $ids)->update(['delete_time'=>time()]);
    $this->success('删除成功');
}





    public function setCoupon()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $field = $this->request->param('field');
        $CouponModel = new CouponModel();
        $CouponModel->where(['id' => $id])->save([$field => $status]);
        $this->success('设置成功');
    }

    /*
    *优惠券列表
    */
    public function selectList()
    {
        $Model = new UserCouponModel();
        $keyword = $this->request->param('keyword', '', 'trim');
        $used = $this->request->param('used', -1);

        $map = [];
        $map[] = ['a.status', 'in', [1, 2]];
        if ($used != -1) {
            $map[] = ['a.used', '=', $used];
        }
        if (!empty($keyword)) {
            $map[] = ['b.nickname|b.true_name|b.mobile|a.name', 'like', "%$keyword%"];
        }

        $list = $Model->alias('a')
            ->field('a.*,b.nickname,b.mobile,b.true_name')
            ->join('member b', 'a.user_id=b.id')
            ->where([])->order('id desc')->paginate(10);

        $CouponModel = new CouponModel();

        $list = $CouponModel->changeData($list);
        // 获取分页显示
        $list->appends(['keyword' => $keyword, 'used' => $used]);
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('used', $used);
        return $this->fetch();
    }

}
