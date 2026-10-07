<?php

namespace api\wxapp\controller;

use app\admin\model\forum\StreetShopConfig;
use think\facade\Db;

/**
 * 沿街商铺：用户登记商户身份（点位照片走 clock_scene_point hub=street）
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

    public function save_merchant()
    {
        $this->checkUser();
        $shopName = trim((string)$this->request->param('shop_name', ''));
        $shopType = StreetShopConfig::normalizeShopType((string)$this->request->param('shop_type', ''));
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
        $now = time();
        $save = [
            'shop_name'      => $shopName,
            'shop_type'      => $shopType,
            'shop_type_name' => StreetShopConfig::shopTypeLabel($shopType),
            'cate_id'        => (int)$cate['id'],
            'cate_name'      => (string)$cate['name'],
            'drop_point_id'  => 0,
            'update_time'    => $now,
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
        Db::name('clock_scene_point')
            ->where('user_id', $this->user_id)
            ->where('hub_key', 'street')
            ->where('source', 0)
            ->update([
                'cate_id'     => (int)$cate['id'],
                'cate_name'   => (string)$cate['name'],
                'update_time' => $now,
            ]);
        $row = Db::name('street_shop_merchant')->where('id', $id)->find();
        $this->success('已保存', $this->formatMerchantRow($row));
    }

    private function formatMerchantRow(array $row): array
    {
        return [
            'id'             => (int)$row['id'],
            'shop_name'      => (string)$row['shop_name'],
            'shop_type'      => (string)$row['shop_type'],
            'shop_type_name' => (string)($row['shop_type_name'] ?? StreetShopConfig::shopTypeLabel($row['shop_type'] ?? '')),
            'cate_id'        => (int)$row['cate_id'],
            'cate_name'      => (string)$row['cate_name'],
        ];
    }
}
