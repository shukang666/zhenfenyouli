<?php

namespace api\wxapp\controller;

use think\facade\Db;

/**
 * 健康频道新版问卷（小程序）
 */
class HealthSurveyController extends AuthController
{
    /**
     * 问卷详情 + 字段定义
     */
    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        if ($id <= 0) {
            $this->error('参数错误');
        }
        $survey = Db::name('health_survey')
            ->where('id', $id)
            ->where('delete_time', 0)
            ->where('status', 1)
            ->find();
        if (empty($survey)) {
            $this->error('问卷不存在或已下线');
        }

        $fields = Db::name('health_survey_field')
            ->where('survey_id', $id)
            ->order('list_order asc,id asc')
            ->select()
            ->toArray();

        foreach ($fields as &$f) {
            $opts = json_decode((string)($f['options'] ?? ''), true);
            $f['options'] = is_array($opts) ? $opts : [];
            unset($f['survey_id']);
        }
        unset($f);

        $submitted = false;
        if ((int)$survey['allow_repeat'] !== 1 && $this->user_id > 0) {
            $submitted = (bool)Db::name('health_survey_record')
                ->where('survey_id', $id)
                ->where('user_id', $this->user_id)
                ->value('id');
        }

        $this->success('请求成功', [
            'id'            => (int)$survey['id'],
            'title'         => (string)$survey['title'],
            'description'   => (string)$survey['description'],
            'allow_repeat'  => (int)$survey['allow_repeat'],
            'fields'        => $fields,
            'has_submitted' => $submitted,
        ]);
    }

    /**
     * 提交答卷
     */
    public function submit()
    {
        $surveyId = (int)$this->request->param('survey_id', 0);
        if ($surveyId <= 0) {
            $this->error('参数错误');
        }
        if ($this->user_id <= 0) {
            $this->error('请先登录');
        }

        $survey = Db::name('health_survey')
            ->where('id', $surveyId)
            ->where('delete_time', 0)
            ->where('status', 1)
            ->find();
        if (empty($survey)) {
            $this->error('问卷不存在或已下线');
        }

        if ((int)$survey['allow_repeat'] !== 1) {
            $exists = Db::name('health_survey_record')
                ->where('survey_id', $surveyId)
                ->where('user_id', $this->user_id)
                ->value('id');
            if ($exists) {
                $this->error('您已提交过本问卷');
            }
        }

        $answersParam = $this->request->param('answers');
        if (is_string($answersParam)) {
            $answers = json_decode($answersParam, true);
        } else {
            $answers = is_array($answersParam) ? $answersParam : [];
        }
        if (!is_array($answers)) {
            $this->error('答案格式错误');
        }

        $fields = Db::name('health_survey_field')
            ->where('survey_id', $surveyId)
            ->order('list_order asc,id asc')
            ->select()
            ->toArray();

        $normalized = [];
        foreach ($fields as $f) {
            $key = $f['field_key'];
            $val = $answers[$key] ?? null;
            if ((int)$f['required'] === 1) {
                if ($val === null || $val === '' || (is_array($val) && count($val) === 0)) {
                    $this->error('请完成：' . $f['label']);
                }
            }
            if ($val === null || $val === '') {
                continue;
            }
            $type = $f['field_type'];
            if ($type === 'checkbox') {
                if (!is_array($val)) {
                    $val = [$val];
                }
                $normalized[$key] = array_values(array_map('strval', $val));
            } elseif ($type === 'number' || $type === 'rate') {
                $normalized[$key] = (string)$val;
            } else {
                $normalized[$key] = is_array($val) ? implode(',', $val) : trim((string)$val);
            }
        }

        Db::name('health_survey_record')->insert([
            'survey_id'   => $surveyId,
            'user_id'     => $this->user_id,
            'answers'     => json_encode($normalized, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
        ]);

        $this->success('提交成功，感谢您的参与');
    }
}
