<?php

namespace api\wxapp\service;

use think\facade\Db;

/**
 * 七贤岭学考 — 分模块（category）组织，模块内篇章互不锁定
 */
class QxlLearnModuleService
{
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

    /**
     * @param int $userId
     * @return array<int, array<string, mixed>>
     */
    public static function buildModulesSummary($userId)
    {
        $defs = self::moduleDefinitions();
        $passedMap = self::passedKnowledgeMap($userId);

        $countsByCat = Db::name('qxl_knowledge')
            ->where('status', 1)
            ->field('category, COUNT(*) AS cnt')
            ->group('category')
            ->select()
            ->toArray();
        $articleCount = [];
        foreach ($countsByCat as $row) {
            $cat = trim((string)($row['category'] ?? ''));
            if ($cat !== '') {
                $articleCount[$cat] = intval($row['cnt']);
            }
        }

        $passedByCat = [];
        if (!empty($passedMap)) {
            $ids = array_keys($passedMap);
            $rows = Db::name('qxl_knowledge')
                ->where('status', 1)
                ->where('id', 'in', $ids)
                ->column('category', 'id');
            foreach ($rows as $kid => $cat) {
                $cat = trim((string)$cat);
                if ($cat === '') {
                    continue;
                }
                if (!isset($passedByCat[$cat])) {
                    $passedByCat[$cat] = 0;
                }
                $passedByCat[$cat]++;
            }
        }

        $modules = [];
        foreach ($defs as $def) {
            $cat = $def['category'];
            $total = $articleCount[$cat] ?? 0;
            $passed = $passedByCat[$cat] ?? 0;
            $modules[] = [
                'category'      => $cat,
                'subtitle'      => $def['subtitle'],
                'article_count' => $total,
                'passed_count'  => $passed,
                'all_passed'    => $total > 0 && $passed >= $total,
            ];
        }

        return $modules;
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
            return ['category' => '', 'articles' => [], 'passed_count' => 0, 'article_count' => 0];
        }

        $passedMap = self::passedKnowledgeMap($userId);
        $articles = Db::name('qxl_knowledge')
            ->where('status', 1)
            ->where('category', $category)
            ->order('list_order asc, id asc')
            ->select()
            ->toArray();

        $questionCounts = self::questionCountsByKnowledge();
        $list = [];
        $passedCount = 0;
        foreach ($articles as $item) {
            $kid = intval($item['id']);
            $passed = isset($passedMap[$kid]);
            if ($passed) {
                $passedCount++;
            }
            $list[] = [
                'id'             => $kid,
                'title'          => $item['title'],
                'image'          => $item['image'],
                'passed'         => $passed ? 1 : 0,
                'question_count' => $questionCounts[$kid] ?? 0,
            ];
        }

        return [
            'category'      => $category,
            'articles'      => $list,
            'passed_count'  => $passedCount,
            'article_count' => count($list),
        ];
    }

    /** @return array<int, true> knowledge_id => true */
    public static function passedKnowledgeMap($userId)
    {
        if (empty($userId)) {
            return [];
        }
        $logs = Db::name('qxl_chapter_log')
            ->where('user_id', $userId)
            ->where('passed', 1)
            ->column('id', 'knowledge_id');
        return $logs ?: [];
    }

    /** @return array<int, int> */
    public static function questionCountsByKnowledge()
    {
        $rows = Db::name('qxl_quiz_question')
            ->where('status', 1)
            ->where('knowledge_id', '>', 0)
            ->field('knowledge_id, COUNT(*) AS cnt')
            ->group('knowledge_id')
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $row) {
            $map[intval($row['knowledge_id'])] = intval($row['cnt']);
        }
        return $map;
    }
}
