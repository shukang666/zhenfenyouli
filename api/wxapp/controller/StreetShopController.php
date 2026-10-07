<?php

namespace api\wxapp\controller;

use app\admin\model\forum\StreetShopConfig;
use think\facade\Db;

/**
 * 沿街商铺：用户商户身份 + 统一投放点
 */
class StreetShopController extends AuthController
{
    public function shop_types()
    {
        $this->success('请求成功', StreetShopConfig::shopTypesForApi());
    }

    public function my_merchant()
    {
        $this->checkUser();
        $row = Db::name('street_shop_merchant')->where('user_id', $this->user_id)->find();
        if (empty($row)) {
            $this->success('请求成功', null);
            return;
        }
        $this->success('请求成功', $this->formatMerchantRow($row));
    }

    public function drop_points()
    {
        $this->checkUser();
        $shopType = StreetShopConfig::normalizeShopType((string)$this->request->param('shop_type', ''));
        if ($shopType === '') {
            $this->error('请选择商铺类型');
        }
        $list = Db::name('street_shop_drop_point')
            ->where('shop_type', $shopType)
            ->where('status', 1)
            ->order('list_order asc,id asc')
            ->select()
            ->toArray();
        foreach ($list as &$item) {
            $item = $this->formatDropPointRow($item);
        }
        unset($item);
        $this->success('请求成功', $list);
    }

    public function save_merchant()
    {
        $this->checkUser();
        $shopName = trim((string)$this->request->param('shop_name', ''));
        $shopType = StreetShopConfig::normalizeShopType((string)$this->request->param('shop_type', ''));
        $dropPointId = (int)$this->request->param('drop_point_id', 0);
        if ($shopName === '') {
            $this->error('请填写商铺名称');
        }
        if (mb_strlen($shopName) > 64) {
            $this->error('商铺名称过长');
        }
        if ($shopType === '') {
            $this->error('请选择商铺类型');
        }
        $cate = StreetShopConfig::resolveCateByShopType($shopType);
        if (!$cate) {
            $this->error('该商铺类型暂未配置垃圾种类，请联系社区');
        }
        if ($dropPointId <= 0) {
            $this->error('请选择统一投放点');
        }
        $drop = Db::name('street_shop_drop_point')
            ->where('id', $dropPointId)
            ->where('status', 1)
            ->find();
        if (empty($drop)) {
            $this->error('投放点不存在或已停用');
        }
        if ((string)$drop['shop_type'] !== $shopType) {
            $this->error('该投放点与商铺类型不匹配');
        }
        $now = time();
        $save = [
            'shop_name'     => $shopName,
            'shop_type'     => $shopType,
            'shop_type_name'=> StreetShopConfig::shopTypeLabel($shopType),
            'cate_id'       => (int)$cate['id'],
            'cate_name'     => (string)$cate['name'],
            'drop_point_id' => $dropPointId,
            'update_time'   => $now,
        ];
        $exists = Db::name('street_shop_merchant')->where('user_id', $this->user_id)->find();
        if (!empty($exists)) {
            Db::name('street_shop_merchant')->where('id', (int)$exists['id'])->update($save);
            $id = (int)$exists['id'];
        } else {
            $save['user_id'] = $this->user_id;
            $save['status'] = 1;
            $save['create_time'] = $now;
            $id = (int)Db::name('street_shop_merchant')->insertGetId($save);
        }
        $row = Db::name('street_shop_merchant')->where('id', $id)->find();
        $this->success('已保存', $this->formatMerchantRow($row));
    }

    private function formatMerchantRow(array $row): array
    {
        $drop = null;
        if (!empty($row['drop_point_id'])) {
            $d = Db::name('street_shop_drop_point')->where('id', (int)$row['drop_point_id'])->find();
            if (!empty($d)) {
                $drop = $this->formatDropPointRow($d);
            }
        }
        return [
            'id'              => (int)$row['id'],
            'shop_name'       => (string)$row['shop_name'],
            'shop_type'       => (string)$row['shop_type'],
            'shop_type_name'  => (string)($row['shop_type_name'] ?? StreetShopConfig::shopTypeLabel($row['shop_type'] ?? '')),
            'cate_id'         => (int)$row['cate_id'],
            'cate_name'       => (string)$row['cate_name'],
            'drop_point_id'   => (int)$row['drop_point_id'],
            'drop_point'      => $drop,
        ];
    }

    private function formatDropPointRow(array $row): array
    {
        return [
            'id'          => (int)$row['id'],
            'name'        => (string)$row['name'],
            'address'     => (string)$row['address'],
            'lng'         => (float)$row['lng'],
            'lat'         => (float)$row['lat'],
            'shop_type'   => (string)$row['shop_type'],
            'shop_type_name' => StreetShopConfig::shopTypeLabel($row['shop_type'] ?? ''),
            'cate_id'     => (int)$row['cate_id'],
            'cate_name'   => (string)$row['cate_name'],
        ];
    }
}
