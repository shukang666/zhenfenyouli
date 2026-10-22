<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 健康频道 - 新版可配置问卷
 */
class HealthSurveyController extends AdminBaseController
{
    public const FIELD_TYPES = [
        'text'     => '单行文本',
        'textarea' => '多行文本',
        'radio'    => '单选',
        'checkbox' => '多选',
        'select'   => '下拉选择',
        'number'   => '数字',
        'date'     => '日期',
        'rate'     => '评分(1-5)',
    ];

    public function index()
    {
        $params = $this->request->param();
        $where = [['delete_time', '=', 0]];
        $keyword = trim((string)$this->request->param('keyword', ''));
        if ($keyword !== '') {
            $where[] = ['title', 'like', '%' . $keyword . '%'];
        }
        $status = $this->request->param('status', '');
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }

        $list = Db::name('health_survey')
            ->where($where)
            ->order('list_order asc,id desc')
            ->paginate(15)
            ->each(function ($item) {
                $item['record_count'] = (int)Db::name('health_survey_record')
                    ->where('survey_id', $item['id'])
                    ->count();
                $item['field_count'] = (int)Db::name('health_survey_field')
                    ->where('survey_id', $item['id'])
                    ->count();
                return $item;
            });

        $list->appends($params);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('keyword', $keyword);
        $this->assign('status', $status);

        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $this->saveSurvey(0);
        }
        $this->assign('data', $this->emptySurvey());
        $this->assign('fields', []);
        $this->assign('field_types', self::FIELD_TYPES);

        return $this->fetch('edit');
    }

    public function edit()
    {
        $id = (int)$this->request->param('id', 0);
        $survey = $this->findSurvey($id);
        if ($this->request->isPost()) {
            $this->saveSurvey($id);
        }
        $fields = Db::name('health_survey_field')
            ->where('survey_id', $id)
            ->order('list_order asc,id asc')
            ->select()
            ->toArray();
        foreach ($fields as &$f) {
            $opts = json_decode((string)($f['options'] ?? ''), true);
            $f['options_text'] = is_array($opts) ? implode("\n", $opts) : '';
        }
        unset($f);

        $this->assign('data', $survey);
        $this->assign('fields', $fields);
        $this->assign('field_types', self::FIELD_TYPES);

        return $this->fetch();
    }

    public function delete()
    {
        $id = (int)$this->request->param('id', 0);
        Db::name('health_survey')->where('id', $id)->update([
            'delete_time' => time(),
            'update_time' => time(),
        ]);
        $this->success('已删除');
    }

    public function set_status()
    {
        $id = (int)$this->request->param('id', 0);
        $status = (int)$this->request->param('status', 0);
        Db::name('health_survey')->where('id', $id)->update([
            'status'      => $status ? 1 : 0,
            'update_time' => time(),
        ]);
        $this->success('设置成功');
    }

    /**
     * 某份问卷的提交记录
     */
    public function record()
    {
        $surveyId = (int)$this->request->param('survey_id', 0);
        $survey = $this->findSurvey($surveyId);
        $params = $this->request->param();
        $where = [['r.survey_id', '=', $surveyId]];
        $keyword = trim((string)$this->request->param('keyword', ''));
        if ($keyword !== '') {
            $where[] = ['m.nickname|m.mobile', 'like', '%' . $keyword . '%'];
        }

        $list = Db::name('health_survey_record')->alias('r')
            ->leftJoin('member m', 'm.id = r.user_id')
            ->where($where)
            ->field('r.*, m.nickname, m.mobile')
            ->order('r.id desc')
            ->paginate(15);

        $list->appends($params);
        $fields = Db::name('health_survey_field')
            ->where('survey_id', $surveyId)
            ->order('list_order asc,id asc')
            ->select()
            ->toArray();

        $this->assign('survey', $survey);
        $this->assign('fields', $fields);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $this->assign('keyword', $keyword);
        $this->assign('survey_id', $surveyId);

        return $this->fetch();
    }

    public function record_detail()
    {
        $id = (int)$this->request->param('id', 0);
        $row = Db::name('health_survey_record')->alias('r')
            ->leftJoin('member m', 'm.id = r.user_id')
            ->where('r.id', $id)
            ->field('r.*, m.nickname, m.mobile')
            ->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }
        $survey = $this->findSurvey((int)$row['survey_id']);
        $fields = Db::name('health_survey_field')
            ->where('survey_id', $row['survey_id'])
            ->order('list_order asc,id asc')
            ->select()
            ->toArray();
        $answers = json_decode((string)($row['answers'] ?? ''), true);
        if (!is_array($answers)) {
            $answers = [];
        }
        $lines = [];
        foreach ($fields as $f) {
            $key = $f['field_key'];
            $val = $answers[$key] ?? '';
            if (is_array($val)) {
                $val = implode('、', $val);
            }
            $lines[] = [
                'label' => $f['label'],
                'value' => (string)$val,
            ];
        }

        $this->assign('survey', $survey);
        $this->assign('row', $row);
        $this->assign('lines', $lines);

        return $this->fetch();
    }

    public function export_record()
    {
        $surveyId = (int)$this->request->param('survey_id', 0);
        $survey = $this->findSurvey($surveyId);
        $fields = Db::name('health_survey_field')
            ->where('survey_id', $surveyId)
            ->order('list_order asc,id asc')
            ->select()
            ->toArray();

        $where = [['r.survey_id', '=', $surveyId]];
        $keyword = trim((string)$this->request->param('keyword', ''));
        if ($keyword !== '') {
            $where[] = ['m.nickname|m.mobile', 'like', '%' . $keyword . '%'];
        }

        $rows = Db::name('health_survey_record')->alias('r')
            ->leftJoin('member m', 'm.id = r.user_id')
            ->where($where)
            ->field('r.*, m.nickname, m.mobile')
            ->order('r.id desc')
            ->select()
            ->toArray();

        if (empty($rows)) {
            $this->error('暂无数据可导出');
        }

        $head = ['记录ID', '用户昵称', '手机号', '提交时间'];
        $keys = ['id', 'nickname', 'mobile', 'create_time_fmt'];
        foreach ($fields as $f) {
            $head[] = $f['label'];
            $keys[] = 'f_' . $f['field_key'];
        }

        $export = [];
        foreach ($rows as $r) {
            $answers = json_decode((string)($r['answers'] ?? ''), true);
            if (!is_array($answers)) {
                $answers = [];
            }
            $item = [
                'id'              => $r['id'],
                'nickname'        => $r['nickname'] ?? '',
                'mobile'          => $r['mobile'] ?? '',
                'create_time_fmt' => date('Y-m-d H:i:s', (int)$r['create_time']),
            ];
            foreach ($fields as $f) {
                $k = 'f_' . $f['field_key'];
                $val = $answers[$f['field_key']] ?? '';
                if (is_array($val)) {
                    $val = implode('、', $val);
                }
                $item[$k] = $val;
            }
            $export[] = $item;
        }

        $name = preg_replace('/[^\x{4e00}-\x{9fa5}a-zA-Z0-9_-]/u', '', $survey['title']);
        if ($name === '') {
            $name = '问卷';
        }
        $this->outdata($name . '_提交记录_' . date('Ymd'), $export, $head, $keys);
    }

    private function emptySurvey(): array
    {
        return [
            'id'           => 0,
            'title'        => '',
            'description'  => '',
            'status'       => 1,
            'allow_repeat' => 0,
            'list_order'   => 0,
        ];
    }

    private function findSurvey(int $id): array
    {
        $survey = Db::name('health_survey')
            ->where('id', $id)
            ->where('delete_time', 0)
            ->find();
        if (empty($survey)) {
            $this->error('问卷不存在');
        }

        return $survey;
    }

    private function saveSurvey(int $id): void
    {
        $title = trim((string)$this->request->param('title', ''));
        if ($title === '') {
            $this->error('请填写问卷标题');
        }
        $now = time();
        $surveyData = [
            'title'        => $title,
            'description'  => trim((string)$this->request->param('description', '')),
            'status'       => (int)$this->request->param('status', 1) ? 1 : 0,
            'allow_repeat' => (int)$this->request->param('allow_repeat', 0) ? 1 : 0,
            'list_order'   => (int)$this->request->param('list_order', 0),
            'update_time'  => $now,
        ];

        if ($id > 0) {
            Db::name('health_survey')->where('id', $id)->update($surveyData);
        } else {
            $surveyData['create_time'] = $now;
            $surveyData['delete_time'] = 0;
            $id = (int)Db::name('health_survey')->insertGetId($surveyData);
        }

        $fields = $this->request->param('fields/a', []);
        $keepIds = [];
        $order = 0;
        foreach (array_values($fields) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = trim((string)($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $fieldType = (string)($row['field_type'] ?? 'text');
            if (!isset(self::FIELD_TYPES[$fieldType])) {
                $fieldType = 'text';
            }
            $optionsRaw = trim((string)($row['options_text'] ?? ''));
            $options = [];
            if ($optionsRaw !== '') {
                foreach (preg_split('/\r\n|\r|\n/', $optionsRaw) as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $options[] = $line;
                    }
                }
            }
            $fieldId = (int)($row['id'] ?? 0);
            $fieldKey = trim((string)($row['field_key'] ?? ''));
            if ($fieldKey === '') {
                $fieldKey = 'f' . $id . '_' . substr(md5($label . microtime(true)), 0, 8);
            }

            $payload = [
                'survey_id'  => $id,
                'field_key'  => $fieldKey,
                'label'      => $label,
                'field_type' => $fieldType,
                'options'    => json_encode($options, JSON_UNESCAPED_UNICODE),
                'placeholder'=> trim((string)($row['placeholder'] ?? '')),
                'required'   => !empty($row['required']) ? 1 : 0,
                'list_order' => $order++,
            ];

            if ($fieldId > 0) {
                $exists = Db::name('health_survey_field')
                    ->where('id', $fieldId)
                    ->where('survey_id', $id)
                    ->find();
                if ($exists) {
                    Db::name('health_survey_field')->where('id', $fieldId)->update($payload);
                    $keepIds[] = $fieldId;
                    continue;
                }
            }
            $newId = (int)Db::name('health_survey_field')->insertGetId($payload);
            $keepIds[] = $newId;
        }

        if (!empty($keepIds)) {
            Db::name('health_survey_field')
                ->where('survey_id', $id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        } else {
            Db::name('health_survey_field')->where('survey_id', $id)->delete();
        }

        $this->success('保存成功', url('HealthSurvey/edit', ['id' => $id]));
    }

    public function outdata($name = '', $data = [], $head = [], $keys = [])
    {
        if (empty($data)) {
            $this->error('暂无数据可导出');
        }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $count = count($head);
        for ($i = 0; $i < $count; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $head[$i]);
        }
        foreach ($data as $key => $item) {
            for ($i = 0; $i < $count; $i++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue($col . ($key + 2), $item[$keys[$i]] ?? '');
            }
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
