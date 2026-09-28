<?php

namespace api\wxapp\controller;

use app\admin\model\forum\ClockSceneGuideModel;
use app\admin\model\forum\ClockScenePointModel;
use think\facade\Db;

/**
 * 场景真分点：家庭/亲子用户自建；沿街/办公由后台统一配置
 */
class ClockScenePointController extends AuthController
{
    /** @var string[] */
    private $userHubs = ['home', 'qinzi'];

    /** @var string[] */
    private $listHubs = ['home', 'qinzi', 'street', 'office'];

    public function list()
    {
        $this->checkUser();
        $hub = $this->normalizeListHub($this->request->param('hub_key', ''));
        if ($hub === '') {
            $this->error('场景参数无效');
        }
        $list = Db::name('clock_scene_point')
            ->where('user_id', $this->user_id)
            ->where('hub_key', $hub)
            ->order('list_order asc,id asc')
            ->select()
            ->toArray();
        foreach ($list as &$item) {
            $img = trim((string)($item['point_image'] ?? ''));
            $item['point_image'] = $img === '' ? '' : cmf_get_asset_url($img);
        }
        unset($item);
        $this->success('请求成功', $list);
    }

    public function save()
    {
        $this->checkUser();
        $hub = $this->normalizeUserHub($this->request->param('hub_key', ''));
        if ($hub === '') {
            $this->error('该场景回收点由社区统一配置，请联系工作人员');
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
        $pointImage = trim((string)$this->request->param('point_image', ''));
        if ($pointImage !== '' && mb_strlen($pointImage) > 255) {
            $this->error('点位照片路径无效');
        }
        $id = intval($this->request->param('id', 0));
        $now = time();
        $row = [
            'point_name'  => $pointName,
            'cate_id'     => $cateId,
            'cate_name'   => $cate['name'],
            'update_time' => $now,
        ];
        if ($id <= 0 && $pointImage === '') {
            $this->error('请上传点位照片');
        }
        if ($pointImage !== '') {
            $row['point_image'] = $pointImage;
        } elseif ($id <= 0) {
            $row['point_image'] = '';
        }
        if ($id > 0) {
            $exists = Db::name('clock_scene_point')
                ->where('id', $id)
                ->where('user_id', $this->user_id)
                ->where('hub_key', $hub)
                ->find();
            if (empty($exists)) {
                $this->error('点位不存在');
            }
            if ((int)($exists['source'] ?? 0) === ClockScenePointModel::SOURCE_ADMIN) {
                $this->error('该点位由社区配置，无法自行修改');
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
        $row['source'] = ClockScenePointModel::SOURCE_USER;
        $row['remark'] = '';
        $row['admin_id'] = 0;
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
        $exists = Db::name('clock_scene_point')
            ->where('id', $id)
            ->where('user_id', $this->user_id)
            ->find();
        if (empty($exists)) {
            $this->error('点位不存在');
        }
        if ((int)($exists['source'] ?? 0) === ClockScenePointModel::SOURCE_ADMIN) {
            $this->error('该点位由社区配置，无法删除');
        }
        Db::name('clock_scene_point')->where('id', $id)->delete();
        $this->success('已删除');
    }

    private function normalizeListHub($hub): string
    {
        $hub = ClockSceneGuideModel::normalizeHub(trim((string)$hub));
        return in_array($hub, $this->listHubs, true) ? $hub : '';
    }

    private function normalizeUserHub($hub): string
    {
        $hub = ClockSceneGuideModel::normalizeHub(trim((string)$hub));
        return in_array($hub, $this->userHubs, true) ? $hub : '';
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
