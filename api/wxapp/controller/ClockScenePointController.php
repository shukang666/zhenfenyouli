<?php

namespace api\wxapp\controller;

use app\admin\model\forum\ClockSceneGuideModel;
use think\facade\Db;

/**
 * 活动场景用户真分点（家庭/亲子/沿街，每用户独立；办公真分角不设点）
 */
class ClockScenePointController extends AuthController
{
    /** @var string[] */
    private $pointHubs = ['home', 'qinzi', 'street'];

    public function list()
    {
        $this->checkUser();
        $hub = $this->normalizePointHub($this->request->param('hub_key', ''));
        if ($hub === '') {
            $this->error('场景参数无效');
        }
        $list = Db::name('clock_scene_point')
            ->where('user_id', $this->user_id)
            ->where('hub_key', $hub)
            ->order('list_order asc,id asc')
            ->select()
            ->toArray();
        $this->success('请求成功', $list);
    }

    public function save()
    {
        $this->checkUser();
        $hub = $this->normalizePointHub($this->request->param('hub_key', ''));
        if ($hub === '') {
            $this->error('场景参数无效');
        }
        $pointName = trim((string)$this->request->param('point_name', ''));
        $cateId = intval($this->request->param('cate_id', 0));
        if ($pointName === '') {
            $this->error('请填写真分点名称');
        }
        if (mb_strlen($pointName) > 32) {
            $this->error('真分点名称过长');
        }
        $cate = $this->resolveClockCate($cateId);
        if (!$cate) {
            $this->error('请选择垃圾种类');
        }
        $id = intval($this->request->param('id', 0));
        $now = time();
        $row = [
            'point_name'  => $pointName,
            'cate_id'     => $cateId,
            'cate_name'   => $cate['name'],
            'update_time' => $now,
        ];
        if ($id > 0) {
            $exists = Db::name('clock_scene_point')
                ->where('id', $id)
                ->where('user_id', $this->user_id)
                ->where('hub_key', $hub)
                ->find();
            if (empty($exists)) {
                $this->error('点位不存在');
            }
            Db::name('clock_scene_point')->where('id', $id)->update($row);
            $this->success('已保存', ['id' => $id]);
            return;
        }
        $maxOrder = (int)Db::name('clock_scene_point')
            ->where('user_id', $this->user_id)
            ->where('hub_key', $hub)
            ->max('list_order');
        $row['user_id'] = $this->user_id;
        $row['hub_key'] = $hub;
        $row['list_order'] = $maxOrder + 1;
        $row['create_time'] = $now;
        $newId = Db::name('clock_scene_point')->insertGetId($row);
        $this->success('已添加', ['id' => $newId]);
    }

    public function delete()
    {
        $this->checkUser();
        $id = intval($this->request->param('id', 0));
        if ($id <= 0) {
            $this->error('参数错误');
        }
        Db::name('clock_scene_point')
            ->where('id', $id)
            ->where('user_id', $this->user_id)
            ->delete();
        $this->success('已删除');
    }

    private function normalizePointHub($hub): string
    {
        $hub = ClockSceneGuideModel::normalizeHub(trim((string)$hub));
        return in_array($hub, $this->pointHubs, true) ? $hub : '';
    }

    /**
     * @return array|null id,name
     */
    private function resolveClockCate(int $cateId)
    {
        if ($cateId <= 0) {
            return null;
        }
        $row = Db::name('basic_select')
            ->where('id', $cateId)
            ->where('type', 2)
            ->where('pid', 0)
            ->field('id,name')
            ->find();
        return $row ?: null;
    }
}
