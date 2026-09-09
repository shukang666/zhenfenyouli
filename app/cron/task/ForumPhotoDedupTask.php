<?php

namespace app\cron\task;

use api\wxapp\model\AccountLogModel;
use app\admin\model\forum\ForumDeleteLogModel;
use app\admin\model\forum\ForumModel;
use app\cron\CronTaskInterface;
use app\cron\lib\ImagePerceptualHash;
use think\facade\Db;

/**
 * 定时任务：垃圾分类打卡(type=2) 上传图片自动去重
 *
 * 背景：用户每日垃圾分类打卡需上传投放照片并获积分，存在把同一张(或感知上完全相同的)照片
 * 反复上传打卡骗取积分的可能。
 *
 * 原理（与后台描述保持一致）：
 *   1) 每次调度扫描“自上次处理进度以来”新产生的 type=2 打卡记录（游标 last_forum_id 落 state JSON）；
 *   2) 对每条新记录里的每张图做感知哈希(aHash, 64bit 指纹)，与该用户历史打卡图片指纹逐一比对汉明距离；
 *   3) 相似度 = (1 - 汉明距离/64)*100，达到配置的 similarity_threshold(默认100% = 汉明距离0 = 完全相同) 即判定重复；
 *      若图片 URL 与其本人历史完全一致（同一文件再次上传），按 100% 命中；
 *   4) 判重命中即自动删除该条打卡：软删除 + 写 cmf_forum_delete_log + 追回已发放积分
 *      （删除动作与后台“删除打卡”逻辑一致，operator_id=0 标识系统定时任务）。
 *
 * 参数(存 param JSON, 键含义见 meta()):
 *   similarity_threshold 相似度阈值%，100 表示仅完全相同的图才删除
 *   history_limit        单用户比对的历史打卡条数上限(最近N条)
 *   scan_step            单次执行最多扫描的新打卡条数
 *   delete_same_url      同一图片 URL 直接判重(1/0)
 *
 * 状态(state JSON):
 *   last_forum_id 已处理到的最新 cmf_forum.id。首次运行时自动锚定到当前最大 id，
 *   即只监管“启用之后”的新打卡；如需回扫存量可手工把该值改小（0=全量，耗时长请谨慎）。
 */
class ForumPhotoDedupTask implements CronTaskInterface
{
    const FORUM_TYPE = 2; // cmf_forum.type = 2 垃圾分类打卡

    public static function meta(): array
    {
        return [
            'task_key'       => 'forum_photo_dedup',
            'title'          => '垃圾分类打卡重复图片自动清理',
            'description'    => '原理：扫描每次定时到点时新产生的垃圾分类打卡记录，把本次上传的图片与同一用户历史打卡图片做感知哈希(aHash)比对，按汉明距离换算相似度百分比（100%=完全相同）。达到“相似度阈值”即判定为重复图片，自动删除该条打卡记录、追回积分并写入删除日志。首次启用自动从最新打卡开始监管（不回溯存量），若需回扫存量可在“运行状态”中将 last_forum_id 改小。',
            'status_default' => 1,
            'cycle_type'     => 3,          // 每天定点执行
            'cycle_interval' => 1,
            'run_time'       => '02:00',
            'param_defaults' => [
                'similarity_threshold' => 100,
                'history_limit'        => 200,
                'scan_step'            => 50,
                'delete_same_url'      => 1,
            ],
            'param_schema'   => [
                [
                    'key'     => 'similarity_threshold',
                    'label'   => '相似度阈值(%)',
                    'type'    => 'number',
                    'default' => 100,
                    'min'     => 0,
                    'max'     => 100,
                    'desc'    => '两图感知哈希的相似度 = (1-汉明距离/64)*100。达到该值即判定重复并自动删除打卡。默认100%表示只有完全相同的图才删除；调低(如95)会删除肉眼近似但可能仍是不同实拍照片的图，易误删，请谨慎。',
                ],
                [
                    'key'     => 'history_limit',
                    'label'   => '单用户比对历史条数',
                    'type'    => 'number',
                    'default' => 200,
                    'min'     => 10,
                    'max'     => 500,
                    'desc'    => '新打卡图片与该用户“最近 N 条历史打卡”的图片做逐张感知哈希比对。值越大查得越全、执行越慢。',
                ],
                [
                    'key'     => 'scan_step',
                    'label'   => '单次执行扫描条数',
                    'type'    => 'number',
                    'default' => 50,
                    'min'     => 1,
                    'max'     => 500,
                    'desc'    => '每次调度最多处理多少条新打卡，防止单次执行过久（若有积压会由后续调度继续消化）。',
                ],
                [
                    'key'     => 'delete_same_url',
                    'label'   => '图片地址相同直接判重',
                    'type'    => 'switch',
                    'default' => 1,
                    'desc'    => '该图片 URL 与本人历史任一打卡图片 URL 完全相同（即同一文件再次上传，OSS 对象相同）时，直接按 100% 重复处理，无需再下载比对。',
                ],
            ],
        ];
    }

    /** 解码 cmf_forum.image 多图 JSON 为 URL 数组 */
    private static function decodeImages($image): array
    {
        if (is_array($image)) {
            return array_values($image);
        }
        if (is_string($image) && $image !== '') {
            $arr = json_decode($image, true);
            if (is_array($arr)) {
                return array_values($arr);
            }
        }
        return [];
    }

    /**
     * 首次运行自动锚定：从当前最大 id 开始监管新打卡，避免对存量历史全量回扫。
     */
    public function run(array $task, array $param, array &$state): array
    {
        $forumModel = new ForumModel();

        // state 初始化 / 首次锚定
        if (!isset($state['last_forum_id']) || $state['last_forum_id'] === '') {
            $state['last_forum_id'] = (int)$forumModel->where('type', self::FORUM_TYPE)->max('id');
            $anchored               = true;
        } else {
            $anchored = false;
        }
        $lastId = (int)$state['last_forum_id'];

        $step         = (int)($param['scan_step'] ?? 50);
        $step         = min(500, max(1, $step));
        $historyLimit = (int)($param['history_limit'] ?? 200);
        $historyLimit = min(500, max(10, $historyLimit));
        $threshold    = (int)($param['similarity_threshold'] ?? 100);
        $threshold    = min(100, max(0, $threshold));
        $deleteSameUrl = !empty($param['delete_same_url']);

        // 扫描本批次新打卡（已软删除的不处理）
        $list = $forumModel->where('type', self::FORUM_TYPE)
            ->where('delete_time', 0)
            ->where('id', '>', $lastId)
            ->field('id,user_id,content,image,point')
            ->order('id', 'asc')
            ->limit($step)
            ->select();
        $list = $list ? $list->toArray() : [];
        if (empty($list)) {
            if ($anchored) {
                return ['summary' => "首次运行已锚定最新打卡 #{$lastId}，暂无可扫描的新打卡记录（只监管之后的新数据）。"];
            }
            return ['summary' => "无新增打卡（处理进度 #{$lastId}）。"];
        }

        // 每用户的比对上下文；urlMap: 该用户已见图片URL=>最早forum_id；hashList: 感知哈希指纹列表
        $userCtx = [];
        $urlHashCache = [];
        $deleted = [];
        $unreadable = 0;   // 无法下载/解码，跳过不判的图片张数

        foreach ($list as $row) {
            $rid = (int)$row['id'];
            $uid = (int)$row['user_id'];
            $urls = self::decodeImages($row['image']);
            if (empty($urls)) {
                continue;
            }
            // 惰性加载该用户“更早”的历史图片指纹(最近 historyLimit 条, 含已删记录, 以识别连环复用)
            if (!isset($userCtx[$uid])) {
                $userCtx[$uid] = $this->loadUserHistory($forumModel, $uid, $rid, $historyLimit, $urlHashCache);
            }
            $ctx = &$userCtx[$uid];

            $hit = null;
            foreach ($urls as $idx => $url) {
                $raw = ImagePerceptualHash::rawUrl($url);
                if ($raw === null) {
                    $unreadable++;
                    continue;
                }
                // 1) URL 完全一致（同一文件再次上传）
                if ($deleteSameUrl && array_key_exists($raw, $ctx['urlMap'])) {
                    $hit = [
                        'sim'     => 100.0,
                        'matchFid'=> (int)$ctx['urlMap'][$raw],
                        'imageNo' => $idx + 1,
                        'way'     => '图片地址与本人历史打卡完全一致',
                    ];
                    break;
                }
                // 2) 感知哈希比对
                $hash = $this->fingerprintCached($urlHashCache, $raw);
                if ($hash === null) {
                    $unreadable++;
                    continue;
                }
                foreach ($ctx['hashList'] as $old) {
                    $sim = ImagePerceptualHash::similarityPercent($hash, $old['hash']);
                    if ($sim >= $threshold) {
                        $hit = [
                            'sim'     => $sim,
                            'matchFid'=> (int)$old['fid'],
                            'imageNo' => $idx + 1,
                            'way'     => '感知哈希相似度 ' . $sim . '% 命中历史打卡',
                        ];
                        break 2;
                    }
                }
                // 未命中: 视为该用户“已见”图片, 供后续(更晚)打卡继续比对
                if (!array_key_exists($raw, $ctx['urlMap'])) {
                    $ctx['urlMap'][$raw] = $rid;
                }
                $ctx['hashList'][] = ['hash' => $hash, 'fid' => $rid];
            }
            unset($ctx);

            if ($hit) {
                $deleted[] = [
                    'forum_id' => $rid,
                    'user_id'  => $uid,
                    'content'  => (string)($row['content'] ?? ''),
                    'point'    => (int)($row['point'] ?? 0),
                    'hit'      => $hit,
                ];
            }
        }

        $lastRowId = (int)$list[count($list) - 1]['id'];
        $state['last_forum_id'] = $lastRowId; // 无论如何推进游标

        $delCount = $this->deleteDuplicates($deleted);

        $summary = "扫描至打卡 #{$lastRowId}，共检查 " . count($list)
            . " 条新打卡、命中重复 " . $delCount . " 条并自动删除";
        if ($unreadable > 0) {
            $summary .= "（{$unreadable} 张图无法读取已跳过）";
        }
        return ['summary' => $summary];
    }

    /**
     * 加载某用户最近 historyLimit 条历史打卡的图片指纹上下文。
     */
    private function loadUserHistory(ForumModel $forumModel, int $uid, int $anchorId, int $historyLimit, array &$urlHashCache): array
    {
        $ctx = ['urlMap' => [], 'hashList' => []];
        $rows = $forumModel->where('type', self::FORUM_TYPE)
            ->where('user_id', $uid)
            ->where('id', '<', $anchorId)
            ->field('id,image')
            ->order('id', 'desc')
            ->limit($historyLimit)
            ->select();
        $rows = $rows ? $rows->toArray() : [];
        foreach ($rows as $hr) {
            $fid = (int)$hr['id'];
            foreach (self::decodeImages($hr['image']) as $url) {
                $raw = ImagePerceptualHash::rawUrl($url);
                if ($raw === null) {
                    continue;
                }
                if (!array_key_exists($raw, $ctx['urlMap'])) {
                    $ctx['urlMap'][$raw] = $fid; // 保留最早出现的那条
                }
                $hash = $this->fingerprintCached($urlHashCache, $raw);
                if ($hash === null) {
                    continue;
                }
                $ctx['hashList'][] = ['hash' => $hash, 'fid' => $fid];
            }
        }
        return $ctx;
    }

    private function fingerprintCached(array &$cache, string $rawUrl): ?string
    {
        if (array_key_exists($rawUrl, $cache)) {
            return $cache[$rawUrl];
        }
        return $cache[$rawUrl] = ImagePerceptualHash::fingerprintFromUrl($rawUrl);
    }

    /**
     * 批量删除判定重复的打卡记录。
     * 与后台 ForumController::delete_all 动作一致：写删除日志、追回积分(type 200/227)、软删除。
     * operator_id = 0 表示系统定时任务。
     */
    private function deleteDuplicates(array $deleted): int
    {
        if (empty($deleted)) {
            return 0;
        }
        $now = time();
        Db::startTrans();
        try {
            foreach ($deleted as $d) {
                $hit = $d['hit'];
                $reason = '【系统定时任务】打卡图片重复自动删除：' . $hit['way']
                    . '(相似度' . rtrim(rtrim(number_format($hit['sim'], 2), '0'), '.') . '%，第' . $hit['imageNo']
                    . '张图命中历史打卡#' . $hit['matchFid'] . ')。';

                ForumDeleteLogModel::create([
                    'forum_id'    => $d['forum_id'],
                    'user_id'     => $d['user_id'],
                    'content'     => $d['content'],
                    'image'       => ForumDeleteLogModel::imageToString($this->getForumImage($d['forum_id'])),
                    'point'       => $d['point'],
                    'reason'      => $reason,
                    'operator_id' => 0,
                    'create_time' => $now,
                ]);

                // 有积分则追回，与后台删除逻辑一致
                if ($d['point'] > 0) {
                    AccountLogModel::add_account_log($d['user_id'], 2, 200, -$d['point'], ['remark' => '垃圾打卡扣除(重复图片)']);
                    $logInfo = AccountLogModel::where('from_uid', $d['forum_id'])->find();
                    if (!empty($logInfo)) {
                        AccountLogModel::add_account_log($d['user_id'], 2, 227, -$logInfo['change_amount'], ['remark' => '垃圾打卡扣除(重复图片)']);
                    }
                }

                (new ForumModel())->where('id', $d['forum_id'])->update(['delete_time' => $now]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return count($deleted);
    }

    private function getForumImage(int $forumId): string
    {
        $img = (new ForumModel())->where('id', $forumId)->value('image');
        return (string)($img ?? '');
    }
}
