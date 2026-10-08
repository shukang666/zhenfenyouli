<?php

namespace api\wxapp\controller;

use app\admin\service\ActivityShowcaseSyncService;
use think\facade\Db;

/**
 * 活动风采 - 小程序接口
 */
class ActivityShowcaseController extends AuthController
{
    /**
     * 风采列表
     * GET /wxapp/activity_showcase/get_list
     * 参数：page, limit（首页预览传 limit=6）
     * source=manual 仅手工上传随拍（active_id=0）；source=archive 仅往期活动归档（active_id>0）；不传则两类混合（兼容旧调用）
     */
    public function get_list()
    {
        ActivityShowcaseSyncService::syncEligibleEndedActives(20);

        $page  = $this->request->param('page', 1, 'intval');
        $limit = $this->request->param('limit', 15, 'intval');
        $limit = max(1, min($limit, 50));
        $source = trim((string)$this->request->param('source', ''));

        $where = [['status', '=', 1]];
        if ($source === 'manual') {
            $where[] = ['active_id', '=', 0];
        } elseif ($source === 'archive') {
            $where[] = ['active_id', '>', 0];
        }

        $total = Db::name('activity_showcase')->where($where)->count();
        $list = Db::name('activity_showcase')
            ->where($where)
            ->field('id, active_id, title, cover, description, content, event_date, photos, view_count, create_time')
            ->order('list_order asc, event_date desc, id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $list = $this->formatShowcaseListRows($list);

        $this->success('success', [
            'list'     => $list,
            'total'    => $total,
            'page'     => $page,
            'has_more' => $page * $limit < $total,
            'source'   => $source,
        ]);
    }

    protected function formatShowcaseListRows(array $list): array
    {
        foreach ($list as &$item) {
            $photos = $this->parsePhotos($item['photos'] ?? '');
            $item['cover'] = $this->formatImage($item['cover'] ?: ($photos[0] ?? ''));
            $item['photo_count'] = count($photos);
            $item['event_date_text'] = $item['event_date'] ? date('Y-m-d', $item['event_date']) : '';
            $item['active_id'] = (int)($item['active_id'] ?? 0);
            $item['is_archive'] = $item['active_id'] > 0 ? 1 : 0;
            if (empty($item['description'])) {
                $plain = trim(strip_tags($this->formatContent($item['content'] ?? '')));
                if ($plain !== '') {
                    $item['description'] = mb_strlen($plain, 'UTF-8') > 80
                        ? mb_substr($plain, 0, 80, 'UTF-8') . '…'
                        : $plain;
                }
            }
            unset($item['content'], $item['photos']);
        }
        unset($item);
        return $list;
    }

    /**
     * 风采详情
     * GET /wxapp/activity_showcase/get_detail
     */
    public function get_detail()
    {
        $id = $this->request->param('id', 0, 'intval');

        $detail = Db::name('activity_showcase')->where('id', $id)->where('status', 1)->find();
        if (empty($detail)) {
            $this->error('内容不存在');
        }

        Db::name('activity_showcase')->where('id', $id)->inc('view_count')->update();

        $photos = $this->parsePhotos($detail['photos'] ?? '');
        $detail['cover'] = $this->formatImage($detail['cover'] ?: ($photos[0] ?? ''));
        $detail['photos'] = array_map([$this, 'formatImage'], $photos);
        $detail['photo_count'] = count($photos);
        $detail['event_date_text'] = $detail['event_date'] ? date('Y-m-d', $detail['event_date']) : '';
        $detail['content'] = $this->formatContent($detail['content'] ?? '');
        $detail['content'] = $this->limitContentImages($detail['content'], 15);
        unset($detail['status']);

        $this->success('success', $detail);
    }

    /**
     * 富文本：解码并补全图片地址
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

    private function limitContentImages($html, $max = 15)
    {
        if ($html === '' || $html === null) {
            return '';
        }
        $n = 0;
        return preg_replace_callback('/<img\b[^>]*>/i', function ($m) use (&$n, $max) {
            $n++;
            return $n <= $max ? $m[0] : '';
        }, (string)$html);
    }

    private function parsePhotos($photos)
    {
        if (empty($photos)) {
            return [];
        }
        $list = array_values(array_filter(array_map('trim', explode(',', $photos))));
        return array_slice($list, 0, 15);
    }

    private function formatImage($image)
    {
        if (empty($image)) {
            return '';
        }

        return cmf_get_asset_url($image);
    }
}
