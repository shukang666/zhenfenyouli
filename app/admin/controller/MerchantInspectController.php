<?php

namespace app\admin\controller;

use api\wxapp\model\CommonModel;
use app\admin\model\member\MemberMerchantInspectModel;
use think\App;

class MerchantInspectController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new MemberMerchantInspectModel();
    }

    /**
     * 商户自治 - 列表
     */
    public function index()
    {
        $params = $this->request->param();
        $export = $this->request->param('export');
        $where = [];
        if (!empty($params['name'])) {
            $where[] = ['c.nickname', 'like', '%' . $params['name'] . '%'];
        }
        if (!empty($params['id'])) {
            $where[] = ['a.user_id', '=', $params['id']];
        }
        if (!empty($params['company'])) {
            $where[] = ['a.company', 'like', '%' . $params['company'] . '%'];
        }
        if (!empty($params['start_time'])) {
            $where[] = ['a.create_time', '>=', strtotime($params['start_time'] . ' 00:00')];
        }
        if (!empty($params['end_time'])) {
            $where[] = ['a.create_time', '<=', strtotime($params['end_time'] . ' 23:59')];
        }

        if ($export) {
            $list = $this->model->alias('a')
                ->join('member c', 'a.user_id = c.id')
                ->field('a.*, c.nickname, c.mobile')
                ->where($where)
                ->order('id desc')
                ->select();

            $this->outData($list);
        }

        $list = $this->model->alias('a')
            ->join('member c', 'a.user_id = c.id')
            ->field('a.*, c.nickname, c.mobile')
            ->where($where)
            ->order('id desc')
            ->paginate(10);

        $this->assign('params', $params);
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());

        return $this->fetch();
    }

    /**
     * 导出
     */
    public function outData($list)
    {
        $image_array = [];
        foreach ($list as &$item) {

            if (!empty($item['image_one'])) {
                $item['image_one'] = cmf_get_asset_url($item['image_one']);
                $image_one = file_get_contents($item['image_one']);
                $value = "upload/file/" . md5(time() . rand(1, 99999)) . ".png";
                file_put_contents($value, $image_one);
                $item['image_one'] = $value;
                $image_array[] = $value;
            }

            if (!empty($item['image_two'])) {
                $item['image_two'] = cmf_get_asset_url($item['image_two']);
                $image_two = file_get_contents($item['image_two']);
                $value = "upload/file/" . md5(time() . rand(1, 99999)) . ".png";
                file_put_contents($value, $image_two);
                $item['image_two'] = $value;
                $image_array[] = $value;
            }

            if (!empty($item['image_three'])) {
                $item['image_three'] = cmf_get_asset_url($item['image_three']);
                $image_three = file_get_contents($item['image_three']);
                $value = "upload/file/" . md5(time() . rand(1, 99999)) . ".png";
                file_put_contents($value, $image_three);
                $item['image_three'] = $value;
                $image_array[] = $value;
            }

            if (!empty($item['image_four'])) {
                $item['image_four'] = cmf_get_asset_url($item['image_four']);
                $image_four = file_get_contents($item['image_four']);
                $value = "upload/file/" . md5(time() . rand(1, 99999)) . ".png";
                file_put_contents($value, $image_four);
                $item['image_four'] = $value;
                $image_array[] = $value;
            }

            if (!empty($item['image_five'])) {
                $item['image_five'] = cmf_get_asset_url($item['image_five']);
                $image_five = file_get_contents($item['image_five']);
                $value = "upload/file/" . md5(time() . rand(1, 99999)) . ".png";
                file_put_contents($value, $image_five);
                $item['image_five'] = $value;
                $image_array[] = $value;
            }

            $item['create_time'] = empty($item['create_time']) ? '' : date('Y-m-d H:i:s', $item['create_time']);
        }

        $filename = '商户自治' . date('Y-m-d');
        $head = ['ID', '用户信息', '公司', '门头照片', '宣传品照片', '投放点位照片', '管理制度/收运协议照片', '桶内照片', '创建时间'];
        $keys = ['id', 'nickname', 'company', 'image_one', 'image_two', 'image_three', 'image_four', 'image_five', 'create_time'];
        $types = ['1', '1', '1', '2', '2', '2', '2', '2', '1'];
        CommonModel::outData($filename, $list, $head, $keys, $types, $image_array);
        exit;
    }

    /**
     * 删除
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }
        $this->model->where('id', $id)->delete();
        $this->success('删除成功');
    }
}
