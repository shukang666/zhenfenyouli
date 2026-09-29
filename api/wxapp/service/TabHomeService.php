<?php

namespace api\wxapp\service;

use api\wxapp\controller\AuthController;
use api\wxapp\model\CommonModel;
use app\admin\model\active\ActiveClockModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\active\ActiveSIgnModel;
use app\admin\model\active\MemberBadgeModel;
use api\wxapp\model\AnswerModel;
use app\admin\model\active\WasteModel;
use app\admin\model\active\WasteUserModel;
use app\admin\model\forum\ForumCommentModel;
use app\admin\model\forum\ForumLikeModel;
use app\admin\model\forum\ForumModel;
use app\admin\model\forum\ForumCateModel;
use app\admin\model\member\CommunityModel;
use app\admin\model\goods\GoodsCategoryModel;
use app\admin\model\goods\GoodsModel;
use app\admin\model\member\MemberModel;
use app\admin\model\SlideItemModel;
use api\wxapp\model\CouponModel;
use api\wxapp\model\UserCouponModel;
use think\facade\Db;
use think\facade\Request;

/**
 * 五个 TabBar 页聚合数据
 */
class TabHomeService
{
    /** @var AuthController */
    protected $auth;

    protected $userId = 0;

    public function __construct(AuthController $auth)
    {
        $this->auth = $auth;
        $this->userId = (int)$auth->user_id;
    }

    public function buildIndexTabHome(): array
    {
        $data = [
            'banner_list' => $this->fetchSlideList(1),
            'active_list' => $this->fetchHomePutActiveList(12),
            'showcase_list' => $this->fetchShowcasePreview(6),
            'home_entries' => $this->fetchOptionArray('home_entries'),
            'home_notice' => $this->fetchHomeNoticeText(),
        ];
        if ($this->userId > 0) {
            $data['sign_status'] = $this->fetchSignStatus();
            $data['user_info'] = $this->fetchIndexUserBrief();
        }
        return $data;
    }

    public function buildActivityTabHome(int $page, string $tag, int $pageSize): array
    {
        $withBlocks = ($page === 1 && $tag === '');
        $data = ['active_page' => $page];
        if ($withBlocks) {
            $data['banner_list'] = $this->fetchSlideList(9);
            $data['tag_list'] = $this->collectActiveTagList();
            $sections = $this->buildActivityPageSections();
            $data['weekly_list'] = $sections['weekly_list'];
            $data['light_list'] = $sections['light_list'];
            $data['recent_list'] = $sections['recent_list'];
        }
        $pager = $this->paginateActiveListForTab($page, $pageSize, $tag);
        $data['active_list'] = $pager['list'];
        $data['active_total'] = $pager['total'];
        $data['active_page_size'] = $pageSize;
        return $data;
    }

    public function buildHealthTabHome(int $page, string $keyword): array
    {
        $pageSize = 10;
        $where = [];
        $where[] = ['a.type', '=', 1];
        if ($keyword !== '') {
            $where[] = ['a.content|a.title', 'like', '%' . $keyword . '%'];
        }
        $forumModel = new ForumModel();
        $forumCateModel = new ForumCateModel();
        $communityModel = new CommunityModel();
        $query = $forumModel->alias('a')
            ->join('member b', 'a.user_id=b.id')
            ->where($where)
            ->order('a.id desc')
            ->field('a.id,title,a.type,image,a.status,a.create_time,user_id,nickname,
                avatar,view_num,cate_id,content,like_total,a.point,b.community_id,a.image_one,a.image_two,a.image_three');
        $total = (clone $query)->count();
        $rows = $query->page($page, $pageSize)->select();
        $list = [];
        foreach ($rows as $item) {
            $item['is_like'] = ForumLikeModel::where('rid', $item['id'])
                ->where('user_id', $this->userId)->where('type', 1)->count();
            $item['comment_total'] = ForumCommentModel::where('info_id', $item['id'])->count();
            $item['community_name'] = $communityModel->where('id', $item['community_id'])->value('name');
            if (empty($item['community_name'])) {
                $community = $communityModel->where(['status' => 1])->find();
                $item['community_name'] = $community['name'] ?? '';
            }
            if ((int)$item['type'] === 1) {
                $item['cate_name'] = $forumCateModel->where('id', $item['cate_id'])->value('name');
            }
            if (!isset($item['like_total']) || $item['like_total'] === null) {
                $item['like_total'] = 0;
            }
            $row = CommonModel::getOne($item, ['thumb', 'avatar', 'image_one', 'image_two', 'image_three'], ['image']);
            $list[] = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : (array)$row);
        }
        $data = [
            'forum_list' => $list,
            'forum_total' => $total,
            'forum_page' => $page,
            'forum_page_size' => $pageSize,
        ];
        if ($page === 1 && $keyword === '') {
            $cfg = cmf_get_new_option('set_config');
            if (!is_array($cfg)) {
                $cfg = [];
            }
            $minLogo = isset($cfg['min_logo']) ? (string)$cfg['min_logo'] : '';
            if ($minLogo !== '') {
                $minLogo = cmf_replace_content_file_url(htmlspecialchars_decode($minLogo));
                $minLogo = cmf_get_asset_url($minLogo);
            }
            $data['page_setting'] = [
                'min_logo' => $minLogo,
            ];
        }
        return $data;
    }

    public function buildMallTabHome(int $page, int $secondCate, string $keyword): array
    {
        $pageSize = 10;
        $data = ['goods_page' => $page];
        $withBlocks = ($page === 1 && $secondCate <= 0 && $keyword === '');
        if ($withBlocks) {
            $data['coupon_list'] = $this->fetchShopCouponList();
            $data['cate_list'] = $this->fetchGeoZoneCategories();
        }
        $goods = $this->fetchGoodsListPage($page, $pageSize, $secondCate, $keyword);
        $data['goods_list'] = $goods['list'];
        $data['goods_total'] = $goods['total'];
        $data['goods_page_size'] = $pageSize;
        return $data;
    }

    public function buildUserTabHome(): array
    {
        return ['logged_in' => false];
    }

    protected function calcNowContinueSignDays(array $timeStampArr): int
    {
        if (empty($timeStampArr)) {
            return 0;
        }
        $dayTsArr = array_map(function ($ts) {
            return strtotime(date('Y-m-d', $ts));
        }, $timeStampArr);
        $uniqueDayTs = array_unique($dayTsArr);
        sort($uniqueDayTs);
        $oneDaySecond = 86400;
        $lastTs = end($uniqueDayTs);
        $todayZero = strtotime(date('Y-m-d'));
        if ($lastTs < $todayZero - $oneDaySecond) {
            return 1;
        }
        $reverseList = array_reverse($uniqueDayTs);
        $prev = $reverseList[0];
        $count = 1;
        foreach ($reverseList as $ts) {
            if ($ts === $prev) {
                continue;
            }
            if ($prev - $ts === $oneDaySecond) {
                $count++;
                $prev = $ts;
            } else {
                break;
            }
        }
        return $count;
    }

    protected function fetchSlideList(int $slideId): array
    {
        $items = (new SlideItemModel())
            ->field('image,url,title,target')
            ->where(['slide_id' => $slideId, 'status' => 1])
            ->order('list_order')
            ->select();
        $out = [];
        foreach ($items as $row) {
            $item = is_array($row) ? $row : $row->toArray();
            $item['image'] = CommonModel::getOneImageFullUrl($item['image']);
            $out[] = $item;
        }
        return $out;
    }

    protected function fetchHomePutActiveList(int $limit): array
    {
        $query = ActiveModel::where($this->activeListPublicWhere())->where('is_put', '=', 1);
        return $this->fetchActiveListSlice($query, $limit);
    }

    protected function fetchShowcasePreview(int $limit): array
    {
        $list = Db::name('activity_showcase')
            ->where('status', 1)
            ->field('id, title, cover, description, content, event_date, photos, view_count, create_time')
            ->order('list_order asc, event_date desc, id desc')
            ->limit($limit)
            ->select()
            ->toArray();
        foreach ($list as &$item) {
            $photos = $this->parseShowcasePhotos($item['photos'] ?? '');
            $cover = $item['cover'] ?: ($photos[0] ?? '');
            $item['cover'] = $cover ? cmf_get_asset_url($cover) : '';
            $item['photo_count'] = count($photos);
            $item['event_date_text'] = !empty($item['event_date']) ? date('Y-m-d', $item['event_date']) : '';
            unset($item['content'], $item['photos']);
        }
        unset($item);
        return $list;
    }

    protected function parseShowcasePhotos($raw): array
    {
        if (empty($raw)) {
            return [];
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            return array_filter(array_map('trim', explode(',', $raw)));
        }
        return is_array($raw) ? $raw : [];
    }

    protected function fetchOptionArray(string $name): array
    {
        $value = cmf_get_new_option($name);
        return is_array($value) ? $value : [];
    }

    protected function fetchHomeNoticeText(): string
    {
        $value = cmf_get_new_option('home_notice');
        if (is_array($value)) {
            return (string)($value['text'] ?? '');
        }
        return '';
    }

    protected function fetchSignStatus(): array
    {
        $today = date('Y-m-d');
        $todaySigned = Db::name('user_sign')
            ->where('user_id', $this->userId)
            ->where('sign_date', $today)
            ->value('id') ? true : false;
        $signDates = Db::name('user_sign')
            ->where('user_id', $this->userId)
            ->column('create_time');
        $continueDays = $this->calcNowContinueSignDays($signDates);
        return [
            'today_signed' => $todaySigned,
            'continue_days' => $continueDays,
        ];
    }

    protected function fetchIndexUserBrief(): array
    {
        $member = MemberModel::where('id', $this->userId)
            ->field('id,nickname,avatar,point,is_environmental')
            ->find();
        if (empty($member)) {
            return [];
        }
        $row = is_array($member) ? $member : $member->toArray();
        if (!empty($row['avatar'])) {
            $row['avatar'] = cmf_get_asset_url($row['avatar']);
        }
        $row['is_answer'] = (new AnswerModel())
            ->where('status', 3)
            ->where('type', 1)
            ->where('user_id', $this->userId)
            ->count() ? 1 : 0;
        $waste_count = WasteModel::count();
        $user_count = WasteUserModel::where('user_id', $this->userId)->count();
        $row['is_learn'] = ($waste_count > 0 && $user_count >= $waste_count) ? 1 : 0;
        $forumTimes = ForumModel::where(['type' => 2, 'user_id' => $this->userId])->column('create_time');
        $row['clock_count'] = $this->calcNowContinueSignDays($forumTimes);
        $row['clock_days'] = $row['clock_count'];
        return $row;
    }

    protected function fetchFullUserInfo(): array
    {
        $memberModel = new MemberModel();
        $user = $memberModel->where(['id' => $this->userId])
            ->field('id,nickname,true_name,mobile,avatar,openid,level,community_id,shop_balance_total,shop_balance,point,shop_balance balance,is_environmental,is_supervisor,is_merchant,community_parent_id')
            ->find();
        if (empty($user)) {
            return [];
        }
        $user = is_array($user) ? $user : $user->toArray();
        if (!empty($user['avatar'])) {
            $user['avatar'] = cmf_get_asset_url($user['avatar']);
        }
        return $user;
    }

    protected function fetchUserBadgeList(): array
    {
        $list = MemberBadgeModel::where('user_id', $this->userId)->order('id desc')->select();
        $out = [];
        foreach ($list as $item) {
            $row = is_array($item) ? $item : $item->toArray();
            $row['image'] = empty($row['image']) ? '' : cmf_get_asset_url($row['image']);
            $out[] = $row;
        }
        return $out;
    }

    protected function fetchGeoZoneCategories(): array
    {
        $parentId = GoodsCategoryModel::where([
            ['parent_id', '=', 0],
            ['cate_name', '=', '地理产区'],
            ['delete_time', '=', 0],
        ])->value('id');
        if (empty($parentId)) {
            return [];
        }
        $list = GoodsCategoryModel::field('id,cate_name,thumb,parent_id')
            ->where(['status' => 1, 'parent_id' => $parentId])
            ->order('list_order')
            ->select();
        $list = getFullData($list);
        $out = [];
        foreach ($list as $item) {
            $out[] = is_array($item) ? $item : $item->toArray();
        }
        return $out;
    }

    protected function fetchShopCouponList(): array
    {
        $couponModel = new CouponModel();
        $map = [];
        $map[] = ['a.status', '=', 1];
        $list = $couponModel->alias('a')
            ->field('a.id,a.name,a.amount,a.full_amount,c.shop_name,indate_day,c.id as shop_id')
            ->leftjoin('shop c', 'a.user_id = c.user_id')
            ->where($map)->order('a.id desc')->limit(20)->select();
        $out = [];
        foreach ($list as $item) {
            $row = is_array($item) ? $item : $item->toArray();
            $ucMap = ['user_id' => $this->userId, 'coupon_id' => $row['id']];
            $row['status'] = (new UserCouponModel())->where($ucMap)->count() ? 1 : 0;
            $out[] = $row;
        }
        return $out;
    }

    protected function fetchGoodsListPage(int $page, int $pageSize, int $secondCate, string $keyword): array
    {
        $GoodsModel = new GoodsModel();
        $map = [];
        $map[] = ['a.status', '=', 1];
        $map[] = ['check_status', '=', 2];
        $map[] = ['a.delete_time', '=', 0];
        if ($keyword !== '') {
            $map[] = ['a.goods_name', 'like', '%' . $keyword . '%'];
        }
        if ($secondCate > 0) {
            $map[] = ['a.second_cate', '=', $secondCate];
        }
        $order = 'a.list_order,a.id desc';
        Request::withGet(array_merge(Request::get(), ['page' => $page]));
        $list = $GoodsModel->getInfo($map, $pageSize, 0, GoodsModel::getListField(), $order);
        if (is_object($list) && method_exists($list, 'total')) {
            $items = $list->items();
            if (is_object($items) && method_exists($items, 'all')) {
                $items = $items->all();
            }
            return ['list' => $items ?: [], 'total' => (int)$list->total()];
        }
        return ['list' => $list ?: [], 'total' => is_countable($list) ? count($list) : 0];
    }

    protected function activeListPublicWhere(): array
    {
        return [
            ['type', '=', 1],
            ['status', '=', 1],
            ['delete_time', '=', 0],
            ['join_type', '=', 1],
        ];
    }

    protected function fetchActiveListSlice($query, $limit, $order = 'list_order asc, id desc'): array
    {
        $rows = $query->order($order)->limit($limit)->select();
        $out = [];
        foreach ($rows as $item) {
            $out[] = $this->applyActiveListItemFields($item);
        }
        return $out;
    }

    protected function collectActiveTagList(): array
    {
        $allTags = ActiveModel::where('type', 1)->where('status', 1)->where('delete_time', 0)
            ->whereNotNull('tags')->where('tags', '<>', '')->column('tags');
        $tagSet = [];
        foreach ($allTags as $tagStr) {
            $tags = array_filter(array_map('trim', explode(',', $tagStr)));
            foreach ($tags as $t) {
                $tagSet[$t] = true;
            }
        }
        $tagList = array_keys($tagSet);
        sort($tagList);
        return $tagList;
    }

    protected function buildActivityPageSections(): array
    {
        $where = $this->activeListPublicWhere();
        $lightTag = '轻活动';
        $weeklyQuery = ActiveModel::where($where)->where('is_month_recommend', '=', 1);
        $lightQuery = ActiveModel::where($where)->whereRaw('FIND_IN_SET(?, tags)', [$lightTag]);
        $recentQuery = ActiveModel::where($where)->whereRaw(
            '(tags IS NULL OR tags = "" OR NOT FIND_IN_SET(?, tags))',
            [$lightTag]
        );
        return [
            'weekly_list' => $this->fetchActiveListSlice($weeklyQuery, 10),
            'light_list' => $this->fetchActiveListSlice($lightQuery, 3),
            'recent_list' => $this->fetchActiveListSlice($recentQuery, 2, 'date desc, id desc'),
        ];
    }

    protected function paginateActiveListForTab($page, $pageSize, $tag = ''): array
    {
        $query = ActiveModel::where($this->activeListPublicWhere());
        if ($tag !== '') {
            $query->whereRaw('FIND_IN_SET(?, tags)', [$tag]);
        }
        $total = (clone $query)->count();
        $rows = $query->order('list_order asc, id desc')->page($page, $pageSize)->select();
        $list = [];
        foreach ($rows as $item) {
            $list[] = $this->applyActiveListItemFields($item);
        }
        return ['list' => $list, 'total' => $total];
    }

    protected function applyActiveListItemFields($item)
    {
        $item['thumb'] = empty($item['thumb']) ? '' : cmf_get_asset_url($item['thumb']);
        $item['badge_image'] = empty($item['badge_image']) ? '' : cmf_get_asset_url($item['badge_image']);
        $item['content'] = htmlspecialchars_decode($item['content']);
        $item['send_point'] = $item['send_point'] ?? 0;

        $active_count = ActiveSIgnModel::where('active_id', $item['id'])->count();
        $item['active_count'] = $active_count;
        $avatar_array = ActiveSIgnModel::alias('a')
            ->join('member b', 'a.user_id=b.id')
            ->where('a.active_id', $item['id'])->limit(5)->column('b.avatar');
        foreach ($avatar_array as &$value) {
            $value = empty($value) ? '' : cmf_get_asset_url($value);
        }
        $item['avatar_array'] = $avatar_array;

        $sign_info = ActiveSIgnModel::where('active_id', $item['id'])->where('user_id', $this->userId)->find();
        $item['sign_id'] = empty($sign_info) ? 0 : $sign_info['id'];
        $item['is_sign'] = empty($sign_info) ? 0 : 1;

        $date_time = strtotime(date('Y-m-d 00:00:00'));
        $clockWhere = [];
        $clockWhere[] = ['active_id', '=', $item['id']];
        $clockWhere[] = ['user_id', '=', $this->userId];
        $clockWhere[] = ['sign_id', '=', $item['sign_id']];
        $clockWhere[] = ['date_time', '=', $date_time];
        $clock_info = ActiveClockModel::where($clockWhere)->find();
        $item['is_sign_clock'] = empty($clock_info) ? 0 : 1;

        $item['is_date_pending'] = (int)($item['is_date_pending'] ?? 0);
        $item['is_end'] = ActiveModel::resolveIsEnd($item);
        return $item;
    }
}
