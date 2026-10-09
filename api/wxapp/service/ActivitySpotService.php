<?php

namespace api\wxapp\service;

use app\admin\model\active\ActiveModel;
use think\facade\Db;

/**
 * 活动 Tab 四大专题：我和七贤岭 + 徒步拾遗 / 绿色二手集市 / 垃圾真分小课堂（后三者以分会场子活动展示）
 */
class ActivitySpotService
{
    public const SPOT_QXL = 'qxl';
    public const SPOT_HIKING = 'hiking';
    public const SPOT_FLEA = 'flea_market';
    public const SPOT_ZHENFEN_CLASS = 'zhenfen_class';

    /** @return list<array<string,mixed>> */
    public static function moduleList(): array
    {
        return [
            [
                'key' => self::SPOT_QXL,
                'name' => '我和七贤岭',
                'subtitle' => '认识 · 发现',
                'emoji' => '⛰️',
                'entry_type' => 'qxl_page',
            ],
            [
                'key' => self::SPOT_HIKING,
                'name' => '步行山海，净美家园',
                'subtitle' => '边走边捡 · 洁净山野',
                'emoji' => '🥾',
                'entry_type' => 'spot_hub',
            ],
            [
                'key' => self::SPOT_FLEA,
                'name' => '七贤岭绿色二手集市',
                'subtitle' => '闲置循环 · 绿色生活',
                'emoji' => '♻️',
                'entry_type' => 'spot_hub',
            ],
            [
                'key' => self::SPOT_ZHENFEN_CLASS,
                'name' => '垃圾真分小课堂',
                'subtitle' => '学分类 · 真投放',
                'emoji' => '📗',
                'entry_type' => 'spot_hub',
            ],
        ];
    }

    public static function isValidSpotKey(string $key): bool
    {
        return in_array($key, [self::SPOT_HIKING, self::SPOT_FLEA, self::SPOT_ZHENFEN_CLASS], true);
    }

    /**
     * 分会场列表：配置式活动按 spot_key；二手集市按场次表展开
     *
     * @return list<array<string,mixed>>
     */
    public static function venueList(string $spotKey): array
    {
        if (!self::isValidSpotKey($spotKey)) {
            return [];
        }

        if ($spotKey === self::SPOT_FLEA) {
            return self::fleaSessionVenues();
        }

        $rows = ActiveModel::miniProgramVisible()
            ->where('spot_key', $spotKey)
            ->order('list_order asc, id desc')
            ->select()
            ->toArray();

        $list = [];
        foreach ($rows as $row) {
            $list[] = self::formatActiveVenue($row, $spotKey);
        }
        return $list;
    }

    /** @return list<array<string,mixed>> */
    protected static function fleaSessionVenues(): array
    {
        $activeIds = ActiveModel::miniProgramVisible()
            ->where('spot_key', self::SPOT_FLEA)
            ->column('id');
        if (empty($activeIds)) {
            return [];
        }

        $sessions = Db::name(FleaMarketService::sessionTable())
            ->whereIn('active_id', $activeIds)
            ->where('status', 1)
            ->order('list_order asc, start_time asc, id asc')
            ->select()
            ->toArray();

        $activeMap = [];
        foreach (ActiveModel::whereIn('id', $activeIds)->select() as $activeRow) {
            $activeMap[(int)$activeRow['id']] = $activeRow->toArray();
        }
        $list = [];
        foreach ($sessions as $s) {
            $aid = (int)$s['active_id'];
            $active = $activeMap[$aid] ?? [];
            $list[] = [
                'kind' => 'flea_session',
                'id' => (int)$s['id'],
                'session_id' => (int)$s['id'],
                'active_id' => $aid,
                'title' => self::resolveFleaSessionTitle($s, $active),
                'address' => (string)$s['address'],
                'date_text' => (string)$s['date_text'],
                'thumb' => !empty($active['thumb']) ? cmf_get_asset_url($active['thumb']) : '',
                'display_mode' => (int)($active['display_mode'] ?? 2),
                'web_url' => (string)($active['web_url'] ?? ''),
                'active_count' => 0,
            ];
        }

        // 无场次时仍展示绑定了专题的主活动（便于运营先上架活动再补场次）
        if (empty($list)) {
            foreach ($activeMap as $row) {
                $list[] = self::formatActiveVenue($row, self::SPOT_FLEA);
            }
        }

        return $list;
    }

    /** @param array<string,mixed> $session @param array<string,mixed> $active */
    protected static function resolveFleaSessionTitle(array $session, array $active): string
    {
        $title = trim((string)($session['title'] ?? ''));
        if ($title !== '') {
            return $title;
        }
        $address = trim((string)($session['address'] ?? ''));
        if ($address !== '') {
            return self::shortPlaceLabel($address);
        }
        return self::resolveActiveVenueTitle($active, self::SPOT_FLEA);
    }

    /** 专题内列表项：以地点命名，不重复专题活动名 */
    /** @param array<string,mixed> $row */
    protected static function resolveActiveVenueTitle(array $row, string $spotKey): string
    {
        $name = trim((string)($row['name'] ?? ''));
        $address = trim((string)($row['address'] ?? ''));
        $moduleName = self::moduleNameByKey($spotKey);

        $stripped = self::stripTopicPrefixFromName($name, $spotKey);
        if ($stripped !== '' && ($moduleName === '' || $stripped !== $moduleName)) {
            return $stripped;
        }
        if ($address !== '') {
            return self::shortPlaceLabel($address);
        }
        if ($name !== '') {
            return $name;
        }
        return '地点待定';
    }

    protected static function moduleNameByKey(string $spotKey): string
    {
        foreach (self::moduleList() as $m) {
            if (($m['key'] ?? '') === $spotKey) {
                return trim((string)($m['name'] ?? ''));
            }
        }
        return '';
    }

    protected static function stripTopicPrefixFromName(string $name, string $spotKey): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }
        $prefixes = array_filter([
            self::moduleNameByKey($spotKey),
            '步行山海，净美家园',
            '徒步拾遗',
            '七贤岭绿色二手集市',
            '绿色二手集市',
            '垃圾真分小课堂',
        ]);
        foreach ($prefixes as $prefix) {
            if ($prefix === '') {
                continue;
            }
            if ($name === $prefix) {
                return '';
            }
            $pattern = '/^' . preg_quote($prefix, '/') . '[\s·\-—|｜:：]*+/u';
            $next = preg_replace($pattern, '', $name);
            if (is_string($next) && $next !== $name) {
                $name = trim($next);
            }
        }
        return $name;
    }

    /** 地址过长时取更易读的地点称呼（列表主标题用） */
    protected static function shortPlaceLabel(string $address): string
    {
        $address = trim($address);
        if ($address === '') {
            return '';
        }
        $parts = preg_split('/[\s,，、]+/u', $address);
        if (is_array($parts) && count($parts) > 0) {
            $last = trim((string)end($parts));
            if ($last !== '' && mb_strlen($last) <= 32) {
                return $last;
            }
        }
        return mb_strlen($address) > 32 ? (mb_substr($address, 0, 32) . '…') : $address;
    }

    /** @param array<string,mixed> $row */
    protected static function formatActiveVenue(array $row, string $spotKey = ''): array
    {
        $thumb = $row['thumb'] ?? '';
        if ($thumb !== '') {
            $thumb = cmf_get_asset_url($thumb);
        }
        $spotKey = $spotKey !== '' ? $spotKey : trim((string)($row['spot_key'] ?? ''));
        $address = (string)($row['address'] ?? '');
        $title = $spotKey !== '' ? self::resolveActiveVenueTitle($row, $spotKey) : (string)($row['name'] ?? '');
        return [
            'kind' => 'active',
            'id' => (int)$row['id'],
            'session_id' => 0,
            'active_id' => (int)$row['id'],
            'title' => $title,
            'address' => $address,
            'date_text' => !empty($row['date']) ? date('Y-m-d', (int)$row['date']) : '',
            'thumb' => $thumb,
            'display_mode' => (int)($row['display_mode'] ?? 1),
            'web_url' => (string)($row['web_url'] ?? ''),
            'active_count' => (int)($row['active_count'] ?? 0),
        ];
    }
}
