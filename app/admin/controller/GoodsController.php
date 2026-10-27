<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use api\wxapp\model\CategoryModel;
use app\admin\model\goods\GoodsAttrModel;
use app\admin\model\goods\GoodsAttrValueModel;
use api\wxapp\service\GoodsWebUnlockService;
use app\admin\model\goods\GoodsModel;
use app\admin\model\goods\GoodsSkuAttrModel;
use app\admin\model\goods\GoodsSkuModel;
use app\admin\model\goods\GoodsCategoryModel;
use cmf\controller\AdminBaseController;
use cmf\lib\Upload;
use think\facade\Db;
use think\facade\Log;

class GoodsController extends AdminBaseController
{
    //商品列表列表
    public function index()
    {
        $keyword = $this->request->param('keyword');
        $first_cate = $this->request->param('first_cate');
        $second_cate = $this->request->param('second_cate');

        $map = [];
        $map[] = ['a.delete_time', '=', 0];
        if (!empty($keyword)) $map[] = ['a.goods_name', 'like', "%$keyword%"];
        if (!empty($first_cate)) $map[] = ['a.first_cate', '=', $first_cate];
        if (!empty($second_cate)) $map[] = ['a.second_cate', '=', $second_cate];
        $Model = new GoodsModel();
        $list = $Model->getInfo($map);
        $list->each(function ($item) {
            $aid = (int)($item['require_active_id'] ?? 0);
            if ($aid > 0) {
                if (GoodsModel::isValidSignRequireActiveId($aid)) {
                    $item['require_active_name'] = GoodsModel::getRequireActiveName($aid);
                } else {
                    $item['require_active_name'] = '已失效';
                }
            } else {
                $item['require_active_name'] = '';
            }
            return $item;
        });
        $list->appends(['keyword' => $keyword, 'first_cate' => $first_cate, 'second_cate' => $second_cate]);
        // 获取分页显示
        $page = $list->render();
        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('cate_list', self::getCateList());
        $this->assign('geo_zone_list', self::getGeoZoneList());
        return $this->fetch();
    }

    public static function getCateList()
    {
        $map = [];
        $map[] = ['parent_id', '=', 0];
        $map[] = ['status', 'in', [1, 2]];
        $map[] = ['cate_name', '<>', '地理产区'];
        return GoodsCategoryModel::field('id,cate_name')->where($map)->select();
    }

    /** 地理产区子分类（17 产区 + 综合文创） */
    public static function getGeoZoneList()
    {
        $parentId = GoodsCategoryModel::where([
            ['parent_id', '=', 0],
            ['cate_name', '=', '地理产区'],
            ['delete_time', '=', 0],
        ])->value('id');
        if (empty($parentId)) {
            return [];
        }
        return GoodsCategoryModel::field('id,cate_name')
            ->where([
                ['parent_id', '=', $parentId],
                ['status', '=', 1],
                ['delete_time', '=', 0],
            ])
            ->order('list_order asc,id asc')
            ->select();
    }

    //添加商品或编辑商品
    public function add()
    {

        $GoodsModel = new GoodsModel();

        $goods_id = $this->request->param('goods_id');
        $goods_info = $GoodsModel->getInfo(['a.id' => $goods_id], 1, $goods_id);

        if ($this->request->isPost()) {
            $GoodsSkuModel = new GoodsSkuModel();
            $AttrModel = new GoodsAttrModel();
            $AttrValueModel = new GoodsAttrValueModel();
            $SkuAttrModel = new GoodsSkuAttrModel();

            $param = $this->request->param();
            if (empty($param['photos'])) $this->error('请上传商品图片');
            $param['thumb'] = $param['photos'][0];
            $param['photos'] = implode(',', $param['photos']);
            if (!empty($param['image'])) $param['image'] = implode(',', $param['image']);
            $param['update_time'] = time();


            if (empty($param['is_take']) && empty($param['is_express'])) {
                $this->error('最少选一个收货方式');
            }
            if (empty($param['is_take'])) $param['is_take'] = 0;
            if (empty($param['is_express'])) $param['is_express'] = 0;
            if (empty($param['user_id'])) $param['check_status'] = 2;
            $param['require_active_id'] = GoodsModel::normalizeRequireActiveId($param['require_active_id'] ?? 0);
            if ($param['require_active_id'] > 0 && !GoodsModel::isValidSignRequireActiveId($param['require_active_id'])) {
                $this->error('前置签到活动无效，请选择已开启签到的活动');
            }
            $param['require_web_unlock_key'] = trim((string)($param['require_web_unlock_key'] ?? ''));
            $param['require_web_unlock_label'] = trim((string)($param['require_web_unlock_label'] ?? ''));
            if (strlen($param['require_web_unlock_key']) > 64) {
                $this->error('Web 解锁标识过长（最多 64 字符）');
            }
            if (strlen($param['require_web_unlock_label']) > 200) {
                $this->error('Web 解锁提示文案过长（最多 200 字）');
            }
            Db::startTrans();

            if (empty($goods_id)) {
                $param['create_time'] = time();
                $goods_id = $GoodsModel->strict(false)->insertGetId($param);
            } else {
                $goods_info->save($param);
            }

            $delete_where = [['goods_id', '=', $goods_id]];
            if (!empty($param['is_attribute'])) {
                //多规格
                $GoodsSkuModel->where($delete_where)->save(['status' => 0]);
                $AttrModel->where($delete_where)->save(['status' => 0]);
                $AttrValueModel->where($delete_where)->save(['status' => 0]);
                $SkuAttrModel->where($delete_where)->save(['status' => 0]);

                if (empty($param['attr_data']) || empty($param['skus'])) $this->error('请设置商品规格');

                $attr_id_arr = [];
                $uuid_to_name = [];
                //属性
                foreach ($param['attr_data'] as $attr_key => $attr_item) {
                    $map = [];
                    $map[] = ['attr_name', '=', $attr_item['title']];
                    $map[] = ['goods_id', '=', $goods_id];

                    $is_attr = $AttrModel->where($map)->find();
                    if (empty($is_attr)) {
                        $attr_id = $AttrModel->insertGetId([
                            'attr_name' => $attr_item['title'],
                            'status' => 1,
                            'goods_id' => $goods_id,
                            'create_time' => time(),
                            'list_order' => $attr_key
                        ]);
                    } else {
                        $is_attr->save(['status' => 1, 'list_order' => $attr_key]);
                        $attr_id = $is_attr['id'];
                    }
                    $attr_id_arr[] = $attr_id;
                    $attr_value_arr = $attr_item['child'];
                    foreach ($attr_value_arr as $attr_value_key => $attr_value_item) {
                        $uuid_to_name[$attr_value_item['id']] = $attr_value_item['title'];
                        $map = [];
                        $map[] = ['attr_id', '=', $attr_id];
                        $map[] = ['attr_value_name', '=', $attr_value_item['title']];
                        $is_attr_value = $AttrValueModel->where($map)->find();
                        if (empty($is_attr_value)) {
                            $AttrValueModel->insert([
                                'attr_id' => $attr_id,
                                'attr_value_name' => $attr_value_item['title'],
                                'status' => 1,
                                'goods_id' => $goods_id,
                                'create_time' => time(),
                                'list_order' => $attr_value_key
                            ]);
                        } else {
                            $is_attr_value->save(['status' => 1, 'list_order' => $attr_value_key]);
                        }
                    }
                }
                //规格数据字段
                $insert_field_arr = ['price', 'stock'];

                //规格
                foreach ($param['skus'] as $uuid_key => $sku_item) {
                    $uuid_key_arr = explode('-', $uuid_key);
                    $sku_name_arr = [];
                    foreach ($uuid_key_arr as $uuid_key_item) {
                        $sku_name_arr[] = $uuid_to_name[$uuid_key_item];
                    }
                    $sku_name = implode(';', $sku_name_arr); //遇见特殊规格，如规格里边有;，则替换其他特殊字符
                    $map = [];
                    $map[] = ['goods_id', '=', $goods_id];
                    $map[] = ['sku_name', '=', $sku_name];
                    $have_sku = $GoodsSkuModel->where($map)->find();

                    $thumb = !empty($sku_item['image']) ? self::getImageFilepath($sku_item['image']) : null;
                    $up_data = [
                        'update_time' => time(),
                        'status' => 1,
                        'thumb' => $thumb
                    ];
                    foreach ($insert_field_arr as $insert_field) {
                        $up_data[$insert_field] = $sku_item[$insert_field] ?? null;
                    }
                    if ($have_sku) {
                        $have_sku->save($up_data);
                        $sku_id = $have_sku['id'];
                    } else {
                        $up_data['sku_name'] = $sku_name;
                        $up_data['goods_id'] = $goods_id;
                        $up_data['create_time'] = time();
                        $sku_id = $GoodsSkuModel->insertGetId($up_data);
                    }
                    foreach ($sku_name_arr as $k => $sku_value_item) {
                        $map = [];
                        $map[] = ['goods_id', '=', $goods_id];
                        $map[] = ['attr_id', '=', $attr_id_arr[$k]];
                        $map[] = ['status', '=', 1];
                        $map[] = ['attr_value_name', '=', $sku_value_item];
                        $attr_value_id = $AttrValueModel->where($map)->value('id');
                        $map = [];
                        $map[] = ['sku_id', '=', $sku_id];
                        $map[] = ['attr_id', '=', $attr_id_arr[$k]];
                        $map[] = ['attr_value_id', '=', $attr_value_id];

                        $have_sku_arr = $SkuAttrModel->where($map)->find();
                        if ($have_sku_arr) {
                            $have_sku_arr->save(['status' => 1]);
                        } else {
                            $SkuAttrModel->insert([
                                'goods_id' => $goods_id,
                                'sku_id' => $sku_id,
                                'attr_id' => $attr_id_arr[$k],
                                'attr_value_id' => $attr_value_id,
                                'status' => 1,
                                'create_time' => time(),
                            ]);
                        }
                    }
                    //最小金额 库存
                    $min_price = $GoodsSkuModel->where(['status' => 1, 'goods_id' => $goods_id])->min('price');
                    $total_stock = $GoodsSkuModel->where(['status' => 1, 'goods_id' => $goods_id])->sum('stock');
                    $GoodsModel->where(['id' => $goods_id])->save(['stock' => $total_stock, 'price' => $min_price]);
                }

                $delete_where[] = ['status', '=', 0];
            }
            //删除其他规格数据
            $GoodsSkuModel->where($delete_where)->delete();
            $AttrModel->where($delete_where)->delete();
            $AttrValueModel->where($delete_where)->delete();
            $SkuAttrModel->where($delete_where)->delete();
            Db::commit();
            $this->success('保存成功');
        } else {
            $this->assign('goods_id', $goods_id);
            $this->assign('data', $goods_info);

            $this->assign('cate_list', self::getCateList());
            $this->assign('geo_zone_list', self::getGeoZoneList());
            $requireActiveId = (int)($goods_info['require_active_id'] ?? 0);
            $this->assign('sign_active_list', GoodsModel::getSignActiveListForAdmin($requireActiveId));
            $this->assign('web_unlock_presets', GoodsWebUnlockService::presetOptionsForAdmin());
            return $this->fetch();
        }
    }

    //获取图片路径
    public static function getImageFilepath($image)
    {
        $parsedUrl = parse_url($image);
        $path = ltrim($parsedUrl['path'] ?? '', '/'); // 去掉开头的斜杠

        // 如果路径以 upload/ 开头，则去掉它
        if (strpos($path, 'upload/') === 0) {
            $path = substr($path, strlen('upload/'));
        }

        return $path;

    }


    public function delete()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id');
            $Model = GoodsModel::find($id);
            if (empty($Model)) {
                $this->error('商品不存在');
            } else {
                $Model->save(['delete_time' => time()]);
                $this->success("删除成功！");
            }
        } else {
            $this->error('非法操作');
        }
    }

    public function upManyGoods()
    {

        $GoodsModel = new GoodsModel();
        $ids = $this->request->param('ids');
        $status = $this->request->param('yes');
        $map = [];
        $map[] = ['id', 'in', $ids];
        $GoodsModel->where($map)->save(['status' => $status]);
        $this->success('操作成功');
    }

    public function deleteManyGoods()
    {
        $GoodsModel = new GoodsModel();
        $ids = $this->request->param('ids');
        $map = [];
        $map[] = ['id', 'in', $ids];

        $GoodsModel->where($map)->save(['delete_time' => time()]);

        $this->success('操作成功');
    }

    public function listOrder()
    {
        $Model = new GoodsModel();
        parent::listOrders($Model);
        $this->success("排序更新成功！");
    }

    public function setGoods()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $field = $this->request->param('field');
        $Model = GoodsModel::find($id);

        $res = $Model->save([$field => $status]);

        if ($res) {
            $this->success("设置成功！");
        } else {
            $this->error('设置失败');
        }
    }

    /*
    * 商品分类列表
*/
    public function categoryList()
    {

        $keyword = $this->request->param('keyword');
        $parent_id = $this->request->param('parent_id', 0);

        $map = [];
        $map[] = ['status', 'in', [1, 2]];
        $map[] = ['parent_id', '=', $parent_id];

        if (!empty($keyword)) $map[] = ['cate_name', 'like', "%$keyword%"];
        $Model = new GoodsCategoryModel();
        $list = $Model->where($map)->order('id desc')->paginate(10);

        $list->appends(['keyword' => $keyword, 'parent_id' => $parent_id]);
        // 获取分页显示

        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('parent_id', $parent_id);
        return $this->fetch();
    }

    /*
     * 添加分类
     */
    public function addCategory()
    {
        $parent_id = $this->request->param('parent_id');
        if ($this->request->isPost()) {
            $Model = new GoodsCategoryModel();
            $data = $this->request->param();
            $data['create_time'] = time();
            $data['status'] = 1;
            if (empty($parent_id)) $parent_id = 0;
            $data['parent_id'] = $parent_id;
            $Model->save($data);

            $this->success("添加成功！", url("categoryList", ['parent_id' => $parent_id]));
        } else {
            $this->assign('parent_id', $parent_id);
            return $this->fetch();
        }
    }

    /*
    * 编辑分类
    */
    public function editCategory()
    {
        $id = $this->request->param('id');
        $data = GoodsCategoryModel::find($id);
        if ($this->request->isPost()) {
            $param = $this->request->param();
            if (empty($data)) {
                $this->error('分类不存在');
            } else {
                $data->save($param);
                $this->success("保存成功！", url("categoryList", ['parent_id' => $data['parent_id']]));
            }
        } else {
            $this->assign('data', $data);
            return $this->fetch();
        }
    }

    /*
   * 删除分类
   */
    public function deleteCategory()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id');
            $Model = GoodsCategoryModel::find($id);
            if (empty($Model)) {
                $this->error('分类不存在');
            } else {
                $Model->save(['status' => 0]);
                $this->success("删除成功！");
            }
        } else {
            $this->error('非法操作');
        }
    }

    public function setCategory($id, $field, $status)
    {
        $data = GoodsCategoryModel::find($id);
        $data->save([$field => $status]);
        $this->success('设置成功');
    }

    public function cateListOrder()
    {
        $Model = new GoodsCategoryModel();
        parent::listOrders($Model);
        $this->success("排序更新成功！");
    }


    public function uploadImage()
    {
        $uploader = new Upload();

        $result = $uploader->upload();

        if ($result === false) {
            $this->error($uploader->getError());
        } else {
            $result['url'] = cmf_get_asset_url($result['filepath']);
            $this->success('上传成功', '', $result);
        }
    }


    //获取商品编辑数据
    public function getEditData()
    {
        $goods_id = $this->request->param('goods_id');

        $GoodsSkuModel = new GoodsSkuModel();
        $GoodsAttrModel = new GoodsAttrModel();
        $GoodsAttrValueModel = new GoodsAttrValueModel();
        $GoodsSkuAttrModel = new GoodsSkuAttrModel();
        $GoodsModel = new GoodsModel();
        $goods_info = $GoodsModel->where(['id' => $goods_id])->find();
        $is_attribute = $goods_info['is_attribute'];
        $filed_arr = ['image', 'price', 'stock'];
        $skuData = [];
        $attr_list = [];
        if ($is_attribute == 1) {
            $attr_list = $GoodsAttrModel->field('id,attr_name title')->where(['status' => 1, 'goods_id' => $goods_id])->order('list_order')->select();

            foreach ($attr_list as &$item) {
                $item['child'] = $GoodsAttrValueModel->field('id,attr_value_name title')->where(['attr_id' => $item['id'], 'status' => 1])->order('list_order')->select();
            }

            $sku_list = $GoodsSkuModel->where(['goods_id' => $goods_id, 'status' => 1])->select();

            foreach ($sku_list as $value) {
                $value['image'] = $value['thumb'] ? cmf_get_asset_url($value['thumb']) : '';
                $map = [];
                $map[] = ['sku_id', '=', $value['id']];
                $map[] = ['status', '=', 1];
                $attr_value_id_arr = $GoodsSkuAttrModel->where($map)->column('attr_value_id');
                $attr_value_id_str = implode('-', $attr_value_id_arr);
                foreach ($filed_arr as $field) {
                    $skuData['skus[' . $attr_value_id_str . '][' . $field . ']'] = $value[$field];
                }
            }

        } else {
            foreach ($filed_arr as $field) {
                $skuData[$field] = $goods_info[$field] ?? null;
            }
        }

        $return['skuData'] = $skuData;
        $return['specData'] = $attr_list;
        $return['is_attribute'] = $is_attribute;
        $this->success('', '', $return);
    }


    //页面获取分类数据
    public function getJsonCateList()
    {
        $pid = $this->request->param('pid', 0);
        $shop_id = $this->request->param('shop_id');
        $map = [];

        $map['parent_id'] = $pid;
        $map['status'] = 1;
        if ($shop_id) {
            $map['shop_id'] = $shop_id;
        }
        $CategoryModel = new GoodsCategoryModel();
        $result = $CategoryModel->where($map)->select()->toArray();
        $this->success('', '', $result);
    }


    //审核
    public function fix_status()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $msg = $this->request->param('text');
        $model = new GoodsModel();
        if ($status == 1) {
            $save = ['check_status' => 2];
            $model->where('id', $id)->save($save);
            $this->success('审核通过！');
        } else {
            $save = ['check_msg' => $msg, 'check_status' => 3];
            $model->where('id', $id)->save($save);
            $this->success('保存成功！');
        }

    }
}

