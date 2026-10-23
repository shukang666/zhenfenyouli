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

use api\wxapp\service\DemoTestUserService;
use api\wxapp\model\CommonModel;
use app\admin\model\member\MemberModel;
use app\admin\model\ToolModel;
use think\Model;

class AccountLogModel extends ToolModel
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'user_account_log';

    // 关闭自动写入update_time字段
    protected $updateTime = false;

    /**
     * 根据条件修改
     * @param array $where
     * @param array $data
     * @return id|false
     */
    public function updateByWhere($where, $data)
    {
        $map = is_array($where) ? $where : ['id' => $where];
        $result = $this->where($map)->update($data);
        return (bool)$result;
    }

    public function getInfo($map, $page_size = 10, $id = 0, $field = 'a.*', $list_order = 'a.id desc')
    {

        $list = $this->alias('a')
            ->field($field . ',b.nickname,b.mobile,b.avatar,b.true_name')
            ->leftjoin('member b', 'a.user_id = b.id')
            ->where($map)->order($list_order)->paginate($page_size);

        foreach ($list as &$item) {
            $item = CommonModel::changeTrueNameFromNickname($item);
            $item['type_name'] = self::getAccountLogTypeName($item['account_type'], $item['type']);
            $item['account_type_name'] = self::getAccountTypeField($item['account_type'], 'name');
            $item['param'] = json_decode($item['param'], true);
            if ($item['type'] == 310 || $item['type'] == 311) {
                $withdraw_id = $item['param']['withdraw_id'] ?? 0;
                $withdraw_info = WithdrawModel::where('id',$withdraw_id)->json(['param'],1)->find();
                if (!empty($withdraw_info)) {
                    $item['withdraw_info'] = $withdraw_info;
//                    $item['withdraw_status_name'] = WithdrawModel::getStatusName($withdraw_info['status']);
                }
            }
        }
        return $id ? $list[0] ?? [] : $list;
    }


    static function getAccountTypeField($account_type = -1, $field = 'field')
    {
        $type_arr = [
            1 => ['field' => 'balance', 'name' => '余额'],// 101:充值，102：领红包，103：打赏，104分佣
            2 => ['field' => 'point', 'name' => '积分'],//201签到、202分享、203注册赠送
            3 => ['field' => 'shop_balance', 'name' => '商家余额'],//301充值送
            4 => ['field' => 'reward', 'name' => '佣金'],//301充值送
        ];
        if ($account_type == -1) {
            return $type_arr;
        } else {
            return $type_arr[$account_type][$field] ?? 'balance';
        }
    }

    /**
     * @param int $type 明细类型
     * @param int $account_type 账户类型 1余额 2积分 3商家余额
     * @return string|string[]|\string[][]
     */
    static function getAccountLogTypeName(int $account_type = 1, int $type = -1)
    {

        $type_arr = [
//            1 => [1 => '发红包', 2 => '置顶', 3 => '刷新', 4 => '查看手机号', 5 => '打赏',
//                6 => '商家入驻', 7 => '开通会员', 8 => '', 9 => '提现', 10 => '提现拒绝', 11 => '被打赏', 12 => '返佣'
//                , 13 => '充值', 14 => '领取红包', 106 => '提现成功'],
            2 => [200 => '拍照打卡',201 => '问卷获得',203 => '订单抵扣',204 => '订单取消', 205 => '积分扣除', 210 => '管理员增减', 221 => '报名', 222 => '点位巡查', 223 => '活动分享', 224 => '商家打卡', 225 => '社区活动打卡', 226 => '社区活动打卡记录删除', 227 => '垃圾打卡记录删除', 228 => '场景点位布置', 229 => '闲置交换支出', 230 => '闲置交换收入', 231 => '首次真分奖励', 232 => '1+1活动奖励'],//, 201 => '分享'
            3 => [300 => '商家收入', 310 => '提现', 311 => '提现拒绝',],
        ];

        $cur_type_name = $type_arr[$account_type];

        if ($type == -1) {
            return $cur_type_name;
        } else {
            return $cur_type_name[$type] ?? '';
        }
    }

    // 用户ID ，类型 （1：余额 2：绿色积分 3：消费积分） ，操作类型 ，改变的值
    static function add_account_log($user_id, $account_type, $type, $change, $param = [])
    {
        // 演示测试账号：正积分不入真实余额，见 DemoTestUserService
        if ((int)$account_type === 2 && $change > 0 && DemoTestUserService::shouldIsolatePoints((int)$user_id)) {
            return DemoTestUserService::addIsolatedPointLog((int)$user_id, (int)$type, (float)$change, is_array($param) ? $param : []);
        }

        $UserAccountLogModel = new AccountLogModel();
        $MemberModel = new MemberModel();
        $user_info = $MemberModel->where(['id' => $user_id])->find();
        if (empty($user_info)) return false;

        $field = self::getAccountTypeField($account_type);

        $change = round($change, 2);
        $old_amount = $user_info[$field];
        $left_amount = round($old_amount + $change, 2);
        $data[$field] = $left_amount;
        if ($type == 300) {
            $data['shop_balance_total'] =$user_info['shop_balance_total']+ $change;
        }
        $user_info->save($data);

        $log = [
            'user_id' => $user_id,
            'change_amount' => $change,
            'left_amount' => $left_amount,
            'type' => $type,
            'account_type' => $account_type,
            'create_time' => time(),
        ];

        if (!empty($param['remark'])) {
            $log['remark'] = $param['remark'];
            unset($param['remark']);
        }
        if (!empty($param['order_num'])) {
            $log['order_num'] = $param['order_num'];
            unset($param['order_num']);
        }
        if (!empty($param['from_uid'])) {
            $log['from_uid'] = $param['from_uid'];
            unset($param['from_uid']);
        }
        $log['param'] = json_encode($param, JSON_UNESCAPED_UNICODE);
        return $UserAccountLogModel->insert($log, true);
    }

}
