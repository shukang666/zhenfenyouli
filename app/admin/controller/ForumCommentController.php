<?php

namespace app\admin\controller;

use app\admin\model\forum\ForumCommentModel;
use app\admin\model\member\MemberModel;
use think\App;
use think\facade\Db;

class ForumCommentController extends ToolController
{
    public function __construct(App $app, ForumCommentModel $model)
    {
        parent::__construct($app);
        $this->model = $model;
    }


    public function setWhere()
    {
        $where = [];
        $name = $this->request->param('name', '', 'trim');
        $status = $this->request->param('status', '', 'trim');
        $start_time = $this->request->param('start_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');
        $id = $this->request->param('info_id', '', 'trim');
        $where[] = ['a.info_id', '=', $id];
        $this->assign('info_id', $id);
//
//        if ($name) {
//            $where[] = ['order_num|name|mobile', 'like', '%' . $name . '%'];
//        }
//        if (!empty($status)) {
//            if ($status == 10) {
//                $where[] = ['status', 'in', [10, 16]];
//            } elseif ($status == 2) {
//                $where[] = ['status', 'in', [2, 14]];
//            } else {
//                $where[] = ['status', '=', $status];
//            }
//        }
//        if ($start_time) $where[] = ['create_time', '>=', strtotime($start_time)];
//        if ($end_time) $where[] = ['create_time', '<=', strtotime($end_time) + 86399];
//
//        $this->assign('status', $status);
        return $where;
    }


    public function setIndex()
    {
        return $this->model
            ->alias('a')
            ->join('member b', 'b.id=a.user_id')
            ->field('a.*,b.nickname,b.mobile')
            ->where($this->setWhere())
            ->order('id desc')->paginate(15);
    }


    /**编辑页面
     * @return mixed
     */
    public function update()
    {
        $params = $this->request->param();
        $info_id = $params['info_id'];
        $this->assign('info_id', $info_id);
        $comment = $this->model
            ->where('info_id', $params['info_id'])
            ->select();
        $this->assign('comment', $comment);
        $this->assign('params', $params);
        $this->outParam();
        if (empty($params['id'])) {
            return $this->fetch();
        }
        $item = $this->setEdit($params);



        $item = $this->getParamData($item);
        $this->assign('item', $item);


        return $this->fetch();
    }


    /**编辑提交
     * @return void
     */
    public function updatePost()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if (!empty($params['c_id'])) {
                $info = $this->model
                    ->where('id', $params['c_id'])
                    ->where('info_id', $params['info_id'])
                    ->find();
                if (!$info) $this->error('回复的帖子不存在');
            }
           $user_info= MemberModel::where('id', $params['user_id'])->count();
            if (empty($user_info)) $this->error('用户不存在');
            $params = $this->setParamData($params);
            if (empty($params['id'])) {
                $result = $this->setAddPost($params);
            } else {
                $result = $this->setEditPost($params);
            }
            $this->getMsg(!empty($result));
        } else {
            $this->error('非法请求');
        }
    }

// 示例PHP代码
    // 用户搜索接口
    public function users()
    {
        $keyword = input('keyword', '');
        $where = [
            ['status', '=', 1] // 只查询状态正常的用户
        ];

        if (!empty($keyword)) {
            $where[] = ['nickname|mobile', 'like', "%{$keyword}%"];
        }

        try {
            $list = Db::name('member')
                ->where($where)
                ->field('id, nickname, mobile')
                ->order('id DESC')
                ->limit(10) // 限制返回10条结果
                ->select();

            return json([
                'code' => 1,
                'msg' => 'success',
                'data' => $list ?: []
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '查询失败: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
}