<?php

namespace api\wxapp\service;

use think\facade\Db;

/**
 * 「认识七贤岭」：短视频 + 固定 3 道小互动（非六大模块学考）
 */
class QxlIntroExperienceService
{
    /** cmf_qxl_module_log.category */
    const MODULE_CATEGORY = '认识七贤岭';

    /** COS 对象键（staticAssetBase + 相对路径） */
    const VIDEO_COS_KEY = 'static/video/qxl-intro-v3.mp4';

    /** @return array<int, array<string, mixed>> */
    public static function questionDefinitions()
    {
        return [
            [
                'id'        => 100001,
                'title'     => '七贤岭平时出行有哪些组合方式？',
                'option_a'  => '只能步行，没有公共交通',
                'option_b'  => '可结合地铁 1 号线七贤岭站、公交等，出发前用地图软件确认',
                'option_c'  => '只能私家车，禁止公交',
                'option_d'  => '无需规划，随意出行即可',
                'correct'   => 'B',
            ],
            [
                'id'        => 100002,
                'title'     => '家里需要了解就业、社保或养老服务时，可以先向谁咨询？',
                'option_a'  => '仅问小区物业',
                'option_b'  => '七贤岭街道（含社会事务、就业社保等相关服务渠道）',
                'option_c'  => '任意邻居代答即可',
                'option_d'  => '只能自行上网搜索，不能咨询官方',
                'correct'   => 'B',
            ],
            [
                'id'        => 100003,
                'title'     => '想确认办事材料和时间，应该优先查看哪里？',
                'option_a'  => '未经核实的网络传言',
                'option_b'  => '街道等政府部门正式公布的办公时间、地址、电话及当年办事指南',
                'option_c'  => '随意猜测，不必核对',
                'option_d'  => '只看他人口述，不看正式通知',
                'correct'   => 'B',
            ],
        ];
    }

    public static function isIntroCategory($category)
    {
        return trim((string)$category) === self::MODULE_CATEGORY;
    }

    public static function userPassedIntro($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $row = Db::name('qxl_module_log')
            ->where('user_id', $userId)
            ->where('category', self::MODULE_CATEGORY)
            ->where('passed', 1)
            ->find();
        if (!empty($row)) {
            return true;
        }
        return self::legacyAllModulesPassed($userId);
    }

    /** 旧版六大板块学考已全部过关的用户，视为已完成「认识七贤岭」 */
    private static function legacyAllModulesPassed($userId)
    {
        $defs = QxlLearnModuleService::moduleDefinitions();
        if (empty($defs)) {
            return false;
        }
        $passedMap = QxlLearnModuleService::passedModuleMap($userId);
        foreach ($defs as $def) {
            if (!isset($passedMap[$def['category']])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 枢纽页进度（1 步：看完视频并完成小互动）
     *
     * @return array<string, mixed>
     */
    public static function buildSummary($userId)
    {
        $passed = self::userPassedIntro($userId);
        return [
            'modules'       => [],
            'passed_count'  => $passed ? 1 : 0,
            'module_count'  => 1,
            'article_count' => 1,
            'all_passed'    => $passed,
            'intro_mode'    => 1,
        ];
    }

    /**
     * @param callable $formatVideoUrl function(string $cosKey): string
     * @return array<string, mixed>
     */
    public static function buildIntroFeed($userId, callable $formatVideoUrl)
    {
        $passed = self::userPassedIntro($userId);
        $questions = [];
        if (!$passed) {
            foreach (self::questionDefinitions() as $q) {
                $questions[] = [
                    'id'       => $q['id'],
                    'title'    => $q['title'],
                    'option_a' => $q['option_a'],
                    'option_b' => $q['option_b'],
                    'option_c' => $q['option_c'],
                    'option_d' => $q['option_d'],
                ];
            }
        }

        return [
            'title'         => '认识七贤岭',
            'subtitle'      => '看视频，用几道小互动快速了解本地人文与地理',
            'video_url'     => $formatVideoUrl(self::VIDEO_COS_KEY),
            'passed'        => $passed ? 1 : 0,
            'all_passed'    => $passed,
            'passed_count'  => $passed ? 1 : 0,
            'module_count'  => 1,
            'questions'     => $questions,
            'question_count'=> count($questions),
            'intro_mode'    => 1,
        ];
    }

    /** @return array<int, string> qid => correct option */
    public static function answerBankForSubmitted(array $qids)
    {
        $defs = [];
        foreach (self::questionDefinitions() as $q) {
            $defs[intval($q['id'])] = strtoupper($q['correct']);
        }
        $bank = [];
        foreach ($qids as $id) {
            $id = intval($id);
            if (isset($defs[$id])) {
                $bank[$id] = $defs[$id];
            }
        }
        return $bank;
    }

    public static function expectedQuestionCount()
    {
        return count(self::questionDefinitions());
    }
}
