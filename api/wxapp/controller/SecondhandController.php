<?php

namespace api\wxapp\controller;

use api\wxapp\service\SecondhandService;
use think\facade\Db;

class SecondhandController extends AuthController
{
    /** 分类/成色元数据 */
    public function meta()
    {
        $this->checkUser();
        $communityId = (int)($this->user_info['community_id'] ?? 0);
        $parentId = (int)($this->user_info['community_parent_id'] ?? 0);
        if ($parentId <= 0 && $communityId > 0) $parentId = (int)Db::name('member_community')->where('id', $communityId)->value('pid');
        $community = $communityId ? Db::name('member_community')->field('id,name,pid')->where('id', $communityId)->find() : null;
        $parent = $parentId ? Db::name('member_community_parent')->field('id,name')->where('id', $parentId)->find() : null;
        $this->success('获取成功', [
            'categories' => array_merge([['key' => 'all', 'label' => '全部']], SecondhandService::categories()),
            'conditions' => SecondhandService::conditions(),
            'community_id' => $communityId,
            'community_name' => $community['name'] ?? '',
            'community_parent_id' => $parentId,
            'community_parent_name' => $parent['name'] ?? '',
        ]);
    }

    /** 公开列表（仅出售中） */
    public function list()
    {
        $category = trim((string)$this->request->param('category', 'all'));
        $keyword = trim((string)$this->request->param('keyword', ''));
        $page = max(1, (int)$this->request->param('page', 1));
        $pageSize = min(30, max(1, (int)$this->request->param('page_size', $this->page_size)));

        $q = Db::name(SecondhandService::TABLE_ITEM)->alias('i')
            ->leftJoin('member m', 'm.id = i.user_id')
            ->leftJoin('member_community c', 'c.id = i.scope_id and i.scope_type = "community"')
            ->leftJoin('member_community_parent p', 'p.id = i.scope_id and i.scope_type = "parent"')
            ->field('i.*,m.nickname as seller_nickname,m.avatar as seller_avatar,IF(i.scope_type="community",c.name,IF(i.scope_type="parent",p.name,"整个七贤岭")) as scope_name')
            ->where('i.status', SecondhandService::STATUS_ON_SALE);

        if ($this->user_id) {
            $communityId = (int)($this->user_info['community_id'] ?? 0);
            $parentId = (int)($this->user_info['community_parent_id'] ?? 0);
            if ($parentId <= 0 && $communityId > 0) $parentId = (int)Db::name('member_community')->where('id', $communityId)->value('pid');
            $q->where(function ($query) use ($communityId, $parentId) {
                $query->where('i.scope_type', 'all');
                if ($communityId > 0) $query->whereOr(function ($sub) use ($communityId) { $sub->where('i.scope_type', 'community')->where('i.scope_id', $communityId); });
                if ($parentId > 0) $query->whereOr(function ($sub) use ($parentId) { $sub->where('i.scope_type', 'parent')->where('i.scope_id', $parentId); });
            });
        } else {
            $q->where('i.scope_type', 'all');
        }

        if ($category !== '' && $category !== 'all') {
            $q->where('i.category', $category);
        }
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $q->where(function ($query) use ($like) {
                $query->whereLike('i.title', $like)
                    ->whereOr('i.description', 'like', $like);
            });
        }

        $paginator = $q->order('i.id desc')->paginate(['list_rows' => $pageSize, 'page' => $page]);
        $items = [];
        foreach ($paginator->items() as $row) {
            $items[] = SecondhandService::formatItemForApi($row);
        }

        $this->success('获取成功', [
            'list' => $items,
            'total' => $paginator->total(),
            'page' => $page,
            'page_size' => $pageSize,
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /** 详情 */
    public function detail()
    {
        $id = (int)$this->request->param('id');
        if ($id <= 0) {
            $this->error('参数错误');
        }
        $row = Db::name(SecondhandService::TABLE_ITEM)->alias('i')
            ->leftJoin('member m', 'm.id = i.user_id')
            ->leftJoin('member_community c', 'c.id = i.scope_id and i.scope_type = "community"')
            ->leftJoin('member_community_parent p', 'p.id = i.scope_id and i.scope_type = "parent"')
            ->field('i.*,m.nickname as seller_nickname,m.avatar as seller_avatar,IF(i.scope_type="community",c.name,IF(i.scope_type="parent",p.name,"整个七贤岭")) as scope_name')
            ->where('i.id', $id)
            ->find();
        if (empty($row)) {
            $this->error('物品不存在');
        }
        $status = (int)$row['status'];
        $isOwner = $this->user_id && (int)$row['user_id'] === (int)$this->user_id;
        if (!$isOwner && !$this->canViewScope($row)) {
            $this->error('该闲置仅对指定社区居民开放');
        }
        if (!$isOwner && !in_array($status, [SecondhandService::STATUS_ON_SALE, SecondhandService::STATUS_RESERVED], true)) {
            $this->error('物品已下架');
        }

        $data = SecondhandService::formatItemForApi($row, true, true);
        $data['is_owner'] = $isOwner;
        $this->success('获取成功', $data);
    }

    private function canViewScope(array $row): bool
    {
        $type = (string)($row['scope_type'] ?? 'all');
        if ($type === 'all') return true;
        if (!$this->user_id) return false;
        $communityId = (int)($this->user_info['community_id'] ?? 0);
        $parentId = (int)($this->user_info['community_parent_id'] ?? 0);
        if ($parentId <= 0 && $communityId > 0) $parentId = (int)Db::name('member_community')->where('id', $communityId)->value('pid');
        if ($type === 'community') return $communityId > 0 && $communityId === (int)$row['scope_id'];
        if ($type === 'parent') return $parentId > 0 && $parentId === (int)$row['scope_id'];
        return false;
    }

    /** 发布闲置 */
    public function publish()
    {
        $this->checkUser();

        $title = trim((string)$this->request->param('title', ''));
        $description = trim((string)$this->request->param('description', ''));
        $price = (float)$this->request->param('price', -1);
        $contact = trim((string)$this->request->param('contact', ''));
        $scopeType = trim((string)$this->request->param('scope_type', ''));
        $scopeId = (int)$this->request->param('scope_id', 0);
        $category = trim((string)$this->request->param('category', 'other'));
        $condition = trim((string)$this->request->param('condition', 'used'));
        $areaText = trim((string)$this->request->param('area_text', ''));
        $images = SecondhandService::normalizeImagePaths($this->request->param('images', ''));

        if ($title === '') {
            $this->error('请填写物品名称');
        }
        if (mb_strlen($title) > 40) {
            $this->error('名称不超过40字');
        }
        if ($price < 0 || $price > 9999999 || !preg_match('/^\d{1,7}(\.\d{1,2})?$/', (string)$this->request->param('price', ''))) {
            $this->error('请填写正确的人民币售价');
        }
        if ($contact === '' || mb_strlen($contact) > 40) {
            $this->error('请填写有效联系方式');
        }
        $memberCommunityId = (int)($this->user_info['community_id'] ?? 0);
        $memberParentId = (int)($this->user_info['community_parent_id'] ?? 0);
        if ($memberParentId <= 0 && $memberCommunityId > 0) {
            $memberParentId = (int)Db::name('member_community')->where('id', $memberCommunityId)->value('pid');
        }
        if (!in_array($scopeType, ['community', 'parent', 'all'], true)) {
            $this->error('请选择展示范围');
        }
        if ($scopeType === 'community' && ($memberCommunityId <= 0 || $scopeId !== $memberCommunityId)) {
            $this->error('只能选择您绑定的小区');
        }
        if ($scopeType === 'parent' && $memberParentId <= 0) {
            $this->error('请先完善所属社区信息');
        }
        if ($scopeType === 'parent') {
            $scopeId = $memberParentId;
        }
        if ($scopeType === 'all') $scopeId = 0;
        if (empty($images)) {
            $this->error('请至少上传一张图片');
        }

        $allowedCat = array_column(SecondhandService::categories(), 'key');
        if (!in_array($category, $allowedCat, true)) {
            $category = 'other';
        }
        $allowedCond = array_column(SecondhandService::conditions(), 'key');
        if (!in_array($condition, $allowedCond, true)) {
            $condition = 'used';
        }

        $now = time();
        $id = Db::name(SecondhandService::TABLE_ITEM)->insertGetId([
            'user_id' => $this->user_id,
            'title' => $title,
            'description' => $description,
            'images' => SecondhandService::encodeImages($images),
            'price' => number_format($price, 2, '.', ''),
            'contact' => $contact,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'category' => $category,
            'condition' => $condition,
            'area_text' => $areaText,
            'status' => SecondhandService::STATUS_PENDING,
            'reject_reason' => '',
            'create_time' => $now,
            'update_time' => $now,
            'audit_time' => 0,
        ]);

        $this->success('发布成功，请等待审核', ['id' => (int)$id]);
    }

    /** 我的发布 */
    public function my_list()
    {
        $this->checkUser();
        $page = max(1, (int)$this->request->param('page', 1));
        $pageSize = min(30, max(1, (int)$this->request->param('page_size', $this->page_size)));

        $paginator = Db::name(SecondhandService::TABLE_ITEM)->alias('i')
            ->leftJoin('member m', 'm.id = i.user_id')
            ->leftJoin('member_community c', 'c.id = i.scope_id and i.scope_type = "community"')
            ->leftJoin('member_community_parent p', 'p.id = i.scope_id and i.scope_type = "parent"')
            ->field('i.*,m.nickname as seller_nickname,m.avatar as seller_avatar,IF(i.scope_type="community",c.name,IF(i.scope_type="parent",p.name,"整个七贤岭")) as scope_name')
            ->where('i.user_id', $this->user_id)
            ->order('i.id desc')
            ->paginate(['list_rows' => $pageSize, 'page' => $page]);

        $items = [];
        foreach ($paginator->items() as $row) {
            $items[] = SecondhandService::formatItemForApi($row, true, true);
        }

        $this->success('获取成功', [
            'list' => $items,
            'total' => $paginator->total(),
            'page' => $page,
            'page_size' => $pageSize,
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /** 卖家下架 */
    public function off_shelf()
    {
        $this->checkUser();
        $id = (int)$this->request->param('id');
        if ($id <= 0) {
            $this->error('参数错误');
        }
        $row = Db::name(SecondhandService::TABLE_ITEM)->where('id', $id)->find();
        if (empty($row) || (int)$row['user_id'] !== (int)$this->user_id) {
            $this->error('无权操作');
        }
        if ((int)$row['status'] === SecondhandService::STATUS_CLOSED) {
            $this->error('已关闭');
        }

        Db::startTrans();
        try {
            SecondhandService::cancelActiveIntentsForItem($id);
            Db::name(SecondhandService::TABLE_ITEM)->where('id', $id)->update([
                'status' => SecondhandService::STATUS_CLOSED,
                'update_time' => time(),
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error('操作失败');
        }
        $this->success('已下架');
    }

    /** 我想要 — 登记交换意向 */
    public function create_intent()
    {
        $this->error('社区闲置由买卖双方自行联系，请查看物品详情中的联系方式');
    }
}
