<?php

namespace app\admin\controller;

use api\wxapp\model\CommonModel;
use app\admin\model\member\MemberShopHandleModel;
use think\App;
use think\db\exception\DbException;

class HandleController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new MemberShopHandleModel();
    }

    public function index(){
        $params = $this->request->param();
        $export = $this->request->param('export');
        $where = [];
        if(!empty($params['name'])) $where[] = ['c.name', 'like', '%' . $params['name'] . '%'];
        if(!empty($params['id'])) $where[] = ['a.user_id', '=', $params['id']];

        if (!empty($params['start_time'])) {
            $where[] = ['a.create_time', '>=', strtotime($params['start_time'] . ' 00:00')];
        }
        if (!empty($params['end_time'])) {
            $where[] = ['a.create_time', '<=', strtotime($params['end_time'] . ' 23:59')];
        }


        if ($export) {
            $list = $this->model->alias('a')
                ->join('member b','a.user_id = b.id')
                ->join('member_shop c','a.shop_id = c.id')
                ->field('a.*,b.nickname,b.mobile,c.name')
                ->where($where)->order('id desc')->select();

            $this->outData($list);
        }

        $list = $this->model->alias('a')
            ->join('member b','a.user_id = b.id')
            ->join('member_shop c','a.shop_id = c.id')
            ->field('a.*,b.nickname,b.mobile,c.name')
            ->where($where)->order('id desc')->paginate(10);

        $this->assign('params', $params);
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());

        return $this->fetch();
    }




    public function outData($list)
    {
        $image_array = [];
        foreach($list as &$item){

            if(!empty($item['image_one'])){
                $item['image_one'] = cmf_get_asset_url($item['image_one']);
                //蓝底照片
                $image_one = file_get_contents($item['image_one']);
                $value = "upload/file/" . md5(time() . rand(1,99999) ) . ".png";
                file_put_contents($value, $image_one);
                $item['image_one'] = $value;
                //本地图
                $image_array[] = $value;
            }

            if(!empty($item['image_two'])){
                $item['image_two'] = cmf_get_asset_url($item['image_two']);
                //蓝底照片
                $image_two = file_get_contents($item['image_two']);
                $value = "upload/file/" . md5(time() . rand(1,99999) ) . ".png";
                file_put_contents($value, $image_two);
                $item['image_two'] = $value;
                //本地图
                $image_array[] = $value;
            }

            if(!empty($item['image_three'])){
                $item['image_three'] = cmf_get_asset_url($item['image_three']);
                //蓝底照片
                $image_three = file_get_contents($item['image_three']);
                $value = "upload/file/" . md5(time() . rand(1,99999) ) . ".png";
                file_put_contents($value, $image_three);
                $item['image_three'] = $value;
                //本地图
                $image_array[] = $value;
            }

            if(!empty($item['image_four'])){
                $item['image_four'] = cmf_get_asset_url($item['image_four']);
                //蓝底照片
                $image_four = file_get_contents($item['image_four']);
                $value = "upload/file/" . md5(time() . rand(1,99999) ) . ".png";
                file_put_contents($value, $image_four);
                $item['image_four'] = $value;
                //本地图
                $image_array[] = $value;
            }

            if(!empty($item['image_five'])){
                $item['image_five'] = cmf_get_asset_url($item['image_five']);
                //蓝底照片
                $image_five = file_get_contents($item['image_five']);
                $value = "upload/file/" . md5(time() . rand(1,99999) ) . ".png";
                file_put_contents($value, $image_five);
                $item['image_five'] = $value;
                //本地图
                $image_array[] = $value;
            }

            $item['create_time'] = empty($item['create_time'])?'':date('Y-m-d H:i:s',$item['create_time']);
        }
//        var_dump($image_array);exit();


        $filename = '商家打卡' . date('Y-m-d');
        $head = ['ID', '用户信息', '公司', '门头照片', '宣传品照片', '投放点位照片', '管理制度/收运协议照片', '桶内照片', '创建时间',];//设置表头
        $keys = ['id', 'nickname', 'name', 'image_one', 'image_two', 'image_three', 'image_four', 'image_five', 'create_time',];//设置表头
        $types = ['1', '1', '1', '2', '2', '2', '2', '2', '1'];//设置表头
        CommonModel::outData($filename, $list, $head, $keys, $types, $image_array);
        exit;
    }
}