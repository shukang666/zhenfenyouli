<?php

namespace app\command;

use app\cron\lib\ImagePerceptualHash;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 一次性存量回填：为 type=2 垃圾分类打卡的图片计算 aHash 指纹写入 img_hash 列。
 *
 * 用法(在服务器上执行, 需能访问阿里云 OSS; 可重复执行断点续跑):
 *   php think forum:hash-backfill               # 全量回填, 直到无 img_hash 为空的行
 *   php think forum:hash-backfill --max=10000   # 本次最多处理 N 条, 分批跑
 *   php think forum:hash-backfill --step=500    # 每批 500 条(默认 200)
 *
 * 说明：
 *   - 依赖 cmf_forum.img_hash 列(不存在会自动 ALTER 创建);
 *   - 同一原始图片 URL 在本次运行内只下载/计算一次(进程内缓存);
 *   - 某行任一张图下载/解码失败则不写, 下次运行自动重试; 无图行直接写 [] 防重复扫描。
 */
class ForumHashBackfill extends Command
{
    protected function configure()
    {
        $this->setName('forum:hash-backfill')
            ->addOption('step', null, Option::VALUE_REQUIRED, '每批处理条数', '200')
            ->addOption('max', null, Option::VALUE_REQUIRED, '本次最多处理条数(0=不限直到扫完)', '0')
            ->setDescription('一次性回填垃圾分类打卡图片 img_hash(需联网访问OSS,可断点续跑)');
    }

    protected function execute(Input $input, Output $output)
    {
        @set_time_limit(0);
        $this->ensureColumn();

        $step = max(1, (int)$input->getOption('step'));
        $max  = max(0, (int)$input->getOption('max'));

        $urlCache = []; // 原始URL => hash|null
        $done = 0;
        $fail = 0;

        while (true) {
            if ($max > 0 && $done >= $max) {
                break;
            }
            $take = $step;
            if ($max > 0) {
                $take = min($take, $max - $done);
            }

            $rows = Db::name('forum')->where('type', 2)
                ->where(function ($q) {
                    $q->whereNull('img_hash')->whereOr('img_hash', '');
                })
                ->order('id', 'asc')
                ->field('id,image')
                ->limit($take)
                ->select();
            $rows = $rows ? $rows->toArray() : [];
            if (empty($rows)) {
                break;
            }

            $batchDone = 0;
            foreach ($rows as $r) {
                $id   = (int)$r['id'];
                $urls = self::decodeImages($r['image']);

                if (empty($urls)) {
                    // 无图：直接标记完成，避免每次都被扫到
                    Db::name('forum')->where('id', $id)->update(['img_hash' => '[]']);
                    $done++;
                    $batchDone++;
                    continue;
                }

                $hashes = [];
                $okAll  = true;
                foreach ($urls as $url) {
                    $raw = ImagePerceptualHash::rawUrl((string)$url);
                    if ($raw === null) {
                        $okAll = false;
                        break;
                    }
                    if (!array_key_exists($raw, $urlCache)) {
                        $urlCache[$raw] = ImagePerceptualHash::fingerprintFromUrl($raw);
                    }
                    $h = $urlCache[$raw];
                    if ($h === null) {
                        $okAll = false;
                        break;
                    }
                    $hashes[] = $h;
                }

                if ($okAll) {
                    Db::name('forum')->where('id', $id)->update(['img_hash' => json_encode($hashes)]);
                    $done++;
                    $batchDone++;
                } else {
                    $fail++;
                }
            }

            $output->writeln('本批处理 ' . $batchDone . ' 条(累计成功 ' . $done . '，失败跳过 ' . $fail . ')，进度至打卡 id=' . $rows[count($rows) - 1]['id']);

            // 当前窗口内全部失败、毫无进展 → 退出避免死循环(失败的留下次运行重试)
            if ($batchDone === 0) {
                break;
            }
        }

        $remain = Db::name('forum')->where('type', 2)
            ->where(function ($q) {
                $q->whereNull('img_hash')->whereOr('img_hash', '');
            })
            ->count();

        if ($done > 0 || $fail > 0) {
            $output->writeln('<info>回填结束：本次成功 ' . $done . ' 条，失败跳过 ' . $fail . ' 条，剩余待回填 ' . $remain . ' 条(可再次运行续跑)。</info>');
        } else {
            $output->writeln('<info>没有待回填的记录(剩余 ' . $remain . ' 条)。</info>');
        }
        return 0;
    }

    /** 幂等确保 cmf_forum.img_hash 列存在 */
    private function ensureColumn(): void
    {
        $row = Db::query("SELECT COUNT(*) AS c FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = 'cmf_forum' AND column_name = 'img_hash'");
        if (empty($row[0]['c'])) {
            Db::execute("ALTER TABLE cmf_forum ADD COLUMN img_hash text NULL COMMENT '垃圾分类打卡图片aHash指纹JSON(与image同序)' AFTER image");
        }
    }

    /** 解码 image 字段(JSON数组/数组/单串) */
    private static function decodeImages($image): array
    {
        if (is_array($image)) {
            return array_values($image);
        }
        if (!is_string($image) || trim($image) === '') {
            return [];
        }
        $arr = json_decode($image, true);
        if (is_array($arr)) {
            return array_values($arr);
        }
        if (is_string($arr) && $arr !== '') {
            return [$arr];
        }
        return [];
    }
}
