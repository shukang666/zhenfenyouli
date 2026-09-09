<?php

namespace api\wxapp\controller;

use api\wxapp\model\CommonModel;
use app\admin\model\health\DiseaseCateModel;
use app\admin\model\health\DiseaseModel;
use app\admin\model\health\HealthHomeModel;
use app\admin\model\member\MemberModel;
use app\admin\model\member\ReservationModel;
use think\facade\Db;

class HealthController extends AuthController
{
    /**
     * 测试
     * @OA\Post(
     *tags={"健康"},
     *path="/wxapp/health/test",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function test()
    {

        $memberModel = new MemberModel();
        $list = $memberModel->where('status', 1)->fetchSql(true)->select();
        $this->success('请求成功', $list);
    }


    /**
     * 参与问卷和预约
     * @OA\Post(
     *tags={"问卷调查模块"},
     *path="/wxapp/health/get_health_home_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="1：五个分类，2：其他",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_health_home_list(HealthHomeModel $homeModel)
    {
        $id = $this->request->param('id');
        $type = $this->request->param('type', 1);
        $status = $this->request->param('status', 0);
//        $cate_id = $this->request->param('cate_id', 1);
        if ($id) {
            $item = $homeModel->where('id', $id)->find();
            $item = CommonModel::getOne($item, ['thumb'], [], ['content']);
            $this->success('请求成功', $item);
        }
        $where = [];
        $where[] = ['type', '=', $type];
        if ($status) $where[] = ['status', '=', $status];
        if ($type == 1) {
            $list = $homeModel->where($where)->hidden(['content'])->select();
            foreach ($list as &$item) {
                $item = Db::name('exam')
                    ->field("*")
                    ->order('list_order asc')
                    ->find();
            }
        } else {
            $list = $homeModel->where($where)->hidden(['content'])->select();
            foreach ($list as &$item) {
                if (empty($item['desc'])) $item['desc'] = '';
            }
        }

        $list = CommonModel::getMany($list, ['thumb']);
        $this->success('请求成功', $list);
    }


    /**
     * 身心健康分类
     * @OA\Post(
     *tags={"健康"},
     *path="/wxapp/health/get_disease_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_disease_list(DiseaseCateModel $cateModel, DiseaseModel $diseaseModel)
    {
        $id = $this->request->param('id');
        if ($id) {
            $item = $diseaseModel->where('id', $id)->find();
            $item = CommonModel::getOne($item, ['thumb'], [], ['content']);
            $this->success('请求成功', $item);
        }
        $list = $cateModel->field('id,name')->where('status', 1)
            ->hidden(['id'])
            ->paginate($this->page_size);
        foreach ($list as &$item) {
            $item['child'] = $diseaseModel->field('id,name,thumb')->where('cate_id', $item['id'])->select();

        }
        $this->success('请求成功', $list);
    }


    /**
     * 添加预约
     * @OA\Post(
     *tags={"健康"},
     *path="/wxapp/health/add_reservation",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="name",description="姓名",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="mobile",description="手机号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="demand",description="需求",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="area",description="地区 文字",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="institution",description="机构 文字",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="health_id",description="健康ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_reservation(ReservationModel $reservationModel)
    {
        $param = $this->request->param();
        $param['user_id'] = $this->user_id;
        $list = $reservationModel->save($param);
        $this->success('保存成功');
    }


    /**
     * 我的预约
     * @OA\Post(
     *tags={"健康"},
     *path="/wxapp/health/get_reservation_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_reservation_list(ReservationModel $reservationModel)
    {
        $id = $this->request->param('id');
        if ($id) {
            $item = $reservationModel->find($id);
            $this->success('请求成功', $item);
        }
        $this->checkUser();
        $param['user_id'] = $this->user_id;
//        $param['a.user_id'] = 2;
        $list = $reservationModel
            ->alias('a')
            ->join('health_home b', 'a.health_id=b.id')
            ->field('a.id,a.create_time,b.name')
            ->where($param)
            ->paginate($this->page_size);
        $this->success('请求成功', $list);
    }

}