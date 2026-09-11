<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * PC 后台 - 更新记录（设置 → 更新记录）
 *
 * 只读展示数据表 cmf_update_log：
 *   - 后台不提供新增 / 编辑 / 删除，记录由开发方（AI）直接写库
 *   - 页面只负责「展示 + 按类型筛选 + 关键词搜索 + 分页」
 *
 * content 字段为纯文本（可含换行），模板侧用 <pre> + htmlspecialchars 原样展示，
 * 详见 skill `pc-admin-update-log`。
 */
class UpdateLogController extends AdminBaseController
{
    /** 类型 key → 展示标签 */
    const TYPES = [
        'feature'  => '功能新增',
        'optimize' => '体验优化',
        'fix'      => '问题修复',
        'refactor' => '结构调整',
    ];

    /** 类型 key → Bootstrap 标签颜色 */
    const TYPE_CLASSES = [
        'feature'  => 'label-success',
        'optimize' => 'label-info',
        'fix'      => 'label-warning',
        'refactor' => 'label-default',
    ];

    /** 列表：一行一条更新记录 */
    public function index()
    {
        $keyword = (string)$this->request->param('keyword', '', 'trim');
        $type    = (string)$this->request->param('type', '', 'trim');

        $query = Db::name('update_log');

        if ($keyword !== '') {
            // 转义 like 通配符，避免用户输入的 % _ 变成模糊匹配
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)->whereOr('content', 'like', $like);
            });
        }

        // 只接受已知类型，避免拼出无结果的查询
        if ($type !== '' && isset(self::TYPES[$type])) {
            $query->where('update_type', $type);
        }

        $list = $query->order('create_time desc, id desc')->paginate(15);
        $list->appends(['keyword' => $keyword, 'type' => $type]);

        $list->each(function ($item) {
            $item['title']      = (string)$item['title'];
            $item['content']    = (string)$item['content'];
            $item['version']    = (string)$item['version'];
            $item['type_label'] = self::TYPES[$item['update_type']] ?? '其他';
            $item['type_class'] = self::TYPE_CLASSES[$item['update_type']] ?? 'label-default';
            return $item;
        });

        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $this->assign('types', self::TYPES);
        $this->assign('keyword', $keyword);
        $this->assign('curType', $type);

        return $this->fetch('index');
    }
}
