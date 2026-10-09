<?php

namespace api\wxapp\service;

use think\facade\Db;

/**
 * 七贤岭学考 — 按板块（category）：后台配置学习正文，题库按模块随机抽题
 */
class QxlLearnModuleService
{
    const MODULE_QUIZ_DRAW = 3;

    /** @return array<int, array{category:string,subtitle:string}> */
    public static function moduleDefinitions()
    {
        return [
            ['category' => '人文', 'subtitle' => '历史脉络与社区故事'],
            ['category' => '人口', 'subtitle' => '人口结构与概况'],
            ['category' => '地理', 'subtitle' => '地形区位与自然环境'],
            ['category' => '景点', 'subtitle' => '地标景观与打卡点'],
            ['category' => '七贤由来', 'subtitle' => '地名渊源与传说'],
            ['category' => '小区信息', 'subtitle' => '辖区小区与配套'],
        ];
    }

    public static function isValidModuleCategory($category)
    {
        $category = trim((string)$category);
        foreach (self::moduleDefinitions() as $def) {
            if ($def['category'] === $category) {
                return true;
            }
        }
        return false;
    }

    /** @return array<string, true> category => true */
    public static function passedModuleMap($userId)
    {
        if (empty($userId)) {
            return [];
        }
        $rows = Db::name('qxl_module_log')
            ->where('user_id', $userId)
            ->where('passed', 1)
            ->column('category');
        $map = [];
        foreach ($rows as $cat) {
            $cat = trim((string)$cat);
            if ($cat !== '') {
                $map[$cat] = true;
            }
        }
        return $map;
    }

    /** @return array<int, int> category question pool size */
    public static function questionPoolCountByCategory()
    {
        $rows = Db::name('qxl_quiz_question')->alias('q')
            ->join('qxl_knowledge k', 'k.id = q.knowledge_id')
            ->where('q.status', 1)
            ->where('k.status', 1)
            ->where('k.category', '<>', '')
            ->field('k.category, COUNT(*) AS cnt')
            ->group('k.category')
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $row) {
            $map[trim((string)$row['category'])] = intval($row['cnt']);
        }
        return $map;
    }

    /** @return array<int> question ids */
    public static function questionIdsForCategory($category)
    {
        $category = trim((string)$category);
        if ($category === '') {
            return [];
        }
        return Db::name('qxl_quiz_question')->alias('q')
            ->join('qxl_knowledge k', 'k.id = q.knowledge_id')
            ->where('q.status', 1)
            ->where('k.status', 1)
            ->where('k.category', $category)
            ->order('q.list_order asc, q.id asc')
            ->column('q.id');
    }

    /**
     * 模块随机抽题（不含正确答案）
     *
     * @return array<int, array<string, mixed>>|null null=题库不足
     */
    public static function drawModuleQuestions($category, $limit = self::MODULE_QUIZ_DRAW)
    {
        $ids = self::questionIdsForCategory($category);
        $limit = max(1, intval($limit));
        if (empty($ids)) {
            return null;
        }
        shuffle($ids);
        // 题库少于默认抽题数时，全部题目参与测验
        $picked = count($ids) <= $limit ? $ids : array_slice($ids, 0, $limit);
        $rows = Db::name('qxl_quiz_question')
            ->where('id', 'in', $picked)
            ->where('status', 1)
            ->field('id, title, option_a, option_b, option_c, option_d')
            ->select()
            ->toArray();
        $byId = [];
        foreach ($rows as $row) {
            $byId[intval($row['id'])] = $row;
        }
        $ordered = [];
        foreach ($picked as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }
        return $ordered;
    }

    /**
     * @param int $userId
     * @return array<int, array<string, mixed>>
     */
    public static function buildModulesSummary($userId)
    {
        $passedMap = self::passedModuleMap($userId);
        $poolMap = self::questionPoolCountByCategory();
        $modules = [];
        $passedCount = 0;
        foreach (self::moduleDefinitions() as $def) {
            $cat = $def['category'];
            $passed = isset($passedMap[$cat]);
            if ($passed) {
                $passedCount++;
            }
            $articleCount = Db::name('qxl_knowledge')
                ->where('status', 1)
                ->where('category', $cat)
                ->count();
            $modules[] = [
                'category'      => $cat,
                'subtitle'      => $def['subtitle'],
                'article_count' => intval($articleCount),
                'pool_count'    => $poolMap[$cat] ?? 0,
                'passed'        => $passed ? 1 : 0,
                'all_passed'    => $passed ? 1 : 0,
            ];
        }
        return [
            'modules'       => $modules,
            'passed_count'  => $passedCount,
            'module_count'  => count($modules),
            'article_count' => count($modules),
            'all_passed'    => $passedCount >= count($modules) && count($modules) > 0,
        ];
    }

    /**
     * @param int $userId
     * @param string $category
     * @return array<string, mixed>
     */
    public static function buildModuleArticles($userId, $category)
    {
        $category = trim($category);
        if ($category === '') {
            return ['category' => '', 'articles' => [], 'passed' => 0, 'pool_count' => 0];
        }
        $passedMap = self::passedModuleMap($userId);
        $articles = Db::name('qxl_knowledge')
            ->where('status', 1)
            ->where('category', $category)
            ->order('list_order asc, id asc')
            ->select()
            ->toArray();
        $list = [];
        foreach ($articles as $item) {
            $list[] = [
                'id'    => intval($item['id']),
                'title' => $item['title'],
                'image' => $item['image'],
            ];
        }
        $poolMap = self::questionPoolCountByCategory();
        return [
            'category'   => $category,
            'articles'   => $list,
            'passed'     => isset($passedMap[$category]) ? 1 : 0,
            'pool_count' => $poolMap[$category] ?? 0,
        ];
    }

    /**
     * 学考长页：每板块一组「学习（多篇文章）+ 考试（抽题）」
     *
     * @param callable $formatContent
     * @param callable $formatImage
     * @return array<string, mixed>
     */
    public static function buildLearnFeed($userId, callable $formatContent, callable $formatImage)
    {
        $passedMap = self::passedModuleMap($userId);
        $poolMap = self::questionPoolCountByCategory();
        $units = [];
        $passedCount = 0;

        foreach (self::moduleDefinitions() as $def) {
            $cat = $def['category'];
            $passed = isset($passedMap[$cat]);
            if ($passed) {
                $passedCount++;
            }

            $articles = Db::name('qxl_knowledge')
                ->where('status', 1)
                ->where('category', $cat)
                ->order('list_order asc, id asc')
                ->select()
                ->toArray();

            $learnArticles = [];
            foreach ($articles as $item) {
                $learnArticles[] = [
                    'id'      => intval($item['id']),
                    'title'   => $item['title'],
                    'image'   => $formatImage($item['image'] ?? ''),
                    'content' => $formatContent($item['content'] ?? ''),
                ];
            }

            $poolN = intval($poolMap[$cat] ?? 0);
            $units[] = [
                'category'    => $cat,
                'subtitle'    => $def['subtitle'],
                'passed'      => $passed ? 1 : 0,
                'pool_count'  => $poolN,
                'draw_count'  => $poolN > 0 ? min(self::MODULE_QUIZ_DRAW, $poolN) : 0,
                'articles'    => $learnArticles,
            ];
        }

        $moduleCount = count($units);
        $moduleLabels = array_column(self::moduleDefinitions(), 'category');

        return [
            'units'         => $units,
            'modules'       => $units,
            'passed_count'  => $passedCount,
            'module_count'  => $moduleCount,
            'article_count' => $moduleCount,
            'all_passed'    => $moduleCount > 0 && $passedCount >= $moduleCount,
            'module_labels' => $moduleLabels,
        ];
    }

    /** 校验题目是否属于某模块题库 */
    public static function filterQuestionBankForCategory($category, array $qids)
    {
        $allowed = array_flip(self::questionIdsForCategory($category));
        $bank = [];
        if (empty($qids)) {
            return $bank;
        }
        $rows = Db::name('qxl_quiz_question')
            ->where('id', 'in', $qids)
            ->where('status', 1)
            ->column('correct_option', 'id');
        foreach ($rows as $id => $opt) {
            $id = intval($id);
            if (isset($allowed[$id])) {
                $bank[$id] = strtoupper(trim((string)$opt));
            }
        }
        return $bank;
    }
}
