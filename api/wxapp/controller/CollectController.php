<?php

namespace api\wxapp\controller;

use app\admin\model\basic\CollectModel;
use app\admin\model\basic\BrowseModel;

class CollectController extends AuthController
{




    /**
     * 添加收藏和取消收藏
     * @OA\Post(
     *     tags={"收藏"},
     *     path="/wxapp/collect/add_collect",
     *      @OA\Parameter( name="openid", description="openid",in="query",required=false, @OA\Schema(type="string", )),
     *      @OA\Parameter( name="car_id", description="要收藏的车id",in="query",required=false, @OA\Schema(type="string", )),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_collect(CollectModel $collectModel)
    {
        $this->checkUser();
        $car_id = $this->request->param('car_id');
        if (empty($car_id)) {
            $this->error('car_id不能为空');
        }
        $where = [
            'user_id' => $this->user_id,
            'car_id' => $car_id,
        ];
        $collect=$collectModel->where($where)->find();
        if (empty($collect)) {
            $collectModel->save($where);
            $this->success('收藏成功');
        }else{
            $collectModel->where($where)->delete();
            $this->success('取消成功');
        }


    }



    /**
     * 收藏列表
     * @OA\Post(
     *     tags={"收藏"},
     *     path="/wxapp/collect/get_collect",
     *      @OA\Parameter( name="openid", description="openid",in="query",required=false, @OA\Schema(type="string", )),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_collect_list(CollectModel $collectModel)
    {
        $this->checkUser();
        $id = $this->request->param('id');
        if (empty($id)) {

            $this->checkUser();
            $where = ['a.user_id' => $this->user_id];
            $list = $collectModel->alias('a')
                ->leftJoin('car b','a.car_id=b.id')
                ->leftJoin('car_brand c','b.brand_id=c.id')
                ->field('b.title,b.price,b.first_time,b.mileage,c.name brand_name,b.id')
                ->where($where)->paginate($this->page_size);
            $this->success('查询成功',$list);
        }else{
            $where = [
                'id' => $id,
                'user_id' => $this->user_id,
            ];
            $item=$collectModel->where($where)->find();
            $this->success('查询成功',$item);
        }


    }


    /**
     * 删除收藏
     * @OA\Post(
     *     tags={"收藏"},
     *     path="/wxapp/collect/del_collect",
     *      @OA\Parameter( name="openid", description="openid",in="query",required=false, @OA\Schema(type="string", )),
     *      @OA\Parameter( name="ids", description="传数组",in="query",required=false, @OA\Schema(type="string", )),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function del_collect(CollectModel $collectModel)
    {
        $this->checkUser();
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('id不能为空');
        }
        if (!is_array($ids)) {
            $this->error('ids参数错误');
        }
        foreach ($ids as $id) {
            $where = [
                'id' => $id,
                'user_id' => $this->user_id,
            ];
            $collectModel->where($where)->delete();
        }
        $this->success('删除成功');

    }



    /**
     * 浏览列表
     * @OA\Post(
     *     tags={"浏览记录"},
     *     path="/wxapp/collect/get_browse_list",
     *      @OA\Parameter( name="openid", description="openid",in="query",required=false, @OA\Schema(type="string", )),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_browse_list(BrowseModel $browseModel)
    {
            $this->checkUser();
            $where = ['a.user_id' => $this->user_id];
            $list = $browseModel->alias('a')
                ->leftJoin('car b','a.car_id=b.id')
                ->leftJoin('car_brand c','b.brand_id=c.id')
                ->field('b.title,b.price,b.first_time,b.mileage,c.name brand_name,b.id')
                ->where($where)->paginate($this->page_size);
            $this->success('查询成功',$list);
    }

}