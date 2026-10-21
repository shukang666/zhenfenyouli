<?php

namespace api\wxapp\service;

use app\admin\model\member\MemberModel;
use think\facade\Db;

class SecondhandService
{
    public const TABLE_ITEM = 'secondhand_item';
    public const TABLE_INTENT = 'secondhand_intent';

    /** 待审核 */
    public const STATUS_PENDING = 0;
    /** 出售中 */
    public const STATUS_ON_SALE = 1;
    /** 已预订（有人登记意向） */
    public const STATUS_RESERVED = 2;
    /** 已下架/已成交关闭 */
    public const STATUS_CLOSED = 3;
    /** 审核拒绝 */
    public const STATUS_REJECTED = 4;

    public const INTENT_REGISTERED = 0;
    public const INTENT_WAIT_HANDOVER = 1;
    public const INTENT_DONE = 2;
    public const INTENT_CANCEL = 3;

    public static function categories(): array
    {
        return [
            ['key' => 'books', 'label' => '书籍文具'],
            ['key' => 'home', 'label' => '家居日用'],
            ['key' => 'baby', 'label' => '母婴儿童'],
            ['key' => 'digital', 'label' => '数码小物'],
            ['key' => 'other', 'label' => '其他'],
        ];
    }

    public static function conditions(): array
    {
        return [
            ['key' => 'new', 'label' => '全新'],
            ['key' => 'like_new', 'label' => '几乎全新'],
            ['key' => 'used', 'label' => '正常使用'],
        ];
    }

    public static function categoryLabel(string $key): string
    {
        foreach (self::categories() as $row) {
            if ($row['key'] === $key) {
                return $row['label'];
            }
        }
        return '';
    }

    public static function conditionLabel(string $key): string
    {
        foreach (self::conditions() as $row) {
            if ($row['key'] === $key) {
                return $row['label'];
            }
        }
        return '';
    }

    public static function statusLabel(int $status): string
    {
        $map = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_ON_SALE => '出售中',
            self::STATUS_RESERVED => '已预订',
            self::STATUS_CLOSED => '已关闭',
            self::STATUS_REJECTED => '未通过审核',
        ];
        return $map[$status] ?? '';
    }

    /** @param array<string,mixed> $row */
    public static function formatItemForApi(array $row, bool $withSeller = true, bool $includeContact = false): array
    {
        $images = self::decodeImages($row['images'] ?? '');
        $imageUrls = [];
        foreach ($images as $path) {
            $imageUrls[] = cmf_get_asset_url($path);
        }
        $out = [
            'id' => (int)$row['id'],
            'title' => (string)$row['title'],
            'description' => (string)$row['description'],
            'images' => $imageUrls,
            'image_paths' => $images,
            'price' => number_format((float)($row['price'] ?? 0), 2, '.', ''),
            'scope_type' => (string)($row['scope_type'] ?? ''),
            'scope_id' => (int)($row['scope_id'] ?? 0),
            'scope_name' => (string)($row['scope_name'] ?? ''),
            'category' => (string)$row['category'],
            'category_label' => self::categoryLabel((string)$row['category']),
            'condition' => (string)$row['condition'],
            'condition_label' => self::conditionLabel((string)$row['condition']),
            'area_text' => (string)($row['scope_name'] ?? $row['area_text'] ?? ''),
            'status' => (int)$row['status'],
            'status_label' => self::statusLabel((int)$row['status']),
            'status_key' => self::statusKey((int)$row['status']),
            'reject_reason' => (string)($row['reject_reason'] ?? ''),
            'user_id' => (int)$row['user_id'],
            'create_time' => (int)$row['create_time'],
        ];
        if ($withSeller) {
            $out['seller_nickname'] = (string)($row['seller_nickname'] ?? '');
            $out['seller_avatar'] = empty($row['seller_avatar']) ? '' : cmf_get_asset_url($row['seller_avatar']);
        }
        if ($includeContact) {
            $out['contact'] = (string)($row['contact'] ?? '');
        }
        return $out;
    }

    public static function statusKey(int $status): string
    {
        if ($status === self::STATUS_ON_SALE) {
            return 'on_sale';
        }
        if ($status === self::STATUS_RESERVED) {
            return 'reserved';
        }
        if ($status === self::STATUS_CLOSED) {
            return 'sold';
        }
        if ($status === self::STATUS_PENDING) {
            return 'pending';
        }
        if ($status === self::STATUS_REJECTED) {
            return 'rejected';
        }
        return 'unknown';
    }

    /** @param list<string> $paths */
    public static function encodeImages(array $paths): string
    {
        $clean = [];
        foreach ($paths as $p) {
            $p = trim((string)$p);
            if ($p !== '') {
                $clean[] = $p;
            }
        }
        return json_encode(array_slice($clean, 0, 6), JSON_UNESCAPED_UNICODE);
    }

    /** @return list<string> */
    public static function decodeImages($raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }
        $s = trim((string)$raw);
        if ($s === '') {
            return [];
        }
        if ($s[0] === '[') {
            $decoded = json_decode($s, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }
        }
        return array_filter(array_map('trim', explode(',', $s)));
    }

    /**
     * 发布接口：从请求中解析 images（兼容 JSON 体、JSON.stringify、数组、单图 image）
     *
     * @param \think\Request $request
     * @return list<string>
     */
    public static function extractPublishImagesFromRequest($request): array
    {
        $candidates = [];
        $post = $request->post(false);
        if (is_array($post)) {
            if (array_key_exists('images', $post)) {
                $candidates[] = $post['images'];
            }
            if (!empty($post['image'])) {
                $candidates[] = $post['image'];
            }
        }
        $fromParam = $request->param('images', null);
        if ($fromParam !== null && $fromParam !== '') {
            $candidates[] = $fromParam;
        }
        if (empty($candidates)) {
            $input = method_exists($request, 'getInput') ? (string)$request->getInput() : '';
            if ($input !== '') {
                $decoded = json_decode($input, true);
                if (is_array($decoded)) {
                    if (array_key_exists('images', $decoded)) {
                        $candidates[] = $decoded['images'];
                    }
                    if (!empty($decoded['image'])) {
                        $candidates[] = $decoded['image'];
                    }
                }
            }
        }
        foreach ($candidates as $raw) {
            if ($raw === null || $raw === '') {
                continue;
            }
            $paths = self::normalizeImagePaths($raw);
            if (!empty($paths)) {
                return $paths;
            }
        }

        return [];
    }

    public static function normalizeImagePaths($imagesParam): array
    {
        $imagesParam = self::unwrapImagesPayload($imagesParam);
        if (is_string($imagesParam)) {
            $imagesParam = trim($imagesParam);
            if ($imagesParam === '') {
                $imagesParam = [];
            } elseif ($imagesParam[0] === '[' || $imagesParam[0] === '{') {
                $decoded = json_decode($imagesParam, true);
                $imagesParam = is_array($decoded) ? $decoded : self::decodeImages($imagesParam);
            } else {
                $imagesParam = explode(',', $imagesParam);
            }
        }
        if (!is_array($imagesParam)) {
            return [];
        }
        $out = [];
        foreach ($imagesParam as $p) {
            if (is_array($p)) {
                $p = $p['filepath'] ?? $p['file_path'] ?? $p['url'] ?? $p['path'] ?? '';
            }
            $p = self::toStorageRelativePath(trim((string)$p));
            if ($p === '' || preg_match('#^wxfile:#i', $p)) {
                continue;
            }
            $out[] = $p;
        }
        return array_slice($out, 0, 6);
    }

    /**
     * @param mixed $payload
     * @return mixed
     */
    protected static function unwrapImagesPayload($payload)
    {
        $depth = 0;
        while (is_string($payload) && $depth < 4) {
            $t = trim($payload);
            if ($t === '') {
                return [];
            }
            if ($t[0] !== '[' && $t[0] !== '{') {
                return $payload;
            }
            $decoded = json_decode($t, true);
            if (!is_array($decoded) && !is_string($decoded)) {
                return $payload;
            }
            $payload = $decoded;
            $depth++;
        }

        return $payload;
    }

    protected static function toStorageRelativePath(string $p): string
    {
        if ($p === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $p)) {
            $path = preg_replace('#^https?://[^/]+/#i', '', $p);
            $path = preg_replace('#\?.*$#', '', $path);
            $path = ltrim($path, '/');
            if (function_exists('cmf_strip_upload_object_key_prefix')) {
                return cmf_strip_upload_object_key_prefix($path);
            }

            return preg_replace('#^upload/#', '', $path);
        }

        return preg_replace('#^upload/#', '', $p);
    }

    public static function sellerSnapshot(int $userId): array
    {
        $m = MemberModel::where('id', $userId)->field('id,nickname,avatar')->find();
        if (empty($m)) {
            return ['nickname' => '社区用户', 'avatar' => ''];
        }
        $row = $m->toArray();
        return [
            'nickname' => (string)($row['nickname'] ?: '社区用户'),
            'avatar' => (string)($row['avatar'] ?? ''),
        ];
    }

    /** 取消某物品进行中的意向并恢复在售（若无其它进行中意向） */
    public static function cancelActiveIntentsForItem(int $itemId, int $exceptIntentId = 0): void
    {
        $q = Db::name(self::TABLE_INTENT)
            ->where('item_id', $itemId)
            ->whereIn('status', [self::INTENT_REGISTERED, self::INTENT_WAIT_HANDOVER]);
        if ($exceptIntentId > 0) {
            $q->where('id', '<>', $exceptIntentId);
        }
        $q->update(['status' => self::INTENT_CANCEL, 'update_time' => time()]);
    }
}
