<?php

namespace app\admin\controller;

use api\wxapp\service\ActiveWebOnePlusOneService;
use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 1+1 活动 · 第 1 天有害垃圾 Web 打卡记录
 */
class ActiveOnePlusOneHarmfulController extends AdminBaseController
{
    protected function table()
    {
        return Db::name(ActiveWebOnePlusOneService::harmfulClockTable());
    }

    public function index()
    {
        $params = $this->request->param();
        $activeId = (int)$this->request->param('active_id', 0);
        $keyword = trim((string)$this->request->param('keyword', ''));

        $where = [];
        if ($activeId > 0) {
            $where[] = ['c.active_id', '=', $activeId];
        }
        if ($keyword !== '') {
            $where[] = ['m.nickname|m.mobile|c.remark', 'like', '%' . $keyword . '%'];
        }

        $list = $this->table()->alias('c')
            ->join('member m', 'm.id = c.user_id', 'LEFT')
            ->join('active a', 'a.id = c.active_id', 'LEFT')
            ->field('c.*,m.nickname,m.mobile,a.name as active_name')
            ->where($where)
            ->order('c.id desc')
            ->paginate(20);

        foreach ($list as &$vo) {
            $vo['image_url'] = empty($vo['image']) ? '' : cmf_get_asset_url($vo['image']);
        }
        unset($vo);

        $actives = Db::name('active')
            ->where('delete_time', 0)
            ->where('display_mode', 2)
            ->where('web_url', 'like', '%one-plus-one%')
            ->field('id,name')
            ->order('id desc')
            ->select()
            ->toArray();

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('active_id', $activeId);
        $this->assign('keyword', $keyword);
        $this->assign('actives', $actives);
        return $this->fetch();
    }

    public function delete()
    {
        $id = (int)$this->request->param('id');
        if ($id <= 0) {
            $this->error('参数错误');
        }
        $row = $this->table()->where('id', $id)->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }
        $this->table()->where('id', $id)->delete();
        $this->success('已删除');
    }
}
