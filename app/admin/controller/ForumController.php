<?php

namespace app\admin\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use app\admin\model\basic\SelectModel;
use app\admin\model\forum\ForumCateModel;
use app\admin\model\forum\ForumHandleModel;
use app\admin\model\forum\ForumModel;
use app\admin\model\member\CommunityCateModel;
use app\admin\model\member\CommunityModel;
use think\App;
use think\db\exception\DbException;

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
            $where[] = ['cate_id', '=', trim($param['cate_id'])];
        }

        if (!empty($param['id'])) {
            $where[] = ['b.id', '=', trim($param['id'])];
        }
        if (!empty($param['name'])) {
            $where[] = ['b.nickname|b.mobile', 'like', '%' . trim($param['name']) . '%'];
        }
        if (!empty($param['type'])) {
            $where[] = ['a.type', '=', trim($param['type'])];
        }

        if (!empty($param['community_id'])) {
            $where[] = ['b.community_id', '=', trim($param['community_id'])];
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
            ->field('a.*,b.nickname,b.mobile user_mobile,b.id uid,c.name cate_name,b.community_id')
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

            $item['community_name'] = CommunityModel::where('id', $item['community_id'])->value('name');
            if ($item['type'] == 1) {
                $item['cate_name'] = ForumCateModel::where('id', $item['cate_id'])->value('name');
            } elseif ($item['type'] == 2) {
                $item['cate_name'] = SelectModel::where('id', $item['cate_id'])->value('name');
            } elseif ($item['type'] == 3 || $item['type'] == 4) {//定位巡检
                $item['cate_name'] = CommunityCateModel::where('id', $item['cate_id'])->value('name', '');
            }

            return CommonModel::getOne($item, ['thumb', 'file', 'pay_proof'], ['image']);

        });

        if ($export) {
            $this->outData($list);
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
        $this->assign('item', $item);
        return $this->fetch();
    }

    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $item = $this->model->where('id', $id)->where('point', '>', 0)->find();
        if (!empty($item)) {
            AccountLogModel::add_account_log($item['user_id'], 2, 200, -$item['point'], ['remark' => '垃圾打卡扣除']);

        }

        $result = $this->model->where('id', $id)->update(['delete_time' => time()]);
        $this->getMsg($result, '删除');
    }

    public function delete_all()
    {
        $ids = $this->request->param('ids');
        $list = $this->model->where('id', 'in', $ids)->where('point', '>', 0)->select();

        foreach ($list as $item) {
            AccountLogModel::add_account_log($item['user_id'], 2, 200, -$item['point'], ['remark' => '垃圾打卡扣除']);
        }
        $this->model->whereIn('id', $ids)->update(['delete_time' => time()]);
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


}