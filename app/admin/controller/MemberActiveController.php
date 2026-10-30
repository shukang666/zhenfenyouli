<?php

namespace app\admin\controller;

use api\wxapp\service\MemberActiveService;
use app\admin\model\active\ActiveModel;
use cmf\controller\AdminBaseController;

/**
 * 居民自发活动审核
 */
class MemberActiveController extends AdminBaseController
{
    public function index()
    {
        $status = $this->request->param('status', '');
        $keyword = trim((string)$this->request->param('keyword', ''));

        $where = [['a.publish_user_id', '>', 0], ['a.delete_time', '=', 0]];
        if ($status !== '') {
            $where[] = ['a.publish_audit_status', '=', (int)$status];
        }
        if ($keyword !== '') {
            $where[] = ['a.name', 'like', '%' . $keyword . '%'];
        }

        $list = ActiveModel::alias('a')
            ->join('member m', 'm.id = a.publish_user_id', 'LEFT')
            ->field('a.*,m.nickname,m.mobile')
            ->where($where)
            ->order('a.id desc')
            ->paginate(15);

        $list->each(function ($item) {
            $item['thumb_url'] = empty($item['thumb']) ? '' : cmf_get_asset_url($item['thumb']);
            $st = (int)$item['publish_audit_status'];
            $item['audit_name'] = $st === MemberActiveService::AUDIT_PENDING ? '待审核'
                : ($st === MemberActiveService::AUDIT_REJECTED ? '已拒绝' : '展示中');
            return $item;
        });

        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $this->assign('status', $status);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    public function pass()
    {
        $id = (int)$this->request->param('id');
        $row = ActiveModel::where('id', $id)->where('publish_user_id', '>', 0)->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }
        ActiveModel::where('id', $id)->update([
            'publish_audit_status' => MemberActiveService::AUDIT_APPROVED,
            'publish_reject_reason' => '',
            'status' => 1,
            'update_time' => time(),
        ]);
        MemberActiveService::syncPublishNoticeField($id, $row);
        $this->success('已通过');
    }

    public function reject()
    {
        $id = (int)$this->request->param('id');
        $reason = trim((string)$this->request->param('reason', '内容不符合发布规范'));
        $row = ActiveModel::where('id', $id)->where('publish_user_id', '>', 0)->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }
        ActiveModel::where('id', $id)->update([
            'publish_audit_status' => MemberActiveService::AUDIT_REJECTED,
            'publish_reject_reason' => mb_substr($reason, 0, 200),
            'status' => 0,
            'update_time' => time(),
        ]);
        $this->success('已拒绝并下架');
    }

    public function edit()
    {
        $id = (int)$this->request->param('id');
        $row = ActiveModel::where('id', $id)->where('publish_user_id', '>', 0)->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }
        $row = $row->toArray();
        $row['thumb_url'] = empty($row['thumb']) ? '' : cmf_get_asset_url($row['thumb']);
        if (!MemberActiveService::hasParticipationSchema()) {
            $this->error('请先执行 update.sql 中的居民发布报名/签到字段脚本');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    public function editPost()
    {
        $id = (int)$this->request->param('id');
        try {
            MemberActiveService::updatePublishSettings($id, [
                'allow_signup' => $this->request->param('allow_signup', 0),
                'require_checkin' => $this->request->param('require_checkin', 0),
                'publish_proxy_notice' => $this->request->param('publish_proxy_notice', ''),
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('保存成功', url('admin/MemberActive/index'));
    }
}
