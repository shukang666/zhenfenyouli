<?php

namespace api\wxapp\controller;

use api\wxapp\service\ActiveWebPhotoService;
use app\admin\model\active\ActiveModel;
use app\admin\model\member\MemberModel;
use think\facade\Db;

class ActiveWebPhotoController extends AuthController
{
    protected function requireWebPhotoActive(int $activeId): array
    {
        $active = ActiveWebPhotoService::loadWebPhotoActive($activeId);
        if (empty($active)) {
            $this->error('活动不存在或未启用 Web 式');
        }
        return $active;
    }

    protected function formatPhotoRow(array $row, bool $withUser = false): array
    {
        $row['image'] = empty($row['image']) ? '' : cmf_get_asset_url($row['image']);
        $row['like_count'] = (int)($row['like_count'] ?? 0);
        $row['category_label'] = ActiveWebPhotoService::categoryLabels()[$row['category'] ?? ''] ?? '其他';
        if ($withUser && !empty($row['user_id'])) {
            $m = MemberModel::where('id', $row['user_id'])->field('nickname,avatar')->find();
            $row['author_nickname'] = $m ? (string)$m['nickname'] : '用户';
            $row['author_avatar'] = ($m && $m['avatar']) ? cmf_get_asset_url($m['avatar']) : '';
        }
        return $row;
    }

    protected function buildShareUrls(int $activeId, string $shareToken, string $page, array $extraQuery = []): array
    {
        $base = rtrim(cmf_get_domain(), '/');
        $q = array_merge(['active_id' => $activeId, 'share' => $shareToken], $extraQuery);
        $h5 = $base . '/static/mp-active-web/qxl-photo-share/index.html?' . http_build_query($q) . $page;
        $mp = '/pages/h5/h5?url=' . rawurlencode($h5);
        return ['h5_url' => $h5, 'mp_path' => $mp];
    }

    /** 活动首页：周报入口 + 照片流 + 上传配额 */
    public function home()
    {
        $activeId = (int)$this->request->param('active_id');
        $active = $this->requireWebPhotoActive($activeId);

        $report = Db::name(ActiveWebPhotoService::reportTable())
            ->where('active_id', $activeId)
            ->order('id desc')
            ->find();

        $uploadLeft = ActiveWebPhotoService::MAX_UPLOAD_PER_DAY;
        $pointLeft = ActiveWebPhotoService::MAX_POINTS_PER_DAY;
        if ($this->user_id) {
            $uploadLeft = max(0, ActiveWebPhotoService::MAX_UPLOAD_PER_DAY - ActiveWebPhotoService::countTodayUploads($this->user_id, $activeId));
            $pointLeft = max(0, ActiveWebPhotoService::MAX_POINTS_PER_DAY - ActiveWebPhotoService::sumTodayPoints($this->user_id, $activeId));
        }

        $reportOut = null;
        if ($report) {
            $reportOut = [
                'id' => (int)$report['id'],
                'period_key' => (string)$report['period_key'],
                'title' => (string)$report['title'],
                'cover_image' => empty($report['cover_image']) ? '' : cmf_get_asset_url($report['cover_image']),
                'is_home_featured' => (int)($report['is_home_featured'] ?? 0),
            ];
            if (!empty($report['share_token'])) {
                $reportOut['share'] = $this->buildShareUrls($activeId, $report['share_token'], '#report/' . $report['id']);
            }
        }

        $this->success('请求成功', [
            'active_name' => (string)$active['name'],
            'categories' => ActiveWebPhotoService::categoryLabels(),
            'limits' => [
                'max_upload_per_day' => ActiveWebPhotoService::MAX_UPLOAD_PER_DAY,
                'max_points_per_day' => ActiveWebPhotoService::MAX_POINTS_PER_DAY,
                'upload_left_today' => $uploadLeft,
                'points_left_today' => $pointLeft,
            ],
            'latest_report' => $reportOut,
            'period_key' => ActiveWebPhotoService::periodKey(),
        ]);
    }

    /** 已通过照片列表 */
    public function list_photos()
    {
        $activeId = (int)$this->request->param('active_id');
        $this->requireWebPhotoActive($activeId);
        $page = max(1, (int)$this->request->param('page', 1));
        $pageSize = min(20, max(5, (int)$this->request->param('page_size', 10)));
        $category = trim((string)$this->request->param('category', ''));

        $query = Db::name(ActiveWebPhotoService::photoTable())
            ->where('active_id', $activeId)
            ->where('status', 1);
        if ($category !== '') {
            $query->where('category', $category);
        }
        $paginator = $query->order('like_count desc,id desc')->paginate(['page' => $page, 'list_rows' => $pageSize]);
        $list = [];
        foreach ($paginator->items() as $row) {
            $item = is_array($row) ? $row : $row->toArray();
            $list[] = $this->formatPhotoRow($item, true);
        }
        $this->success('请求成功', ['list' => $list, 'total' => $paginator->total()]);
    }

    /** 照片详情（分享链接可不带登录） */
    public function detail()
    {
        $activeId = (int)$this->request->param('active_id');
        $photoId = (int)$this->request->param('photo_id');
        $share = trim((string)$this->request->param('share', ''));
        $this->requireWebPhotoActive($activeId);

        $row = Db::name(ActiveWebPhotoService::photoTable())->where('id', $photoId)->where('active_id', $activeId)->find();
        if (empty($row)) {
            $this->error('内容不存在');
        }
        if ((int)$row['status'] !== 1) {
            if (!$this->user_id || (int)$row['user_id'] !== (int)$this->user_id) {
                $this->error('内容审核中或不可用');
            }
        }
        if ($share !== '' && $share !== (string)$row['share_token']) {
            $this->error('分享链接无效');
        }

        $item = $this->formatPhotoRow($row, true);
        $item['liked'] = 0;
        if ($this->user_id) {
            $item['liked'] = Db::name(ActiveWebPhotoService::likeTable())
                ->where('photo_id', $photoId)->where('user_id', $this->user_id)->count() ? 1 : 0;
        }
        $item['share'] = $this->buildShareUrls($activeId, (string)$row['share_token'], '#photo/' . $photoId, ['photo_id' => $photoId]);
        $this->success('请求成功', $item);
    }

    /** 提交上传（待审） */
    public function submit()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $active = $this->requireWebPhotoActive($activeId);

        if (ActiveWebPhotoService::countTodayUploads($this->user_id, $activeId) >= ActiveWebPhotoService::MAX_UPLOAD_PER_DAY) {
            $this->error('今日上传已达上限（' . ActiveWebPhotoService::MAX_UPLOAD_PER_DAY . ' 张）');
        }

        $image = trim((string)$this->request->param('image', ''));
        $intro = trim((string)$this->request->param('intro', ''));
        $category = trim((string)$this->request->param('category', 'other'));
        $labels = ActiveWebPhotoService::categoryLabels();
        if (!isset($labels[$category])) {
            $category = 'other';
        }
        if ($image === '') {
            $this->error('请上传照片');
        }
        if ($intro === '') {
            $this->error('请填写简要介绍');
        }
        if (mb_strlen($intro) > 500) {
            $this->error('介绍不超过 500 字');
        }

        $now = time();
        $token = ActiveWebPhotoService::newShareToken();
        $id = Db::name(ActiveWebPhotoService::photoTable())->insertGetId([
            'active_id' => $activeId,
            'user_id' => $this->user_id,
            'image' => $image,
            'intro' => $intro,
            'category' => $category,
            'status' => 0,
            'like_count' => 0,
            'period_key' => ActiveWebPhotoService::periodKey($now),
            'point_awarded' => 0,
            'bonus_point_awarded' => 0,
            'share_token' => $token,
            'create_time' => $now,
            'audit_time' => 0,
        ]);

        $share = $this->buildShareUrls($activeId, $token, '#photo/' . $id, ['photo_id' => $id]);
        $this->success('上传成功，请等待审核', [
            'photo_id' => $id,
            'status' => 0,
            'share' => $share,
            'message' => '审核通过后将展示在专题区，并可获积分（每日有上限）',
        ]);
    }

    /** 点赞 */
    public function like()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $photoId = (int)$this->request->param('photo_id');
        $this->requireWebPhotoActive($activeId);

        $row = Db::name(ActiveWebPhotoService::photoTable())
            ->where('id', $photoId)->where('active_id', $activeId)->where('status', 1)->find();
        if (empty($row)) {
            $this->error('照片不存在或未通过审核');
        }
        if ((int)$row['user_id'] === (int)$this->user_id) {
            $this->error('不能给自己的作品点赞');
        }

        $exists = Db::name(ActiveWebPhotoService::likeTable())
            ->where('photo_id', $photoId)->where('user_id', $this->user_id)->find();
        if ($exists) {
            $this->error('您已经点过赞了');
        }

        Db::name(ActiveWebPhotoService::likeTable())->insert([
            'photo_id' => $photoId,
            'user_id' => $this->user_id,
            'active_id' => $activeId,
            'create_time' => time(),
        ]);
        $count = ActiveWebPhotoService::refreshLikeCount($photoId);

        $bonusGranted = 0;
        if ($count >= ActiveWebPhotoService::BONUS_LIKE_THRESHOLD && (int)$row['bonus_point_awarded'] === 0) {
            $bonus = ActiveWebPhotoService::grantPoints(
                (int)$row['user_id'],
                $activeId,
                ActiveWebPhotoService::BONUS_LIKE_POINTS,
                '七贤岭摄影·高赞奖励'
            );
            if ($bonus > 0) {
                Db::name(ActiveWebPhotoService::photoTable())->where('id', $photoId)->update([
                    'bonus_point_awarded' => $bonus,
                ]);
                $bonusGranted = $bonus;
            }
        }

        $this->success('点赞成功', [
            'like_count' => $count,
            'author_bonus_point' => $bonusGranted,
        ]);
    }

    /** 我的上传 */
    public function my_list()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $this->requireWebPhotoActive($activeId);

        $rows = Db::name(ActiveWebPhotoService::photoTable())
            ->where('active_id', $activeId)
            ->where('user_id', $this->user_id)
            ->order('id desc')
            ->limit(50)
            ->select()
            ->toArray();
        $list = [];
        foreach ($rows as $row) {
            $list[] = $this->formatPhotoRow($row);
        }
        $statusMap = [0 => '待审核', 1 => '已通过', 2 => '已拒绝'];
        foreach ($list as &$item) {
            $item['status_text'] = $statusMap[(int)$item['status']] ?? '';
        }
        unset($item);
        $this->success('请求成功', ['list' => $list]);
    }

    /** 周报详情 */
    public function report_detail()
    {
        $activeId = (int)$this->request->param('active_id');
        $reportId = (int)$this->request->param('report_id');
        $share = trim((string)$this->request->param('share', ''));
        $this->requireWebPhotoActive($activeId);

        $report = Db::name(ActiveWebPhotoService::reportTable())
            ->where('id', $reportId)->where('active_id', $activeId)->find();
        if (empty($report)) {
            $this->error('周报不存在');
        }
        if ($share !== '' && $share !== (string)$report['share_token']) {
            $this->error('分享链接无效');
        }

        $photoIds = json_decode($report['photo_ids'] ?? '[]', true);
        if (!is_array($photoIds)) {
            $photoIds = [];
        }
        $photos = [];
        if ($photoIds) {
            $rows = Db::name(ActiveWebPhotoService::photoTable())
                ->where('id', 'in', $photoIds)->where('status', 1)->select()->toArray();
            foreach ($rows as $row) {
                $photos[] = $this->formatPhotoRow($row, true);
            }
        }

        $out = [
            'id' => (int)$report['id'],
            'period_key' => (string)$report['period_key'],
            'title' => (string)$report['title'],
            'summary_html' => (string)$report['summary_html'],
            'cover_image' => empty($report['cover_image']) ? '' : cmf_get_asset_url($report['cover_image']),
            'photos' => $photos,
            'share' => $this->buildShareUrls($activeId, (string)$report['share_token'], '#report/' . $reportId, ['report_id' => $reportId]),
        ];
        $this->success('请求成功', $out);
    }
}
