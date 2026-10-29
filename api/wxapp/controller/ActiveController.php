<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use api\wxapp\model\GeoUtil;
use app\admin\model\active\ActiveCateModel;
use app\admin\model\active\ActiveClockModel;
use app\admin\model\active\ActiveCommunityCateModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\active\ActiveInterestModel;
use app\admin\model\active\ActiveSIgnModel;
use app\admin\service\ActivityShowcaseSyncService;
use app\admin\model\active\ActiveTabModel;
use app\admin\model\active\ActiveUserModel;
use app\admin\model\active\MemberBadgeModel;
use app\admin\model\active\WasteCateModel;
use app\admin\model\active\WasteModel;
use app\admin\model\active\WasteUserModel;
use app\admin\model\active\ActiveClockActionModel;
use app\admin\model\active\ActiveClockActionFormModel;
use app\admin\model\active\ActiveClockActionUserModel;
use app\admin\model\active\ActiveClockPushUserModel;
use api\wxapp\service\ActivitySpotService;
use app\admin\model\forum\ForumCommentModel;
use app\admin\model\forum\ForumLikeModel;
use app\admin\model\goods\GoodsModel;
use app\admin\model\member\MemberModel;
use app\admin\model\SlideItemModel;
use api\wxapp\service\ActiveWebContext;
use think\facade\Db;

class ActiveController extends AuthController
{
    /**
     * 测试
     * @OA\Post(
     *tags={"活动"},
     *path="/wxapp/active/test",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function test()
    {

        $memberModel = new MemberModel();
        $list = $memberModel->where('status', 1)->fetchSql(true)->select();
        $this->success('请求成功', $list);
    }


    /**
     * 低碳活动分类
     * @OA\Post(
     *tags={"活动"},
     *path="/wxapp/active/get_active_cate_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_active_cate_list(ActiveCateModel $activeCateModel)
    {
        $id = $this->request->param('id');
        $type = $this->request->param('type');
        if ($id) {
            $item = $activeCateModel->where('id', $id)->find();
            $this->success('请求成功', $item);
        } else {
            $where = [];
//            if ($type) $where[] = ['type', '=', $type];
            $where[] = ['status', '=', 1];
            $list = $activeCateModel->where($where)
                ->order('list_order asc')
                ->paginate($this->page_size);
            $list = CommonModel::getMany($list, ['thumb']);
        }
        $this->success('请求成功', $list);
    }


    /**
     * 活动详情列表
     * @OA\Post(
     *tags={"活动"},
     *path="/wxapp/active/get_active_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="cate_id",description="类型  低碳类型",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="1.低碳类型,2.环保活动,3社区活动",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_put",description="1:首页推荐",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_my",description="1:我参加的活动",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_active_list(ActiveModel $activeModel, ActiveUserModel $activeUserModel)
    {
        $type = $this->request->param('type');
        $cate_id = $this->request->param('cate_id');
        $id = $this->request->param('id');
        $is_my = $this->request->param('is_my', 0);
        $is_put = $this->request->param('is_put', 0);
        if ($id) {
            $item = $activeModel->where('id', $id)->find();
            $item = CommonModel::getOne($item, ['thumb'], [], ['content']);
            $this->success('请求成功', $item);
        }
        $where = [];
        $where[] = ['type', '<>', 1];
        if ($type) $where[] = ['type', '=', $type];
        if ($cate_id) $where[] = ['cate_id', '=', $cate_id];
        if ($is_put) $where[] = ['is_put', '=', 1];
        if ($is_my) {
            $active_ids = $activeUserModel
                ->where('type', 1)  ->where('user_id', $this->user_id)
                ->column('active_id');
//            var_dump($active_ids);exit;
            $where[] = ['id', 'in', $active_ids];
        } else {
            // 浏览型列表不展示扫码参加(join_type=2)的活动；我参加的活动(is_my)仍正常展示
            $where[] = ['join_type', '=', 1];
        }

        $list = $activeModel->where($where)
            ->order('id desc')
            ->hidden(['content'])
            ->paginate($this->page_size);
        foreach ($list as &$item) {
            $item = CommonModel::getOne($item, ['thumb']);
            $item['people_num'] = $activeModel->where('id', $item['id'])->count();
            $item['date'] = date('Y-m-d', ($item['date']));
        }
        $list = CommonModel::getMany($list, ['thumb']);
        $this->success('请求成功', $list);
    }

    /**
     * 添加活动
     * @OA\Post(
     *tags={"活动"},
     *path="/wxapp/active/add_active_user",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="active_id",description="活动ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image",description="多图",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="content",description="内容",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_active_user(ActiveUserModel $activeUserModel, ActiveModel $activeModel)
    {
        $this->checkUser();
        $params = $this->request->param();
        $active = $activeModel->find($params['active_id']);
        if (!$active) $this->error('活动不存在');
        $params['type'] = $active['type'];
        $params['user_id'] = $this->user_id;
        if (!empty($params['image'])) $params['image'] = json_encode($params['image']);
        $activeUserModel->save($params);
        $this->success('提交成功');
    }


    /**
     * 提交活动列表
     * @OA\Post(
     *tags={"活动"},
     *path="/wxapp/active/get_active_user_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="1.低碳类型,2.环保活动,3社区活动",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="active_id",description="活动ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="user_type",description="1:我的",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_active_user_list(ActiveUserModel $activeUserModel)
    {
        $id = $this->request->param('id');
        if ($id) {
            $item = $activeUserModel
                ->alias('a')
                ->join('member b', 'a.user_id=b.id')
                ->field('a.*,b.nickname,b.avatar')
                ->where('a.id', $id)->find();
            if (!$item) $this->error('数据不存在');
            $item = CommonModel::getOne($item, ['avatar'], ['image']);
            $item['is_like'] = ForumLikeModel::where('rid', $item['id'])
                ->where('user_id', $this->user_id)->where('type', 1)
                ->where('forum_type', 2)
                ->count();
            $item['comment_total'] = ForumCommentModel::where('info_id', $item['id'])
                ->where('forum_type', 2)->count();
            $activeUserModel->where('id', $id)->inc('view_num')->update();
            $this->success('请求成功', $item);
        }
        $type = $this->request->param('type');
        $user_type = $this->request->param('user_type');
        $active_id = $this->request->param('active_id');
        $where = [];
        if ($type) $where[] = ['a.type', '=', $type];
        if ($active_id) $where[] = ['a.active_id', '=', $active_id];
        if ($user_type == 1) $where[] = ['a.user_id', '=', $this->user_id];
        $list = $activeUserModel
            ->alias('a')
            ->join('member b', 'a.user_id=b.id')
            ->field('a.*,b.nickname,b.avatar')
            ->where($where)->paginate($this->page_size);
        $list = CommonModel::getMany($list, ['avatar'], ['image']);

        foreach ($list as &$item) {
            $item['is_like'] = ForumLikeModel::where('rid', $item['id'])
                ->where('user_id', $this->user_id)->where('type', 1)
                ->where('forum_type', 2)
                ->count();
            $item['comment_total'] = ForumCommentModel::where('info_id', $item['id'])
                ->where('forum_type', 2)->count();
            $item['is_like'] = ForumLikeModel::where('rid', $item['id'])
                ->where('user_id', $this->user_id)->where('type', 1)
                ->where('forum_type', 2)
                ->count();
            $item['comment_total'] = ForumCommentModel::where('info_id', $item['id'])
                ->where('forum_type', 2)->count();
        }
        $this->success('请求成功', $list);
    }


    /**
     * 低碳生活
     * @OA\Post(
     *tags={"低碳"},
     *path="/wxapp/active/get_waste_cate_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_waste_cate_list(WasteCateModel $cateModel)
    {

        $id = $this->request->param('id');
        $type = $this->request->param('type');
        if ($id) {
            $item = $cateModel->where('id', $id)->find();
            $this->success('请求成功', $item);
        } else {
            $where = [];
//            if ($type) $where[] = ['type', '=', $type];
            $list = $cateModel->where($where)
                ->order('list_order asc')
                ->paginate($this->page_size);
            $list = CommonModel::getMany($list, ['thumb']);
            $this->success('请求成功', $list);

        }
    }


    /**
     * 低碳生活-垃圾分类指导
     * @OA\Post(
     *tags={"低碳"},
     *path="/wxapp/active/get_waste_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="cate_id",description="垃圾",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_waste_list(WasteModel $wasteModel, WasteUserModel $wasteUserModel)
    {
        $id = $this->request->param('id');
        if ($id) {
            $item = $wasteModel->where('id', $id)->find();
            if (!$item) $this->error('数据不存在');
            $item = CommonModel::getOne($item, [], [], ['content']);
            //添加学习进度
            $where = ['user_id' => $this->user_id, 'waste_id' => $id, 'cate_id' => $item['cate_id']];
            $wasteUser = $wasteUserModel->where($where)->find();
            if (!$wasteUser) {
                $wasteUserModel->save($where);
            }
            $this->success('请求成功', $item);
        } else {
            $where = [];
            $cate_id = $this->request->param('cate_id');
            if ($cate_id) $where[] = ['cate_id', '=', $cate_id];
            $item['user_count'] = $wasteUser_count = $wasteUserModel->where($where)->where('user_id', $this->user_id)->count();
            $item['waste_count'] = $wasteModel->where($where)->count();
            $list = $wasteModel
                ->where($where)
                ->order('create_time desc,id desc')
                ->hidden(['content'])
                ->paginate($this->page_size)->each(function ($item) use ($wasteUserModel) {
                    $item['is_study'] = $wasteUserModel
                        ->where(['user_id' => $this->user_id, 'waste_id' => $item['id']])
                        ->count();
                });
            $list = CommonModel::getMany($list, ['thumb']);
            $item['list'] = $list;
            $this->success('请求成功', $item);
        }
    }

    /**
     * 活动社区分类列表（新版9大标准分类）
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_new_cate_active_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_new_cate_active_list(ActiveCommunityCateModel $cateModel){
        $list = $cateModel->where('id', '>', 0)->order('list_order asc,id asc')->select()->each(function ($item){
            $item['image'] = empty($item['image'])?'':cmf_get_asset_url($item['image']);
            return $item;
        });
        $this->success('请求成功', $list);
    }

    /**
     * 获取活动所有标签（去重）
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_active_tags_list",
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_active_tags_list(){
        $this->success('请求成功', $this->collectActiveTagList());
    }

    /**
     * 活动页标签列表
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_active_tab_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_active_tab_list(){
        $where = [];
        $where[] = ['status', '=', 1];
        $list = ActiveTabModel::where($where)->order('list_order asc,id asc')->select();
        $this->success('请求成功', $list);
    }

    /**
     * 社区活动分类列表（七贤岭 4 个分类 + 普通 5 个分类）
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_community_cate_list",
     *@OA\Response(response="200", description="社区活动分类列表"),
     *@OA\Response(response="default", description="error")
     * )
     */
    public function get_community_cate_list(){
        $id = $this->request->param('id');
        $list = ActiveCommunityCateModel::order('list_order asc, id asc')->select();
        if ($id) {
            $item = ActiveCommunityCateModel::where('id', $id)->find();
            $this->success('请求成功', $item);
        }
        $this->success('请求成功', $list);
    }

    /** 我和七贤岭页：学考进度 + 「100个瞬间」主推活动 */
    public function get_love_qixianling_hub()
    {
        $moment = ActiveModel::miniProgramVisible()
            ->where('qxl_hub_slot', 'moment_100')
            ->order('list_order asc, id desc')
            ->find();
        $momentOut = null;
        if ($moment) {
            $momentOut = $this->applyActiveListItemFields($moment);
        }
        $this->success('请求成功', [
            'moment_active' => $momentOut,
            'showcase_path' => '/pages/activity/showcase-list',
        ]);
    }

    /** 活动 Tab 四大专题入口（我和七贤岭 + 三个分会场专题） */
    public function get_activity_spot_modules()
    {
        $this->success('请求成功', ActivitySpotService::moduleList());
    }

    /** 专题分会场列表（徒步拾遗 / 二手集市 / 真分小课堂） */
    public function get_activity_spot_venues()
    {
        $spotKey = trim((string)$this->request->param('spot_key', ''));
        if (!ActivitySpotService::isValidSpotKey($spotKey)) {
            $this->error('专题不存在');
        }
        $module = null;
        foreach (ActivitySpotService::moduleList() as $m) {
            if ($m['key'] === $spotKey) {
                $module = $m;
                break;
            }
        }
        $venues = ActivitySpotService::venueList($spotKey);
        foreach ($venues as &$row) {
            if (($row['kind'] ?? '') !== 'active') {
                continue;
            }
            $active = ActiveModel::miniProgramVisible()->where('id', (int)$row['active_id'])->find();
            if (empty($active)) {
                continue;
            }
            $active = $this->applyActiveListItemFields($active);
            $row['thumb'] = $active['thumb'];
            $row['active_count'] = (int)$active['active_count'];
            $row['date_text'] = !empty($active['date']) ? date('Y-m-d', (int)$active['date']) : $row['date_text'];
            $row['display_mode'] = (int)$active['display_mode'];
            $row['web_url'] = (string)($active['web_url'] ?? '');
            $row['web_launch_url'] = (string)($active['web_launch_url'] ?? '');
            $row['web_page_url'] = (string)($active['web_page_url'] ?? '');
        }
        unset($row);
        if (is_array($module)) {
            $module = ActivitySpotService::enrichModuleForHub($module, $spotKey);
        }
        $this->success('请求成功', [
            'module' => $module,
            'venues' => $venues,
        ]);
    }

    /**
     * 小手拉大手板块分类（云间亲子分类汇 / 分类小当家·亲子实践营）
     */
    public function get_xsdld_cate_list()
    {
        $list = ActiveCommunityCateModel::where('cate_group', 'xsdld')
            ->order('list_order asc, id asc')
            ->select();
        $this->success('请求成功', $list);
    }

    /**
     * 活动列表
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_new_active_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="community_cate_id",description="社区活动分类id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_recommendation",description="是否推荐，1；是",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_put",description="1:首页推荐",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="keyword",description="关键词",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_new_active_list(){
        $page_size = $this->request->param('page_size', 10);
        $community_cate_id = $this->request->param('community_cate_id');
        $xsdld_cate_id = (int)$this->request->param('xsdld_cate_id', 0);
        $is_recommendation = $this->request->param('is_recommendation');
        $keyword = $this->request->param('keyword');
        $is_put = $this->request->param('is_put');
        $is_month_recommend = $this->request->param('is_month_recommend');
        $tag = $this->request->param('tag');
        $where = [];
        if(!empty($is_recommendation)) $where[] = ['is_recommendation', '=', 1];
        if(!empty($is_put)) $where[] = ['is_put', '=', 1];
        if(!empty($is_month_recommend)) $where[] = ['is_month_recommend', '=', 1];
        if(!empty($keyword)) $where[] = ['name|address', 'like', '%' . $keyword . '%'];
        $query = ActiveModel::miniProgramVisible();
        if (!empty($where)) {
            $query = $query->where($where);
        }
        // 分类过滤：兼容新旧字段（community_cate_id 为新分类系统，cate_id 为旧分类系统）
        if(!empty($community_cate_id)) {
            $query = $query->where(function($q) use ($community_cate_id) {
                $q->where('community_cate_id', '=', $community_cate_id)
                  ->whereOr('cate_id', '=', $community_cate_id);
            });
        }
        if ($xsdld_cate_id > 0) {
            $query = $query->where('xsdld_cate_id', '=', $xsdld_cate_id);
        }
        // 活动标签过滤（FIND_IN_SET处理逗号分隔的tags字段）
        if(!empty($tag)) {
            $query = $query->whereRaw('FIND_IN_SET(?, tags)', [$tag]);
        }
        $controller = $this;
        $list = $query->order('list_order asc, id desc')->paginate($page_size)->each(function ($item) use ($controller) {
            return $controller->applyActiveListItemFields($item);
        });
        $this->success('请求成功', $list);
    }

    /**
     * 活动 Tab 首页聚合：Banner、标签、三块推荐、底部活动列表（首屏一次请求）
     * 分页 / 按标签筛列表时传 page、tag，仅返回 active_list 等列表字段（不传 blocks）
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_activity_tab_home",
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_activity_tab_home()
    {
        $page = max(1, (int)$this->request->param('page', 1));
        $tag = trim((string)$this->request->param('tag', ''));
        $pageSize = max(1, min(50, (int)$this->request->param('page_size', 10)));
        $cacheKey = ['page' => $page, 'tag' => $tag, 'ps' => $pageSize];
        $service = new \api\wxapp\service\TabHomeService($this);
        $data = \api\wxapp\support\TabHomeCache::remember(
            'activity_tab',
            (int)$this->user_id,
            $cacheKey,
            \api\wxapp\support\TabHomeCache::TTL_USER,
            function () use ($service, $page, $tag, $pageSize) {
                return $service->buildActivityTabHome($page, $tag, $pageSize);
            }
        );
        $this->success('请求成功', $data);
    }

    /** 我的进行中（已报名 + 我发起且未截止） */
    public function get_my_ongoing_list()
    {
        $this->checkUser();
        $page = max(1, (int)$this->request->param('page', 1));
        $pageSize = max(1, min(30, (int)$this->request->param('page_size', 10)));
        $service = new \api\wxapp\service\TabHomeService($this);
        $data = $service->paginateMyOngoingList($page, $pageSize);
        $this->success('获取成功', $data);
    }

    /**
     * 活动 Tab 页三块推荐区（本周推荐 / 轻活动 / 近期活动）一次返回，避免重复请求列表接口
     * @deprecated 活动 Tab 请用 get_activity_tab_home
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_activity_page_sections",
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_activity_page_sections()
    {
        $this->success('请求成功', $this->buildActivityPageSections());
    }

    /**
     * 活动详情
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_new_active_detail",
     *@OA\Parameter(name="active_id",description="活动id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_new_active_detail(){
        $active_id = $this->request->param('active_id');
        $item = ActiveModel::where('id',$active_id)->find();
        if(empty($item)) $this->error('活动不存在');
        $item['role'] = explode(',',$item['role']);
        $item['thumb'] = empty($item['thumb'])?'':cmf_get_asset_url($item['thumb']);
        $item['badge_image'] = empty($item['badge_image'])?'':cmf_get_asset_url($item['badge_image']);
        $item['content'] = htmlspecialchars_decode($item['content']);
        // 新字段：适合人群 / 活动时长 / 活动流程（反义） / 你将获得（反义）
        $item['suit_people'] = strval($item['suit_people'] ?? '');
        $item['duration']    = strval($item['duration'] ?? '');
        $item['flow']        = htmlspecialchars_decode($item['flow'] ?? '');
        $item['gain']        = htmlspecialchars_decode($item['gain'] ?? '');
        $item['doc_rules_field']  = ActiveModel::parseDocFieldToken((string)($item['rules_url'] ?? ''));
        $item['doc_safety_field'] = ActiveModel::parseDocFieldToken((string)($item['safety_url'] ?? ''));

        $item['publish_custom_blocks'] = \api\wxapp\service\MemberActiveService::formatBlocksForApi($item['publish_custom_blocks'] ?? '');

        //是否报名
        $where = [];
        $where[] = ['active_id', '=', $item['id']];
        $where[] = ['user_id', '=', $this->user_id];
        $sign_info = ActiveSIgnModel::where($where)->find();
        // 只有打卡活动（is_clock=1）才自动报名，非打卡活动需用户手动报名
        $autoClockJoin = intval($item['is_clock']) === 1 || intval($item['is_clock_challenge'] ?? 0) === 1;
        if (empty($sign_info) && $this->user_id && $autoClockJoin && !ActiveModel::isOfflineEvent($item) && !ActiveModel::isDatePending($item)) {
            // 自动创建报名记录，跳过角色条件校验和积分扣除（详情 onLoad/onShow 并发时需防重）
            ActiveSIgnModel::ensureClockAutoSign((int) $this->user_id, (int) $item['id'], [
                'type'        => 2,
                'start_time'  => $item['clock_start_time'],
                'end_time'    => $item['clock_end_time'],
            ]);
            $sign_info = ActiveSIgnModel::where($where)->find();
        }
        $item['sign_id'] = empty($sign_info)?0:$sign_info['id'];
        $item['is_sign'] = empty($sign_info)?0:1;

        //参与人数
        $item['active_count'] = ActiveSIgnModel::countParticipants((int) $item['id']);

        $item['event_kind'] = (int)($item['event_kind'] ?? ActiveModel::EVENT_KIND_NORMAL);
        $item['offline_sign_start'] = (int)($item['offline_sign_start'] ?? 0);
        $item['offline_sign_end'] = (int)($item['offline_sign_end'] ?? 0);
        $now = time();
        $item['offline_sign_open'] = 0;
        $item['offline_sign_wait'] = 0;
        $item['offline_sign_ended'] = 0;
        if (ActiveModel::isOfflineEvent($item)) {
            if ($item['offline_sign_start'] > 0 && $now < $item['offline_sign_start']) {
                $item['offline_sign_wait'] = 1;
            } elseif ($item['offline_sign_end'] > 0 && $now > $item['offline_sign_end']) {
                $item['offline_sign_ended'] = 1;
            } else {
                $item['offline_sign_open'] = 1;
            }
        }

        //是否打卡 / 线下是否已签到
        if (ActiveModel::isOfflineEvent($item)) {
            $clock_info = ActiveClockModel::where([
                ['active_id', '=', $item['id']],
                ['user_id', '=', $this->user_id],
            ])->find();
            $item['is_sign_clock'] = empty($clock_info) ? 0 : 1;
        } else {
            $date_time = strtotime(date('Y-m-d 00:00:00'));
            $where = [];
            $where[] = ['active_id', '=', $item['id']];
            $where[] = ['user_id', '=', $this->user_id];
            $where[] = ['sign_id', '=', $item['sign_id']];
            $where[] = ['date_time', '=', $date_time];
            $clock_info = ActiveClockModel::where($where)->find();
            $item['is_sign_clock'] = empty($clock_info) ? 0 : 1;
        }

//        $item['sign_id'] = $sign_info['id'];

        $item['is_date_pending'] = (int)($item['is_date_pending'] ?? 0);
        $item['is_recurring'] = (int)($item['is_recurring'] ?? 0);
        $this->attachInterestFields($item);
        $item['is_end'] = ActiveModel::resolveIsEnd($item);
        if ((int)$item['is_clock'] === 1 || (int)($item['is_clock_challenge'] ?? 0) === 1) {
            $clockEnd = (int)($item['clock_end_time'] ?? 0);
            if ($clockEnd > 0 && $clockEnd < time()) {
                $item['is_end'] = 1;
            }
        }
        $item['showcase_id'] = 0;
        if ($item['is_end'] == 1 && !ActiveModel::isRecurring($item)) {
            $activeRow = is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : (array)$item;
            ActivityShowcaseSyncService::syncOneActive($activeRow);
            $item['showcase_id'] = ActivityShowcaseSyncService::findShowcaseIdByActive((int)$item['id']);
        }
        $this->attachWebLaunchFields($item);
        $pwdConfigured = 0;
        if (ActiveModel::isOfflineEvent($item)) {
            $pwdRow = ActiveModel::where('id', $item['id'])->value('offline_sign_password');
            $pwdConfigured = ($pwdRow !== '' && $pwdRow !== null) ? 1 : 0;
        }
        unset($item['offline_sign_password']);
        $item['has_offline_password'] = $pwdConfigured;
        $this->appendClockChallengeForDetail($item);
        $this->success('请求成功', $item);
    }

    /**
     * 详情：打卡满 N 天兑礼（累计自然日 + 绑定商品列表）
     */
    protected function appendClockChallengeForDetail(&$item): void
    {
        $activeId = (int)($item['id'] ?? 0);
        $item['is_clock_challenge'] = (int)($item['is_clock_challenge'] ?? 0);
        $item['clock_challenge_days'] = (int)($item['clock_challenge_days'] ?? 0);
        $item['clock_challenge_user_days'] = 0;
        $item['clock_challenge_can_redeem'] = 0;
        $item['clock_challenge_progress'] = 0;
        $item['redeem_goods_list'] = [];

        if ($item['is_clock_challenge'] !== 1) {
            return;
        }

        $item['redeem_goods_list'] = GoodsModel::listRedeemGoodsForActive($activeId);
        if (!$this->user_id) {
            return;
        }
        $progress = GoodsModel::getSignRequireProgress($this->user_id, $activeId);
        $userDays = (int)($progress['user_clock_days'] ?? 0);
        $needDays = (int)($progress['require_clock_days'] ?? 0);
        $item['clock_challenge_user_days'] = $userDays;
        $item['clock_challenge_progress'] = $userDays;
        $item['clock_challenge_can_redeem'] = ($needDays > 0 && $userDays >= $needDays) ? 1 : 0;
    }

    /**
     * 线下活动现场签到（定位 500 米内或口令）
     */
    public function post_offline_event_sign()
    {
        $this->checkUser();
        $active_id = (int)$this->request->param('active_id');
        $lat = $this->request->param('lat');
        $lng = $this->request->param('lng');
        $password = trim((string)$this->request->param('password', ''));
        $sign_mode = trim((string)$this->request->param('sign_mode', 'location'));

        $active_info = ActiveModel::where('id', $active_id)->find();
        if (empty($active_info) || !ActiveModel::isOfflineEvent($active_info)) {
            $this->error('活动不存在或不是线下活动');
        }
        if (ActiveModel::isDatePending($active_info)) {
            $this->error('活动时间待定，确定时间后再签到');
        }
        if (!ActiveModel::isDatePending($active_info) && $active_info['date'] < time()) {
            $this->error('活动已结束');
        }
        $start = (int)$active_info['offline_sign_start'];
        $end = (int)$active_info['offline_sign_end'];
        $now = time();
        if ($start > 0 && $now < $start) {
            $this->error('签到尚未开始');
        }
        if ($end > 0 && $now > $end) {
            $this->error('签到已结束');
        }

        $sign_info = ActiveSIgnModel::where([
            ['active_id', '=', $active_id],
            ['user_id', '=', $this->user_id],
        ])->find();
        if (empty($sign_info)) {
            $this->error('请先报名参加活动');
        }

        $exists = ActiveClockModel::where([
            ['active_id', '=', $active_id],
            ['user_id', '=', $this->user_id],
        ])->find();
        if (!empty($exists)) {
            $this->error('已签到');
        }

        $verified = false;
        if ($sign_mode === 'password' && $password !== '') {
            $cfgPwd = (string)($active_info['offline_sign_password'] ?? '');
            if ($cfgPwd === '') {
                $this->error('本活动未开启口令签到');
            }
            if ($password !== $cfgPwd) {
                $this->error('口令不正确');
            }
            $verified = true;
        } else {
            if ($lat === '' || $lng === '' || $lat === null || $lng === null) {
                $this->error('请授权位置信息后重试');
            }
            $targetLat = (float)$active_info['lat'];
            $targetLng = (float)$active_info['lng'];
            if ($targetLat == 0.0 && $targetLng == 0.0) {
                $this->error('活动未配置签到地点');
            }
            $dist = GeoUtil::distanceMeters($lat, $lng, $targetLat, $targetLng);
            if ($dist > 500) {
                $this->error('您不在签到范围内（距活动地点约' . (int)round($dist) . '米，需在500米内）');
            }
            $verified = true;
        }
        if (!$verified) {
            $this->error('签到失败');
        }

        $date_time = strtotime(date('Y-m-d 00:00:00'));
        $send_point = (int)($active_info['send_point'] ?? 0);
        ActiveClockModel::create([
            'user_id' => $this->user_id,
            'active_id' => $active_id,
            'sign_id' => $sign_info['id'],
            'date_time' => $date_time,
            'image_url' => '',
            'send_point' => $send_point,
        ]);

        if ($send_point > 0) {
            AccountLogModel::add_account_log($this->user_id, 2, 225, $send_point, ['remark' => '线下活动签到']);
        }

        $this->success('签到成功');
    }

    /**
     * 活动签到排行榜
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_new_active_ranking",
     *@OA\Parameter(name="active_id",description="活动id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_new_active_ranking(){
        $active_id = $this->request->param('active_id');
        $item = ActiveModel::where('id',$active_id)->find();
        if(empty($item)) $this->error('活动不存在');
        $where = [];
        $where[] = ['active_id', '=', $active_id];
        $list = ActiveSIgnModel::where($where)->select()->each(function ($item){
            $item['qr_code'] = empty($item['qr_code'])?'':cmf_get_asset_url($item['qr_code']);
            //签到数量
            $map = [];
            $map[] = ['user_id', '=', $item['user_id']];
            $map[] = ['active_id', '=', $item['active_id']];
            $map[] = ['sign_id', '=', $item['id']];
            $clock_count = ActiveClockModel::where($map)->count();
            $item['clock_count'] = $clock_count;
            //用户信息
            $member_info = MemberModel::where('id',$item['user_id'])->find();
            $item['member_info'] = $member_info;
            return $item;
        })->toArray();
        $count = ActiveSIgnModel::where($where)->count();

        for($i = 0; $i < $count; $i++){
            for($j = $i + 1; $j < $count - 1; $j++){
                if($list[$i]['clock_count'] < $list[$j]['clock_count']){
                    $array = $list[$i];
                    $list[$i] = $list[$j];
                    $list[$j] = $array;
                }
            }
        }

        $this->success('请求成功', $list);
    }

    /**
     * 活动报名
     * @OA\Post (
     *tags={"新版活动"},
     *path="/wxapp/active/post_new_active_sign",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="active_id",description="活动id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function post_new_active_sign(){
        $this->checkUser();
        $active_id = $this->request->param('active_id');
        $item = ActiveModel::where('id',$active_id)->find();
        if(empty($item)) $this->error('活动不存在');
        if (ActiveModel::isDatePending($item)) {
            $this->error('活动时间待定，请先点击「感兴趣」，确定时间后再报名参与');
        }
        if (!ActiveModel::isDatePending($item) && $item['date'] < time()) {
            $this->error('活动已结束');
        }
        $role = explode(',',$item['role']);
        //是否具备报名条件
        if($item['is_clock'] == 1){
            $is_sign = 0;
            foreach($role as $k=>$v){
                if($v == 1) $is_sign = 1;
                elseif($v == 2){
                    if($this->user_info['is_environmental'] == 1) $is_sign = 1;
                }elseif($v == 3){
                    if($this->user_info['is_supervisor'] == 1) $is_sign = 1;
                }elseif($v == 4){
                    if($this->user_info['is_merchant'] == 1) $is_sign = 1;
                }
            }
            if($is_sign == 0) $this->error('您不具备报名条件');
        }

        $data = [];
        $data['user_id'] = $this->user_id;
        $data['active_id'] = $active_id;
        $sign_info = ActiveSIgnModel::where($data)->find();
        if(!empty($sign_info)) $this->error('已报名');

        $point = $item['point'];
        if($point > 0){
            if($this->user_info['point'] < $point) $this->error('积分不足');

            AccountLogModel::add_account_log($this->user_id, 2, 221, -$point, ['active_id' => $active_id]);
        }
        $type = 1;
        if($item['is_clock'] == 1) $type = 2;

        $start_time = $item['clock_start_time'];
        $end_time = $item['clock_end_time'];

        $data['type'] = $type;
        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        $result = ActiveSIgnModel::create($data);
        if(!empty($result)){
            //增加二维码
            $text = (string)$result['id'];
            $qrcode = CommonModel::createQrcode($text, $text.'.png');
            ActiveSIgnModel::where('id',$result['id'])->update(['qr_code' => $qrcode]);
        }

        $this->success('报名成功');
    }

    /**
     * 活动时间待定：标记/取消「感兴趣」
     * @OA\Post (
     *tags={"新版活动"},
     *path="/wxapp/active/post_toggle_active_interest",
     *@OA\Parameter(name="active_id",description="活动id",in="query",required=true, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource")
     * )
     */
    public function post_toggle_active_interest()
    {
        $this->checkUser();
        $activeId = (int) $this->request->param('active_id');
        if ($activeId <= 0) {
            $this->error('参数错误');
        }
        $item = ActiveModel::where('id', $activeId)->find();
        if (empty($item)) {
            $this->error('活动不存在');
        }
        if (!ActiveModel::isDatePending($item)) {
            $this->error('活动已公布时间，请直接报名或打卡参与');
        }
        $result = ActiveInterestModel::toggle($activeId, (int) $this->user_id);
        $msg = $result['action'] === 'add' ? '已标记感兴趣' : '已取消感兴趣';
        $this->success($msg, $result);
    }

    /** 详情/列表：感兴趣人数与当前用户状态 */
    protected function attachInterestFields(&$item): void
    {
        $activeId = (int) ($item['id'] ?? 0);
        $item['interest_count'] = ActiveInterestModel::countByActive($activeId);
        $item['is_interested'] = 0;
        if ($this->user_id && ActiveInterestModel::isInterested($activeId, (int) $this->user_id)) {
            $item['is_interested'] = 1;
        }
    }

    /**
     * 报名列表
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_new_sign_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_new_sign_list(){
        $this->checkUser();
        $page_size = $this->request->param('page_size', 10);
        $where = [];
        $where[] = ['user_id', '=', $this->user_id];
        $list = ActiveSIgnModel::where($where)->order('id desc')->paginate($page_size)->each(function ($item){
            $item['qr_code'] = empty($item['qr_code'])?'':cmf_get_asset_url($item['qr_code']);

            $active_info = ActiveModel::where('id',$item['active_id'])->find();
            if(!empty($active_info)){
                $active_info['thumb'] = empty($active_info['thumb'])?'':cmf_get_asset_url($active_info['thumb']);
                $active_info['badge_image'] = empty($active_info['badge_image'])?'':cmf_get_asset_url($active_info['badge_image']);
                $active_info['content'] = htmlspecialchars_decode($active_info['content']);
            }
            $item['active_info'] = $active_info;
            return $item;
        });
        $this->success('请求成功', $list);
    }

    /**
     * 报名详情
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_new_sign_detail",
     *@OA\Parameter(name="sign_id",description="报名记录id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_new_sign_detail(){
        $sign_id = $this->request->param('sign_id');
        $item = ActiveSIgnModel::where('id',$sign_id)->find();
        if(empty($item)) $this->error('报名记录不存在');
        $item['qr_code'] = empty($item['qr_code'])?'':cmf_get_asset_url($item['qr_code']);

        $active_info = ActiveModel::where('id',$item['active_id'])->find();
        if(!empty($active_info)){
            $active_info['thumb'] = empty($active_info['thumb'])?'':cmf_get_asset_url($active_info['thumb']);
            $active_info['badge_image'] = empty($active_info['badge_image'])?'':cmf_get_asset_url($active_info['badge_image']);
            $active_info['content'] = htmlspecialchars_decode($active_info['content']);
        }
        $item['active_info'] = $active_info;
        $this->success('请求成功', $item);
    }

    /**
     * 报名打卡
     * @OA\Post (
     *tags={"新版活动"},
     *path="/wxapp/active/post_new_sign_clock",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="sign_id",description="报名记录id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_url",description="上传图",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function post_new_sign_clock(){
        $sign_id = $this->request->param('sign_id');
        $image_url = $this->request->param('image_url');
        $experience_share = trim((string)$this->request->param('experience_share', ''));
        if (mb_strlen($experience_share, 'UTF-8') > 500) {
            $this->error('经验分享不超过500字');
        }
        $item = ActiveSIgnModel::where('id',$sign_id)->find();
        if(empty($item)) $this->error('报名记录不存在');
        if($item['start_time'] > time()) $this->error('打卡时间未到');
        if($item['end_time'] < time()) $this->error('打卡时间已过');
        $active_id = $item['active_id'];
        $active_info = ActiveModel::where('id',$active_id)->find();
        if(empty($active_info)) $this->error('活动不存在');
        if (ActiveModel::isDatePending($active_info)) {
            $this->error('活动时间待定，确定时间后再打卡');
        }
        // 照片校验：扫码参加(join_type=2)活动免上传照片，点击按钮直接签到；其余活动照片必填
        if(empty($image_url) && intval($active_info['join_type']) !== 2) $this->error('照片不能为空');
        $date_time = strtotime(date('Y-m-d 00:00:00'));
        $send_point = $active_info['send_point'];

        $data = [];
        $data['user_id'] = $this->user_id;
        $data['active_id'] = $active_id;
        $data['sign_id'] = $sign_id;
        $data['date_time'] = $date_time;
        $clock_info = ActiveClockModel::where($data)->find();
        $data['image_url'] = $image_url;
        $data['experience_share'] = $experience_share;
        $data['send_point'] = $send_point;
        if(empty($clock_info)){
            ActiveClockModel::create($data);
        }else{
            $this->error('已打卡');
        }
        if($active_info['is_badge'] == 1){
            //计算连续打卡次数
            $where = [];
            $where['user_id'] = $this->user_id;
            $where['active_id'] = $active_id;
            $where['sign_id'] = $sign_id;
            $timeStampArr = ActiveClockModel::where($where)->column('date_time');
            $clock_count = $this->getMaxContinueSignDays($timeStampArr);
            //当前徽章是否存在，已存在就跳过
            $map = [];
            $map['user_id'] = $this->user_id;
            $map['badge_id'] = $active_id;
            $badge_info = MemberBadgeModel::where($map)->find();
            if($clock_count >= $active_info['sign_day'] && empty($badge_info)){
                $map['name'] = $active_info['badge_name'];
                $map['image'] = $active_info['badge_image'];
                MemberBadgeModel::create($map);
            }
        }

        if($active_info['is_clock'] == 1){
            //赠送积分
            AccountLogModel::add_account_log($this->user_id, 2, 225, $send_point, ['remark' => '垃圾打卡扣除']);
        }

        // ===== 打卡动作触发判断 =====
        $action_trigger = $this->checkAndTriggerClockAction($active_id, $sign_id);

        $this->success('请求成功', ['action_trigger' => $action_trigger]);
    }

    /**
     * 检查并触发打卡动作
     * @param int $activeId 活动ID
     * @param int $signId 报名记录ID
     * @return array|null 触发的动作配置（含表单字段），无触发返回null
     */
    protected function checkAndTriggerClockAction(int $activeId, int $signId): ?array
    {
        // 计算当前用户在该活动的累计打卡次数
        $clockActions = ActiveClockActionModel::where('active_id', $activeId)
            ->where('status', 1)
            ->where('delete_time', 0)
            ->select();

        if ($clockActions->isEmpty()) {
            return null;
        }

        // 获取用户今天的打卡次数（包含刚打卡的）
        $totalClockCount = ActiveClockModel::where('user_id', $this->user_id)
            ->where('active_id', $activeId)
            ->where('sign_id', $signId)
            ->where('date_time', '>', 0)
            ->count();

        $activeInfo = ActiveModel::where('id', $activeId)->find();
        $now = time();

        foreach ($clockActions as $action) {
            // 检查时效
            if ($action['expire_time'] > 0 && $action['expire_time'] < $now) {
                continue;
            }

            // 检查打卡次数是否满足
            if ($action['notify_type'] == 1) {
                // 即时表单通知：需要累计打卡次数满足
                if ($totalClockCount < $action['clock_count']) {
                    continue;
                }

                // 判断连续性要求
                if ($action['is_continuous'] == 1 && $activeInfo) {
                    if (!$this->checkContinuousClock($activeId, $signId, $action['clock_count'])) {
                        continue;
                    }
                }
            } else {
                // 滞后服务消息推送：检查是否已满足条件但未订阅
                if ($totalClockCount < $action['clock_count']) {
                    continue;
                }

                // 判断连续性要求
                if ($action['is_continuous'] == 1 && $activeInfo) {
                    if (!$this->checkContinuousClock($activeId, $signId, $action['clock_count'])) {
                        continue;
                    }
                }
            }

            // 对于即时表单通知，检查是否已经提交过
            if ($action['notify_type'] == 1) {
                $alreadySubmitted = ActiveClockActionUserModel::where('action_id', $action['id'])
                    ->where('user_id', $this->user_id)
                    ->where('sign_id', $signId)
                    ->count();
                if ($alreadySubmitted > 0) {
                    continue;
                }
            }

            // 对于滞后推送，检查是否已记录订阅
            if ($action['notify_type'] == 2) {
                $pushUser = ActiveClockPushUserModel::where('action_id', $action['id'])
                    ->where('active_id', $activeId)
                    ->where('user_id', $this->user_id)
                    ->find();
                // 已订阅的不重复返回，未订阅的需要返回让小程序请求授权
                if ($pushUser && $pushUser['is_subscribed'] == 1) {
                    continue;
                }
            }

            // 返回动作配置
            $actionData = $action->toArray();
            $actionData['forms'] = ActiveClockActionFormModel::where('action_id', $action['id'])
                ->where('delete_time', 0)
                ->order('list_order asc, id asc')
                ->select()
                ->toArray();

            // 解码选项
            foreach ($actionData['forms'] as &$form) {
                $form['field_options_arr'] = json_decode($form['field_options'] ?? '[]', true);
                if (!is_array($form['field_options_arr'])) {
                    $form['field_options_arr'] = [];
                }
            }

            return $actionData;
        }

        return null;
    }

    /**
     * 检查用户是否连续打卡达到指定天数
     */
    protected function checkContinuousClock(int $activeId, int $signId, int $targetDays): bool
    {
        $timestamps = ActiveClockModel::where('user_id', $this->user_id)
            ->where('active_id', $activeId)
            ->where('sign_id', $signId)
            ->column('date_time');

        if (count($timestamps) < $targetDays) {
            return false;
        }

        return $this->getMaxContinueSignDays($timestamps) >= $targetDays;
    }

    /**
     * 提交打卡动作表单（即时表单通知）
     * @OA\Post (
     *tags={"新版活动"},
     *path="/wxapp/active/post_clock_action_form",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="action_id",description="动作配置ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="form_data",description="表单数据JSON",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function post_clock_action_form()
    {
        $this->checkUser();
        $actionId = $this->request->param('action_id', 0, 'intval');
        $formDataJson = $this->request->param('form_data', '', 'trim');

        if (empty($actionId)) {
            $this->error('动作ID不能为空');
        }

        $action = ActiveClockActionModel::where('id', $actionId)->find();
        if (!$action) {
            $this->error('动作配置不存在');
        }

        $formData = json_decode($formDataJson, true);
        if (!is_array($formData)) {
            $formData = [];
        }

        // 获取当前用户的报名记录
        $sign = ActiveSIgnModel::where('active_id', $action['active_id'])
            ->where('user_id', $this->user_id)
            ->find();
        $signId = $sign ? $sign['id'] : 0;

        // 检查是否已提交
        if (ActiveClockActionUserModel::hasUserSubmitted($actionId, $this->user_id, $signId)) {
            $this->error('您已提交过');
        }

        // 提交表单
        $result = ActiveClockActionUserModel::submitForm($actionId, $action['active_id'], $this->user_id, $signId, $formData);
        if (!$result) {
            $this->error('提交失败');
        }

        $this->success('提交成功');
    }

    /**
     * 记录用户订阅授权状态（滞后推送用）
     * @OA\Post (
     *tags={"新版活动"},
     *path="/wxapp/active/post_clock_action_subscribe",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="action_id",description="动作配置ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_subscribed",description="是否已订阅 1=是 0=否",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function post_clock_action_subscribe()
    {
        $this->checkUser();
        $actionId = $this->request->param('action_id', 0, 'intval');
        $isSubscribed = $this->request->param('is_subscribed', 0, 'intval');

        if (empty($actionId)) {
            $this->error('动作ID不能为空');
        }

        $action = ActiveClockActionModel::where('id', $actionId)->find();
        if (!$action) {
            $this->error('动作配置不存在');
        }

        // 记录订阅状态
        ActiveClockPushUserModel::recordSubscription($actionId, $action['active_id'], $this->user_id, $isSubscribed == 1);

        $this->success('操作成功');
    }

    /**
     * 我的徽章列表
     * @OA\Get (
     *tags={"新版活动"},
     *path="/wxapp/active/get_new_badge_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_new_badge_list(){
        $this->checkUser();
        $list = MemberBadgeModel::where('user_id',$this->user_id)->order('id desc')->select()->each(function ($item){
            $item['image'] = empty($item['image'])?'':cmf_get_asset_url($item['image']);
            return $item;
        });
        $this->success('请求成功', $list);
    }


    /**
     * 获取一组打卡时间戳中 最长连续打卡天数
     * @param array $timeStampArr 打卡时间戳数组
     * @return int 最长连续天数
     */
    function getMaxContinueSignDays(array $timeStampArr): int
    {
        // 无打卡数据直接返回0
        if (empty($timeStampArr)) {
            return 0;
        }

        // 1、全部转为当天0点时间戳
        $dayTsArr = array_map(function ($ts) {
            return strtotime(date('Y-m-d', $ts));
        }, $timeStampArr);

        // 2、去重：同一天多次打卡只保留1条
        $uniqueDayTs = array_unique($dayTsArr);
        // 3、升序排序
        sort($uniqueDayTs);

        $oneDaySecond = 86400; // 一天总秒数
        $maxDays = 1;    // 最大连续天数，最少1天
        $currentDays = 1;// 当前连续计数
        $prevTs = $uniqueDayTs[0];

        foreach ($uniqueDayTs as $ts) {
            // 跳过第一条自身
            if ($ts === $prevTs) {
                continue;
            }
            // 和前一天刚好差一整天 = 连续
            if (($ts - $prevTs) === $oneDaySecond) {
                $currentDays++;
                // 更新最大值
                $maxDays = max($maxDays, $currentDays);
            } else {
                // 断签，重置当前连续计数
                $currentDays = 1;
            }
            $prevTs = $ts;
        }

        return $maxDays;
    }

    /** 拉取限定条数的活动列表并补全展示字段 */
    private function fetchActiveListSlice($query, $limit, $order = 'list_order asc, id desc')
    {
        $rows = $query->order($order)->limit($limit)->select();
        $out = [];
        foreach ($rows as $item) {
            $out[] = $this->applyActiveListItemFields($item);
        }
        return $out;
    }

    /** 活动 Tab 轮播（slide_id=9） */
    private function fetchActivityTabBannerList($slideId = 9)
    {
        $slideId = (int)$slideId;
        if ($slideId <= 0) {
            return [];
        }
        $items = (new SlideItemModel())
            ->field('image,url,title,target')
            ->where(['slide_id' => $slideId, 'status' => 1])
            ->order('list_order')
            ->select();
        $out = [];
        foreach ($items as $row) {
            $item = is_array($row) ? $row : $row->toArray();
            $item['image'] = CommonModel::getOneImageFullUrl($item['image']);
            $out[] = $item;
        }
        return $out;
    }

    /** 活动标签去重列表 */
    private function collectActiveTagList()
    {
        $allTags = ActiveModel::where('type', 1)->where('status', 1)->where('delete_time', 0)
            ->whereNotNull('tags')->where('tags', '<>', '')->column('tags');
        $tagSet = [];
        foreach ($allTags as $tagStr) {
            $tags = array_filter(array_map('trim', explode(',', $tagStr)));
            foreach ($tags as $t) {
                $tagSet[$t] = true;
            }
        }
        $tagList = array_keys($tagSet);
        sort($tagList);
        return $tagList;
    }

    /** 本周推荐 / 轻活动 / 近期活动 */
    private function buildActivityPageSections()
    {
        $lightTag = '轻活动';

        $weeklyQuery = ActiveModel::miniProgramVisible()->where('is_month_recommend', '=', 1);
        $lightQuery = ActiveModel::miniProgramVisible()->whereRaw('FIND_IN_SET(?, tags)', [$lightTag]);
        return [
            'weekly_list' => $this->fetchActiveListSlice($weeklyQuery, 10),
            'light_list' => $this->fetchActiveListSlice($lightQuery, 3),
        ];
    }

    /** 活动 Tab 底部列表分页（与 get_new_active_list 公共条件一致，支持 tag） */
    private function paginateActiveListForTab($page, $pageSize, $tag = '')
    {
        $query = ActiveModel::miniProgramVisible();
        if ($tag !== '') {
            $query->whereRaw('FIND_IN_SET(?, tags)', [$tag]);
        }
        $total = (clone $query)->count();
        $rows = $query->order('list_order asc, id desc')->page($page, $pageSize)->select();
        $list = [];
        foreach ($rows as $item) {
            $list[] = $this->applyActiveListItemFields($item);
        }
        return ['list' => $list, 'total' => $total];
    }

    /** 单条活动列表项字段补全（列表/分页/聚合接口共用） */
    protected function applyActiveListItemFields($item)
    {
        $item['thumb'] = empty($item['thumb']) ? '' : cmf_get_asset_url($item['thumb']);
        $item['badge_image'] = empty($item['badge_image']) ? '' : cmf_get_asset_url($item['badge_image']);
        $item['content'] = htmlspecialchars_decode($item['content']);
        $item['send_point'] = $item['send_point'] ?? 0;

        $item['active_count'] = ActiveSIgnModel::countParticipants((int) $item['id']);
        $avatar_array = ActiveSIgnModel::alias('a')
            ->join('member b', 'a.user_id=b.id')
            ->where('a.active_id', $item['id'])->limit(5)->column('b.avatar');
        foreach ($avatar_array as &$value) {
            $value = empty($value) ? '' : cmf_get_asset_url($value);
        }
        $item['avatar_array'] = $avatar_array;

        $sign_info = ActiveSIgnModel::where('active_id', $item['id'])->where('user_id', $this->user_id)->find();
        $item['sign_id'] = empty($sign_info) ? 0 : $sign_info['id'];
        $item['is_sign'] = empty($sign_info) ? 0 : 1;

        $date_time = strtotime(date('Y-m-d 00:00:00'));
        $clockWhere = [];
        $clockWhere[] = ['active_id', '=', $item['id']];
        $clockWhere[] = ['user_id', '=', $this->user_id];
        $clockWhere[] = ['sign_id', '=', $item['sign_id']];
        $clockWhere[] = ['date_time', '=', $date_time];
        $clock_info = ActiveClockModel::where($clockWhere)->find();
        $item['is_sign_clock'] = empty($clock_info) ? 0 : 1;

        $item['is_date_pending'] = (int)($item['is_date_pending'] ?? 0);
        $item['is_end'] = ActiveModel::resolveIsEnd($item);
        $this->attachWebLaunchFields($item);
        return $item;
    }

    /**
     * 小程序打开 Web 式活动前签发 web_ctx（需登录，Header openid）
     */
    public function issue_web_context()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        if ($activeId <= 0) {
            $this->error('缺少活动 ID');
        }
        $item = ActiveModel::where('id', $activeId)->where('delete_time', 0)->find();
        if (empty($item)) {
            $this->error('活动不存在');
        }
        if (!ActiveModel::isWebDisplay($item)) {
            $this->error('该活动不是 Web 式');
        }
        $token = ActiveWebContext::issue((int)$this->user_id, $activeId);
        $launch = ActiveModel::buildWebActivityUrl((string)$item['web_url'], $activeId);
        $launch = ActiveWebContext::appendQueryParam($launch, 'web_ctx', $token);
        $this->success('请求成功', [
            'web_ctx' => $token,
            'web_launch_url' => $launch,
            'expires_in' => ActiveWebContext::TTL,
        ]);
    }

    /** Web 式：web_page_url + 已登录时带 web_ctx 的 web_launch_url */
    protected function attachWebLaunchFields(&$item): void
    {
        $item['display_mode'] = (int)($item['display_mode'] ?? ActiveModel::DISPLAY_MODE_CONFIG);
        $item['web_url'] = trim((string)($item['web_url'] ?? ''));
        $item['web_page_url'] = '';
        $item['web_launch_url'] = '';
        if (!ActiveModel::isWebDisplay($item)) {
            return;
        }
        $activeId = (int)$item['id'];
        $base = ActiveModel::buildWebActivityUrl($item['web_url'], $activeId);
        $item['web_page_url'] = $base;
        $launch = $base;
        if (!empty($this->user_id)) {
            $token = ActiveWebContext::issue((int)$this->user_id, $activeId);
            $launch = ActiveWebContext::appendQueryParam($launch, 'web_ctx', $token);
        }
        $item['web_launch_url'] = $launch;
    }

}