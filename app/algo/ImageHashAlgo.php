<?php

namespace app\algo;

use app\algo\lib\ImagePerceptualHash;

/**
 * 算法条目：垃圾分类打卡图片查重（感知哈希 aHash）
 *
 * 当前项目唯一核心算法：解决“同一张照片反复打卡刷积分”问题。
 * 数据存于 cmf_forum(type=2) 的 img_hash 列，与 image 数组同序。
 */
class ImageHashAlgo implements AlgoInterface
{
    public static function meta(): array
    {
        return [
            'algo_key'  => 'forum_image_hash',
            'name'      => '垃圾分类打卡图片查重（感知哈希 aHash）',
            'category'  => '图像处理 · 内容查重',
            'status'    => 'online',
            'summary'   => '把每张打卡图压缩成 8×8 灰度图取 64 位指纹，发布打卡时与本人历史打卡比对，指纹一致即拦截，防止用同一张照片反复刷积分。',
            'principle' => "一、背景：垃圾分类打卡要求居民每天拍照提交（cmf_forum.type=2），此前存在用同一张照片反复上传刷积分的情况。需要在不误伤正常打卡的前提下，识别\"同一张原图复用\"。\n"
                . "二、指纹生成（aHash / Average Hash）：把图片无损缩放为 8×8 共 64 个像素的灰度图，算出 64 个像素的平均灰度，再逐个像素与平均值比较：大于均值记 1、否则记 0，得到 64 位二进制指纹（每 4 位转一个十六进制字符，落库为 16 位 hex 串）。该指纹对缩放、压缩、轻微滤镜不敏感，但能稳定区分\"不同照片\"与\"同一原图\"。\n"
                . "三、相似度判定（汉明距离）：两张图比对 = 统计两串指纹逐位不同的个数；相似度百分比 = (1 - 汉明距离/64) × 100。相似度 100%（汉明距离 0）即判定为感知完全相同。\n"
                . "四、判定范围与防绕过：发布时取该用户最近 200 条历史打卡（含已软删记录，防止\"删了再传\"连环复用）比对；先按归一化 URL（剥离 ?watermark 等样式参数取 OSS 原图）快速判同，再做指纹比对；新图首次比对才下载原图计算一次指纹，算完落库，后续全部纯内存位运算，毫秒级完成。\n"
                . "五、历史回填：存量历史图片通过命令 php think forum:hash-backfill 统一回填指纹（幂等、断点续跑），保证新老数据都在同一套指纹体系下。\n"
                . "六、策略说明：当前仅\"完全相同\"才拦截（阈值 100%，与后台人工删除判重口径一致），最大程度避免误伤正常打卡；如后续需拦截近似翻拍，可评估将阈值放宽到 95% 附近，但需先跑历史数据评估误伤率。",
            'key_params' => [
                ['label' => '指纹长度', 'value' => '64 bit（16 位 hex）', 'desc' => '8×8 缩略图 = 64 个像素，逐像素与平均灰度比较生成'],
                ['label' => '指纹计算', 'value' => '平均哈希 aHash', 'desc' => 'ImagePerceptualHash 库，PHP GD 实现'],
                ['label' => '相似度阈值', 'value' => '100%（汉明距离=0）', 'desc' => '仅完全相同才拦截，避免误伤；可调但需先评估'],
                ['label' => '比对范围', 'value' => '本人最近 200 条', 'desc' => 'type=2 打卡，含已软删记录，防连环复用'],
                ['label' => 'URL 归一化', 'value' => '剥离 ?watermark', 'desc' => '同图换链接也能命中，取 OSS 原始图片计算'],
                ['label' => '存储位置', 'value' => 'cmf_forum.img_hash', 'desc' => 'JSON 数组，与 image 数组一一对应'],
            ],
            'applications' => [
                ['where' => '打卡发布实时拦截', 'detail' => 'ForumController::add_forum(type=2)：发布前与本人历史打卡比对，命中直接提示换图并拒绝发布', 'status' => '已上线'],
                ['where' => '打卡编辑再次查重', 'detail' => 'ForumController::add_forum 编辑分支(id 存在时)，重算指纹并排除自身后校验', 'status' => '已上线'],
                ['where' => '存量历史回填', 'detail' => '命令行 php think forum:hash-backfill：为 img_hash 为空的历史打卡补指纹(幂等可续跑)', 'status' => '已上线'],
            ],
            'impl' => [
                'class' => 'app\\algo\\lib\\ImagePerceptualHash',
                'file'  => 'app/algo/lib/ImagePerceptualHash.php',
            ],
        ];
    }
}
