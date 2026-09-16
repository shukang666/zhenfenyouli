<?php

namespace api\wxapp\controller;

use think\facade\Db;

/**
 * 七贤岭学习 - 小程序接口
 * 列表 + 详情（独立于垃圾分类的 KnowledgeController）
 */
class QxlKnowledgeController extends AuthController
{
    /**
     * 获取七贤岭学习列表
     * GET /wxapp/qxl_knowledge/get_list
     */
    public function get_list()
    {
        $category = $this->request->param('category', '');
        $keyword  = $this->request->param('keyword', '');
        $page     = $this->request->param('page', 1, 'intval');
        $limit    = 15;

        $where = [['status', '=', 1]];

        if (!empty($category)) {
            $where[] = ['category', '=', trim($category)];
        }
        if (!empty($keyword)) {
            $where[] = ['title', 'like', '%' . trim($keyword) . '%'];
        }

        $total = Db::name('qxl_knowledge')->where($where)->count();
        $list = Db::name('qxl_knowledge')
            ->where($where)
            ->field('id, title, image, category, view_count, create_time')
            ->order('list_order asc, id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['image'] = $this->formatImage($item['image']);
            $item['create_time_friendly'] = date('Y-m-d', $item['create_time']);
        }
        unset($item);

        $this->success('success', [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'has_more' => $page * $limit < $total,
            'categories' => $this->getAllCategories(),
        ]);
    }

    /**
     * 获取七贤岭学习详情
     * GET /wxapp/qxl_knowledge/get_detail
     */
    public function get_detail()
    {
        $id = $this->request->param('id', 0, 'intval');

        $knowledge = Db::name('qxl_knowledge')->where('id', $id)->where('status', 1)->find();
        if (empty($knowledge)) {
            $this->error('内容不存在');
        }

        Db::name('qxl_knowledge')->where('id', $id)->inc('view_count')->update();

        $knowledge['image'] = $this->formatImage($knowledge['image']);
        $knowledge['create_time_friendly'] = date('Y-m-d', $knowledge['create_time']);

        $this->success('success', $knowledge);
    }

    /**
     * 获取所有分类
     */
    private function getAllCategories()
    {
        $categories = Db::name('qxl_knowledge')
            ->where('status', 1)
            ->where('category', '<>', '')
            ->column('category');
        return array_values(array_unique($categories));
    }

    /**
     * 图片URL补全
     */
    private function formatImage($image)
    {
        if (empty($image)) return '';
        if (preg_match('/^(http|https):\/\//', $image)) return $image;
        return cmf_get_asset_url($image);
    }
}
