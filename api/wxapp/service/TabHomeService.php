<?php

namespace api\wxapp\service;

use api\wxapp\controller\AuthController;
use api\wxapp\model\CommonModel;
use app\admin\model\active\ActiveClockModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\active\ActiveInterestModel;
use app\admin\model\active\ActiveSIgnModel;
use app\admin\service\ActivityShowcaseSyncService;
use app\admin\model\active\MemberBadgeModel;
use api\wxapp\model\AnswerModel;
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
            // 近期活动区暂时下线，不返回 recent_list
        }
        // 底部「全部活动」列表暂时下线，不查分页列表
        $data['active_list'] = [];
        $data['active_total'] = 0;
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
            $data['health_hub'] = $this->fetchHealthTabHubConfig();
        }
        return $data;
    }

    /**
     * 健康 Tab 顶部 Banner + 双入口（cmf_option: health_tab_config）
     */
    protected function fetchHealthTabHubConfig(): array
    {
        $raw = cmf_get_option('health_tab_config');
        if (!is_array($raw) || empty($raw['banner'])) {
            $raw = \app\admin\controller\HealthTabConfigController::defaultConfig();
        }
        if (empty($raw['shortcuts']) || !is_array($raw['shortcuts'])) {
            $raw['shortcuts'] = [];
        }

        $banner = $raw['banner'];
        if (!empty($banner['image'])) {
            $banner['image'] = cmf_get_asset_url($banner['image']);
        }
        $banner['title'] = (string)($banner['title'] ?? '');
        $banner['subtitle'] = (string)($banner['subtitle'] ?? '');
        $banner['link_mode'] = (string)($banner['link_mode'] ?? 'survey');
        if ($banner['link_mode'] === 'exam') {
            $banner['link_mode'] = 'survey';
        }
        $banner['survey_id'] = (int)($banner['survey_id'] ?? 0);
        if ($banner['survey_id'] <= 0) {
            $banner['survey_id'] = (int)($banner['exam_id'] ?? 0);
        }
        unset($banner['exam_id']);
        $banner['url'] = (string)($banner['url'] ?? '');
        $banner['route_type'] = (string)($banner['route_type'] ?? 'navigateTo');

        $shortcuts = [];
        foreach ($raw['shortcuts'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $shortcuts[] = [
                'key'        => (string)($item['key'] ?? ''),
                'name'       => (string)($item['name'] ?? ''),
                'desc'       => (string)($item['desc'] ?? ''),
                'icon'       => (string)($item['icon'] ?? 'grid'),
                'url'        => (string)($item['url'] ?? ''),
                'route_type' => (string)($item['route_type'] ?? 'navigateTo'),
            ];
        }

        return [
            'banner'    => $banner,
            'shortcuts' => $shortcuts,
        ];
    }

    public function buildMallTabHome(int $page, int $secondCate, int $firstCate, string $keyword): array
    {
        $pageSize = 10;
        $data = ['goods_page' => $page];
        $withBlocks = ($page === 1 && $secondCate <= 0 && $firstCate <= 0 && $keyword === '');
        if ($withBlocks) {
            $data['coupon_list'] = $this->fetchShopCouponList();
            $data['cate_list'] = $this->fetchGeoZoneCategories();
            $data['first_cate_list'] = $this->fetchMallFirstCategories();
        }
        $goods = $this->fetchGoodsListPage($page, $pageSize, $secondCate, $firstCate, $keyword);
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
        $query = ActiveModel::miniProgramVisible()->where('is_put', '=', 1);
        return $this->fetchActiveListSlice($query, $limit);
    }

    protected function fetchShowcasePreview(int $limit): array
    {
        ActivityShowcaseSyncService::syncEligibleEndedActives(15);

        $list = Db::name('activity_showcase')
            ->where('status', 1)
            ->where('active_id', 0)
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
        if (!is_array($value)) {
            return [];
        }
        if ($name === 'home_entries') {
            foreach ($value as $idx => $item) {
                if (!is_array($item) || empty($item['image'])) {
                    continue;
                }
                $value[$idx]['image'] = cmf_get_asset_url($item['image']);
            }
        }
        return $value;
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
            ->field('id,nickname,avatar,point,is_environmental,clock_pre_learn')
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
        $row['is_learn'] = !empty($row['clock_pre_learn']) ? 1 : 0;
        unset($row['clock_pre_learn']);
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

    /** 积分商城一级分类（不含「地理产区」） */
    protected function fetchMallFirstCategories(): array
    {
        $list = GoodsCategoryModel::field('id,cate_name')
            ->where([
                ['parent_id', '=', 0],
                ['status', 'in', [1, 2]],
                ['cate_name', '<>', '地理产区'],
                ['delete_time', '=', 0],
            ])
            ->order('list_order asc,id asc')
            ->select();
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

    /** @param array<int,array<string,mixed>> $items */
    protected static function normalizeMallGoodsListItems(array $items): array
    {
        foreach ($items as &$item) {
            $key = trim((string)($item['require_web_unlock_key'] ?? ''));
            $item['is_web_activity_goods'] = $key !== '' ? 1 : 0;
            $item['web_unlock_active_name'] = $key !== '' ? GoodsWebUnlockService::activeNameForKey($key) : '';
        }
        unset($item);
        return $items;
    }

    protected function fetchGoodsListPage(int $page, int $pageSize, int $secondCate, int $firstCate, string $keyword): array
    {
        $GoodsModel = new GoodsModel();
        $map = [];
        $map[] = ['a.status', '=', 1];
        $map[] = ['check_status', '=', 2];
        $map[] = ['a.delete_time', '=', 0];
        if ($keyword !== '') {
            $map[] = ['a.goods_name', 'like', '%' . $keyword . '%'];
        }
        if ($firstCate > 0) {
            $map[] = ['a.first_cate', '=', $firstCate];
        }
        if ($secondCate > 0) {
            $map[] = ['a.second_cate', '=', $secondCate];
        }
        // list_order 升序：数值越小越靠前；同序再按 id 降序
        $order = 'a.list_order asc,a.id desc';
        Request::withGet(array_merge(Request::get(), ['page' => $page]));
        $list = $GoodsModel->getInfo($map, $pageSize, 0, GoodsModel::getListField(), $order);
        if (is_object($list) && method_exists($list, 'total')) {
            $items = $list->items();
            if (is_object($items) && method_exists($items, 'all')) {
                $items = $items->all();
            }
            $items = self::normalizeMallGoodsListItems($items ?: []);
            return ['list' => $items, 'total' => (int)$list->total()];
        }
        $plain = self::normalizeMallGoodsListItems(is_array($list) ? $list : []);
        return ['list' => $plain, 'total' => count($plain)];
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
        $allTags = ActiveModel::miniProgramVisible()
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
        $lightTag = '轻活动';
        $weeklyQuery = ActiveModel::miniProgramVisible()->where('is_month_recommend', '=', 1);
        $lightQuery = ActiveModel::miniProgramVisible()->whereRaw('FIND_IN_SET(?, tags)', [$lightTag]);
        return [
            'weekly_list' => $this->fetchActiveListSlice($weeklyQuery, 10),
            'light_list' => $this->fetchActiveListSlice($lightQuery, 3),
        ];
    }

    protected function paginateActiveListForTab($page, $pageSize, $tag = ''): array
    {
        $query = ActiveModel::miniProgramVisible();
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

        $item['active_count'] = ActiveSIgnModel::countParticipants((int) $item['id']);
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
        $item['interest_count'] = ActiveInterestModel::countByActive((int) $item['id']);
        $item['is_interested'] = 0;
        if ($this->userId && ActiveInterestModel::isInterested((int) $item['id'], (int) $this->userId)) {
            $item['is_interested'] = 1;
        }
        $item['is_end'] = ActiveModel::resolveIsEnd($item);
        $this->attachWebLaunchFields($item);
        return $item;
    }

    /** 列表项附带 Web 式打开地址（与 ActiveController 详情逻辑一致） */
    protected function attachWebLaunchFields(&$item): void
    {
        $item['display_mode'] = (int)($item['display_mode'] ?? ActiveModel::DISPLAY_MODE_CONFIG);
        $item['web_url'] = trim((string)($item['web_url'] ?? ''));
        $item['web_page_url'] = '';
        $item['web_launch_url'] = '';
        if (!ActiveModel::isWebDisplay($item)) {
            return;
        }
        $activeId = (int)$item['id'];
        $base = ActiveModel::buildWebActivityUrl($item['web_url'], $activeId);
        $item['web_page_url'] = $base;
        $launch = $base;
        if ($this->userId > 0) {
            $token = ActiveWebContext::issue((int)$this->userId, $activeId);
            $launch = ActiveWebContext::appendQueryParam($launch, 'web_ctx', $token);
        }
        $item['web_launch_url'] = $launch;
    }
}
