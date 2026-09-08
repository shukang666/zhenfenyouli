<?php

namespace app\admin\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use app\admin\model\basic\SelectModel;
use app\admin\model\forum\ForumCateModel;
use app\admin\model\forum\ForumDeleteLogModel;
use app\admin\model\forum\ForumHandleModel;
use app\admin\model\forum\ForumModel;
use app\admin\model\member\CommunityCateModel;
use app\admin\model\member\CommunityModel;
use think\App;
use think\Db;
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
            // 与列表展示的 community_name 来源一致：subject_community_id 实际全为 0，
            // community_name 回退取自 b.community_id，故此处按 b.community_id 筛选
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
            ->field('a.*,b.nickname,b.mobile user_mobile,b.id uid,b.avatar,c.name cate_name,b.community_id')
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

            //小区名称
            $item['community_name'] = CommunityModel::where('id', $item['subject_community_id'])->value('name');
            if (empty($item['community_name'])) {
                $item['community_name'] = CommunityModel::where('id', $item['community_id'])->value('name');
            }

            if ($item['type'] == 1) {
                $item['cate_name'] = ForumCateModel::where('id', $item['cate_id'])->value('name');
            } elseif ($item['type'] == 2) {
                $item['cate_name'] = SelectModel::where('id', $item['cate_id'])->value('name');
            } elseif ($item['type'] == 3 || $item['type'] == 4) {//定位巡检
                $item['cate_name'] = CommunityCateModel::where('id', $item['cate_id'])->value('name', '');
            } else {
                $item['cate_name'] = '';
            }

            return CommonModel::getOne($item, ['thumb', 'file', 'pay_proof'], ['image']);

        });

        if ($export) {
            $noImage = $this->request->param('no_image', 0);
            if($type == 3){
                if ($noImage) {
                    $this->outData3NoImage($list);
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
            $this->success('删除成功');
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('删除失败：' . $e->getMessage());
        }
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
            $this->success('删除成功');
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('删除失败：' . $e->getMessage());
        }
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
     * type=3 无图导出：不导出点位照片、投放指南、垃圾桶照片
     * @param $list
     */
    public function outData3NoImage($list)
    {
        // 关闭所有层级输出缓冲
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        ini_set('default_socket_timeout', 8);
        gc_enable();

        foreach ($list as &$item) {
            $item['create_at'] = date('Y-m-d H:i:s', $item['create_time']);
        }
        unset($item);

        $filename = '点位打卡_无图_' . date('Y-m-d');
        $head = ['ID', '用户信息', '小区', '站点', '内容', '创建时间'];
        $keys = ['id', 'nickname', 'community_name', 'cate_name', 'content', 'create_at'];
        $types = ['1', '1', '1', '1', '1', '1'];
        CommonModel::outData($filename, $list, $head, $keys, $types, []);
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

}