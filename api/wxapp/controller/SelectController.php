<?php

namespace api\wxapp\controller;

use app\admin\model\basic\SelectModel;
use app\admin\model\member\MemberModel;

class SelectController extends AuthController
{
    /**
     * 选择列表
     * @OA\Post(
     *tags={"选择"},
     *path="/wxapp/select/get_select_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="1:区域列表，2：垃圾打卡 ",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="pid",description="上级id ",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_select_list(SelectModel $selectModel)
    {
        $type = $this->request->param('type', 1);
        $pid = $this->request->param('pid', 0);
        $where = [];
        if (empty($type)) $this->error('type不能为空');
        $where[] = ['pid', '=', $pid];
        $where[] = ['type', '=', $type];
        $list = $selectModel->where('status', 1)
            ->where($where)
            ->withAttr('waste_thumb', function ($value) {
                return cmf_get_asset_url($value);
            })
            ->field('id,name,waste_thumb')
            ->order('list_order ')
            ->select();

        foreach ($list as &$item) {
            $item['child'] = $selectModel ->field('id,name')
                ->where('pid', $item['id'])->select();
        }
        $this->success('请求成功', $list);
    }

}