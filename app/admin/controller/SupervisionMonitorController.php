<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 督导监测
 * 在垃圾分类活动菜单下
 * 数据来源：小程序"志愿督导工作台-指导员-督导监测"表单（cmf_supervision_monitor）
 */
class SupervisionMonitorController extends AdminBaseController
{
    /**
     * 列表
     */
    public function index()
    {
        $params = $this->request->param();
        $where = $this->buildWhere();

        $list = Db::name('supervision_monitor')->alias('r')
            ->join('cmf_member m', 'm.id = r.user_id', 'LEFT')
            ->field('r.*, m.nickname, m.mobile, r.submitter_name')
            ->where($where)
            ->order('r.id desc')
            ->paginate(15);

        $unit_type_list = [1 => '公共机构', 2 => '经营场所', 3 => '沿街商铺', 4 => '经营区域'];

        $list->each(function ($item) use ($unit_type_list) {
            $item['unit_type_name'] = isset($unit_type_list[$item['unit_type']]) ? $unit_type_list[$item['unit_type']] : '';
            $item['yes_no_has_waste'] = $item['has_waste'] == 1 ? '是' : '否';
            $item['yes_no_waste_correct'] = $item['waste_correct'] == 1 ? '是' : '否';
            $item['yes_no_need_rectify'] = $item['need_rectify'] == 1 ? '是' : '否';
            $item['display_submitter'] = !empty($item['submitter_name']) ? $item['submitter_name'] : (!empty($item['nickname']) ? $item['nickname'] . ' (' . $item['mobile'] . ')' : '已删除');
            return $item;
        });

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('keyword', $this->request->param('keyword', ''));
        $this->assign('unit_type', $this->request->param('unit_type', ''));
        $this->assign('unit_type_list', $unit_type_list);
        $this->assign('start_time', $this->request->param('start_time', ''));
        $this->assign('end_time', $this->request->param('end_time', ''));
        return $this->fetch();
    }

    /**
     * 详情
     */
    public function detail()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }

        $item = Db::name('supervision_monitor')->alias('r')
            ->join('cmf_member m', 'm.id = r.user_id', 'LEFT')
            ->field('r.*, m.nickname, m.mobile, r.submitter_name')
            ->where('r.id', $id)
            ->find();

        $item['display_submitter'] = !empty($item['submitter_name']) ? $item['submitter_name'] : (!empty($item['nickname']) ? $item['nickname'] . ' (' . $item['mobile'] . ')' : '已删除');
        if (empty($item)) {
            $this->error('记录不存在');
        }

        $unit_type_list = [1 => '公共机构', 2 => '经营场所', 3 => '沿街商铺', 4 => '经营区域'];
        $item['unit_type_name'] = isset($unit_type_list[$item['unit_type']]) ? $unit_type_list[$item['unit_type']] : '';

        // 照片字段统一补全 URL 供模板展示
        $photo_fields = [
            'door_photo'           => '门头照片',
            'inner_photo'          => '内部环境照片',
            'publicity_photo'      => '宣传品照片',
            'inner_rectify_photo'  => '内部环境整改后照片',
            'point_photo'          => '投放点照片',
            'point_rectify_photo'  => '投放点整改后照片',
            'manage_photo'         => '点位管理照片',
            'manage_rectify_photo' => '点位管理整改后照片',
            'transport_photo'      => '收运链条照片',
            'catering_photo'       => '餐饮企业相关宣传',
            'accuracy_photo'       => '准确率照片',
        ];
        $photos = [];
        foreach ($photo_fields as $field => $name) {
            $storedUrls = \app\common\SupervisionMonitorPhotos::urlsFromStored($item[$field] ?? '');
            if (empty($storedUrls)) {
                continue;
            }
            $total = count($storedUrls);
            foreach ($storedUrls as $idx => $path) {
                $label = $name;
                if ($total > 1) {
                    $label .= '（' . ($idx + 1) . '/' . $total . '）';
                }
                $photos[] = [
                    'name' => $label,
                    'url'  => cmf_get_image_preview_url($path),
                ];
            }
        }
        $this->assign('photos', $photos);
        $this->assign('photosJson', json_encode($photos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->assign('item', $item);

        // 列表最新在最下面（往下看更新的）：上一条=更旧 id 更小，下一条=更新 id 更大
        $where = $this->buildWhere();
        $prevId = Db::name('supervision_monitor')->alias('r')
            ->join('cmf_member m', 'm.id = r.user_id', 'LEFT')
            ->where($where)
            ->where('r.id', '<', $id)
            ->order('r.id', 'desc')
            ->value('r.id');
        $nextId = Db::name('supervision_monitor')->alias('r')
            ->join('cmf_member m', 'm.id = r.user_id', 'LEFT')
            ->where($where)
            ->where('r.id', '>', $id)
            ->order('r.id', 'asc')
            ->value('r.id');
        $navQuery = $this->request->except(['id']);
        $this->assign('prevUrl', $prevId ? (string) url('admin/SupervisionMonitor/detail', array_merge($navQuery, ['id' => intval($prevId)])) : '');
        $this->assign('nextUrl', $nextId ? (string) url('admin/SupervisionMonitor/detail', array_merge($navQuery, ['id' => intval($nextId)])) : '');
        $this->assign('listUrl', (string) url('admin/SupervisionMonitor/index', $navQuery));

        return $this->fetch();
    }

    /**
     * 导出（与 index 筛选条件一致；流式 CSV，不加载照片字段、不占满内存）
     */
    public function outData()
    {
        // 流式导出：固定内存占用，允许全量；仅放宽执行时间，不设 memory_limit=-1
        @set_time_limit(0);
        @ini_set('memory_limit', '128M');
        if (function_exists('session_write_close')) {
            session_write_close();
        }

        $where = $this->buildWhere();

        if (!$this->exportBaseQuery()->where($where)->limit(1)->value('r.id')) {
            $this->error('当前筛选条件下没有可导出的数据');
        }

        $filename = '督导监测' . date('Y-m-d');
        $head = ['ID', '提交人', '手机号', '所属社区', '单位名称', '单位类型', '检查日期', '地址', '联系人', '联系电话',
            '是否产生厨余垃圾', '厨余垃圾是否正确分出', '厨余垃圾流向', '内部环境是否合格', '是否需要整改',
            '投放点是否合格', '点位管理是否合格', '总体评价', '提交时间'];

        $unit_type_list = [1 => '公共机构', 2 => '经营场所', 3 => '沿街商铺', 4 => '经营区域'];

        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=UTF-8');
        $encodedName = rawurlencode($filename . '.csv');
        header('Content-Disposition: attachment; filename="supervision_monitor.csv"; filename*=UTF-8\'\'' . $encodedName);
        header('Cache-Control: max-age=0');
        header('X-Accel-Buffering: no');

        $fp = fopen('php://output', 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, $head);

        $cursor = $this->exportBaseQuery()
            ->where($where)
            ->order('r.id desc')
            ->cursor();

        $rowNum = 0;
        foreach ($cursor as $row) {
            $item = $this->mapExportRow((array) $row, $unit_type_list);
            fputcsv($fp, [
                $item['id'],
                $item['display_submitter'],
                $item['mobile'],
                $item['community_name'],
                $item['unit_name'],
                $item['unit_type_name'],
                $item['check_date'],
                $item['address'],
                $item['contact'],
                $item['contact_phone'],
                $item['yes_no_has_waste'],
                $item['yes_no_waste_correct'],
                $item['waste_flow'],
                $item['yes_no_inner_qualified'],
                $item['yes_no_need_rectify'],
                $item['yes_no_point_qualified'],
                $item['yes_no_manage_qualified'],
                $item['overall_evaluation'],
                $item['create_time'],
            ]);
            $rowNum++;
            if ($rowNum % 500 === 0) {
                fflush($fp);
                if (function_exists('flush')) {
                    flush();
                }
            }
        }

        fclose($fp);
        exit;
    }

    /**
     * 导出查询：不含各照片 JSON 字段，避免大结果集撑爆内存
     */
    private function exportBaseQuery()
    {
        return Db::name('supervision_monitor')->alias('r')
            ->join('cmf_member m', 'm.id = r.user_id', 'LEFT')
            ->field('r.id,r.submitter_name,r.community_name,r.unit_name,r.unit_type,r.check_date,r.address,r.contact,r.contact_phone,'
                . 'r.has_waste,r.waste_correct,r.waste_flow,r.inner_qualified,r.need_rectify,r.point_qualified,r.manage_qualified,'
                . 'r.overall_evaluation,r.create_time,m.nickname,m.mobile');
    }

    private function mapExportRow(array $item, array $unit_type_list): array
    {
        $item['unit_type_name'] = isset($unit_type_list[$item['unit_type']]) ? $unit_type_list[$item['unit_type']] : '';
        $item['yes_no_has_waste'] = (int) $item['has_waste'] === 1 ? '是' : '否';
        $item['yes_no_waste_correct'] = (int) $item['waste_correct'] === 1 ? '是' : '否';
        $item['yes_no_inner_qualified'] = (int) $item['inner_qualified'] === 1 ? '是' : '否';
        $item['yes_no_need_rectify'] = (int) $item['need_rectify'] === 1 ? '是' : '否';
        $item['yes_no_point_qualified'] = (int) $item['point_qualified'] === 1 ? '是' : '否';
        $item['yes_no_manage_qualified'] = (int) $item['manage_qualified'] === 1 ? '是' : '否';
        $item['create_time'] = !empty($item['create_time']) ? date('Y-m-d H:i:s', (int) $item['create_time']) : '';
        $item['display_submitter'] = !empty($item['submitter_name'])
            ? $item['submitter_name']
            : (!empty($item['nickname']) ? $item['nickname'] : '已删除');

        return $item;
    }

    /**
     * 构建搜索条件（index 与 outData 共用，保证数据一致）
     */
    private function buildWhere()
    {
        $where = [];

        // 关键词：单位名称/联系人/联系电话/昵称/手机号
        $keyword = trim($this->request->param('keyword', ''));
        if (!empty($keyword)) {
            $where[] = ['r.unit_name|r.contact|r.contact_phone|m.nickname|m.mobile|r.submitter_name', 'like', '%' . $keyword . '%'];
        }

        // 单位类型
        $unit_type = $this->request->param('unit_type', '');
        if ($unit_type !== '') {
            $where[] = ['r.unit_type', '=', intval($unit_type)];
        }

        // 提交时间范围（含当天全量）
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time) {
            $where[] = ['r.create_time', '>=', strtotime($start_time)];
        }
        if ($end_time) {
            $where[] = ['r.create_time', '<=', strtotime($end_time) + 86399];
        }

        return $where;
    }
}
