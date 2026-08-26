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

class AnswerModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'xx_answer';

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

    public function getInfo($map,$page_size=10,$id=0,$field='a.*',$list_order='a.id desc')
    {

        $query = $this->alias('a')
            ->field($field.',b.true_name,b.mobile,b.nickname')
            ->join('member b','a.user_id = b.id')
//            ->join('school_cate c','a.exam_id = c.id')
//            ->leftjoin('school_cate d','a.id = d.cate_id')
            ->where($map)->order($list_order);
        $list = $page_size == -1 ?  $query->select() : $query->paginate($page_size);
        $list = getFullData($list);
        foreach ($list as &$item){
            $item = CommonModel::changeTrueNameFromNickname($item);
            $item->problem_name=  ProblemModel::where('id', $item['exam_id'])->value('name');

            if(!empty($item['total_num'])){
                $item['right_per'] = round($item['right_num']/$item['total_num']*100,2);
                $item['wrong_num'] = $item['total_num']-$item['right_num'];
            }else{
                $item['right_per'] = 0;
            }
        }

        return $id ? $list[0] ?? [] : $list;
    }



}
