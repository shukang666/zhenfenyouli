<?php

namespace api\wxapp\controller;

use api\wxapp\service\SecondhandService;
use app\admin\model\member\MemberModel;
use think\facade\Db;

class SecondhandController extends AuthController
{
    /** 分类/成色元数据 */
    public function meta()
    {
        $this->success('获取成功', [
            'categories' => array_merge([['key' => 'all', 'label' => '全部']], SecondhandService::categories()),
            'conditions' => SecondhandService::conditions(),
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
            ->field('i.*,m.nickname as seller_nickname,m.avatar as seller_avatar')
            ->where('i.status', SecondhandService::STATUS_ON_SALE);

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
            ->field('i.*,m.nickname as seller_nickname,m.avatar as seller_avatar')
            ->where('i.id', $id)
            ->find();
        if (empty($row)) {
            $this->error('物品不存在');
        }
        $status = (int)$row['status'];
        $isOwner = $this->user_id && (int)$row['user_id'] === (int)$this->user_id;
        if (!$isOwner && !in_array($status, [SecondhandService::STATUS_ON_SALE, SecondhandService::STATUS_RESERVED], true)) {
            $this->error('物品已下架');
        }

        $data = SecondhandService::formatItemForApi($row);
        $data['is_owner'] = $isOwner;
        $data['my_intent'] = null;
        if ($this->user_id) {
            $intent = Db::name(SecondhandService::TABLE_INTENT)
                ->where('item_id', $id)
                ->where('buyer_user_id', $this->user_id)
                ->whereIn('status', [SecondhandService::INTENT_REGISTERED, SecondhandService::INTENT_WAIT_HANDOVER])
                ->order('id desc')
                ->find();
            if ($intent) {
                $data['my_intent'] = [
                    'id' => (int)$intent['id'],
                    'status' => (int)$intent['status'],
                ];
            }
        }
        $this->success('获取成功', $data);
    }

    /** 发布闲置 */
    public function publish()
    {
        $this->checkUser();

        $title = trim((string)$this->request->param('title', ''));
        $description = trim((string)$this->request->param('description', ''));
        $pointPrice = (int)$this->request->param('point_price', 0);
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
        if ($pointPrice < 1) {
            $this->error('请填写正确的积分价格');
        }
        if ($pointPrice > 99999) {
            $this->error('积分价格过高');
        }
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
            'point_price' => $pointPrice,
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
            ->field('i.*,m.nickname as seller_nickname,m.avatar as seller_avatar')
            ->where('i.user_id', $this->user_id)
            ->order('i.id desc')
            ->paginate(['list_rows' => $pageSize, 'page' => $page]);

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
        $this->checkUser();
        $itemId = (int)$this->request->param('item_id');
        if ($itemId <= 0) {
            $this->error('参数错误');
        }

        $item = Db::name(SecondhandService::TABLE_ITEM)->where('id', $itemId)->find();
        if (empty($item)) {
            $this->error('物品不存在');
        }
        if ((int)$item['user_id'] === (int)$this->user_id) {
            $this->error('不能预约自己的闲置');
        }
        if ((int)$item['status'] !== SecondhandService::STATUS_ON_SALE) {
            $this->error('该物品当前不可预约');
        }

        $price = (int)$item['point_price'];
        $member = MemberModel::where('id', $this->user_id)->field('id,point')->find();
        if (empty($member)) {
            $this->error('用户不存在');
        }
        if ((float)$member['point'] < $price) {
            $this->error('积分不足，无法登记意向（交割时将在市集现场扣减）');
        }

        $exists = Db::name(SecondhandService::TABLE_INTENT)
            ->where('item_id', $itemId)
            ->where('buyer_user_id', $this->user_id)
            ->whereIn('status', [SecondhandService::INTENT_REGISTERED, SecondhandService::INTENT_WAIT_HANDOVER])
            ->find();
        if ($exists) {
            $this->success('您已登记过意向', ['intent_id' => (int)$exists['id']]);
            return;
        }

        $activeOther = Db::name(SecondhandService::TABLE_INTENT)
            ->where('item_id', $itemId)
            ->whereIn('status', [SecondhandService::INTENT_REGISTERED, SecondhandService::INTENT_WAIT_HANDOVER])
            ->find();
        if ($activeOther) {
            $this->error('该物品已被他人预约');
        }

        $now = time();
        Db::startTrans();
        try {
            $intentId = Db::name(SecondhandService::TABLE_INTENT)->insertGetId([
                'item_id' => $itemId,
                'buyer_user_id' => $this->user_id,
                'seller_user_id' => (int)$item['user_id'],
                'point_price' => $price,
                'status' => SecondhandService::INTENT_REGISTERED,
                'create_time' => $now,
                'update_time' => $now,
                'complete_time' => 0,
            ]);
            Db::name(SecondhandService::TABLE_ITEM)->where('id', $itemId)->update([
                'status' => SecondhandService::STATUS_RESERVED,
                'update_time' => $now,
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error('登记失败，请稍后再试');
        }

        $this->success('已登记意向，请按说明前往绿色二手市集现场交接', ['intent_id' => (int)$intentId]);
    }
}
