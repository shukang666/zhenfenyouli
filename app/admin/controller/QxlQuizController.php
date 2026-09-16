<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 七贤岭考试管理
 * 题库管理 + 答题记录（独立于垃圾分类的 QuizController）
 */
class QxlQuizController extends AdminBaseController
{
    /**
     * 题库管理
     */
    public function index()
    {
        $params = $this->request->param();
        $where = [];

        // 状态筛选
        $status = $this->request->param('status', '');
        if ($status !== '') {
            $where[] = ['status', '=', intval($status)];
        }

        // 关键词搜索
        $keyword = $this->request->param('keyword', '');
        if (!empty($keyword)) {
            $where[] = ['title', 'like', '%' . trim($keyword) . '%'];
        }

        $list = Db::name('qxl_quiz_question')
            ->where($where)
            ->order('list_order asc, id desc')
            ->paginate(15);

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('status', $status);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 添加题目
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['title']) || empty($data['correct_option'])) {
                $this->error('题目内容和正确答案不能为空');
            }
            // C/D 选项可为空
            $data['option_c'] = $data['option_c'] ?? '';
            $data['option_d'] = $data['option_d'] ?? '';
            $data['create_time'] = time();
            $data['update_time'] = time();
            unset($data['id']);

            $id = Db::name('qxl_quiz_question')->insertGetId($data);
            if ($id) {
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        }
        $this->assign('data', [
            'title' => '',
            'option_a' => '',
            'option_b' => '',
            'option_c' => '',
            'option_d' => '',
            'correct_option' => '',
            'list_order' => 0,
        ]);
        return $this->fetch();
    }

    /**
     * 编辑题目
     */
    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        $question = Db::name('qxl_quiz_question')->where('id', $id)->find();
        if (empty($question)) {
            $this->error('题目不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['title']) || empty($data['correct_option'])) {
                $this->error('题目内容和正确答案不能为空');
            }
            $data['option_c'] = $data['option_c'] ?? '';
            $data['option_d'] = $data['option_d'] ?? '';
            $data['update_time'] = time();
            unset($data['id']);

            Db::name('qxl_quiz_question')->where('id', $id)->update($data);
            $this->success('更新成功');
        }

        $this->assign('data', $question);
        return $this->fetch('add');
    }

    /**
     * 删除题目
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        Db::name('qxl_quiz_question')->where('id', $id)->delete();
        $this->success('删除成功');
    }

    /**
     * 批量删除
     */
    public function delete_all()
    {
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('请选择要删除的选项');
        }
        Db::name('qxl_quiz_question')->whereIn('id', $ids)->delete();
        $this->success('删除成功');
    }

    /**
     * 排序
     */
    public function listOrder()
    {
        $model = Db::name('qxl_quiz_question');
        parent::listOrders($model);
        $this->success('排序更新成功');
    }

    /**
     * 设置状态
     */
    public function setStatus()
    {
        $params = $this->request->param();
        Db::name('qxl_quiz_question')
            ->where('id', $params['id'])
            ->update(['status' => $params['status']]);
        $this->success('设置成功');
    }

    /**
     * 答题记录
     */
    public function record()
    {
        $params = $this->request->param();
        $where = [];

        $keyword = $this->request->param('keyword', '');
        if (!empty($keyword)) {
            $where[] = ['m.nickname|m.mobile', 'like', '%' . trim($keyword) . '%'];
        }

        $date = $this->request->param('date', '');
        if (!empty($date)) {
            $where[] = ['l.quiz_date', '=', $date];
        }

        $list = Db::name('qxl_quiz_log')->alias('l')
            ->join('cmf_member m', 'm.id = l.user_id', 'LEFT')
            ->field('l.*, m.nickname, m.mobile')
            ->where($where)
            ->order('l.id desc')
            ->paginate(15);

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('keyword', $keyword);
        $this->assign('date', $date);
        return $this->fetch('record');
    }

    /**
     * 导出答题记录
     */
    public function export_record()
    {
        $where = [];
        $keyword = $this->request->param('keyword', '');
        if (!empty($keyword)) {
            $where[] = ['m.nickname|m.mobile', 'like', '%' . trim($keyword) . '%'];
        }
        $date = $this->request->param('date', '');
        if (!empty($date)) {
            $where[] = ['l.quiz_date', '=', $date];
        }

        $list = Db::name('qxl_quiz_log')->alias('l')
            ->join('cmf_member m', 'm.id = l.user_id', 'LEFT')
            ->field('l.id, m.nickname, m.mobile, l.quiz_date, l.total_count, l.correct_count, l.point, l.create_time')
            ->where($where)
            ->order('l.id desc')
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
        }

        $name = '七贤岭答题记录_' . date('Ymd');
        $head = ['ID', '昵称', '手机号', '答题日期', '总题数', '答对数', '获得积分', '提交时间'];
        $keys = ['id', 'nickname', 'mobile', 'quiz_date', 'total_count', 'correct_count', 'point', 'create_time'];

        $this->outdata($name, $list, $head, $keys);
    }

    /**
     * 导出excel辅助方法
     */
    public function outdata($name = '', $data = [], $head = [], $keys = [])
    {
        if (empty($data)) {
            $this->error('暂无数据可导出');
            return;
        }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $count = count($head);
        for ($i = 65; $i < $count + 65; $i++) {
            $sheet->setCellValue(strtoupper(chr($i)) . '1', $head[$i - 65]);
        }
        foreach ($data as $key => $item) {
            for ($i = 65; $i < $count + 65; $i++) {
                $sheet->setCellValue(strtoupper(chr($i)) . ($key + 2), $item[$keys[$i - 65]] ?? '');
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
