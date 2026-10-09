<?php

namespace api\wxapp\controller;

use api\wxapp\service\QxlLearnModuleService;
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
     * 学考融合路径：按篇章顺序返回学习内容 + 本篇题目 + 通关/解锁状态
     * GET /wxapp/qxl_knowledge/get_learn_path
     */
    /**
     * 学考模块总览（枢纽页 Bento 入口）
     * GET /wxapp/qxl_knowledge/get_learn_modules
     */
    public function get_learn_modules()
    {
        $modules = QxlLearnModuleService::buildModulesSummary(intval($this->user_id));
        $passedTotal = 0;
        $articleTotal = 0;
        foreach ($modules as $m) {
            $passedTotal += intval($m['passed_count']);
            $articleTotal += intval($m['article_count']);
        }
        $this->success('success', [
            'modules'       => $modules,
            'passed_count'  => $passedTotal,
            'article_count' => $articleTotal,
            'all_passed'    => $articleTotal > 0 && $passedTotal >= $articleTotal,
        ]);
    }

    /**
     * 学考长页数据：Banner 下交替的学习/考试单元
     * GET /wxapp/qxl_knowledge/get_learn_feed
     */
    public function get_learn_feed()
    {
        $data = QxlLearnModuleService::buildLearnFeed(
            intval($this->user_id),
            function ($raw) {
                return $this->formatContent($raw);
            },
            function ($raw) {
                return $this->formatImage($raw);
            }
        );
        $this->success('success', $data);
    }

    /**
     * 某模块下的篇章摘要（不含正文）
     * GET /wxapp/qxl_knowledge/get_module_articles?category=
     */
    public function get_module_articles()
    {
        $category = trim((string)$this->request->param('category', ''));
        if ($category === '') {
            $this->error('请指定模块');
        }
        $data = QxlLearnModuleService::buildModuleArticles(intval($this->user_id), $category);
        foreach ($data['articles'] as &$item) {
            $item['image'] = $this->formatImage($item['image'] ?? '');
        }
        unset($item);
        $this->success('success', $data);
    }

    /**
     * 单篇学考：正文 + 未过关时的题目
     * GET /wxapp/qxl_knowledge/get_chapter_learn?id=
     */
    public function get_chapter_learn()
    {
        $id = intval($this->request->param('id', 0));
        if ($id <= 0) {
            $this->error('参数错误');
        }
        $item = Db::name('qxl_knowledge')->where('id', $id)->where('status', 1)->find();
        if (empty($item)) {
            $this->error('内容不存在');
        }

        $passed = false;
        if (!empty($this->user_id)) {
            $log = Db::name('qxl_chapter_log')
                ->where('user_id', $this->user_id)
                ->where('knowledge_id', $id)
                ->where('passed', 1)
                ->find();
            $passed = !empty($log);
        }

        $questions = [];
        if (!$passed) {
            $questions = Db::name('qxl_quiz_question')
                ->where('knowledge_id', $id)
                ->where('status', 1)
                ->field('id, title, option_a, option_b, option_c, option_d')
                ->order('list_order asc, id asc')
                ->select()
                ->toArray();
        }

        $this->success('success', [
            'id'             => $id,
            'title'          => $item['title'],
            'category'       => $item['category'],
            'image'          => $this->formatImage($item['image']),
            'content'        => $this->formatContent($item['content'] ?? ''),
            'passed'         => $passed ? 1 : 0,
            'question_count' => count($questions),
            'questions'      => $questions,
        ]);
    }

    public function get_learn_path()
    {
        $category = trim((string)$this->request->param('category', ''));
        $query = Db::name('qxl_knowledge')->where('status', 1);
        if ($category !== '') {
            $query->where('category', $category);
        }
        $articles = $query->order('list_order asc, id asc')->select()->toArray();

        $passedMap = QxlLearnModuleService::passedKnowledgeMap(intval($this->user_id));
        $questionsByKid = [];
        $questionRows = Db::name('qxl_quiz_question')
            ->where('status', 1)
            ->where('knowledge_id', '>', 0)
            ->field('id, knowledge_id, title, option_a, option_b, option_c, option_d')
            ->order('list_order asc, id asc')
            ->select()
            ->toArray();
        foreach ($questionRows as $q) {
            $kid = intval($q['knowledge_id']);
            unset($q['knowledge_id']);
            $questionsByKid[$kid][] = $q;
        }

        $chapters = [];
        $passedCount = 0;
        $currentId = 0;

        foreach ($articles as $item) {
            $kid = intval($item['id']);
            $passed = isset($passedMap[$kid]);
            if ($passed) {
                $passedCount++;
            }
            if (!$passed && $currentId === 0) {
                $currentId = $kid;
            }

            $chapter = [
                'id'             => $kid,
                'title'          => $item['title'],
                'image'          => $this->formatImage($item['image']),
                'category'       => $item['category'],
                'unlocked'       => 1,
                'passed'         => $passed ? 1 : 0,
                'question_count' => isset($questionsByKid[$kid]) ? count($questionsByKid[$kid]) : 0,
                'content'        => '',
                'questions'      => [],
            ];

            $chapters[] = $chapter;
        }

        $total = count($chapters);
        $this->success('success', [
            'chapters'     => $chapters,
            'passed_count' => $passedCount,
            'total_count'  => $total,
            'all_passed'   => $total > 0 && $passedCount >= $total,
            'current_id'   => $currentId,
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
        $knowledge['content'] = $this->formatContent($knowledge['content'] ?? '');
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

    /**
     * 学考正文：库内可能是 htmlspecialchars 后的 HTML，需解码再给小程序 rich-text
     */
    private function formatContent($content)
    {
        if ($content === '' || $content === null) {
            return '';
        }
        $html = htmlspecialchars_decode((string)$content, ENT_QUOTES);
        if (strpos($html, '&lt;') !== false && strpos($html, '<') === false) {
            $html = htmlspecialchars_decode($html, ENT_QUOTES);
        }
        if (function_exists('cmf_replace_content_file_url')) {
            $html = cmf_replace_content_file_url($html);
        }
        return $html;
    }
}
