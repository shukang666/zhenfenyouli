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

use OpenApi\Tests\Annotations\ValidateRelationsTest;
use think\Model;

class QuestionModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'xx_question';

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

    public function getInfo($map,$page_size=10,$id=0,$field='a.*',$list_order='a.id',$answer_id=0)
    {

        $query = $this->alias('a')
            ->field($field)
            ->where($map)->order($list_order);
        $list = $page_size == -1 ?  $query->select() : $query->paginate($page_size);
        $list = getFullData($list);
        $QuestionOptionModel = new QuestionOptionModel();
        $AnswerLogModel = new AnswerLogModel();
        foreach ($list as &$item){
            $item['title'] = cmf_replace_content_file_url(htmlspecialchars_decode($item['title']));
            $item['desc'] = cmf_replace_content_file_url(htmlspecialchars_decode($item['desc']));
            $item['type_name'] = self::getType($item['type']);
            $map = [];
            $map[] = ['a.q_id','=',$item['id']];
            $option_list = $QuestionOptionModel->getInfo($map,-1);

            $map = [];
            $map[] = ['answer_id','=',$answer_id];
            $map[] = ['q_id','=',$item['id']];
            $answer_log = $AnswerLogModel->where($map)->find();
            $item['user_answer'] = $answer_log['user_answer'] ?? '';
            $item['option_list'] = QuestionOptionModel::getSelected($option_list,$answer_log);



        }
        return $id ? $list[0] ?? [] : $list;
    }

    public static function getType($type=-1,$is_type5=0){
       // 1单选 2多选 3判断 4问答 5材料题
        if($is_type5 == 0){
            $type_arr = [1=>'单选题',2=>'多选题'];//,4=>'判断',3=>'填空题'
        }else{
            $type_arr = [1=>'单选题',2=>'多选题'];//,3=>'填空题',4=>'判断',3=>'填空题',2=>'多选题'
        }
        if($type == -1){
            return $type_arr;
        }else{
            return $type_arr[$type] ?? '';
        }
    }



}
