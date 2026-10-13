<?php

namespace app\admin\controller;

use api\wxapp\model\CommonModel;
use app\common\SupervisionMonitorPhotos;
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

        $photo_fields = $this->photoFieldLabels();
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
     * 导出 xlsx：照片嵌入单元格（与商户打卡导出一致）；游标逐行拉图，避免先拼全量 CSV
     */
    public function outData()
    {
        if (function_exists('session_write_close')) {
            session_write_close();
        }

        $where = $this->buildWhere();

        if (!$this->exportBaseQuery()->where($where)->limit(1)->value('r.id')) {
            $this->error('当前筛选条件下没有可导出的数据');
        }

        $unit_type_list = [1 => '公共机构', 2 => '经营场所', 3 => '沿街商铺', 4 => '经营区域'];
        $photoFields = $this->photoFieldLabels();

        $head = ['ID', '提交人', '手机号', '所属社区', '单位名称', '单位类型', '检查日期', '地址', '联系人', '联系电话',
            '是否产生厨余垃圾', '厨余垃圾是否正确分出', '厨余垃圾流向', '内部环境是否合格', '是否需要整改',
            '投放点是否合格', '点位管理是否合格', '总体评价', '提交时间'];
        $keys = ['id', 'display_submitter', 'mobile', 'community_name', 'unit_name', 'unit_type_name', 'check_date', 'address',
            'contact', 'contact_phone', 'yes_no_has_waste', 'yes_no_waste_correct', 'waste_flow', 'yes_no_inner_qualified',
            'yes_no_need_rectify', 'yes_no_point_qualified', 'yes_no_manage_qualified', 'overall_evaluation', 'create_time'];
        $types = array_fill(0, count($head), '1');

        foreach (array_keys($photoFields) as $field) {
            $head[] = $photoFields[$field];
            $keys[] = $field;
            $types[] = '2';
        }

        $image_array = [];
        $list = [];
        $cursor = $this->exportBaseQuery()
            ->where($where)
            ->order('r.id desc')
            ->cursor();

        foreach ($cursor as $row) {
            $rowArr = (array) $row;
            $item = $this->mapExportRow($rowArr, $unit_type_list);
            foreach (array_keys($photoFields) as $field) {
                $item[$field] = $this->prepareExportPhotoCell($rowArr[$field] ?? '', $image_array);
            }
            $list[] = $item;
        }

        $filename = '督导监测' . date('Y-m-d');
        CommonModel::outData($filename, $list, $head, $keys, $types, $image_array);
    }

    /**
     * 导出游标查询（含照片字段；一次只驻留一行）
     */
    private function exportBaseQuery()
    {
        $photoCols = implode(',', array_map(static function ($f) {
            return 'r.' . $f;
        }, array_keys($this->photoFieldLabels())));

        return Db::name('supervision_monitor')->alias('r')
            ->join('cmf_member m', 'm.id = r.user_id', 'LEFT')
            ->field('r.id,r.submitter_name,r.community_name,r.unit_name,r.unit_type,r.check_date,r.address,r.contact,r.contact_phone,'
                . 'r.has_waste,r.waste_correct,r.waste_flow,r.inner_qualified,r.need_rectify,r.point_qualified,r.manage_qualified,'
                . 'r.overall_evaluation,r.create_time,m.nickname,m.mobile,' . $photoCols);
    }

    /**
     * 与详情页「照片凭证」类型一致（多图字段导出首图；其余张数可在详情查看）
     */
    private function photoFieldLabels(): array
    {
        return [
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
    }

    /**
     * 多图字段取首张写入 Excel；其余张在详情页查看
     *
     * @return string 供 PhpSpreadsheet Drawing 使用的本地相对路径，失败返回空
     */
    private function prepareExportPhotoCell($stored, array &$image_array): string
    {
        $paths = SupervisionMonitorPhotos::urlsFromStored($stored);
        if ($paths === []) {
            return '';
        }

        $local = $this->resolveStoredImageToLocalFile($paths[0]);
        if ($local === '') {
            return '';
        }
        if (!in_array($local, $image_array, true)) {
            $image_array[] = $local;
        }

        return $local;
    }

    private function resolveStoredImageToLocalFile(string $storedPath): string
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $storedPath)) {
            $root = defined('CMF_ROOT') ? CMF_ROOT : root_path();
            foreach ([
                $root . 'public/upload/' . ltrim($storedPath, '/'),
                $root . 'public/' . ltrim($storedPath, '/'),
                $root . ltrim($storedPath, '/'),
            ] as $abs) {
                if (is_file($abs)) {
                    return $this->copyImageToExportTemp($abs, $storedPath);
                }
            }
        }

        $url = preg_match('#^https?://#i', $storedPath) ? $storedPath : cmf_get_asset_url($storedPath);
        $ctx = stream_context_create([
            'http' => ['timeout' => 20, 'follow_location' => 1],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $bin = @file_get_contents($url, false, $ctx);
        if ($bin === false || $bin === '') {
            return '';
        }

        return $this->writeExportTempBinary($bin, $storedPath, $url);
    }

    private function copyImageToExportTemp(string $absPath, string $hintPath): string
    {
        $bin = @file_get_contents($absPath);
        if ($bin === false || $bin === '') {
            return '';
        }

        return $this->writeExportTempBinary($bin, $hintPath, $absPath);
    }

    private function writeExportTempBinary(string $bin, string $hintPath, string $cacheKey): string
    {
        $ext = '.jpg';
        if (preg_match('/\.(png|jpe?g|gif|webp)(\?|#|$)/i', $hintPath, $m)) {
            $ext = '.' . strtolower($m[1]);
            if ($ext === '.jpeg') {
                $ext = '.jpg';
            }
        }
        $rel = 'upload/file/sm_exp_' . md5($cacheKey) . $ext;
        if (is_file($rel) && filesize($rel) > 0) {
            return $rel;
        }
        $dir = dirname($rel);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (@file_put_contents($rel, $bin) === false) {
            return '';
        }

        return $rel;
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
