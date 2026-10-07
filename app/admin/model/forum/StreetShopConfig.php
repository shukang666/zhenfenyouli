<?php

namespace app\admin\model\forum;

use think\facade\Db;

/**
 * 沿街商铺类型 → 打卡垃圾种类（basic_select type=2 按名称匹配）
 */
class StreetShopConfig
{
    /** @return array<string,array{name:string,category:string,cate_keyword:string}> */
    public static function shopTypes(): array
    {
        return [
            // 餐饮类
            'restaurant'  => ['name' => '餐厅', 'category' => 'catering', 'cate_keyword' => '厨余'],
            'fast_food'   => ['name' => '快餐店', 'category' => 'catering', 'cate_keyword' => '厨余'],
            'snack'       => ['name' => '小吃店', 'category' => 'catering', 'cate_keyword' => '厨余'],
            'bakery'      => ['name' => '烘焙店', 'category' => 'catering', 'cate_keyword' => '厨余'],
            'beverage'    => ['name' => '饮品店', 'category' => 'catering', 'cate_keyword' => '厨余'],
            'fruit'       => ['name' => '水果店', 'category' => 'catering', 'cate_keyword' => '厨余'],
            // 生活服务类
            'bath'        => ['name' => '洗浴', 'category' => 'life', 'cate_keyword' => '其他'],
            'hotel'       => ['name' => '酒店/宾馆', 'category' => 'life', 'cate_keyword' => '其他'],
            'netcafe'     => ['name' => '网吧', 'category' => 'life', 'cate_keyword' => '其他'],
            'barber'      => ['name' => '美容美发', 'category' => 'life', 'cate_keyword' => '其他'],
            'gym'         => ['name' => '健身房', 'category' => 'life', 'cate_keyword' => '其他'],
            'laundry'     => ['name' => '洗衣店', 'category' => 'life', 'cate_keyword' => '其他'],
            // 零售类
            'supermarket' => ['name' => '超市', 'category' => 'retail', 'cate_keyword' => '可回收'],
            'convenience' => ['name' => '便利店', 'category' => 'retail', 'cate_keyword' => '可回收'],
            'clothing'    => ['name' => '服装店', 'category' => 'retail', 'cate_keyword' => '可回收'],
            'express'     => ['name' => '快递驿站', 'category' => 'retail', 'cate_keyword' => '可回收'],
            'stationery'  => ['name' => '文具店', 'category' => 'retail', 'cate_keyword' => '可回收'],
            'hardware'    => ['name' => '五金店', 'category' => 'retail', 'cate_keyword' => '其他'],
            'print'       => ['name' => '图文印刷', 'category' => 'retail', 'cate_keyword' => '可回收'],
            // 医药类
            'pharmacy'    => ['name' => '药店', 'category' => 'medical', 'cate_keyword' => '有害'],
            'clinic'      => ['name' => '诊所', 'category' => 'medical', 'cate_keyword' => '有害'],
            'community_clinic' => ['name' => '社区卫生服务站', 'category' => 'medical', 'cate_keyword' => '有害'],
            'medical_store'  => ['name' => '医疗器械店', 'category' => 'medical', 'cate_keyword' => '有害'],
            // 兜底
            'other'       => ['name' => '其他商铺', 'category' => 'retail', 'cate_keyword' => '其他'],
        ];
    }

    /** @return array<string,string> */
    public static function shopTypeCategoryLabels(): array
    {
        return [
            'catering' => '餐饮类',
            'life'     => '生活服务类',
            'retail'   => '零售类',
            'medical'  => '医药类',
        ];
    }

    public static function shopTypeLabel(string $typeKey): string
    {
        $types = self::shopTypes();
        return isset($types[$typeKey]) ? (string)$types[$typeKey]['name'] : '';
    }

    public static function normalizeShopType(string $typeKey): string
    {
        $typeKey = trim($typeKey);
        return array_key_exists($typeKey, self::shopTypes()) ? $typeKey : '';
    }

    /**
     * @return array{id:int,name:string}|null
     */
    public static function resolveCateByShopType(string $typeKey): ?array
    {
        $typeKey = self::normalizeShopType($typeKey);
        if ($typeKey === '') {
            return null;
        }
        $keyword = self::shopTypes()[$typeKey]['cate_keyword'];
        $row = Db::name('basic_select')
            ->where('type', 2)
            ->where('pid', 0)
            ->where(function ($q) use ($keyword) {
                $q->where('name', $keyword)->whereOr('name', 'like', '%' . $keyword . '%');
            })
            ->order('list_order asc,id asc')
            ->field('id,name')
            ->find();
        return $row ?: null;
    }

    /** @return array<int,array{key:string,name:string,cate_id:int,cate_name:string,category:string}> */
    public static function shopTypesForApi(): array
    {
        $out = [];
        foreach (self::shopTypes() as $key => $def) {
            $cate = self::resolveCateByShopType($key);
            $out[] = [
                'key'        => $key,
                'name'       => $def['name'],
                'category'   => (string)($def['category'] ?? ''),
                'cate_id'    => $cate ? (int)$cate['id'] : 0,
                'cate_name'  => $cate ? (string)$cate['name'] : '',
            ];
        }
        return $out;
    }

    /** @return array<int,array{id:string,name:string,types:array}> */
    public static function shopTypeGroupsForApi(): array
    {
        $labels = self::shopTypeCategoryLabels();
        $bucket = [];
        foreach (array_keys($labels) as $cid) {
            $bucket[$cid] = [];
        }
        foreach (self::shopTypes() as $key => $def) {
            if ($key === 'other') {
                continue;
            }
            $cid = (string)($def['category'] ?? '');
            if (!isset($bucket[$cid])) {
                $cid = 'retail';
            }
            $cate = self::resolveCateByShopType($key);
            $bucket[$cid][] = [
                'key'       => $key,
                'name'      => $def['name'],
                'cate_id'   => $cate ? (int)$cate['id'] : 0,
                'cate_name' => $cate ? (string)$cate['name'] : '',
            ];
        }
        $groups = [];
        foreach ($labels as $cid => $label) {
            if (empty($bucket[$cid])) {
                continue;
            }
            $groups[] = [
                'id'    => $cid,
                'name'  => $label,
                'types' => $bucket[$cid],
            ];
        }
        return $groups;
    }
}
