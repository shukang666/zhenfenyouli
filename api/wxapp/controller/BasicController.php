<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use api\wxapp\model\CommonModel;
use api\wxapp\model\AddressModel;
use api\wxapp\model\AdviceModel;
use api\wxapp\model\AreaModel;
use api\wxapp\model\CategoryModel;
use app\admin\controller\WxOrderController;
use app\admin\model\shop\NumberLogModel;
use app\admin\model\shop\ShopInfoModel;
use app\admin\model\shop\ShopModel;
use app\admin\model\SlideItemModel;
use app\admin\model\SlideModel;
use cmf\lib\Upload;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use think\Model;

class BasicController extends AuthController
{

    /**
     * @OA\Post(
     *     tags={"小程序-基础"},
     *     summary="测试",
     *     path="/wxapp/basic/test",
     *
     *        @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *        @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num",
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
    public function test($order_num=''){

    }
    /**
     * @OA\Post(
     *     tags={"小程序-基础"},
     *     summary="获取轮播图",
     *     path="/wxapp/basic/find_slide",
     *
     *        @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="轮播图分组id，支持多个,多个用,分隔",
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
    public function find_slide(){

        $id = $this->request->param('id');
        if(empty($id)) $this->error('id必传');
        $id_arr = explode(',',$id);
        $ItemModel = new SlideItemModel();
        foreach($id_arr as $value){
            $item = $ItemModel->field('image,url,title,target')->where(['slide_id'=>$value,'status'=>1])->order('list_order')->select();
            foreach($item as &$v){
                $v['image'] = CommonModel::getOneImageFullUrl($v['image']);
            }
             $return[$value] = $item;
        }
        if(count($id_arr) == 1){
            $this->success("该组幻灯片获取成功!", $item);
        }else{
            $this->success("该组幻灯片获取成功!", $return);
        }
    }

    /**
     * @OA\Post(
     *     tags={"小程序-基础"},
     *     summary="获取配置数据  需传name值，不传取基础配置信息set_config，和公司字段统一，传多个name用逗号隔开",
     *     path="/wxapp/basic/find_setting",
     *
     *        @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="名称键值",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *          @OA\Parameter(
     *         name="key",
     *         in="query",
     *         description="key",
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
    public function find_setting(){
        $option_name = $this->request->param('name','set_config');
        $option_value = $this->request->param('value');
        $option_key = $this->request->param('key');
        $value = $this->getValues($option_name);
        if (!empty($option_key)) {
            $this->success('获取成功',$value[$option_key]);
        }
        if ($option_name == 'set_config') {
            if (!empty($value['carbon_notice'])) {
                $value['carbon_notice'] = [$value['carbon_notice']];
            }
            // 补全需要完整 URL 的图片字段
            $image_keys = ['app_logo', 'qxl_hero_bg', 'xsdld_hero_bg', 'dtsh_banner'];
            foreach ($image_keys as $img_key) {
                if (!empty($value[$img_key])) {
                    $value[$img_key] = cmf_get_asset_url($value[$img_key]);
                }
            }
        }
        if(!empty($option_value)){
            $this->error('暂不支持');
            cmf_set_option($option_name,$option_value);
            $this->success('设置成功');
        }else{
            $this->success('获取成功',$value);
        }


    }

    /**
     * 首页 Tab 聚合（Banner + 热门活动 + 风采 + 入口配置 + 登录态问候/签到）
     */
    public function get_index_tab_home()
    {
        $uid = (int)$this->user_id;
        $cacheKey = ['v' => 12];
        $service = new \api\wxapp\service\TabHomeService($this);
        $ttl = $uid > 0 ? \api\wxapp\support\TabHomeCache::TTL_USER : \api\wxapp\support\TabHomeCache::TTL_PUBLIC;
        $data = \api\wxapp\support\TabHomeCache::remember(
            'index_tab',
            $uid,
            $cacheKey,
            $ttl,
            function () use ($service) {
                return $service->buildIndexTabHome();
            }
        );
        $this->success('获取成功', $data);
    }

    public function getValue($option_name){
        $value = cmf_get_new_option($option_name);


        if(!empty($value)){
            if(!is_array($value)){
                $value = cmf_replace_content_file_url(htmlspecialchars_decode($value));
            }else{
                foreach($value as $k => $v){
                    if(is_array($v)){
                        continue;
                    }
                    $value[$k] = cmf_replace_content_file_url(htmlspecialchars_decode($v));
                    if(in_array($k, ['waste_thumb','min_logo','logo','wx_code','image','icon','thumb','wx_image','app_logo','app_code'], true)){
                        $value[$k] = cmf_get_asset_url($v);
                    }
                }
                // home_entries：二维数组，每个子项里的 image 字段也要补全 URL
                if ($option_name === 'home_entries') {
                    foreach ($value as $idx => $item) {
                        if (!is_array($item)) continue;
                        if (!empty($item['image'])) {
                            $value[$idx]['image'] = cmf_get_asset_url($item['image']);
                        }
                    }
                }
            }
        }else{
            $value = $value == 0 ? 0 : '';
        }
        return $value;
    }

    public function getValues($option_name){
        $option_name_arr = explode(',',$option_name);
        if(count($option_name_arr) == 1){
            return $this->getValue($option_name);
        }else{
            $return = [];
            foreach($option_name_arr as $v){
                $return[$v] = $this->getValue($v);
            }
            return $return;
        }
    }

    /**
     * @OA\Post(
     *     tags={"小程序-基础"},
     *     summary="h5获取js_sign",
     *     path="/wxapp/basic/get_js_sign",
     *
     *       @OA\Parameter(
     *         name="url",
     *         in="query",
     *         description="url",
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
    public function get_js_sign(){

        $url = $this->request->param('url');
        $jsapi_ticket = CommonModel::getJsapiTicket();
        $param['noncestr'] = mt_rand(10000, 99999).time();
        $param['jsapi_ticket'] = $jsapi_ticket;
        $param['timestamp'] = time();
        $param['url'] = urldecode($url);
        $param['sign'] = strtolower(CommonModel::makeSign($param));
        $param['appid'] = config('wxapp.appid');

        $this->success('获取成功',$param);
    }

    /**
     * @OA\Post(
     *      tags={"小程序-基础"},
     *      summary="获取省市区",
     *     path="/wxapp/basic/get_area",
     *
     *       @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code值，不传取全部省市区，传0取省，传其他取下级",
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
    public function get_area(){

        $code = $this->request->param('code');
        $Model = new AreaModel();
        if(!empty($code)){
            $pid = $Model->where(['code'=>$code])->value('id');
            $list = $Model->field('name label,code value')->where(['pid'=>$pid])->select()->toArray();

        }else{
            if($code === 0 || $code === '0'){
                $list = $Model->field('name label,code value')->where(['level_id'=>1])->select()->toArray();
            }else{
                $list = Cache::get('area_list');

                if(empty($list)){
                    $list = $Model->field('name label,code value,id')->where(['level_id'=>1])->select()->toArray();
                    foreach($list as &$item){
                        $child = $Model->field('name label,code value,id')->where(['pid'=>$item['id']])->select()->toArray();
                        foreach($child as &$value){
                            $three_child = $Model->field('name label,code value')->where(['pid'=>$value['id']])->select()->toArray();
                            $value['children'] = $three_child;
                            unset($value['id']);
                        }
                        $item['children'] = $child;
                        unset($item['id']);
                    }
                    Cache::set('area_list',$list);
                }
            }
        }
        $this->success('获取成功',$list);
    }

    /**
     * @OA\Post(
     *     tags={"小程序-基础"},
     *     summary="添加反馈",
     *     path="/wxapp/basic/add_advice",
     *
     *        @OA\Parameter(
     *         name="content",
     *         in="query",
     *         description="内容",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *        @OA\Parameter(
     *         name="image",
     *         in="query",
     *         description="图片数组",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */

    public function add_advice(){
        $this->checkUser();
        $AdviceModel = new AdviceModel();
        $data = $this->request->param();
        $data['user_id'] = $this->user_id;
        $data['create_time'] = time();

        if(empty($data['content'])){
            $this->error('请提交内容');
        }

        if(empty($data['image'])){
            $data['image'] = json_encode($data['image']);
        }

        $AdviceModel->insert($data);
        $this->success('提交成功');
    }

    /**
     * @OA\Post(
     *     tags={"小程序-基础"},
     *     summary="当前位置所在城市code",
     *     path="/wxapp/basic/get_local_city",
     *
     *       @OA\Parameter(
     *         name="lon",
     *         in="query",
     *         description="经度",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *       @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         description="纬度",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_local_city(){
        $lon = $this->request->param('lon');
        $lat = $this->request->param('lat');
        $res = CommonModel::getLocalCity($lon,$lat);
        $this->success('获取成功',$res);
    }




































}
