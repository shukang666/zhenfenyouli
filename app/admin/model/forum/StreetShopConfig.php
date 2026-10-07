<?php

namespace app\admin\model\forum;

use think\facade\Db;

/**
 * 沿街商铺类型 → 打卡垃圾种类（basic_select type=2 按名称匹配）
 */
class StreetShopConfig
{
    /** @return array<string,array{name:string,cate_keyword:string}> */
    public static function shopTypes(): array
    {
        return [
            'restaurant'  => ['name' => '餐馆', 'cate_keyword' => '厨余'],
            'pharmacy'    => ['name' => '药房', 'cate_keyword' => '有害'],
            'fruit'       => ['name' => '生鲜蔬果', 'cate_keyword' => '厨余'],
            'convenience' => ['name' => '便利店', 'cate_keyword' => '可回收'],
            'clothing'    => ['name' => '服装店', 'cate_keyword' => '可回收'],
            'barber'      => ['name' => '美容美发', 'cate_keyword' => '其他'],
            'print'       => ['name' => '图文印刷', 'cate_keyword' => '可回收'],
            'other'       => ['name' => '其他商铺', 'cate_keyword' => '其他'],
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

    /** @return array<int,array{key:string,name:string,cate_id:int,cate_name:string}> */
    public static function shopTypesForApi(): array
    {
        $out = [];
        foreach (self::shopTypes() as $key => $def) {
            $cate = self::resolveCateByShopType($key);
            $out[] = [
                'key'        => $key,
                'name'       => $def['name'],
                'cate_id'    => $cate ? (int)$cate['id'] : 0,
                'cate_name'  => $cate ? (string)$cate['name'] : '',
            ];
        }
        return $out;
    }
}
