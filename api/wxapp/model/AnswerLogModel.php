<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace api\wxapp\model;

use api\wxapp\service\ClockLearnExamService;
use think\Model;

class AnswerLogModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'xx_answer_log';

    // 关闭自动写入update_time字段
    protected $updateTime = false;

    /**
     * 根据条件修改
     * @param array $where
     * @param array $data
     * @return id|false
     */
    public function updateByWhere($where,$data)
    {
        $map = is_array($where) ? $where :  ['id'=>$where];
        $result = $this->where($map)->update($data);
        return (bool)$result;
    }

    public function getInfo($map,$page_size=10,$id=0,$field='a.*',$list_order='a.list_num')
    {

        $query = $this->alias('a')
            ->field($field)
            ->where($map)->order($list_order);
        $list = $page_size == -1 ?  $query->select() : $query->paginate($page_size);
        $list = getFullData($list);
        $QuestionModel = new QuestionModel();
        $QuestionOptionModel = new QuestionOptionModel();
        $answer_status_name = [1=>'正确',2=>'错误',3=>'未回答'];
        foreach ($list as &$item){
            $qid = (int)($item['q_id'] ?? 0);
            $synthetic = ClockLearnExamService::syntheticQuizMeta($qid);
            if ($synthetic !== null) {
                $item['q_title'] = '【学考一体】' . ($synthetic['title'] ?? '');
                $item['desc'] = '';
                $item['true_answer'] = $synthetic['true_answer'] ?? ($item['true_answer'] ?? '');
                $optionList = [];
                foreach ($synthetic['options'] ?? [] as $opt) {
                    $optionList[] = [
                        'letter' => $opt['key'] ?? '',
                        'option' => $opt['text'] ?? '',
                    ];
                }
                $item['option_list'] = $optionList;
            } else {
                $q_info = $QuestionModel->where(['id' => $qid])->find();
                $item['q_title'] = cmf_replace_content_file_url(htmlspecialchars_decode($q_info['title'] ?? ''));
                $item['desc'] = cmf_replace_content_file_url(htmlspecialchars_decode($q_info['desc'] ?? ''));
                $map = [];
                $map[] = ['a.q_id', '=', $qid];
                $item['option_list'] = $QuestionOptionModel->getInfo($map, -1);
            }
        }
        return $id ? $list[0] ?? [] : $list;
    }



}
