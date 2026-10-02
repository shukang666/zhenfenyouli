<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\service\FleaMarketService;
use think\facade\Db;

class FleaMarketController extends AuthController
{
    protected function requireActive(int $activeId): array
    {
        $active = FleaMarketService::loadWebActive($activeId);
        if (empty($active)) {
            $this->error('活动不存在或未启用 Web 式');
        }
        return $active;
    }

    /** 活动主页 */
    public function home()
    {
        $activeId = (int)$this->request->param('active_id');
        $active = $this->requireActive($activeId);
        $base = FleaMarketService::staticPageBase();

        $sessions = Db::name(FleaMarketService::sessionTable())
            ->where('active_id', $activeId)
            ->where('status', 1)
            ->order('list_order asc, start_time asc, id asc')
            ->select()
            ->toArray();

        $list = [];
        foreach ($sessions as $s) {
            $list[] = [
                'id' => (int)$s['id'],
                'title' => (string)$s['title'],
                'address' => (string)$s['address'],
                'date_text' => (string)$s['date_text'],
                'start_time' => (int)$s['start_time'],
                'end_time' => (int)$s['end_time'],
            ];
        }

        $myApplies = [];
        if ($this->user_id) {
            $rows = Db::name(FleaMarketService::vendorTable())
                ->where('user_id', $this->user_id)
                ->where('active_id', $activeId)
                ->column('status', 'session_id');
            $myApplies = $rows ?: [];
        }

        $this->success('请求成功', [
            'active_id' => $activeId,
            'active_name' => (string)$active['name'],
            'intro' => htmlspecialchars_decode((string)($active['content'] ?? '')),
            'rules_url' => $base . 'rules.html',
            'safety_url' => $base . 'safety.html',
            'promote_point_cost' => FleaMarketService::PROMOTE_POINT_COST,
            'sessions' => $list,
            'my_apply_status' => $myApplies,
        ]);
    }

    /** 场次详情 + 已通过摊主的贵重品展示 */
    public function session_detail()
    {
        $sessionId = (int)$this->request->param('session_id');
        $session = Db::name(FleaMarketService::sessionTable())->where('id', $sessionId)->where('status', 1)->find();
        if (empty($session)) {
            $this->error('场次不存在');
        }
        $activeId = (int)$session['active_id'];
        $this->requireActive($activeId);

        $vendorIds = Db::name(FleaMarketService::vendorTable())
            ->where('session_id', $sessionId)
            ->where('status', 1)
            ->column('id');

        $highlights = [];
        if ($vendorIds) {
            $rows = Db::name(FleaMarketService::highlightTable())
                ->where('session_id', $sessionId)
                ->where('vendor_id', 'in', $vendorIds)
                ->where('status', 1)
                ->order('id desc')
                ->select()
                ->toArray();
            foreach ($rows as $h) {
                $highlights[] = [
                    'image' => empty($h['image']) ? '' : cmf_get_asset_url($h['image']),
                    'price_text' => (string)$h['price_text'],
                    'exchange_text' => (string)$h['exchange_text'],
                ];
            }
        }

        $myStatus = -1;
        if ($this->user_id) {
            $st = Db::name(FleaMarketService::vendorTable())
                ->where('session_id', $sessionId)
                ->where('user_id', $this->user_id)
                ->value('status');
            $myStatus = $st === null ? -1 : (int)$st;
        }

        $this->success('请求成功', [
            'session' => [
                'id' => (int)$session['id'],
                'active_id' => $activeId,
                'title' => (string)$session['title'],
                'address' => (string)$session['address'],
                'date_text' => (string)$session['date_text'],
                'brief' => (string)($session['brief'] ?? ''),
                'start_time' => (int)$session['start_time'],
                'end_time' => (int)$session['end_time'],
            ],
            'highlights' => $highlights,
            'my_apply_status' => $myStatus,
            'promote_point_cost' => FleaMarketService::PROMOTE_POINT_COST,
        ]);
    }

    /** 摊主报名 */
    public function apply_submit()
    {
        $this->checkUser();
        $sessionId = (int)$this->request->param('session_id');
        $session = Db::name(FleaMarketService::sessionTable())->where('id', $sessionId)->where('status', 1)->find();
        if (empty($session)) {
            $this->error('场次不存在');
        }
        $activeId = (int)$session['active_id'];
        $this->requireActive($activeId);

        $exists = Db::name(FleaMarketService::vendorTable())
            ->where('session_id', $sessionId)
            ->where('user_id', $this->user_id)
            ->find();
        if ($exists) {
            $this->error('您已报名该场次，请勿重复提交');
        }

        $wantPromote = (int)$this->request->param('want_promote', 0);
        $image = trim((string)$this->request->param('image', ''));
        $priceText = trim((string)$this->request->param('price_text', ''));
        $exchangeText = trim((string)$this->request->param('exchange_text', ''));
        $contactName = trim((string)$this->request->param('contact_name', ''));
        $contactMobile = trim((string)$this->request->param('contact_mobile', ''));

        if ($wantPromote) {
            if ($image === '') {
                $this->error('宣传贵重品请上传照片');
            }
            if ($priceText === '' && $exchangeText === '') {
                $this->error('请填写售价或想换取的物品');
            }
        }

        $pointSpent = 0;
        if ($wantPromote) {
            $cost = FleaMarketService::PROMOTE_POINT_COST;
            $member = Db::name('member')->where('id', $this->user_id)->field('point')->find();
            if (empty($member) || (float)$member['point'] < $cost) {
                $this->error('积分不足，宣传需消耗 ' . $cost . ' 积分');
            }
            AccountLogModel::add_account_log(
                $this->user_id,
                2,
                205,
                -$cost,
                ['remark' => '二手集市贵重品宣传', 'active_id' => $activeId, 'session_id' => $sessionId]
            );
            $pointSpent = $cost;
        }

        $now = time();
        $vendorId = Db::name(FleaMarketService::vendorTable())->insertGetId([
            'active_id' => $activeId,
            'session_id' => $sessionId,
            'user_id' => $this->user_id,
            'contact_name' => $contactName,
            'contact_mobile' => $contactMobile,
            'status' => 0,
            'create_time' => $now,
            'audit_time' => 0,
        ]);

        if ($wantPromote) {
            Db::name(FleaMarketService::highlightTable())->insert([
                'active_id' => $activeId,
                'session_id' => $sessionId,
                'vendor_id' => $vendorId,
                'user_id' => $this->user_id,
                'image' => $image,
                'price_text' => $priceText,
                'exchange_text' => $exchangeText,
                'point_spent' => $pointSpent,
                'status' => 0,
                'create_time' => $now,
            ]);
        }

        $this->success('报名成功', [
            'vendor_id' => $vendorId,
            'message' => '报名成功，请等待平台审核。审核通过后将通知您。',
        ]);
    }
}
