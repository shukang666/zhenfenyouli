<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 七贤岭学习内容管理
 * 管理七贤岭学习文章（标题/封面/富文本/分类）
 * 独立于垃圾分类的 KnowledgeController
 */
class QxlKnowledgeController extends AdminBaseController
{
    /**
     * 知识列表
     */
    public function index()
    {
        $params = $this->request->param();
        $where = [['status', '<>', '-1']];

        // 状态筛选
        $status = $this->request->param('status', '');
        if ($status !== '') {
            $where[] = ['status', '=', intval($status)];
        }

        // 分类筛选
        $category = $this->request->param('category', '');
        if (!empty($category)) {
            $where[] = ['category', '=', trim($category)];
        }

        // 关键词搜索
        $keyword = $this->request->param('keyword', '');
        if (!empty($keyword)) {
            $where[] = ['title', 'like', '%' . trim($keyword) . '%'];
        }

        $list = Db::name('qxl_knowledge')
            ->where($where)
            ->order('list_order asc, id desc')
            ->paginate(15);

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('status', $status);
        $this->assign('category', $category);
        $this->assign('keyword', $keyword);

        // 所有分类供筛选下拉
        $categories = Db::name('qxl_knowledge')
            ->where('category', '<>', '')
            ->where('status', '<>', '-1')
            ->column('category');
        $categories = array_unique($categories);
        $this->assign('categories', $categories);

        return $this->fetch();
    }

    /**
     * 添加/编辑表单（复用模板）
     */
    public function add()
    {
        $id = $this->request->param('id', 0, 'intval');

        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['title'])) {
                $this->error('标题不能为空');
            }
            if (empty($data['content'])) {
                $this->error('内容不能为空');
            }

            $data['update_time'] = time();
            $data['category'] = trim($data['category'] ?? '');
            $data['image'] = trim($data['image'] ?? '');
            $data['status'] = isset($data['status']) ? 1 : 0;

            if ($id) {
                Db::name('qxl_knowledge')->where('id', $id)->update($data);
                $this->success('更新成功');
            } else {
                $data['create_time'] = time();
                $data['view_count'] = 0;
                Db::name('qxl_knowledge')->insertGetId($data);
                $this->success('添加成功');
            }
        }

        $data = [
            'id' => 0,
            'title' => '',
            'image' => '',
            'content' => '',
            'category' => '',
            'list_order' => 0,
            'status' => 1,
        ];

        if ($id) {
            $knowledge = Db::name('qxl_knowledge')->where('id', $id)->find();
            if (empty($knowledge)) {
                $this->error('内容不存在');
            }
            $data = $knowledge;
        }

        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 删除（软删除：status=-1）
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        Db::name('qxl_knowledge')->where('id', $id)->update(['status' => '-1', 'update_time' => time()]);
        $this->success('删除成功');
    }

    /**
     * 批量删除
     */
    public function delete_all()
    {
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('请选择要删除的项');
        }
        Db::name('qxl_knowledge')->whereIn('id', $ids)->update(['status' => '-1', 'update_time' => time()]);
        $this->success('删除成功');
    }

    /**
     * 排序
     */
    public function listOrder()
    {
        $model = Db::name('qxl_knowledge');
        parent::listOrders($model);
        $this->success('排序更新成功');
    }

    /**
     * 设置状态
     */
    public function setStatus()
    {
        $params = $this->request->param();
        Db::name('qxl_knowledge')
            ->where('id', $params['id'])
            ->update(['status' => $params['status'], 'update_time' => time()]);
        $this->success('设置成功');
    }
}
