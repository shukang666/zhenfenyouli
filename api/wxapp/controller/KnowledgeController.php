<?php

namespace api\wxapp\controller;

use think\facade\Db;

/**
 * 分类知识 - 小程序接口
 * 列表 + 详情
 */
class KnowledgeController extends AuthController
{
    /**
     * 获取分类知识列表
     * GET /wxapp/knowledge/get_list
     * 可选参数：category(分类), page, keyword
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

        $total = Db::name('knowledge')->where($where)->count();
        $list = Db::name('knowledge')
            ->where($where)
            ->field('id, title, image, category, view_count, create_time')
            ->order('list_order asc, id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 图片补全
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
            // 所有分类，方便前端做 Tab 切换
            'categories' => $this->getAllCategories(),
        ]);
    }

    /**
     * 获取知识详情 + 浏览量自增
     * GET /wxapp/knowledge/get_detail
     * 参数：id
     */
    public function get_detail()
    {
        $id = $this->request->param('id', 0, 'intval');

        $knowledge = Db::name('knowledge')->where('id', $id)->where('status', 1)->find();
        if (empty($knowledge)) {
            $this->error('知识内容不存在');
        }

        // 浏览量自增
        Db::name('knowledge')->where('id', $id)->inc('view_count')->update();

        // 图片补全 + 内容处理
        $knowledge['image'] = $this->formatImage($knowledge['image']);
        $knowledge['create_time_friendly'] = date('Y-m-d', $knowledge['create_time']);

        $this->success('success', $knowledge);
    }

    /**
     * 获取所有分类
     */
    private function getAllCategories()
    {
        $categories = Db::name('knowledge')
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
