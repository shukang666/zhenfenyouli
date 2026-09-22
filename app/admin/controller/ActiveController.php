<?php

namespace app\admin\controller;

use api\wxapp\model\CommonModel;
use app\admin\model\active\ActiveCateModel;
use app\admin\model\active\ActiveCommunityCateModel;
use app\admin\model\active\ActiveClockModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\member\CommunityCateModel;
use think\App;
use think\db\exception\DbException;

class ActiveController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new ActiveModel();
    }

    /**
     * 列表默认展示的参加方式：1=小程序参加（活动列表） 2=扫码参加（签到列表）
     * @var int
     */
    protected $joinType = 1;

    public function setIndex()
    {

        return $this->model->where($this->setWhere())->order('list_order asc,id desc')->paginate(15);
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

    public function setWhere()
    {
        $name = $this->request->param('name', '', 'trim');
        $type = $this->request->param('type', '', 'trim');

        $where = [];
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        if ($type !== '') {
            $where[] = ['type', '=', $type];
        }
        // 参加方式：1=小程序参加 2=扫码参加，两类活动分开管理，列表互不混显
        $join_type = $this->request->param('join_type', '', 'trim');
        if ($join_type === '') {
            $join_type = $this->joinType;
        }
        $where[] = ['join_type', '=', $join_type];

        $community_cate_list = ActiveCommunityCateModel::where('cate_group', 'qxl')
            ->whereNotIn('id', [6])
            ->order('list_order asc,id desc')
            ->select();
        $xsdld_cate_list = ActiveCommunityCateModel::where('cate_group', 'xsdld')
            ->order('list_order asc,id desc')
            ->select();
        $this->assign('community_cate_list', $community_cate_list);
        $this->assign('xsdld_cate_list', $xsdld_cate_list);
        $this->assign('cate_list', (new  ActiveCateModel())->select());
        return $where;
    }

    public function get_params()
    {
        $community_cate_list = ActiveCommunityCateModel::where('cate_group', 'qxl')
            ->whereNotIn('id', [6])
            ->order('list_order asc,id desc')
            ->select();
        $xsdld_cate_list = ActiveCommunityCateModel::where('cate_group', 'xsdld')
            ->order('list_order asc,id desc')
            ->select();
        $this->assign('community_cate_list', $community_cate_list);
        $this->assign('xsdld_cate_list', $xsdld_cate_list);
        $this->assign('cate_list', (new  ActiveCateModel())->select());
    }

    public function index1()
    {
        return redirect((string)url('index', ['type' => 1]));
    }

    public function index2()
    {
        return redirect((string)url('index', ['type' => 2]));
    }

    public function index3()
    {
        return redirect((string)url('index', ['type' => 3]));
    }

    /**
     * 签到列表：扫码参加的活动（join_type=2），不限制活动类型
     * @return mixed
     * @throws DbException
     */
    public function sign_list()
    {
        // 固定只查扫码参加的活动
        $this->joinType = 2;

        $params = $this->request->param();
        $params['join_type'] = 2;

        $list = $this->setIndex()->each(function ($item) {
            $item = $this->getList($item);
            $item['sign_count'] = ActiveClockModel::where('active_id', $item['id'])->count();
            return $item;
        });
        $this->assign('params', $params);
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());

        return $this->fetch();
    }


    public function setData($item, $type = 1)
    {
        if ($type == 1) {
            if (!empty($item['date'])) $item['date'] = strtotime($item['date']);
        } else {
            if (!empty($item['date'])) $item['date'] = date('Y-m-d', $item['date']);
        }

        return $item;
    }

    //操作页面
    public function update()
    {
        $params = $this->request->param();

        $this->get_params();

        $this->assign('params', $params);
        $this->outParam();
        if (empty($params['id'])) {
            // 添加模式也渲染 update.html，赋空 item 避免模板读取 $item.xxx 报"未定义变量"
            $this->assign('item', []);
            return $this->fetch();
        }
        $item = $this->setEdit($params);
        if (!empty($item['content'])) {
            $item['content'] = htmlspecialchars_decode($item['content']);
        }
        if (!empty($item['role'])) {
            $item['role'] = explode(',', $item['role']);
        }
        $item = $this->getParamData($item);
        $this->assign('item', $item);
        $badge_list = '';
        $this->assign('badge_list', $badge_list);

        return $this->fetch();
    }

    //操作功能
    public function updatePost()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if(isset($params['role'])) $params['role'] = implode(',',$params['role']);
            if(!empty($params['clock_start_time'])) $params['clock_start_time'] = strtotime($params['clock_start_time']);
            if(!empty($params['clock_end_time'])) $params['clock_end_time'] = strtotime($params['clock_end_time']);
            $params['event_kind'] = (int)($params['event_kind'] ?? ActiveModel::EVENT_KIND_NORMAL);
            if (!in_array($params['event_kind'], [ActiveModel::EVENT_KIND_NORMAL, ActiveModel::EVENT_KIND_OFFLINE], true)) {
                $params['event_kind'] = ActiveModel::EVENT_KIND_NORMAL;
            }
            if (!empty($params['offline_sign_start'])) {
                $params['offline_sign_start'] = strtotime($params['offline_sign_start']);
            } else {
                $params['offline_sign_start'] = 0;
            }
            if (!empty($params['offline_sign_end'])) {
                $params['offline_sign_end'] = strtotime($params['offline_sign_end']);
            } else {
                $params['offline_sign_end'] = 0;
            }
            $params['offline_sign_password'] = trim((string)($params['offline_sign_password'] ?? ''));
            if ($params['event_kind'] === ActiveModel::EVENT_KIND_OFFLINE) {
                $params['is_clock'] = 0;
                $params['is_clock_challenge'] = 0;
                $params['clock_challenge_days'] = 0;
                $params['clock_challenge_point'] = 0;
                $params['join_type'] = 1;
                if (empty($params['offline_sign_start']) || empty($params['offline_sign_end'])) {
                    $this->error('线下活动请在「活动类型」下方绿色区域填写「允许签到开始/结束」时间');
                }
                if ($params['offline_sign_end'] <= $params['offline_sign_start']) {
                    $this->error('签到结束时间须晚于开始时间');
                }
                if (empty($params['lat']) || empty($params['lng'])) {
                    $this->error('线下活动请在地图上设置活动地点经纬度');
                }
            }
            $params['is_clock_challenge'] = empty($params['is_clock_challenge']) ? 0 : 1;
            $params['clock_challenge_days'] = max(0, (int)($params['clock_challenge_days'] ?? 0));
            $params['clock_challenge_point'] = max(0, (int)($params['clock_challenge_point'] ?? 0));
            if ($params['event_kind'] !== ActiveModel::EVENT_KIND_OFFLINE && $params['is_clock_challenge']) {
                $params['is_clock'] = 1;
                if ($params['clock_challenge_days'] < 1) {
                    $this->error('开启多日打卡挑战时请填写挑战天数（至少 1 天）');
                }
                if (empty($params['clock_start_time']) || empty($params['clock_end_time'])) {
                    $this->error('多日打卡挑战请填写下方「活动开始/结束日期」（打卡有效期）');
                }
            } elseif (!$params['is_clock_challenge']) {
                $params['clock_challenge_days'] = 0;
                $params['clock_challenge_point'] = 0;
            }
            $params['is_clock'] = empty($params['is_clock']) ? 0 : 1;
            $params['is_badge'] = empty($params['is_badge']) ? 0 : 1;
            $params['is_month_recommend'] = empty($params['is_month_recommend']) ? 0 : 1;
            $params['is_date_pending'] = empty($params['is_date_pending']) ? 0 : 1;
            if ($params['is_date_pending']) {
                if (empty($params['date'])) {
                    $params['date'] = 0;
                }
            } elseif (empty($params['date'])) {
                $this->error('请填写活动截止日期，或勾选「时间待定」');
            }

            $params = $this->setParamData($params);
            $share_url = 'pages/activity/lsxf/details-new';
            if (empty($params['id'])) {
                $result = ActiveModel::create($params);
                //生成小程序码
                $scene = (string)$result['id'];
                $qr_code = CommonModel::setShareImage($scene,'active_'.$result['id'],'dz1111',$share_url);
                $this->model->where('id',$result['id'])->update(['qr_code' => $qr_code]);
            } else {
                $item = ActiveModel::where('id',$params['id'])->find();
                if (empty($item['qr_code'])) {
                    //生成小程序码
                    $scene = (string)$params['id'];
                    $params['qr_code'] = CommonModel::setShareImage($scene,'active_'.$params['id'],'dz1111',$share_url);
                }
                $result = $this->setEditPost($params);
            }


            $this->getMsg(!empty($result));
        } else {
            $this->error('非法请求');
        }
    }
}