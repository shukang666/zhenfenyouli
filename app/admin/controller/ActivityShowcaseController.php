<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 活动风采 - 后台管理
 * 管理社区活动/宣讲现场照片，供小程序首页展示
 */
class ActivityShowcaseController extends AdminBaseController
{
    /**
     * 风采列表
     */
    public function index()
    {
        $params = $this->request->param();
        $where = [['status', '<>', -1]];

        $keyword = trim($this->request->param('keyword', ''));
        if ($keyword !== '') {
            $where[] = ['title', 'like', '%' . $keyword . '%'];
        }

        $status = $this->request->param('status', '');
        if ($status !== '') {
            $where[] = ['status', '=', intval($status)];
        }

        $list = Db::name('activity_showcase')
            ->where($where)
            ->order('list_order asc, id desc')
            ->paginate(15);

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('keyword', $keyword);
        $this->assign('status', $status);

        return $this->fetch();
    }

    /**
     * 添加/编辑
     */
    public function add()
    {
        $id = $this->request->param('id', 0, 'intval');

        if ($this->request->isPost()) {
            $data = $this->request->param();

            if (empty($data['title'])) {
                $this->error('标题不能为空');
            }
            if (empty($data['photos'])) {
                $this->error('请上传至少一张照片');
            }

            $photos = is_array($data['photos']) ? $data['photos'] : [];
            $photos = array_values(array_filter(array_map('trim', $photos)));
            if (empty($photos)) {
                $this->error('请上传至少一张照片');
            }

            $content = (string)($data['content'] ?? '');
            if (trim(strip_tags(htmlspecialchars_decode($content))) === '') {
                $this->error('请填写活动正文（富文本）');
            }

            $eventDate = trim($data['event_date'] ?? '');
            $eventTimestamp = $eventDate ? strtotime($eventDate) : 0;

            $save = [
                'title'       => trim($data['title']),
                'cover'       => trim($data['cover'] ?? '') ?: $photos[0],
                'description' => trim($data['description'] ?? ''),
                'content'     => $content,
                'event_date'  => $eventTimestamp ?: time(),
                'photos'      => implode(',', $photos),
                'list_order'  => intval($data['list_order'] ?? 0),
                'status'      => isset($data['status']) ? 1 : 0,
                'update_time' => time(),
            ];

            if ($id) {
                Db::name('activity_showcase')->where('id', $id)->update($save);
                $this->success('更新成功');
            } else {
                $save['create_time'] = time();
                $save['view_count'] = 0;
                Db::name('activity_showcase')->insertGetId($save);
                $this->success('添加成功');
            }
        }

        $data = [
            'id'          => 0,
            'title'       => '',
            'cover'       => '',
            'description' => '',
            'content'     => '',
            'event_date'  => date('Y-m-d'),
            'photos'      => [],
            'list_order'  => 0,
            'status'      => 1,
        ];

        if ($id) {
            $row = Db::name('activity_showcase')->where('id', $id)->where('status', '<>', -1)->find();
            if (empty($row)) {
                $this->error('记录不存在');
            }
            $row['photos'] = !empty($row['photos']) ? explode(',', $row['photos']) : [];
            $row['event_date'] = $row['event_date'] ? date('Y-m-d', $row['event_date']) : '';
            $data = $row;
        }

        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 删除（软删除）
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        Db::name('activity_showcase')->where('id', $id)->update([
            'status'      => -1,
            'update_time' => time(),
        ]);
        $this->success('删除成功');
    }

    /**
     * 批量删除
     */
    public function delete_all()
    {
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('请选择要删除的项');
        }
        Db::name('activity_showcase')->whereIn('id', $ids)->update([
            'status'      => -1,
            'update_time' => time(),
        ]);
        $this->success('删除成功');
    }

    /**
     * 从 public/临时传活动风采 导入（需在服务器上已上传该目录）
     */
    public function import_temp()
    {
        $adminId = cmf_get_current_admin_id();
        if ((int)$adminId !== 1) {
            $this->error('仅超级管理员可执行导入');
        }
        require_once CMF_ROOT . 'data/import_showcase_from_temp.php';
        $env = parse_ini_file(CMF_ROOT . '.env');
        $pdo = new \PDO(
            "mysql:host={$env['DATABASE_HOSTNAME']};port={$env['DATABASE_HOSTPORT']};dbname={$env['DATABASE_DATABASE']};charset=utf8mb4",
            $env['DATABASE_USERNAME'],
            $env['DATABASE_PASSWORD'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        $report = import_activity_showcase_from_temp(CMF_ROOT, $pdo, false);
        $this->assign('report', $report);
        $this->assign('reportJson', json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $this->fetch('import_temp');
    }
}
