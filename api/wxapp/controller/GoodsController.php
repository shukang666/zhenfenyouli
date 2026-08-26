<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------

namespace api\wxapp\controller;


use api\wxapp\model\CommonModel;
use api\wxapp\model\GoodsColModel;
use api\wxapp\model\OrderCommentModel;
use app\admin\model\goods\CartModel;
use app\admin\model\goods\GoodsCategoryModel;
use app\admin\model\goods\GoodsModel;
use app\admin\model\goods\GoodsSkuModel;
use app\admin\model\shop\ShopModel;
use think\facade\Log;

/**
 * Class IndexController
 * @package api\demo\controller
 */
class GoodsController extends AuthController
{


    /**
     * 添加商品
     * @OA\Post(
     *tags={"商城"},
     *path="/wxapp/goods/add_goods",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="photos",description="商品照片",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="goods_name",description="商品名称",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="price",description="商品价格",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="discount",description="折扣比例（%）",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_take",description="1：自提 ，2：否",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_express",description="1:快递，2：否",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="stock",description="商品库存",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="first_cate",description="商品类别",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="content",description="商品详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image",description="商品多图",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="status",description="用于上下架 1：上架，2：下架",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_goods()
    {

        $model = new GoodsModel();
        $this->checkUser();
        $params = $this->request->param();
//        $this->error("1");
//        $this->error("{$params['status']}");
        if (!empty($params['image'])) $params['image'] = implode(',', $params['image']);
        if (!empty($params['photos'])) {
            $params['thumb'] = $params['photos'][0];
            $params['photos'] = implode(',', $params['photos']);
        }
        if (empty($params['id'])) {
            $params['user_id'] = $this->user_id;
            $model->save($params);
        } else {
            $model->update($params, ['id' => $params['id']]);
        }
        $this->success('提交成功');
    }

    /**
     * 商家商品列表
     * @OA\Post(
     *tags={"商城"},
     *path="/wxapp/goods/get_shop_goods_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="shop_id",description="商家id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="keyword",description="关键字",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_shop_goods_list()
    {

        $model = new GoodsModel();
        $id = $this->request->param('id');
        $shop_id = $this->request->param('shop_id');
        if ($id) {
            $item = $model->find($id);
            $item['image'] = CommonModel::getArrImageFullUrl($item['image']);
            $item['photos'] = CommonModel::getArrImageFullUrl($item['photos']);
            $item = CommonModel::getOne($item, ['thumb']);
            $GoodsCategoryModel = new GoodsCategoryModel();
            $item['cate_name'] = $GoodsCategoryModel->where('id', $item['first_cate'])->value('cate_name');
            $this->success('获取成功', $item);
        }
        $this->checkUser();
        if ($shop_id) {
            if ($shop_id == -1) $shop_id = 0;
            $user_id = ShopModel::where('id', $shop_id)->value('user_id');
        } else {
            $user_id = $this->user_id;
        }
        $user_id = (int) $user_id;
//        Log::write($user_id,'get_shop_goods_list');
        $keyword = $this->request->param('keyword');
        $map = [];
        $map[] = ['status', '=', 1];
        $map[] = ['check_status', '=', 2];
        $map[] = ['user_id', '=', $user_id];
        if (!empty($keyword)) {
            $map[] = ['goods_name', 'like', '%' . $keyword . '%'];
        }
        $list = $model
            ->where($map)
            ->field('id,goods_name,thumb,price,stock,status,shop_id')
            ->order('id desc')
            ->paginate($this->page_size);
        foreach ($list as &$item) {
            $item = CommonModel::getOne($item, ['thumb']);
        }
        $this->success('提交成功', $list);
    }


    /**
     * 商品的商家信息
     * @OA\Post(
     *tags={"商城"},
     *path="/wxapp/goods/get_shop",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_shop(ShopModel $model, OrderCommentModel $CommentModel)
    {
        $id = $this->request->param('id', 0);
        if ($id == -1) {
            $point = $CommentModel->where(['shop_id' => 0])->avg('point');
            $item = [
                'id' => -1,
                'shop_name' => '平台',
                'avatar' => cmf_get_asset_url(cmf_get_option('set_config')['app_logo'])
                , 'point' => $point
            ];
            $this->success('请求成功', $item);
        }
//        Log::write($id,'get_shop_id');
//        if (!$id)$this->error('暂无数据');
        $item = $model->where('user_id', $id)->find();
        if (empty($item)) $this->error('暂无数据');
        $item['point'] = $CommentModel->where(['shop_id' => $item['user_id']])->avg('point');
        $item = CommonModel::getOne($item, ['license', 'avatar']);
        $this->success('请求成功', $item);
    }


    public function get_shop_goods_list1(ShopModel $model)
    {
        $GoodsModel = new GoodsModel();
        $map[] = ['a.user_id', '=', $this->user_id];
        $order = 'a.list_order,a.id desc';
        $list = $GoodsModel->getInfo($map, $this->page_size, 0, GoodsModel::getListField(), $order);
        $this->success('请求成功', $list);
    }

    /**
     * @OA\Get(
     *     tags={"商城"},
     *     summary="获取商品分类",
     *     path="/wxapp/goods/get_category",
     *
     *
     *         @OA\Parameter(
     *         name="is_home",
     *         in="query",
     *         description="1首页分类",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Parameter(
     *         name="parent_id",
     *         in="query",
     *         description="父级分类0取第一级，传值取下级",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_category($parent_id = 0, $is_home = '', $goods_type = 1)
    {

        $map = [];
        $map[] = ['status', '=', 1];

        $map[] = ['parent_id', '=', $parent_id];
//        $map[] = ['goods_type', '=', $goods_type];
        if ($is_home == 1) {
            $map[] = ['is_home', '=', 1];
        }
        $CategoryModel = new GoodsCategoryModel();
        $list = $CategoryModel->field('id,cate_name,thumb,parent_id')->where($map)->order('list_order')->select();

        $list = getFullData($list);
        $this->success('获取成功', $list);
    }

    /**
     * @OA\Get(
     *     tags={"商城"},
     *     summary="获取商品列表",
     *     path="/wxapp/goods/get_goods_list",
     *@OA\Parameter(name="openid",description="关键词",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="keyword",description="一级分类",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="first_cate",description="二级分类",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="second_cate",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_recommend",description="1：推荐",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_new",description="1新品",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="sell_count_sort",description="1:销量",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="price_sort",description="1：价格升序，2：价格降序",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */

    public function get_goods_list()
    {
        $param = $this->request->param();
        $type = $this->request->param('type');

        $GoodsModel = new GoodsModel();
        $map = [];
        $map[] = ['a.status', '=', 1];
        $map[] = ['check_status', '=', 2];
        $map[] = ['a.delete_time', '=', 0];
        if (!empty($param['keyword'])) $map[] = ['a.goods_name', 'like', "%" . $param['keyword'] . "%"];
        if (!empty($param['first_cate'])) $map[] = ['a.first_cate', '=', $param['first_cate']];
        if (!empty($param['second_cate'])) $map[] = ['a.second_cate', '=', $param['second_cate']];
        if (!empty($param['is_recommend'])) $map[] = ['a.is_recommend', '=', 1];
        if (!empty($param['is_new'])) $map[] = ['a.is_new', '=', 1];
        $order = '';

        if (!empty($param['sell_count_sort'])) {
            $order = 'a.sell_count desc,';
        }
        if (!empty($param['price_sort'])) {

            switch ($param['price_sort']) {
                case 1:
                    $order .= 'a.price asc,';
                    break;
                case 2:
                    $order .= 'a.price desc,';
                    break;
            }

        }

        $order .= 'a.list_order,a.id desc';
//        var_dump($order);exit;
        $list = $GoodsModel->getInfo($map, $this->page_size, 0, GoodsModel::getListField(), $order);

        $this->success('获取成功', $list);

    }

    /**
     * @OA\Get(
     *     tags={"商城"},
     *     summary="获取商品详情",
     *     path="/wxapp/goods/get_goods_detail",
     *
     *
     *       @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */

    public function get_goods_detail($id = '')
    {

        $GoodsModel = new GoodsModel();
        $map = [];
        $map[] = ['a.id', '=', $id];
        $data = $GoodsModel->getInfo($map, $this->page_size, $id, 'a.*', 'a.list_order,a.id desc', 1);

        $OrderCommentModel = new OrderCommentModel();
        $data['point_avg'] = $OrderCommentModel->where('goods_id', $id)->avg('point');
        $data['is_collect'] = GoodsColModel::where(['goods_id' => $id, 'user_id' => $this->user_id,])->count();
        $this->success('获取成功', $data);
    }

    /**
     * @OA\Get(
     *     tags={"商城"},
     *     summary="获取商品评论",
     *     path="/wxapp/goods/get_goods_comment",
     *       @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_goods_comment($id = '')
    {
        $OrderCommentModel = new OrderCommentModel();
        $map = [];
//        $map[] = ['a.status', '=', 1];
        $map[] = ['a.goods_id', '=', $id];

        $list = $OrderCommentModel->getList($map);

        $this->success('获取成功', $list);

    }

    /**
     * @OA\Post(
     *     tags={"商城"},
     *     summary="加入购物车",
     *     path="/wxapp/goods/add_cart",
     *
     *
     *       @OA\Parameter(
     *         name="goods_id",
     *         in="query",
     *         description="goods_id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="sku_id",
     *         in="query",
     *         description="有就传",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="count",
     *         in="query",
     *         description="数量",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_cart($goods_id = '', $sku_id = 0, $count = 1)
    {
        $this->checkUser();
        if (empty($goods_id)) $this->error('商品id不能为空');

        $CartModel = new CartModel();
        $GoodsModel = new GoodsModel();
        $goods_info = $GoodsModel->where('id', $goods_id)->find();
        $map = [];
        $map['shop_id'] = $goods_info['user_id'];
        $map['goods_id'] = $goods_id;
        $map['sku_id'] = $sku_id;
        $map['user_id'] = $this->user_id;
        $is = $CartModel->where($map)->count();
        if ($is > 0) {
            $res = $CartModel->where($map)->inc('count', $count)->update();

            $new = $CartModel->where($map)->value('count');
            if ($new == 0) {
                $CartModel->where($map)->delete();
            }

        } else {
            $data_i = [];
            $data_i['shop_id'] = $goods_info['user_id'] ?? -1;
            $data_i['sku_id'] = $sku_id;
            $data_i['user_id'] = $this->user_id;
            $data_i['count'] = $count;
            $data_i['goods_id'] = $goods_id;
            $data_i['is_take'] = $goods_info['is_take'];
            $data_i['is_express'] = $goods_info['is_express'];
            $data_i['create_time'] = time();
            $res = $CartModel->save($data_i);
        }
        if ($res) {
            $this->success('加入成功');
        } else {
            $this->error('加入失败');
        }
    }


    /**
     * @OA\Post(
     *     tags={"商城"},
     *     summary="我的购物车",
     *     path="/wxapp/goods/my_cart",
     *     @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="delivery_type",
     *         in="query",
     *         description="1:快递，2：自提",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function my_cart()
    {
        $this->checkUser();
        $CartModel = new CartModel();
        $GoodsModel = new GoodsModel();
        $shopModel = new ShopModel();
        $SkuModel = new GoodsSkuModel();
        $delivery_type = $this->request->param('delivery_type', 1);
        if ($delivery_type == 1) {
            $map[] = ['b.is_express', '=', 1];
        } else {
            $map[] = ['b.is_take', '=', 1];
        }
        $list = $CartModel
            ->alias('a')
            ->field('a.*')
            ->where(['a.user_id' => $this->user_id])
            ->field('a.shop_id')
            ->group('a.shop_id')
            ->order('a.id desc')
            ->paginate($this->page_size)
            ->each(function ($itema) use ($shopModel, $CartModel, $GoodsModel, $SkuModel, $map) {
                $shop_info = $shopModel->where('user_id', $itema['shop_id'])
                    ->field('shop_name,avatar,address')
                    ->withAttr('avatar', function ($v) {
                        return cmf_get_asset_url($v);
                    })->find();
                if ($shop_info) {
                    $itema['shop_name'] = $shop_info['shop_name'];
                    $itema['avatar'] = $shop_info['avatar'];
                    $itema['address'] = $shop_info['address'];
                } else {
                    $itema['id'] = -1;
                    $itema['shop_name'] = '平台';
                    $itema['avatar'] = cmf_get_asset_url(cmf_get_option('set_config')['app_logo']);
                }

                $list = $CartModel
                    ->alias('a')
                    ->field('a.*')
                    ->where(['a.user_id' => $this->user_id,
                        'a.shop_id' => $itema['shop_id']])
                    ->where($map)
                    ->join('goods b', 'b.id=a.goods_id')
                    ->order('a.id desc')->select();
                foreach ($list as &$item) {
                    $info = $GoodsModel->where(['id' => $item['goods_id']])->find();
                    $item['goods_name'] = $info['goods_name'];
                    $item['point'] = $info['point'];
                    $item['thumb'] = cmf_get_asset_url($info['thumb']);
                    if (!empty($item['sku_id'])) {
                        $info = $SkuModel->where(['id' => $item['sku_id']])->find();
                        $item['sku_name'] = $info['sku_name'];
                    }
                    $item['price'] = $info['price'];
                    $item['stock'] = $info['stock'];
                }
                $itema['goods_list'] = $list;
            });
        $this->success('获取成功', $list);


    }


    /**
     * @OA\Post(
     *     tags={"商城"},
     *     summary="设置购物车，加减 删除",
     *     path="/wxapp/goods/set_cart",
     *
     *
     *      @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="购物车id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="count",
     *         in="query",
     *         description="数量",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Parameter(
     *         name="delete",
     *         in="query",
     *         description="传1 删除",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function set_cart($id = '', $count = '', $delete = '')
    {
        $this->checkUser();

        if (empty($id)) $this->error('请选择');

        $CartModel = new CartModel();
        if ($delete == 1) {
            $map = [];
            $map[] = ['user_id', '=', $this->user_id];
            if (is_array($id)) {
                $map[] = ['id', 'in', $id];
            } else {
                $map[] = ['id', '=', $id];
            }
            $CartModel->where($map)->delete();
            $this->success('删除成功');
        } else {
            if (!is_numeric($count)) $this->error('错误');
            $info = $CartModel->where(['id' => $id, 'user_id' => $this->user_id])->find();

            if (empty($info)) {
                $this->error('错误');
            }
            if ($count == 0) {
                $info->delete();
            } else {
                $info->save(['count' => $count]);
            }
            $this->success('成功');

        }
    }

    /**
     * @OA\Get(
     *     tags={"商城"},
     *     summary="收藏 取消收藏",
     *     path="/wxapp/goods/col_goods",
     *
     *
     *       @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function col_goods()
    {
        $this->checkUser();
        $id = $this->request->param('id');
        $GoodsColModel = new GoodsColModel();
        $GoodsModel = new GoodsModel();
        $map = [];
        $map[] = ['goods_id', '=', $id];
        $map[] = ['user_id', '=', $this->user_id];
        $is = $GoodsColModel->where($map)->find();
        if ($is) {
            $GoodsModel->where(['id' => $id])->dec('col_total', 1)->update();
            $is->delete();
            $this->success('取消成功');
        } else {
            $GoodsModel->where(['id' => $id])->inc('col_total', 1)->update();
            $GoodsColModel->insert([
                'goods_id' => $id,
                'user_id' => $this->user_id,
                'create_time' => time()
            ]);
            $this->success('收藏成功');
        }
    }


    /**
     * @OA\Get(
     *     tags={"商城"},
     *     summary="我的收藏",
     *     path="/wxapp/goods/my_col",
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function my_col()
    {
        $this->checkUser();
        $map = [];
        $map[] = ['a.user_id', '=', $this->user_id];

        $GoodsColModel = new GoodsColModel();
        $list = $GoodsColModel->getInfo($map, $this->page_size);
        $this->success('获取成功', $list);
    }


    /**
     * @OA\Get(
     *     tags={"商城"},
     *     summary="排行榜",
     *     path="/wxapp/goods/get_goods_ranking_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="1:销量，2：点赞",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */

    public function get_goods_ranking_list()
    {
        $param = $this->request->param();
        $type = $this->request->param('type', 1);

        $GoodsModel = new GoodsModel();
        $map = [];
        $map[] = ['a.status', '=', 1];
        $order = '';
        if ($type == 1) {
            $order = 'a.sell_count desc,';
        } elseif ($type == 2) {
            $order = 'a.col_total desc,';
        }
        $order .= 'a.list_order,a.id desc';
        $list = $GoodsModel->getInfo($map, $this->page_size, 0, GoodsModel::getListField(), $order);

        $this->success('获取成功', $list);

    }

}
