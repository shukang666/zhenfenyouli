<?php

namespace app\admin\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use app\admin\model\basic\SelectModel;
use app\admin\model\forum\ForumCateModel;
use app\admin\model\forum\ClockSceneGuideModel;
use app\admin\model\forum\ClockScenePointModel;
use app\admin\model\forum\ClockTutorialModel;
use app\admin\model\forum\ForumDeleteLogModel;
use app\admin\model\forum\ForumHandleModel;
use app\admin\model\forum\ForumModel;
use app\admin\model\member\CommunityCateModel;
use app\admin\model\member\CommunityModel;
use app\admin\model\member\MemberLevelModel;
use app\admin\model\member\MemberModel;
use think\App;
use think\db\exception\DbException;
use think\facade\Db;

class ForumController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ForumModel();
    }

    public function setWhere()
    {
        $param = $this->request->param();
        $where = [];
        if (!empty($param['keyword'])) {
            $where[] = ['content', 'like', '%' . trim($param['keyword']) . '%'];
        }

        if (!empty($param['cate_id'])) {
            // type=3/4 的分类来自 member_community_cate，可能存在同名不同id的情况
            // 用名称匹配所有同名分类，避免丢失数据
            if (isset($param['type']) && in_array($param['type'], [3, 4])) {
                $cate_name = CommunityCateModel::where('id', $param['cate_id'])->value('name');
                if ($cate_name) {
                    $cate_ids = CommunityCateModel::where('name', $cate_name)->column('id');
                    if (!empty($cate_ids)) {
                        $where[] = ['a.cate_id', 'in', $cate_ids];
                    } else {
                        $where[] = ['a.cate_id', '=', trim($param['cate_id'])];
                    }
                } else {
                    $where[] = ['a.cate_id', '=', trim($param['cate_id'])];
                }
            } else {
                $where[] = ['cate_id', '=', trim($param['cate_id'])];
            }
        }

        if (!empty($param['id'])) {
            $where[] = ['b.id', '=', trim($param['id'])];
        }
        if (!empty($param['name'])) {
            // 列表“用户”列显示为 (ID)昵称，用户可能按 ID 或昵称搜索，故同时匹配 b.id
            $where[] = ['b.nickname|b.mobile|b.id', 'like', '%' . trim($param['name']) . '%'];
        }
        if (!empty($param['type'])) {
            $where[] = ['a.type', '=', trim($param['type'])];
        }

        if (!empty($param['community_id'])) {
            $cid = (int)trim($param['community_id']);
            if ($cid > 0) {
                // 与列表展示一致：优先 subject_community_id，否则帖子 community_id 或用户绑定小区
                $where[] = function ($query) use ($cid) {
                    $query->where(function ($q) use ($cid) {
                        $q->where('a.subject_community_id', '=', $cid)
                            ->whereOr(function ($q2) use ($cid) {
                                $q2->where(function ($q3) {
                                    $q3->whereNull('a.subject_community_id')
                                        ->whereOr('a.subject_community_id', '=', 0);
                                })->where(function ($q4) use ($cid) {
                                    $q4->where('a.community_id', '=', $cid)
                                        ->whereOr('b.community_id', '=', $cid);
                                });
                            });
                    });
                };
            }
        }

//        $where[] = ['a.type', 'in', [1, 3]];
        $this->assign('cate_id', $param['cate_id'] ?? 0);

        $handle_arr = [3, 4];
        $is_handle = 0;
        if (in_array($param['type'], $handle_arr)) {
            $is_handle = 1;
        }
        $this->assign('is_handle', $is_handle);

        $title_arr = [5, 6, 7];
        $is_title = 0;
        if (in_array($param['type'], $title_arr)) {
            $is_title = 1;
        }
        $this->assign('is_title', $is_title);

        $start_time = $this->request->param('start_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');
        if ($start_time) $where[] = ['a.create_time', '>=', strtotime($start_time)];
        if ($end_time) $where[] = ['a.create_time', '<=', strtotime($end_time) + 86399];

        return $where;
    }


    public function setIndex()
    {
        $export = $this->request->param('export', 0);
        $type = $this->request->param('type');
//        var_dump($export);exit;
        $where = $this->setWhere();
        //后台待支付,不需要展示
//        $where[] = ['a.status', '>', 1];
        $where[] = ['a.delete_time', '=', 0];

        $sql = $this->model
//            ->withTrashed()
            ->alias('a')
            ->join('member b', 'a.user_id=b.id')
            ->leftjoin('forum_cate c', 'a.cate_id=c.id')
            ->field('a.*,b.nickname,b.mobile user_mobile,b.id uid,b.avatar,c.name cate_name,b.community_id as member_community_id')
            ->where($where)
            ->order('id desc');
        if ($export) {
            $list = $sql->select();
        } else {
            $list = $sql->paginate(15);
        }

        $list->each(function ($item) {
            $item->top_end_time = $item['top_end_time'] != 0 ? date('Y-m-d H:i:s', $item['top_end_time']) : '无';
            $item['status_name'] = $this->model->get_status()[$item['status']];
            if (!empty($item['nickname'])) {
                $item['user_info'] = "({$item['user_id']}){$item['nickname']}";
            } else {
                $item['user_info'] = "被删除";
            }

            // 小区名称（勿用 b.community_id 覆盖 a.community_id 后再查，见 field 别名）
            $displayCommunityId = (int)($item['subject_community_id'] ?? 0);
            if ($displayCommunityId <= 0) {
                $displayCommunityId = (int)($item['community_id'] ?? 0);
            }
            if ($displayCommunityId <= 0) {
                $displayCommunityId = (int)($item['member_community_id'] ?? 0);
            }
            $item['community_name'] = $displayCommunityId > 0
                ? (CommunityModel::where('id', $displayCommunityId)->value('name') ?: '')
                : '';

            if ($item['type'] == 1) {
                $item['cate_name'] = ForumCateModel::where('id', $item['cate_id'])->value('name');
            } elseif ($item['type'] == 2) {
                $item['cate_name'] = SelectModel::where('id', $item['cate_id'])->value('name');
                $item['clock_scene_label'] = $this->formatClockSceneLabel($item['clock_scene'] ?? '');
            } elseif ($item['type'] == 3 || $item['type'] == 4) {//定位巡检
                $item['cate_name'] = CommunityCateModel::where('id', $item['cate_id'])->value('name', '');
            } else {
                $item['cate_name'] = '';
            }

            return CommonModel::getOne($item, ['thumb', 'file', 'pay_proof'], ['image']);

        });

        if ($export) {
            $supervisorExport = $this->request->param('supervisor_export', 0);
            if($type == 3){
                if ($supervisorExport) {
                    $this->outSupervisor($where);
                } else {
                    $this->outData3($list);
                }
            }else{
                $this->outData($list);
            }
        }
        return $list;
    }


    /**
     * 列表
     * @return mixed
     * @throws DbException
     */
    public function index()
    {
        $params = $this->request->param();
        $list = $this->setIndex()->each(function ($item) {
            return $this->getList($item);
        });
//        $list = $this->getParamData($list);
        $this->assign('params', $params);
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());

        return $this->fetch();
    }


    //页面获取分类数据
    public function getJsonCateList()
    {
        $cate = $this->request->param('cate');
        $pid = $this->request->param('pid');
//        var_dump($cate,$pid);exit;
        $map = [];
        if (!empty($pid)) {
            $map[] = ['pid', '=', $pid];
        } else {
            $map[] = ['pid', '=', 0];
        }

        $CategoryModel = new ForumCateModel();
        $result = $CategoryModel->where($map)->field('id,name cate_name')->select();
        $this->success('', '', $result);
    }


    public function handle()
    {
        $id = $this->request->param('id');

        $ForumHandleModel = new ForumHandleModel();
        $item = $ForumHandleModel->where('forum_id', $id)->find();
//       dump($item);
        $item = CommonModel::getOne($item, [], ['image']);
        if (!empty($item['create_time'])) {
            $item['create_time'] = date("Y-m-d H:i:s", $item['create_time']);
        }
        if (!empty($item['image_one'])) $item['image_one'] = cmf_get_asset_url($item['image_one']);
        if (!empty($item['image_two'])) $item['image_two'] = cmf_get_asset_url($item['image_two']);
        if (!empty($item['image_three'])) $item['image_three'] = cmf_get_asset_url($item['image_three']);

        $this->assign('item', $item);
        return $this->fetch();
    }

    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $reason = $this->request->param('reason', '', 'trim');

        // 删除原因为必填项
        if (empty($reason)) {
            $this->error('请填写删除原因');
        }

        // 查询要删除的打卡记录（获取删除前的完整内容）
        $forum = $this->model->where('id', $id)->find();
        if (empty($forum)) {
            $this->error('打卡记录不存在');
        }

        $item = $this->model->where('id', $id)->where('point', '>', 0)->find();

        // 使用事务：记录删除日志 + 更新积分 + 软删除
        Db::startTrans();
        try {
            // 1. 记录删除日志
            $deleteLogData = [
                'forum_id'    => $forum['id'],
                'user_id'     => $forum['user_id'],
                'content'     => $forum['content'] ?? '',
                'image'       => ForumDeleteLogModel::imageToString($forum['image'] ?? ''),
                'point'       => $forum['point'] ?? 0,
                'reason'      => $reason,
                'operator_id' => session('ADMIN_ID') ?? 0,
                'create_time' => time(),
            ];
            ForumDeleteLogModel::create($deleteLogData);

            // 2. 扣除积分
            if (!empty($item)) {
                AccountLogModel::add_account_log($item['user_id'], 2, 200, -$item['point'], ['remark' => '垃圾打卡扣除']);
            }

            $log_info = AccountLogModel::where(['from_uid' => $id])->find();
            if (!empty($log_info)) {
                AccountLogModel::add_account_log($forum['user_id'], 2, 227, -$log_info['change_amount'], ['remark' => '垃圾打卡扣除']);
            }

            // 3. 执行软删除
            $this->model->where('id', $id)->update(['delete_time' => time()]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('删除失败：' . $e->getMessage());
        }

        $this->success('删除成功');
    }

    public function delete_all()
    {
        $ids = $this->request->param('ids');
        $reason = $this->request->param('reason', '', 'trim');

        if (empty($reason)) {
            $this->error('请填写删除原因');
        }

        if (empty($ids)) {
            $this->error('请选择要删除的记录');
        }

        $list = $this->model->where('id', 'in', $ids)->select();
        $pointList = $this->model->where('id', 'in', $ids)->where('point', '>', 0)->select();

        Db::startTrans();
        try {
            foreach ($list as $item) {
                // 记录删除日志
                $deleteLogData = [
                    'forum_id'    => $item['id'],
                    'user_id'     => $item['user_id'],
                    'content'     => $item['content'] ?? '',
                    'image'       => ForumDeleteLogModel::imageToString($item['image'] ?? ''),
                    'point'       => $item['point'] ?? 0,
                    'reason'      => $reason,
                    'operator_id' => session('ADMIN_ID') ?? 0,
                    'create_time' => time(),
                ];
                ForumDeleteLogModel::create($deleteLogData);
            }

            foreach ($pointList as $item) {
                AccountLogModel::add_account_log($item['user_id'], 2, 200, -$item['point'], ['remark' => '垃圾打卡扣除']);

                $id = $item['id'];
                $log_info = AccountLogModel::where(['from_uid' => $id])->find();
                if (!empty($log_info)) {
                    AccountLogModel::add_account_log($item['user_id'], 2, 227, -$log_info['change_amount'], ['remark' => '垃圾打卡扣除']);
                }
            }

            $this->model->whereIn('id', $ids)->update(['delete_time' => time()]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('删除失败：' . $e->getMessage());
        }

        $this->success('删除成功');
    }


    public function index1()
    {
        return redirect((string)url('index', ['type' => 1]));
    }

    public function index2()
    {
        return redirect((string)url('index', ['type' => 2]));
    }

    /** 垃圾打卡 · 打卡教程配置 */
    public function clock_tutorial_index()
    {
        $list = ClockTutorialModel::order('list_order asc,id asc')->select();
        $labels = ClockTutorialModel::cateKeyLabels();
        $list->each(function ($item) use ($labels) {
            $item['cate_key_label'] = $labels[$item['cate_key']] ?? $item['cate_key'];
            return $item;
        });
        return $this->fetch('clock_tutorial_index', ['list' => $list]);
    }

    public function clock_tutorial_update()
    {
        $id = $this->request->param('id', 0, 'intval');
        $item = ClockTutorialModel::where('id', $id)->find();
        if (empty($item)) {
            $this->error('记录不存在');
        }
        $labels = ClockTutorialModel::cateKeyLabels();
        $item['cate_key_label'] = $labels[$item['cate_key']] ?? $item['cate_key'];
        $arr = $item->toArray();
        if (trim((string)($arr['content'] ?? '')) === '') {
            $item['content'] = ClockTutorialModel::buildLegacyHtml($arr);
        }
        return $this->fetch('clock_tutorial_update', ['item' => $item]);
    }

    public function clock_tutorial_update_post()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $id = $this->request->param('id', 0, 'intval');
        $item = ClockTutorialModel::where('id', $id)->find();
        if (empty($item)) {
            $this->error('记录不存在');
        }
        $save = [
            'title'       => trim((string)$this->request->param('title', '')),
            'content'     => (string)$this->request->param('content', ''),
            'status'      => (int)$this->request->param('status', 1),
            'update_time' => time(),
        ];
        if ($save['title'] === '') {
            $this->error('请填写教程标题');
        }
        if (trim($save['content']) === '') {
            $this->error('请填写教程正文（富文本）');
        }
        ClockTutorialModel::where('id', $id)->update($save);
        $this->success('保存成功', url('clock_tutorial_index'));
    }

    /** 场景分类引导（活动页场景入口） */
    public function clock_scene_guide_index()
    {
        $hub = ClockSceneGuideModel::normalizeHub($this->request->param('hub_key', ''));
        $query = ClockSceneGuideModel::order('hub_key asc,sub_key asc,list_order asc,id asc');
        if ($hub !== '') {
            $query->where('hub_key', $hub);
        }
        $list = $query->select();
        $hubLabels = ClockSceneGuideModel::hubLabels();
        $subLabels = ClockSceneGuideModel::subLabels();
        $list->each(function ($item) use ($hubLabels, $subLabels) {
            $item['hub_label'] = $hubLabels[$item['hub_key']] ?? $item['hub_key'];
            $sk = $item['sub_key'] ?? '';
            $item['sub_label'] = $subLabels[$sk] ?? ($sk ?: '—');
            return $item;
        });
        return $this->fetch('clock_scene_guide_index', [
            'list'      => $list,
            'hub_key'   => $hub,
            'hubLabels' => $hubLabels,
        ]);
    }

    public function clock_scene_guide_update()
    {
        $id = $this->request->param('id', 0, 'intval');
        $item = ClockSceneGuideModel::where('id', $id)->find();
        if (empty($item)) {
            $this->error('记录不存在');
        }
        $hubLabels = ClockSceneGuideModel::hubLabels();
        $subLabels = ClockSceneGuideModel::subLabels();
        $item['hub_label'] = $hubLabels[$item['hub_key']] ?? $item['hub_key'];
        $sk = $item['sub_key'] ?? '';
        $item['sub_label'] = $subLabels[$sk] ?? ($sk ?: '—');
        $tips = trim((string)($item['preset_tips'] ?? ''));
        if ($tips !== '' && $tips[0] === '[') {
            $decoded = json_decode($tips, true);
            if (is_array($decoded)) {
                $item['preset_tips_text'] = implode("\n", $decoded);
            }
        }
        if (empty($item['preset_tips_text'])) {
            $lines = ClockSceneGuideModel::linesToArray($tips);
            $item['preset_tips_text'] = implode("\n", $lines);
        }
        return $this->fetch('clock_scene_guide_update', ['item' => $item]);
    }

    public function clock_scene_guide_update_post()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $id = $this->request->param('id', 0, 'intval');
        $item = ClockSceneGuideModel::where('id', $id)->find();
        if (empty($item)) {
            $this->error('记录不存在');
        }
        $tipsText = (string)$this->request->param('preset_tips_text', '');
        $tipsLines = ClockSceneGuideModel::linesToArray($tipsText);
        $save = [
            'handle_guide'    => (string)$this->request->param('handle_guide', ''),
            'dispose_time'    => trim((string)$this->request->param('dispose_time', '')),
            'survey_question' => trim((string)$this->request->param('survey_question', '')),
            'preset_tips'     => json_encode($tipsLines, JSON_UNESCAPED_UNICODE),
            'learn_path'      => trim((string)$this->request->param('learn_path', '/pages/flhd/list')),
            'status'          => (int)$this->request->param('status', 1),
            'update_time'     => time(),
        ];
        if (trim($save['handle_guide']) === '') {
            $this->error('请填写处理指引');
        }
        ClockSceneGuideModel::where('id', $id)->update($save);
        $this->success('保存成功', url('clock_scene_guide_index'));
    }

    /** 沿街/办公：按用户人工配置统一回收点 */
    public function clock_scene_point_index()
    {
        $hub = trim((string)$this->request->param('hub_key', ''));
        $keyword = trim((string)$this->request->param('keyword', ''));
        $adminHubs = array_keys(ClockScenePointModel::adminHubLabels());
        $query = Db::name('clock_scene_point')->alias('p')
            ->join('member m', 'm.id = p.user_id', 'LEFT')
            ->where('p.source', ClockScenePointModel::SOURCE_ADMIN)
            ->whereIn('p.hub_key', $adminHubs)
            ->field('p.*, m.nickname, m.mobile')
            ->order('p.id desc');
        if ($hub !== '' && in_array($hub, $adminHubs, true)) {
            $query->where('p.hub_key', $hub);
        }
        if ($keyword !== '') {
            $query->where('m.nickname|m.mobile|p.point_name|p.remark', 'like', '%' . $keyword . '%');
        }
        $list = $query->paginate(20);
        $hubLabels = ClockScenePointModel::adminHubLabels();
        return $this->fetch('clock_scene_point_index', [
            'list'      => $list,
            'page'      => $list->render(),
            'hub_key'   => $hub,
            'keyword'   => $keyword,
            'hubLabels' => $hubLabels,
        ]);
    }

    public function clock_scene_point_add()
    {
        $id = $this->request->param('id', 0, 'intval');
        $item = null;
        if ($id > 0) {
            $item = ClockScenePointModel::where('id', $id)
                ->where('source', ClockScenePointModel::SOURCE_ADMIN)
                ->find();
            if (empty($item)) {
                $this->error('记录不存在');
            }
        }
        $cateList = SelectModel::where('type', 2)->where('pid', 0)->order('list_order asc,id asc')->select();
        $communityList = CommunityModel::where('delete_time', 0)->order('id desc')->limit(500)->select();
        return $this->fetch('clock_scene_point_add', [
            'data'          => $item,
            'cateList'      => $cateList,
            'hubLabels'     => ClockScenePointModel::adminHubLabels(),
            'communityList' => $communityList,
        ]);
    }

    public function clock_scene_point_add_post()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $id = $this->request->param('id', 0, 'intval');
        $userId = intval($this->request->param('user_id', 0));
        $hub = trim((string)$this->request->param('hub_key', ''));
        $adminHubs = array_keys(ClockScenePointModel::adminHubLabels());
        if ($userId <= 0) {
            $this->error('请填写用户ID');
        }
        if (!in_array($hub, $adminHubs, true)) {
            $this->error('请选择沿街商铺场景');
        }
        if ($hub === 'office') {
            $this->error('办公大楼请使用「办公大楼回收点」菜单配置');
        }
        $member = Db::name('member')->where('id', $userId)->find();
        if (empty($member)) {
            $this->error('用户不存在');
        }
        $pointName = trim((string)$this->request->param('point_name', ''));
        if ($pointName === '') {
            $this->error('请填写回收点名称');
        }
        $cateId = intval($this->request->param('cate_id', 0));
        $cate = SelectModel::where('id', $cateId)->where('type', 2)->where('pid', 0)->find();
        if (empty($cate)) {
            $this->error('请选择垃圾种类');
        }
        $now = time();
        $communityId = intval($this->request->param('community_id', 0));
        $pointImage = trim((string)$this->request->param('point_image', ''));
        if ($pointImage !== '' && mb_strlen($pointImage) > 255) {
            $this->error('点位照片路径过长');
        }
        $save = [
            'user_id'      => $userId,
            'community_id' => $hub === 'office' ? $communityId : 0,
            'hub_key'      => $hub,
            'point_name'   => mb_substr($pointName, 0, 32),
            'cate_id'      => $cateId,
            'cate_name'    => $cate['name'],
            'point_image'  => $pointImage,
            'source'       => ClockScenePointModel::SOURCE_ADMIN,
            'remark'       => mb_substr(trim((string)$this->request->param('remark', '')), 0, 255),
            'admin_id'     => (int)session('ADMIN_ID'),
            'update_time'  => $now,
        ];
        if ($id > 0) {
            $row = ClockScenePointModel::where('id', $id)
                ->where('source', ClockScenePointModel::SOURCE_ADMIN)
                ->find();
            if (empty($row)) {
                $this->error('记录不存在');
            }
            ClockScenePointModel::where('id', $id)->update($save);
            $this->success('保存成功', url('clock_scene_point_index'));
            return;
        }
        $maxOrder = (int)ClockScenePointModel::where('user_id', $userId)->where('hub_key', $hub)->max('list_order');
        $save['list_order'] = $maxOrder + 1;
        $save['create_time'] = $now;
        ClockScenePointModel::insert($save);
        $this->success('添加成功', url('clock_scene_point_index'));
    }

    public function clock_scene_point_delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id <= 0) {
            $this->error('参数错误');
        }
        $row = ClockScenePointModel::where('id', $id)
            ->where('source', ClockScenePointModel::SOURCE_ADMIN)
            ->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }
        if (($row['hub_key'] ?? '') === ClockScenePointModel::officeBuildingHubKey()) {
            $this->error('办公大楼请使用「办公大楼回收点」菜单删除');
        }
        ClockScenePointModel::where('id', $id)->delete();
        $this->success('已删除');
    }

    /** 办公：维护大楼名、回收点地址与示意图（小程序选楼后展示） */
    public function office_recycle_building_index()
    {
        $keyword = trim((string)$this->request->param('keyword', ''));
        $query = Db::name('clock_scene_point')
            ->where('hub_key', ClockScenePointModel::officeBuildingHubKey())
            ->where('source', ClockScenePointModel::SOURCE_ADMIN)
            ->order('list_order asc,id asc');
        if ($keyword !== '') {
            $query->where('point_name|remark', 'like', '%' . $keyword . '%');
        }
        $list = $query->paginate(20);
        return $this->fetch('office_recycle_building_index', [
            'list'    => $list,
            'page'    => $list->render(),
            'keyword' => $keyword,
        ]);
    }

    public function office_recycle_building_add()
    {
        $id = $this->request->param('id', 0, 'intval');
        $item = null;
        if ($id > 0) {
            $item = ClockScenePointModel::where('id', $id)
                ->where('hub_key', ClockScenePointModel::officeBuildingHubKey())
                ->where('source', ClockScenePointModel::SOURCE_ADMIN)
                ->find();
            if (empty($item)) {
                $this->error('记录不存在');
            }
        }
        return $this->fetch('office_recycle_building_add', [
            'data' => $item,
        ]);
    }

    public function office_recycle_building_add_post()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $id = $this->request->param('id', 0, 'intval');
        $buildingName = trim((string)$this->request->param('building_name', ''));
        if ($buildingName === '') {
            $this->error('请填写办公大楼名称');
        }
        if (mb_strlen($buildingName) > 32) {
            $this->error('大楼名称过长（最多32字）');
        }
        $address = trim((string)$this->request->param('recycle_address', ''));
        if (mb_strlen($address) > 255) {
            $this->error('地址说明过长');
        }
        $cate = ClockScenePointModel::resolveRecycleCate();
        if (empty($cate)) {
            $this->error('未找到「可回收物」种类，请先在基础数据里配置垃圾分类');
        }
        $pointImage = trim((string)$this->request->param('point_image', ''));
        if ($pointImage !== '' && mb_strlen($pointImage) > 255) {
            $this->error('示意图路径过长');
        }
        $listOrder = intval($this->request->param('list_order', 0));
        $now = time();
        $dupQuery = Db::name('clock_scene_point')
            ->where('hub_key', ClockScenePointModel::officeBuildingHubKey())
            ->where('source', ClockScenePointModel::SOURCE_ADMIN)
            ->where('point_name', $buildingName);
        if ($id > 0) {
            $dupQuery->where('id', '<>', $id);
        }
        if ($dupQuery->find()) {
            $this->error('已存在同名办公大楼，请直接编辑原记录');
        }
        $save = [
            'user_id'      => 0,
            'community_id' => 0,
            'hub_key'      => ClockScenePointModel::officeBuildingHubKey(),
            'point_name'   => mb_substr($buildingName, 0, 32),
            'cate_id'      => $cate['id'],
            'cate_name'    => $cate['name'],
            'point_image'  => $pointImage,
            'source'       => ClockScenePointModel::SOURCE_ADMIN,
            'remark'       => mb_substr($address, 0, 255),
            'list_order'   => $listOrder,
            'admin_id'     => (int)session('ADMIN_ID'),
            'update_time'  => $now,
        ];
        if ($id > 0) {
            $row = ClockScenePointModel::where('id', $id)
                ->where('hub_key', ClockScenePointModel::officeBuildingHubKey())
                ->where('source', ClockScenePointModel::SOURCE_ADMIN)
                ->find();
            if (empty($row)) {
                $this->error('记录不存在');
            }
            ClockScenePointModel::where('id', $id)->update($save);
            $this->success('保存成功', url('office_recycle_building_index'));
            return;
        }
        $save['create_time'] = $now;
        ClockScenePointModel::insert($save);
        $this->success('添加成功', url('office_recycle_building_index'));
    }

    public function office_recycle_building_delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id <= 0) {
            $this->error('参数错误');
        }
        ClockScenePointModel::where('id', $id)
            ->where('hub_key', ClockScenePointModel::officeBuildingHubKey())
            ->where('source', ClockScenePointModel::SOURCE_ADMIN)
            ->delete();
        $this->success('已删除');
    }

    /** 家庭/沿街：用户在小程序布置的真分点位（只读查看） */
    public function user_clock_scene_point_index()
    {
        $hub = trim((string)$this->request->param('hub_key', ''));
        $keyword = trim((string)$this->request->param('keyword', ''));
        $userHubs = array_keys(ClockScenePointModel::userHubLabels());
        $userHubs[] = 'street';
        $hubLabels = array_merge(ClockScenePointModel::userHubLabels(), [
            'street' => '沿街商铺',
        ]);
        $query = Db::name('clock_scene_point')->alias('p')
            ->join('member m', 'm.id = p.user_id', 'LEFT')
            ->leftJoin('street_shop_merchant sm', 'sm.user_id = p.user_id')
            ->where('p.source', ClockScenePointModel::SOURCE_USER)
            ->where('p.user_id', '>', 0)
            ->field('p.*, m.nickname, m.mobile, sm.shop_name, sm.shop_type_name')
            ->order('p.update_time desc,p.id desc');
        if ($hub !== '' && in_array($hub, $userHubs, true)) {
            if ($hub === 'home') {
                $query->whereIn('p.hub_key', ['home', 'qinzi']);
            } else {
                $query->where('p.hub_key', $hub);
            }
        } else {
            $query->whereIn('p.hub_key', array_merge(['home', 'qinzi', 'street']));
        }
        if ($keyword !== '') {
            $query->where(
                'm.nickname|m.mobile|p.point_name|p.cate_name|sm.shop_name',
                'like',
                '%' . $keyword . '%'
            );
        }
        $list = $query->paginate(20);
        return $this->fetch('user_clock_scene_point_index', [
            'list'      => $list,
            'page'      => $list->render(),
            'hub_key'   => $hub,
            'keyword'   => $keyword,
            'hubLabels' => $hubLabels,
        ]);
    }

    /** 沿街：用户登记商户（点位由用户在小程序上传） */
    public function street_shop_merchant_index()
    {
        $keyword = trim((string)$this->request->param('keyword', ''));
        $query = Db::name('street_shop_merchant')->alias('m')
            ->join('member mem', 'mem.id = m.user_id', 'LEFT')
            ->field('m.*, mem.nickname, mem.mobile')
            ->order('m.id desc');
        if ($keyword !== '') {
            $query->where('m.shop_name|mem.nickname|mem.mobile', 'like', '%' . $keyword . '%');
        }
        $list = $query->paginate(20);
        return $this->fetch('street_shop_merchant_index', [
            'list'    => $list,
            'page'    => $list->render(),
            'keyword' => $keyword,
        ]);
    }

    /**
     * 点位巡检：后台代传（与小程序 dwxj/add type=3 一致）
     */
    public function patrolAdd()
    {
        $community_list = CommunityModel::where('delete_time', 0)
            ->field('id,name')
            ->order('id desc')
            ->select();
        $this->assign('community_list', $community_list);
        $this->assign('photo_fields', [
            ['key' => 'image_one', 'label' => '点位环境', 'req' => 1],
            ['key' => 'image_two', 'label' => '分类指引', 'req' => 0],
            ['key' => 'image_three', 'label' => '桶位情况', 'req' => 0],
        ]);
        return $this->fetch('patrol_add');
    }

    /** 按小区加载巡检点位（member_community_cate.cate_id = 小区 id） */
    public function patrolPointsJson()
    {
        $communityId = (int)$this->request->param('community_id', 0);
        if ($communityId <= 0) {
            $this->error('请选择小区');
        }
        $list = CommunityCateModel::where('cate_id', $communityId)
            ->where('delete_time', 0)
            ->field('id,name,inspection_hour,inspection_point')
            ->order('id asc')
            ->select();
        $this->success('', '', $list);
    }

    public function patrolAddPost()
    {
        $userId = (int)$this->request->param('user_id', 0);
        $communityId = (int)$this->request->param('community_id', 0);
        $cateId = (int)$this->request->param('cate_id', 0);
        $siteHygiene = trim((string)$this->request->param('site_hygiene', 'tidy'));
        $hasRectify = (int)$this->request->param('has_rectify', 0);
        $rectifyRemark = trim((string)$this->request->param('rectify_remark', ''));
        $workContent = trim((string)$this->request->param('content', ''));
        $imageOne = trim((string)$this->request->param('image_one', ''));
        $imageTwo = trim((string)$this->request->param('image_two', ''));
        $imageThree = trim((string)$this->request->param('image_three', ''));
        $skipLimit = (int)$this->request->param('skip_inspection_limit', 0);

        if ($userId <= 0) {
            $this->error('请填写归属会员 ID');
        }
        if (!MemberModel::where('id', $userId)->where('delete_time', 0)->find()) {
            $this->error('会员不存在或已删除');
        }
        if ($communityId <= 0) {
            $this->error('请选择工作小区');
        }
        if (!CommunityModel::where('id', $communityId)->where('delete_time', 0)->find()) {
            $this->error('所选小区不存在或已停用');
        }
        if ($cateId <= 0) {
            $this->error('请选择巡检点位');
        }
        $cateInfo = CommunityCateModel::where('id', $cateId)->where('delete_time', 0)->find();
        if (empty($cateInfo) || (int)$cateInfo['cate_id'] !== $communityId) {
            $this->error('点位与小区不匹配');
        }
        if ($imageOne === '') {
            $this->error('请上传点位环境照片');
        }
        if ($workContent === '') {
            $this->error('请填写工作情况');
        }
        if ($hasRectify === 1 && $rectifyRemark === '') {
            $this->error('请填写整改意见');
        }

        $hygieneMap = ['tidy' => '整洁', 'average' => '一般', 'poor' => '较差'];
        $lines = [
            '现场整洁：' . ($hygieneMap[$siteHygiene] ?? $siteHygiene),
            '整改意见：' . ($hasRectify === 1 ? '有' : '无'),
        ];
        if ($hasRectify === 1 && $rectifyRemark !== '') {
            $lines[] = '整改说明：' . $rectifyRemark;
        }
        $lines[] = '';
        $lines[] = '工作情况：';
        $lines[] = $workContent;
        $lines[] = '';
        $lines[] = '【后台代传】操作员 ID：' . (int)(session('ADMIN_ID') ?? 0);
        $content = implode("\n", $lines);

        if ($skipLimit !== 1) {
            $handleInfo = $this->model->where([
                ['type', '=', 3],
                ['user_id', '=', $userId],
                ['cate_id', '=', $cateId],
                ['delete_time', '=', 0],
            ])->order('id desc')->find();
            $hour = (int)($cateInfo['inspection_hour'] ?? 0);
            if (!empty($handleInfo) && $hour > 0) {
                if ($handleInfo['create_time'] > strtotime('-' . $hour . ' hour')) {
                    $this->error('该会员在此点位尚在巡检间隔内，可勾选「跳过间隔限制」后提交');
                }
            }
        }

        $member = MemberModel::where('id', $userId)->field('id,community_id')->find();
        $point = (int)($cateInfo['inspection_point'] ?? 0);

        Db::startTrans();
        try {
            $save = [
                'type'                  => 3,
                'user_id'               => $userId,
                'cate_id'               => $cateId,
                'community_id'          => (int)($member['community_id'] ?? 0),
                'subject_community_id'  => $communityId,
                'content'               => $content,
                'image_one'             => $imageOne,
                'image_two'             => $imageTwo,
                'image_three'           => $imageThree,
                'status'                => 1,
                'create_time'           => time(),
            ];
            if ($point > 0) {
                $save['point'] = $point;
            }
            $this->model->save($save);
            $forumId = (int)$this->model->id;
            if ($point > 0) {
                AccountLogModel::add_account_log($userId, 2, 222, $point, ['from_uid' => $forumId]);
            }
            MemberLevelModel::isLevel($userId);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error('提交失败：' . $e->getMessage());
        }

        $this->success('巡检记录已提交', url('index', ['type' => 3]));
    }

    public function index3()
    {
        return redirect((string)url('index', ['type' => 3]));
    }

    public function index4()
    {
        return redirect((string)url('index', ['type' => 4]));
    }

    public function index5()
    {
        return redirect((string)url('index', ['type' => 5]));
    }

    public function index6()
    {
        return redirect((string)url('index', ['type' => 6]));
    }

    public function index7()
    {
        return redirect((string)url('index', ['type' => 7]));
    }

    /**
     * 设置/取消精选打卡
     */
    public function setChoice()
    {
        $id = $this->request->param('id', 0, 'intval');
        $is_choice = $this->request->param('is_choice', 0, 'intval');

        if (empty($id)) {
            $this->error('参数错误');
        }

        $forum = $this->model->where('id', $id)->find();
        if (empty($forum)) {
            $this->error('打卡记录不存在');
        }

        // 切换精选状态
        $new_status = $forum['is_choice'] == 1 ? 0 : 1;
        $this->model->where('id', $id)->update(['is_choice' => $new_status]);

        $msg = $new_status == 1 ? '已设为精选打卡' : '已取消精选';
        $this->success($msg);
    }

    /**
     * 精选打卡列表页面
     */
    public function choiceList()
    {
        $export = $this->request->param('export', 0);
        $keyword = $this->request->param('keyword', '', 'trim');
        $name = $this->request->param('name', '', 'trim');
        $start_time = $this->request->param('start_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');

        $where = [];
        $where[] = ['a.is_choice', '=', 1];
        $where[] = ['a.delete_time', '=', 0];

        if ($keyword) {
            $where[] = ['a.content', 'like', '%' . trim($keyword) . '%'];
        }
        if ($name) {
            $where[] = ['b.nickname|b.mobile|a.user_id', 'like', '%' . trim($name) . '%'];
        }
        if ($start_time) {
            $where[] = ['a.create_time', '>=', strtotime($start_time)];
        }
        if ($end_time) {
            $where[] = ['a.create_time', '<=', strtotime($end_time) + 86399];
        }

        $sql = $this->model
            ->alias('a')
            ->join('member b', 'a.user_id=b.id')
            ->leftjoin('select c', 'a.cate_id=c.id')
            ->field('a.*,b.nickname,b.mobile user_mobile,b.id uid,b.avatar,c.name cate_name,b.community_id')
            ->where($where)
            ->order('a.id desc');

        if ($export) {
            $list = $sql->select();
        } else {
            $list = $sql->paginate(15);
        }

        $list->each(function ($item) {
            $item['user_info'] = !empty($item['nickname']) ? "({$item['user_id']}){$item['nickname']}" : '被删除';
            $item['community_name'] = \app\admin\model\member\CommunityModel::where('id', $item['community_id'])->value('name');
            $item['cate_name'] = \app\admin\model\basic\SelectModel::where('id', $item['cate_id'])->value('name');
            return CommonModel::getOne($item, ['thumb', 'file', 'pay_proof'], ['image']);
        });

        if ($export) {
            $this->outChoiceData($list);
        }

        $this->assign('list', $list);
        $list->appends($this->request->param());
        $this->assign('page', $list->render());
        return $this->fetch('choice_list');
    }

    /**
     * 精选打卡列表导出
     */
    protected function outChoiceData($list)
    {
        foreach ($list as &$item) {
            if ($item['nickname']) {
                $item['nickname'] = "({$item['user_id']}){$item['nickname']}";
            } else {
                $item['nickname'] = $item['user_id'];
            }
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        }

        $filename = '精选打卡_' . date('Y-m-d');
        $head = ['ID', '用户信息', '小区', '分类', '内容', '积分', '点赞数', '精选时间', '创建时间'];
        $keys = ['id', 'nickname', 'community_name', 'cate_name', 'content', 'point', 'like_total', 'create_time', 'create_time'];
        CommonModel::outData($filename, $list, $head, $keys);
        exit;
    }

    /**
     * 删除记录入口 - 重定向到实际列表方法
     * 后台菜单点的就是这个 action，让它跳到 delete_log_index
     */
    public function deleteLog()
    {
        return $this->redirect('delete_log_index');
    }

    /**
     * 删除记录列表页 - 替代外部Excel
     */
    public function delete_log_index()
    {
        $export = $this->request->param('export', 0);
        $name = $this->request->param('name', '', 'trim');
        $keyword = $this->request->param('keyword', '', 'trim');
        $start_time = $this->request->param('start_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');

        $forumDeleteLogModel = new ForumDeleteLogModel();
        $sql = $forumDeleteLogModel->alias('a')
            ->join('member b', 'a.user_id=b.id', 'LEFT')
            ->field('a.*, b.nickname, b.mobile');

        if ($name) {
            // 用户昵称/手机/ID 模糊搜索
            $sql = $sql->where('b.nickname|b.mobile|a.user_id', 'like', '%' . trim($name) . '%');
        }

        if ($keyword) {
            // 删除原因模糊搜索
            $sql = $sql->whereLike('a.reason', '%' . trim($keyword) . '%');
        }

        if ($start_time) {
            $sql = $sql->where('a.create_time', '>=', strtotime($start_time));
        }
        if ($end_time) {
            $sql = $sql->where('a.create_time', '<=', strtotime($end_time) + 86399);
        }

        $sql = $sql->order('a.id desc');

        if ($export) {
            $list = $sql->select();
        } else {
            $list = $sql->paginate(15);
        }

        // 处理数据展示
        $list->each(function ($item) {
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            if (!empty($item['nickname'])) {
                $item['user_info'] = "({$item['user_id']}){$item['nickname']}";
            } else {
                $item['user_info'] = "用户已删除({$item['user_id']})";
            }
            return $item;
        });

        if ($export) {
            $this->out_delete_log($list);
        }

        $this->assign('list', $list);
        $list->appends($this->request->param());
        $this->assign('page', $list->render());
        return $this->fetch('delete_log');
    }

    /**
     * 删除记录导出
     * @param $list
     */
    protected function out_delete_log($list)
    {
        foreach ($list as &$item) {
            if ($item['nickname']) {
                $item['user_info'] = "({$item['user_id']}){$item['nickname']}";
            } else {
                $item['user_info'] = $item['user_id'];
            }
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        }

        $filename = '打卡删除记录_' . date('Y-m-d');
        $head = ['ID', '打卡ID', '用户信息', '删除原因', '内容', '扣除积分', '操作时间'];
        $keys = ['id', 'forum_id', 'user_info', 'reason', 'content', 'point', 'create_time'];
        CommonModel::outData($filename, $list, $head, $keys);
        exit;
    }

    public function outData($list)
    {

        foreach ($list as &$item) {
            if ($item['nickname']) {
                $item['nickname'] = "({$item['user_id']}){$item['nickname']}";
            } else {
                $item['nickname'] = $item['user_id'];
            }
//            $item['order_num'] = $item['order_num'] . "\n";
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
//            $item['pay_time'] = date('Y-m-d H:i:s', $item['pay_time']);
        }


        $filename = '点位打卡' . date('Y-m-d');
        $head = ['ID', '用户信息', '内容', '创建时间',];//设置表头
        $keys = ['id', 'nickname', 'content', 'create_time',];//设置表头
        CommonModel::outData($filename, $list, $head, $keys);
        exit;
    }



    public function outData3($list)
    {
        // 关闭所有层级输出缓冲
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        //不限制时间
        set_time_limit(0);
        //设置文件大小
        ini_set('memory_limit','2048M');
        // 【重点】file_get_contents 默认socket超时！不加这个，远程图片卡住永不超时
        ini_set('default_socket_timeout', 8);
        gc_enable();

        $image_array = [];
        foreach($list as &$item){
            // 封装图片下载逻辑，统一加上下文超时
            if(!empty($item['image_one'])){
                $item['image_one'] = cmf_get_asset_url($item['image_one']);
                $item['image_one'] = $this->downloadRemoteImage($item['image_one']);
                if($item['image_one']) $image_array[] = $item['image_one'];
            }
            if(!empty($item['image_two'])){
                $item['image_two'] = cmf_get_asset_url($item['image_two']);
                $item['image_two'] = $this->downloadRemoteImage($item['image_two']);
                if($item['image_two']) $image_array[] = $item['image_two'];
            }
            if(!empty($item['image_three'])){
                $item['image_three'] = cmf_get_asset_url($item['image_three']);
                $item['image_three'] = $this->downloadRemoteImage($item['image_three']);
                if($item['image_three']) $image_array[] = $item['image_three'];
            }

            $item['create_at'] = date('Y-m-d H:i:s',$item['create_time']);
        }
        // 【必须！解除引用，防止内存泄漏】
        unset($item);


        $filename = '点位打卡' . date('Y-m-d');
        $head = ['ID', '用户信息', '小区', '站点', '内容', '点位照片', '投放指南', '垃圾桶照片', '创建时间',];//设置表头
        $keys = ['id', 'nickname', 'community_name', 'cate_name', 'content', 'image_one', 'image_two', 'image_three', 'create_at',];//设置表头
        $types = ['1', '1', '1', '1', '1', '2', '2', '2', '1'];//设置表头
        CommonModel::outData($filename, $list, $head, $keys, $types, $image_array);
        exit;
    }

    /**
     * 活跃督导员导出
     * 算法：综合评估用户提交频率、互动、内容完整度
     *
     * @param array $where 查询条件（继承列表筛选条件）
     */
    public function outSupervisor($where)
    {
        // 关闭所有层级输出缓冲
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        gc_enable();

        // 取当前筛选条件下的所有记录（含用户关联）
        $allRecords = $this->model
            ->alias('a')
            ->join('member b', 'a.user_id=b.id')
            ->leftjoin('forum_cate c', 'a.cate_id=c.id')
            ->field('a.id, a.user_id, a.create_time, a.content, a.like_total,
                     a.image_one, a.image_two, a.image_three,
                     b.nickname, b.mobile, b.community_id,
                     c.name cate_name')
            ->where($where)
            ->order('a.user_id asc, a.create_time asc')
            ->select()
            ->toArray();

        if (empty($allRecords)) {
            exit;
        }

        // 按 user_id 分组统计
        $userStats = [];
        $now = time();
        $days30 = $now - 30 * 86400;
        $days7  = $now - 7 * 86400;

        foreach ($allRecords as $record) {
            $uid = $record['user_id'];
            if (!isset($userStats[$uid])) {
                $userStats[$uid] = [
                    'user_id'        => $uid,
                    'nickname'       => $record['nickname'],
                    'mobile'         => $record['mobile'],
                    'community_id'   => $record['community_id'],
                    'community_name' => '',
                    'cate_name'      => '',
                    'total_count'    => 0,   // 总提交数
                    'count_30d'      => 0,   // 近30天提交数
                    'count_7d'       => 0,   // 近7天提交数
                    'total_likes'    => 0,   // 累计获赞
                    'active_days'    => [],  // 活跃天数（去重日期）
                    'has_photo_count'=> 0,   // 含完整照片记录数
                    'last_time'      => 0,   // 最近提交时间
                    'score'          => 0,   // 综合评分
                ];
            }

            $stat = &$userStats[$uid];
            $stat['total_count']++;
            $stat['total_likes'] += $record['like_total'];

            // 确保 cate_name 取最新的
            $stat['cate_name'] = $record['cate_name'] ?: $stat['cate_name'];

            // 近30天 / 近7天
            if ($record['create_time'] >= $days30) {
                $stat['count_30d']++;
            }
            if ($record['create_time'] >= $days7) {
                $stat['count_7d']++;
            }

            // 活跃天数
            $dateKey = date('Y-m-d', $record['create_time']);
            $stat['active_days'][$dateKey] = true;

            // 含完整照片记录（三张图都有）
            if (!empty($record['image_one']) && !empty($record['image_two']) && !empty($record['image_three'])) {
                $stat['has_photo_count']++;
            }

            // 最近提交时间
            if ($record['create_time'] > $stat['last_time']) {
                $stat['last_time'] = $record['create_time'];
            }
        }
        unset($stat);

        // 补充小区名称
        foreach ($userStats as &$stat) {
            $stat['community_name'] = CommunityModel::where('id', $stat['community_id'])->value('name');
            if (empty($stat['community_name'])) {
                // 回退到 subject_community_id 对应的小区（当前数据全为0）
                $subjectCommunityId = CommunityModel::where('id', $stat['community_id'])->value('id');
                $stat['community_name'] = CommunityModel::where('id', $subjectCommunityId)->value('name');
            }
        }
        unset($stat);

        // 评分 + 阈值过滤
        $result = [];
        foreach ($userStats as $uid => $stat) {
            $score = 0;
            $reasonParts = [];

            // --- 活跃度（40分） ---
            // 近30天提交次数
            if ($stat['count_30d'] >= 5) {
                $score += 25;
                $reasonParts[] = "近30天提交{$stat['count_30d']}次";
            } elseif ($stat['count_30d'] >= 3) {
                $score += 15;
                $reasonParts[] = "近30天提交{$stat['count_30d']}次";
            } elseif ($stat['count_30d'] >= 1) {
                $score += 5;
            }

            // 近7天内仍有提交（近期活跃）
            if ($stat['count_7d'] > 0) {
                $score += 15;
                $daysAgo = floor(($now - $stat['last_time']) / 86400);
                if ($daysAgo == 0) {
                    $reasonParts[] = "今日活跃";
                } else {
                    $reasonParts[] = "最近{$daysAgo}天前活跃";
                }
            }

            // --- 互动（30分） ---
            if ($stat['total_likes'] >= 20) {
                $score += 20;
                $reasonParts[] = "累计获赞{$stat['total_likes']}次";
            } elseif ($stat['total_likes'] >= 10) {
                $score += 15;
                $reasonParts[] = "累计获赞{$stat['total_likes']}次";
            } elseif ($stat['total_likes'] >= 5) {
                $score += 8;
                $reasonParts[] = "累计获赞{$stat['total_likes']}次";
            } elseif ($stat['total_likes'] >= 1) {
                $score += 3;
            }

            // --- 内容质量（30分） ---
            if ($stat['total_count'] > 0) {
                $photoRatio = round($stat['has_photo_count'] / $stat['total_count'] * 100);
                if ($photoRatio >= 80) {
                    $score += 20;
                    $reasonParts[] = "照片完整度{$photoRatio}%";
                } elseif ($photoRatio >= 50) {
                    $score += 12;
                    $reasonParts[] = "照片完整度{$photoRatio}%";
                } elseif ($photoRatio >= 20) {
                    $score += 5;
                }

                // 总提交次数体现持久度
                if ($stat['total_count'] >= 10) {
                    $score += 10;
                    $reasonParts[] = "总提交{$stat['total_count']}次";
                } elseif ($stat['total_count'] >= 5) {
                    $score += 5;
                    $reasonParts[] = "总提交{$stat['total_count']}次";
                }
            }

            // 活跃天数加成
            $activeDayCount = count($stat['active_days']);
            if ($activeDayCount >= 7) {
                $score += 10;
                $reasonParts[] = "活跃{$activeDayCount}天";
            } elseif ($activeDayCount >= 3) {
                $score += 5;
            }

            // 阈值：>=50分视为活跃督导员
            if ($score >= 50) {
                $stat['score'] = $score;
                $stat['reason'] = implode('，', $reasonParts);
                $result[] = $stat;
            }
        }

        // 按评分降序排列
        usort($result, function ($a, $b) {
            return $b['score'] - $a['score'];
        });

        // 构建导出数据
        foreach ($result as &$item) {
            $item['nickname_export'] = "({$item['user_id']}){$item['nickname']}";
            $item['last_time_export'] = $item['last_time'] ? date('Y-m-d H:i:s', $item['last_time']) : '';
        }
        unset($item);

        $filename = '活跃督导员_' . date('Y-m-d');
        $head = ['用户ID', '用户信息', '手机号', '小区', '分类', '总提交次数', '近30天提交', '累计获赞', '综合评分', '最近活跃时间', '原因'];
        $keys = ['user_id', 'nickname_export', 'mobile', 'community_name', 'cate_name', 'total_count', 'count_30d', 'total_likes', 'score', 'last_time_export', 'reason'];
        $types = ['1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1'];
        CommonModel::outData($filename, $result, $head, $keys, $types, []);
        exit;
    }

    /**
     * 带超时的远程图片下载，避免阻塞
     */
    protected function downloadRemoteImage($url)
    {
        // 过滤空链接
        if(trim($url) === '') return '';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT10;Win64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 图片请求失败直接返回空，不中断导出
        if(empty($imageData) || $httpCode < 200 || $httpCode >=400){
            return '';
        }
        $savePath = "upload/file/" . md5(time() . rand(1,99999)) . ".png";
        file_put_contents($savePath, $imageData);
        return $savePath;
    }

    /**
     * 垃圾打卡投放场景展示名
     */
    protected function formatClockSceneLabel($key)
    {
        $key = trim((string)$key);
        if ($key === '') {
            return '-';
        }
        $map = [
            'home'         => '家庭',
            'qinzi'        => '亲子',
            'street'       => '沿街',
            'zhenfenjiao'  => '真分角',
            'office'       => '办公',
        ];
        return $map[$key] ?? $key;
    }

}