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

use think\Model;

class QuestionOptionModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'xx_question_option';

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

    public function getInfo($map,$page_size=10,$id=0,$field='a.*',$list_order='a.letter')
    {

        $query = $this->alias('a')
            ->field($field)
            ->where($map)->order($list_order);
        $list = $page_size == -1 ?  $query->select() : $query->paginate($page_size);
        $list = getFullData($list);

        foreach ($list as &$item){
            $item['option'] = cmf_replace_content_file_url(htmlspecialchars_decode($item['option']));
            $item['selected'] = false;
        }
        return $id ? $list[0] ?? [] : $list;
    }

    public static function getLetter(){
        return ['A','B','C','D','E','F','G','H','I'];
    }

    public static function getSelected($option_list,$answer_log){
        if(!empty($answer_log['option_id'])){
            $option_id_arr = explode(',',$answer_log['option_id']);
            foreach ($option_list as &$item){
                if(in_array($item['id'],$option_id_arr)){
                    $item['selected'] = true;
                }
            }
        }
        return $option_list;
    }



}
