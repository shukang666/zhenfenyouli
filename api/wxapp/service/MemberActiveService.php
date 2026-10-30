<?php

namespace api\wxapp\service;

use app\admin\model\active\ActiveModel;
use app\admin\model\active\ActiveSIgnModel;
use app\admin\model\active\ActiveClockModel;
use think\facade\Db;

/**
 * 活动 Tab「玩转活动」· 居民自发配置式线下活动
 */
class MemberActiveService
{
    public const AUDIT_NONE = 0;
    public const AUDIT_PENDING = 1;
    public const AUDIT_APPROVED = 2;
    public const AUDIT_REJECTED = 3;

    public const TAG_MEMBER = '居民活动';

    public static function hasParticipationSchema(): bool
    {
        static $ok = null;
        if ($ok !== null) {
            return $ok;
        }
        try {
            $fields = Db::name('active')->getTableFields();
            $ok = in_array('publish_allow_signup', $fields, true)
                && in_array('publish_require_checkin', $fields, true);
        } catch (\Throwable $e) {
            $ok = false;
        }
        return $ok;
    }

    /** @param array<string,mixed>|object $row */
    public static function rowToArray($row): array
    {
        if (is_array($row)) {
            return $row;
        }
        if (is_object($row) && method_exists($row, 'toArray')) {
            return $row->toArray();
        }
        return (array)$row;
    }

    /** @param array<string,mixed>|object $row */
    public static function isPublishAllowSignup($row): bool
    {
        $a = self::rowToArray($row);
        if ((int)($a['publish_user_id'] ?? 0) <= 0) {
            return true;
        }
        if (!self::hasParticipationSchema()) {
            return false;
        }
        return (int)($a['publish_allow_signup'] ?? 0) === 1;
    }

    /** @param array<string,mixed>|object $row */
    public static function isPublishRequireCheckin($row): bool
    {
        $a = self::rowToArray($row);
        if ((int)($a['publish_user_id'] ?? 0) <= 0) {
            return true;
        }
        if (!self::hasParticipationSchema() || !self::isPublishAllowSignup($a)) {
            return false;
        }
        return (int)($a['publish_require_checkin'] ?? 0) === 1;
    }

    /** 存量 SQL 回填文案（与 update.sql 一致；写入 publish_proxy_notice 后请在后台按活动改） */
    private const STOCK_MEMBER_NOTICE_BACKFILL = '本活动由邻友代理发布展示，并非平台官方举办活动。平台不提供活动场地、物料与现场支持，也不承担活动组织与安全保障责任。如需了解详情或参与安排，请自行联系活动发布者/主办方。';

    /** 从旧版 content HTML 中提取「温馨提示」段落（曾写入 content 但详情只展示 blocks） */
    public static function extractNoticeFromContentHtml(string $content): string
    {
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($content === '') {
            return '';
        }
        if (preg_match('/<strong>\s*温馨提示\s*[：:]\s*<\/strong>\s*(.*?)<\/p>/isu', $content, $m)) {
            $text = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            return $text !== '' ? $text : '';
        }
        return '';
    }

    /**
     * 保证 publish_proxy_notice 有库内值：优先已有字段，其次从 content 迁移，最后存量回填（写库，非接口兜底文案）
     *
     * @param array<string,mixed>|object $item
     */
    public static function syncPublishNoticeField(int $activeId, &$item): string
    {
        $a = self::rowToArray($item);
        $notice = trim((string)($a['publish_proxy_notice'] ?? ''));
        $pid = (int)($a['publish_user_id'] ?? 0);
        if ($activeId <= 0 || $pid <= 0 || !self::hasParticipationSchema()) {
            return $notice;
        }
        if ($notice !== '') {
            return $notice;
        }
        $fromContent = self::extractNoticeFromContentHtml((string)($a['content'] ?? ''));
        if ($fromContent !== '') {
            $notice = $fromContent;
        } else {
            $notice = self::STOCK_MEMBER_NOTICE_BACKFILL;
        }
        Db::name('active')->where('id', $activeId)->where('publish_user_id', '>', 0)->update([
            'publish_proxy_notice' => $notice,
            'update_time' => time(),
        ]);
        return $notice;
    }

    /** @param array<string,mixed>|object $item ThinkPHP 模型或数组 */
    public static function attachPublishPresentationFields(&$item): void
    {
        $a = self::rowToArray($item);
        $pid = (int)($a['publish_user_id'] ?? 0);
        $activeId = (int)($a['id'] ?? 0);
        $item['is_member_publish'] = $pid > 0 ? 1 : 0;
        $item['publish_allow_signup'] = self::isPublishAllowSignup($item) ? 1 : 0;
        $item['publish_require_checkin'] = self::isPublishRequireCheckin($item) ? 1 : 0;
        $item['publish_proxy_notice'] = self::syncPublishNoticeField($activeId, $item);
    }

    /**
     * 详情页用户须知写入 blocks 首段（线上旧版小程序只渲染 publish_custom_blocks）
     *
     * @param array<string,mixed>|object $item
     */
    public static function injectPublishNoticeIntoDetailBlocks(&$item): void
    {
        $notice = trim((string)($item['publish_proxy_notice'] ?? ''));
        if ($notice === '') {
            return;
        }
        $blocks = $item['publish_custom_blocks'] ?? [];
        if (!is_array($blocks)) {
            $blocks = [];
        }
        foreach ($blocks as $b) {
            $label = (string)($b['label'] ?? '');
            if ($label === '温馨提示' || mb_strpos($label, '温馨提示') !== false) {
                return;
            }
        }
        array_unshift($blocks, [
            'type' => 'textarea',
            'label' => '温馨提示',
            'value' => $notice,
        ]);
        $item['publish_custom_blocks'] = $blocks;
    }

    /** 与活动 Tab 主题活动五分类 id 一致（cmf_active_community_cate） */
    public static function themeOptions(): array
    {
        return [
            ['id' => 1, 'name' => '健康运动', 'hint' => '徒步、晨练、球类…'],
            ['id' => 2, 'name' => '学习成长', 'hint' => '读书会、分享会…'],
            ['id' => 3, 'name' => '兴趣体验', 'hint' => '手工、摄影、体验课…'],
            ['id' => 4, 'name' => '交流互动', 'hint' => '邻里茶话、座谈…'],
            ['id' => 5, 'name' => '公益行动', 'hint' => '志愿、环保、互助…'],
        ];
    }

    public static function themeLabel(int $id): string
    {
        foreach (self::themeOptions() as $row) {
            if ((int)$row['id'] === $id) {
                return (string)$row['name'];
            }
        }
        return '社区活动';
    }

    public static function baseListQuery()
    {
        if (!self::hasPublishSchema()) {
            return ActiveModel::whereRaw('1=0');
        }
        return ActiveModel::where('publish_user_id', '>', 0)
            ->where('publish_audit_status', self::AUDIT_APPROVED)
            ->where('status', 1)
            ->where('delete_time', 0)
            ->where('join_type', 1);
    }

    /**
     * @return array{list:array,total:int,page:int,page_size:int,last_page:int}
     */
    public static function paginateForFeed(int $userId, int $page, int $pageSize, TabHomeService $formatter): array
    {
        $page = max(1, $page);
        $pageSize = min(30, max(1, $pageSize));
        $query = self::baseListQuery();
        $total = (clone $query)->count();
        $rows = $query->order('id desc')->page($page, $pageSize)->select();
        $list = [];
        foreach ($rows as $item) {
            $row = $formatter->formatActiveListItemPublic($item);
            $row['publisher_nickname'] = (string)Db::name('member')->where('id', (int)$item['publish_user_id'])->value('nickname');
            $row['theme_label'] = self::themeLabel((int)($item['community_cate_id'] ?? 0));
            $row['time_line'] = self::formatTimeLine($item);
            $list[] = $row;
        }
        $lastPage = $pageSize > 0 ? (int)ceil($total / $pageSize) : 1;
        return [
            'list' => $list,
            'total' => (int)$total,
            'page' => $page,
            'page_size' => $pageSize,
            'last_page' => max(1, $lastPage),
        ];
    }

    /** @param array<string,mixed>|object $active */
    public static function formatTimeLine($active): string
    {
        $a = self::rowToArray($active);
        $start = (int)($a['offline_sign_start'] ?? 0);
        $end = (int)($a['offline_sign_end'] ?? 0);
        if ($start > 0 && $end > 0) {
            return date('m月d日 H:i', $start) . ' – ' . date('H:i', $end);
        }
        if (!empty($a['date'])) {
            return '截止 ' . date('Y-m-d', (int)$a['date']);
        }
        return '时间见详情';
    }

    /**
     * 居民发布：生成配置式线下活动（免积分报名 + 现场定位签到）
     *
     * @return int active id
     */
    public static function publish(int $userId, array $payload): int
    {
        self::assertPublishSchemaReady();

        $name = trim((string)($payload['name'] ?? ''));
        $summary = trim((string)($payload['summary'] ?? ''));
        $thumb = trim((string)($payload['thumb'] ?? ''));
        $activityDate = trim((string)($payload['activity_date'] ?? ''));
        $startTime = trim((string)($payload['start_time'] ?? ''));
        $endTime = trim((string)($payload['end_time'] ?? ''));
        $address = trim((string)($payload['address'] ?? ''));
        $placeName = trim((string)($payload['place_name'] ?? ''));
        $lat = (float)($payload['lat'] ?? 0);
        $lng = (float)($payload['lng'] ?? 0);
        $phone = trim((string)($payload['contact'] ?? ''));
        $themeId = (int)($payload['theme_id'] ?? 0);

        if ($name === '' || mb_strlen($name) > 30) {
            throw new \InvalidArgumentException('请填写 30 字以内的活动名称');
        }
        if ($thumb === '') {
            throw new \InvalidArgumentException('请上传活动封面');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $activityDate)) {
            throw new \InvalidArgumentException('请选择活动日期');
        }
        if (!preg_match('/^\d{1,2}:\d{2}$/', $startTime) || !preg_match('/^\d{1,2}:\d{2}$/', $endTime)) {
            throw new \InvalidArgumentException('请选择开始与结束时间');
        }
        $startTs = strtotime($activityDate . ' ' . $startTime . ':00');
        $endTs = strtotime($activityDate . ' ' . $endTime . ':00');
        if (!$startTs || !$endTs || $endTs <= $startTs) {
            throw new \InvalidArgumentException('结束时间须晚于开始时间');
        }
        if ($startTs < strtotime('today')) {
            throw new \InvalidArgumentException('活动日期不能早于今天');
        }
        if ($address === '' || $lat == 0.0 || $lng == 0.0) {
            throw new \InvalidArgumentException('请在地图上选活动举办地点');
        }
        if ($phone === '' || mb_strlen($phone) > 40) {
            throw new \InvalidArgumentException('请填写有效联系方式');
        }
        $validTheme = false;
        foreach (self::themeOptions() as $t) {
            if ((int)$t['id'] === $themeId) {
                $validTheme = true;
                break;
            }
        }
        if (!$validTheme) {
            throw new \InvalidArgumentException('请选择活动类型');
        }

        $customBlocksRaw = $payload['custom_blocks'] ?? '';
        $customBlocks = self::parseCustomBlocksInput($customBlocksRaw);

        $displayAddr = $placeName !== '' ? ($placeName . ' · ' . $address) : $address;
        $timeHuman = date('Y年n月j日', $startTs) . ' ' . $startTime . '–' . $endTime;
        $contentParts = ['<p>' . htmlspecialchars($timeHuman, ENT_QUOTES, 'UTF-8') . '</p>'];
        foreach ($customBlocks as $block) {
            $contentParts[] = self::blockToContentHtml($block);
        }
        if ($summary !== '') {
            $contentParts[] = '<p>' . nl2br(htmlspecialchars($summary, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        $content = implode('', $contentParts);
        $customBlocksJson = $customBlocks ? json_encode($customBlocks, JSON_UNESCAPED_UNICODE) : '';

        $allowSignup = 0;
        $requireCheckin = 0;
        if (self::hasParticipationSchema()) {
            $allowSignup = !empty($payload['allow_signup']) ? 1 : 0;
            $requireCheckin = !empty($payload['require_checkin']) ? 1 : 0;
            if (!$allowSignup) {
                $requireCheckin = 0;
            }
        }

        $now = time();
        $pendingCount = ActiveModel::where('publish_user_id', $userId)
            ->whereIn('publish_audit_status', [self::AUDIT_PENDING, self::AUDIT_APPROVED])
            ->where('delete_time', 0)
            ->count();
        if ($pendingCount >= 10) {
            throw new \InvalidArgumentException('您进行中的发布较多，请先下架或等待已有活动结束');
        }

        $insert = [
            'type' => 1,
            'status' => 1,
            'join_type' => 1,
            'display_mode' => ActiveModel::DISPLAY_MODE_CONFIG,
            'event_kind' => ActiveModel::EVENT_KIND_OFFLINE,
            'cate_id' => 0,
            'name' => $name,
            'thumb' => $thumb,
            'content' => $content,
            'flow' => '',
            'gain' => '',
            'suit_people' => '邻里居民',
            'duration' => $timeHuman,
            'day_type' => '',
            'area_range' => '',
            'address' => $displayAddr,
            'province' => '',
            'city' => '',
            'district' => '',
            'lat' => (string)$lat,
            'lng' => (string)$lng,
            'phone' => $phone,
            'point' => 0,
            'share_point' => 0,
            'send_point' => 0,
            'community_cate_id' => $themeId,
            'xsdld_cate_id' => 0,
            'spot_key' => '',
            'qxl_hub_slot' => '',
            'date' => strtotime($activityDate . ' 23:59:59'),
            'offline_sign_start' => $startTs,
            'offline_sign_end' => $endTs,
            'offline_sign_password' => '',
            'is_clock' => 0,
            'is_clock_challenge' => 0,
            'clock_challenge_days' => 0,
            'clock_challenge_point' => 0,
            'clock_start_time' => 0,
            'clock_end_time' => 0,
            'sign_day' => 0,
            'is_badge' => 0,
            'badge_name' => '',
            'badge_image' => '',
            'is_date_pending' => 0,
            'is_recurring' => 0,
            'is_month_recommend' => 0,
            'web_url' => '',
            'rules_url' => '',
            'safety_url' => '',
            'qr_code' => '',
            'tags' => self::TAG_MEMBER,
            'publish_user_id' => $userId,
            'publish_audit_status' => self::AUDIT_PENDING,
            'publish_reject_reason' => '',
            'create_time' => $now,
            'update_time' => $now,
            'delete_time' => 0,
            'list_order' => 10000,
            'role' => '1',
            'publish_custom_blocks' => $customBlocksJson,
            'publish_allow_signup' => $allowSignup,
            'publish_require_checkin' => $requireCheckin,
            'publish_proxy_notice' => '',
        ];

        $tableFields = Db::name('active')->getTableFields();
        $insert = array_intersect_key($insert, array_flip($tableFields));
        $id = (int) Db::name('active')->strict(false)->insertGetId($insert);
        if ($id <= 0) {
            throw new \RuntimeException('活动保存失败');
        }
        return $id;
    }

    public static function blockTypeMeta(): array
    {
        return [
            ['type' => 'text', 'name' => '单行文本', 'hint' => '标题 + 一行内容'],
            ['type' => 'textarea', 'name' => '多行文本', 'hint' => '段落说明、注意事项'],
            ['type' => 'images', 'name' => '图片集', 'hint' => '最多 6 张，详情页横滑展示'],
        ];
    }

    /**
     * @param mixed $raw JSON 字符串或数组
     * @return list<array<string,mixed>>
     */
    public static function parseCustomBlocksInput($raw): array
    {
        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw === '') {
                return [];
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                throw new \InvalidArgumentException('自定义展示字段格式错误');
            }
            $raw = $decoded;
        }
        if (!is_array($raw)) {
            return [];
        }
        if (count($raw) > 12) {
            throw new \InvalidArgumentException('最多添加 12 个展示字段');
        }
        $out = [];
        foreach ($raw as $i => $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('自定义字段第 ' . ($i + 1) . ' 项无效');
            }
            $type = trim((string)($row['type'] ?? ''));
            $label = trim((string)($row['label'] ?? ''));
            if (!in_array($type, ['text', 'textarea', 'images'], true)) {
                throw new \InvalidArgumentException('不支持的展示类型');
            }
            if ($label === '' || mb_strlen($label) > 20) {
                throw new \InvalidArgumentException('请填写第 ' . ($i + 1) . ' 项标题（20 字以内）');
            }
            $item = ['type' => $type, 'label' => $label];
            if ($type === 'images') {
                $images = $row['images'] ?? [];
                if (!is_array($images)) {
                    throw new \InvalidArgumentException('图片集格式错误');
                }
                $paths = [];
                foreach ($images as $p) {
                    $p = trim((string)$p);
                    if ($p !== '') {
                        $paths[] = $p;
                    }
                }
                if (count($paths) > 6) {
                    throw new \InvalidArgumentException('「' . $label . '」最多 6 张图');
                }
                $item['images'] = $paths;
            } else {
                $val = trim((string)($row['value'] ?? ''));
                $max = $type === 'text' ? 100 : 800;
                if (mb_strlen($val) > $max) {
                    throw new \InvalidArgumentException('「' . $label . '」内容过长');
                }
                $item['value'] = $val;
            }
            $out[] = $item;
        }
        return $out;
    }

    /** @param array<string,mixed> $block */
    protected static function blockToContentHtml(array $block): string
    {
        $label = htmlspecialchars((string)$block['label'], ENT_QUOTES, 'UTF-8');
        if ($block['type'] === 'images') {
            return '<p><strong>' . $label . '</strong></p>';
        }
        $val = nl2br(htmlspecialchars((string)($block['value'] ?? ''), ENT_QUOTES, 'UTF-8'));
        return '<p><strong>' . $label . '</strong><br/>' . $val . '</p>';
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function formatBlocksForApi($stored): array
    {
        if ($stored === null || $stored === '') {
            return [];
        }
        if (is_string($stored)) {
            $arr = json_decode($stored, true);
            if (!is_array($arr)) {
                return [];
            }
        } elseif (is_array($stored)) {
            $arr = $stored;
        } else {
            return [];
        }
        $out = [];
        foreach ($arr as $row) {
            if (!is_array($row)) {
                continue;
            }
            $type = (string)($row['type'] ?? '');
            $item = [
                'type' => $type,
                'label' => (string)($row['label'] ?? ''),
            ];
            if ($type === 'images') {
                $urls = [];
                foreach ($row['images'] ?? [] as $p) {
                    $p = trim((string)$p);
                    if ($p !== '') {
                        $urls[] = cmf_get_asset_url($p);
                    }
                }
                $item['image_urls'] = $urls;
            } else {
                $item['value'] = (string)($row['value'] ?? '');
            }
            $out[] = $item;
        }
        return $out;
    }

    public static function updatePublishSettings(int $activeId, array $data): void
    {
        $row = ActiveModel::where('id', $activeId)->where('publish_user_id', '>', 0)->find();
        if (empty($row)) {
            throw new \InvalidArgumentException('记录不存在');
        }
        if (!self::hasParticipationSchema()) {
            throw new \InvalidArgumentException('请先执行 update.sql 升级居民发布字段');
        }
        $allowSignup = !empty($data['allow_signup']) ? 1 : 0;
        $requireCheckin = !empty($data['require_checkin']) ? 1 : 0;
        if (!$allowSignup) {
            $requireCheckin = 0;
        }
        $notice = trim((string)($data['publish_proxy_notice'] ?? ''));
        if (mb_strlen($notice) > 2000) {
            throw new \InvalidArgumentException('用户须知不超过 2000 字');
        }
        ActiveModel::where('id', $activeId)->update([
            'publish_allow_signup' => $allowSignup,
            'publish_require_checkin' => $requireCheckin,
            'publish_proxy_notice' => $notice,
            'update_time' => time(),
        ]);
    }

    /** 线上库未执行 update.sql 时给出可读提示 */
    public static function assertPublishSchemaReady(): void
    {
        $fields = Db::name('active')->getTableFields();
        foreach (['publish_user_id', 'publish_audit_status'] as $col) {
            if (!in_array($col, $fields, true)) {
                throw new \InvalidArgumentException('服务器尚未升级居民活动功能，请联系管理员执行 update.sql 相关脚本');
            }
        }
    }

    public static function hasPublishSchema(): bool
    {
        try {
            self::assertPublishSchemaReady();
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * 发布者「我的发布」列表与统计
     *
     * @return array{stats:array,tab_counts:array,list:list<array<string,mixed>>}
     */
    public static function myPublishDashboard(int $userId, string $filter): array
    {
        self::assertPublishSchemaReady();
        $filter = in_array($filter, ['all', 'auditing', 'enrolling', 'ended'], true) ? $filter : 'all';

        $rows = ActiveModel::where('publish_user_id', $userId)
            ->where('delete_time', 0)
            ->order('id desc')
            ->select()
            ->toArray();

        $allItems = [];
        $tabCounts = ['all' => 0, 'auditing' => 0, 'enrolling' => 0, 'ended' => 0];
        $totalSign = 0;

        foreach ($rows as $row) {
            $item = self::formatMyPublishItem($row);
            $allItems[] = $item;
            $tabCounts['all']++;
            $tk = (string)($item['tab_key'] ?? '');
            if (isset($tabCounts[$tk])) {
                $tabCounts[$tk]++;
            }
            $totalSign += (int)($item['sign_count'] ?? 0);
        }

        $list = $filter === 'all'
            ? $allItems
            : array_values(array_filter($allItems, function ($item) use ($filter) {
                return ($item['tab_key'] ?? '') === $filter;
            }));

        return [
            'stats' => [
                'total_published' => count($allItems),
                'total_signups' => $totalSign,
                'rating' => count($allItems) > 0 ? '4.9' : '—',
            ],
            'tab_counts' => $tabCounts,
            'list' => $list,
        ];
    }

    /** @param array<string,mixed> $row */
    protected static function formatMyPublishItem(array $row): array
    {
        $id = (int)($row['id'] ?? 0);
        $audit = (int)($row['publish_audit_status'] ?? 0);
        $isEnd = ActiveModel::resolveIsEnd($row) === 1;
        $signCount = ActiveSIgnModel::countParticipants($id);
        $allowSignup = self::isPublishAllowSignup($row);
        $requireCheckin = self::isPublishRequireCheckin($row);

        $addr = trim((string)($row['place_name'] ?? ''));
        if ($addr === '') {
            $addr = trim((string)($row['address'] ?? ''));
        }
        $metaLine = self::formatTimeLine($row);
        if ($addr !== '') {
            $metaLine .= ' · ' . $addr;
        }

        $tabKey = 'enrolling';
        $statusLabel = $allowSignup ? '报名中' : '展示中';
        $statusTone = 'ok';
        $footHint = $allowSignup ? self::formatEnrollDeadlineHint($row) : '未开放线上报名，请通过联系方式沟通';
        $progressLine = ($allowSignup && $signCount > 0) ? ('已报名 ' . $signCount . ' 人') : '';
        $actions = [];
        if ($allowSignup) {
            $actions[] = ['key' => 'sign_list', 'label' => '报名名单'];
            if ($requireCheckin) {
                $actions[] = ['key' => 'checkin_list', 'label' => '签到名单'];
            }
        } else {
            $actions[] = ['key' => 'preview', 'label' => '预览'];
        }

        if ($audit === self::AUDIT_PENDING) {
            $tabKey = 'auditing';
            $statusLabel = '审核中';
            $statusTone = 'warn';
            $footHint = '预计24小时内完成审核';
            $progressLine = '';
            $actions = [
                ['key' => 'preview', 'label' => '预览'],
                ['key' => 'withdraw', 'label' => '撤回'],
            ];
        } elseif ($audit === self::AUDIT_REJECTED) {
            $tabKey = 'all';
            $statusLabel = '需修改';
            $statusTone = 'danger';
            $reason = trim((string)($row['publish_reject_reason'] ?? ''));
            $footHint = $reason !== '' ? $reason : '请补充说明后重新提交';
            $progressLine = '';
            $actions = [
                ['key' => 'edit', 'label' => '去修改'],
            ];
        } elseif ($audit !== self::AUDIT_APPROVED) {
            $tabKey = 'all';
            $statusLabel = '草稿';
            $statusTone = 'muted';
            $footHint = '完善信息后提交审核';
            $progressLine = '';
            $actions = [
                ['key' => 'edit', 'label' => '继续编辑'],
            ];
        } elseif ($isEnd) {
            $tabKey = 'ended';
            $statusLabel = '已结束';
            $statusTone = 'muted';
            $footHint = '活动已结束，感谢组织';
            $progressLine = $signCount > 0 ? ('共 ' . $signCount . ' 人参与') : '';
            $actions = [
                ['key' => 'preview', 'label' => '查看回顾'],
            ];
        }

        return [
            'id' => $id,
            'name' => (string)($row['name'] ?? ''),
            'thumb' => empty($row['thumb']) ? '' : cmf_get_asset_url($row['thumb']),
            'tab_key' => $tabKey,
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'meta_line' => $metaLine,
            'foot_hint' => $footHint,
            'progress_line' => $progressLine,
            'sign_count' => $signCount,
            'audit_status' => $audit,
            'allow_signup' => $allowSignup ? 1 : 0,
            'require_checkin' => $requireCheckin ? 1 : 0,
            'actions' => $actions,
        ];
    }

    /** @param array<string,mixed> $row */
    protected static function formatEnrollDeadlineHint(array $row): string
    {
        $start = (int)($row['offline_sign_start'] ?? 0);
        if ($start > 0) {
            $dayStart = strtotime(date('Y-m-d 00:00:00', $start));
            $tomorrowStart = strtotime('tomorrow');
            if ($dayStart === $tomorrowStart) {
                return '报名截止：明天' . date('H:i', $start);
            }
            return '开始时间：' . date('n月j日 H:i', $start);
        }
        $date = (int)($row['date'] ?? 0);
        if ($date > 0) {
            return '截止：' . date('Y-m-d H:i', $date);
        }
        return '报名时间见详情';
    }

    /** 撤回审核中的发布（软删除） */
    public static function withdrawPublish(int $userId, int $activeId): void
    {
        self::assertPublishSchemaReady();
        if ($activeId <= 0) {
            throw new \InvalidArgumentException('缺少活动 ID');
        }
        $row = ActiveModel::where('id', $activeId)
            ->where('publish_user_id', $userId)
            ->where('delete_time', 0)
            ->find();
        if (empty($row)) {
            throw new \InvalidArgumentException('活动不存在或无权操作');
        }
        if ((int)$row['publish_audit_status'] !== self::AUDIT_PENDING) {
            throw new \InvalidArgumentException('仅审核中的活动可撤回');
        }
        ActiveModel::where('id', $activeId)->update([
            'delete_time' => time(),
            'update_time' => time(),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    protected static function loadPublisherActive(int $userId, int $activeId): array
    {
        self::assertPublishSchemaReady();
        if ($activeId <= 0) {
            throw new \InvalidArgumentException('缺少活动 ID');
        }
        $row = ActiveModel::where('id', $activeId)
            ->where('publish_user_id', $userId)
            ->where('delete_time', 0)
            ->find();
        if (empty($row)) {
            throw new \InvalidArgumentException('活动不存在或无权查看');
        }
        return is_array($row) ? $row : $row->toArray();
    }

    protected static function maskMobile(string $mobile): string
    {
        $mobile = trim($mobile);
        if (strlen($mobile) < 7) {
            return $mobile !== '' ? $mobile : '—';
        }
        return substr($mobile, 0, 3) . '****' . substr($mobile, -4);
    }

    /**
     * 发布者查看报名名单
     *
     * @return array{list:array,total:int,page:int,page_size:int,last_page:int,active:array}
     */
    public static function publishSignList(int $userId, int $activeId, int $page, int $pageSize): array
    {
        $active = self::loadPublisherActive($userId, $activeId);
        $page = max(1, $page);
        $pageSize = min(50, max(1, $pageSize));

        $query = ActiveSIgnModel::alias('s')
            ->join('member m', 's.user_id = m.id', 'left')
            ->where('s.active_id', $activeId)
            ->field('s.id,s.user_id,s.create_time,m.nickname,m.avatar,m.mobile')
            ->order('s.id desc');

        $paginator = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);

        $list = [];
        foreach ($paginator->items() as $item) {
            $row = is_array($item) ? $item : $item->toArray();
            $ts = (int)($row['create_time'] ?? 0);
            $list[] = [
                'id' => (int)($row['id'] ?? 0),
                'user_id' => (int)($row['user_id'] ?? 0),
                'nickname' => (string)($row['nickname'] ?? '居民'),
                'avatar' => empty($row['avatar']) ? '' : cmf_get_asset_url($row['avatar']),
                'mobile_mask' => self::maskMobile((string)($row['mobile'] ?? '')),
                'time_text' => $ts > 0 ? date('Y-m-d H:i', $ts) : '—',
            ];
        }

        return [
            'active' => [
                'id' => $activeId,
                'name' => (string)($active['name'] ?? ''),
            ],
            'list' => $list,
            'total' => (int)$paginator->total(),
            'page' => (int)$paginator->currentPage(),
            'page_size' => $pageSize,
            'last_page' => max(1, (int)$paginator->lastPage()),
        ];
    }

    /**
     * 发布者查看现场签到名单（active_clock）
     *
     * @return array{list:array,total:int,page:int,page_size:int,last_page:int,active:array}
     */
    public static function publishCheckinList(int $userId, int $activeId, int $page, int $pageSize): array
    {
        $active = self::loadPublisherActive($userId, $activeId);
        $page = max(1, $page);
        $pageSize = min(50, max(1, $pageSize));

        $query = ActiveClockModel::alias('c')
            ->join('member m', 'c.user_id = m.id', 'left')
            ->where('c.active_id', $activeId)
            ->field('c.id,c.user_id,c.create_time,m.nickname,m.avatar,m.mobile')
            ->order('c.id desc');

        $paginator = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);

        $list = [];
        foreach ($paginator->items() as $item) {
            $row = is_array($item) ? $item : $item->toArray();
            $ts = (int)($row['create_time'] ?? 0);
            $list[] = [
                'id' => (int)($row['id'] ?? 0),
                'user_id' => (int)($row['user_id'] ?? 0),
                'nickname' => (string)($row['nickname'] ?? '居民'),
                'avatar' => empty($row['avatar']) ? '' : cmf_get_asset_url($row['avatar']),
                'mobile_mask' => self::maskMobile((string)($row['mobile'] ?? '')),
                'time_text' => $ts > 0 ? date('Y-m-d H:i', $ts) : '—',
            ];
        }

        return [
            'active' => [
                'id' => $activeId,
                'name' => (string)($active['name'] ?? ''),
            ],
            'list' => $list,
            'total' => (int)$paginator->total(),
            'page' => (int)$paginator->currentPage(),
            'page_size' => $pageSize,
            'last_page' => max(1, (int)$paginator->lastPage()),
        ];
    }
}
