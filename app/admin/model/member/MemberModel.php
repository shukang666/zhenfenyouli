<?php


namespace app\admin\model\member;


use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use api\wxapp\model\MemberLevelModel;
use api\wxapp\model\VipModel;
use think\Model;

class MemberModel extends Model
{

    protected $name = 'member';

    public function get_type()
    {
        return [1 => '用户', 2 => '管理员'];
    }


  public function getAvatarAttr( $name)
  {
      return $name ? cmf_get_asset_url($name) : '';
  }


    public static function setData($data){
//        $MemberLevelModel  = new MemberLevelModel();
        $MemberModel = new MemberModel();
        $data['avatar'] = cmf_get_asset_url($data['avatar']);
        $p_info = $MemberModel->where(['id'=>$data['pid']])->find();

        if($p_info){
            $p_info = CommonModel::changeTrueNameFromNickname($p_info);
            $data['p_info'] = $p_info['true_name'].'(ID:'.$p_info['id'].')';
        }else{
            $data['p_info'] = '';
        }
//        $data['level_name'] = $MemberLevelModel->where(['id'=>$data['level']])->value('level_name');
//        $data['group_name'] = CommonModel::getGroupName($data['group']);
        return $data;
    }

    // 获取用户的所有下级
    public static function getAllChild($pid): array
    {
        static $arr=array();  //第一次调用时初始化
        //通过邀请码查询当前邀请码下级用户
        $MemberModel = new MemberModel();
        $data = $MemberModel->field('id')->where(['pid'=>$pid,'status'=>1])->select()->toArray();
        //遍历获取信息，调用当前方法直至没有下级用户
        foreach ($data as $value) {
            $arr[] = $value['id'];
            self::getAllChild($value['id']);
        }
        return $arr;
    }


    public function getInfo($map,$page_size=10,$id=0,$field='a.*',$list_order='a.id desc')
    {

        $query = $this->alias('a')
            ->field($field)
            ->where($map)->order($list_order);
        if($page_size == -1){
            $list = $query->select();
        }else{
            $list = $query->paginate($page_size);
        }
        foreach ($list as &$item){
//            $item['type_name']=$this->get_type()[$item['type']];
//            $item = self::setData($item);
        }
        return $id ? $list[0] ?? [] : $list;
    }


    //获取某个字段值
    public static function getValueByField($user_id,$field){
        $MemberModel = new MemberModel();
        return $MemberModel->where(['id'=>$user_id])->value($field);
    }




    //上级的上级直接没有上级增加金币
    public static function addCommission($uid, $price, $order_num = 0, $type = 0)

    {
        $gold_rate = 900;
        $MemberModel = new MemberModel();
        //2）购买奖励金币：经销商购买，奖励给本人、合作店购买，奖励给上级经销商（下单数量*900=奖励金币）；
        $member = $MemberModel->where('id', $uid)->find();
        if ($type == 0) $type = $member['type'];//判断是否门店门店
        if ($type == 2) {//经销商购买，奖励给本人
            $MemberModel->update([
                'gold' => $member['gold'] + $price * $gold_rate,//（下单数量*900=奖励金币）
                'point' => $member['point'] + 1//积分加1
            ], ['id' => $member['id']]);
            CommonModel::add_user_point($member['id'], 9, 1, ['order_num' => $order_num]);
        } else if ($type == 1) {//普通用户增加积分
            $MemberModel->update([
                'point' => $member['point'] + 1//积分加1
            ], ['id' => $member['id']]);
            CommonModel::add_user_point($member['id'], 9, 1, ['order_num' => $order_num]);
        } else if ($type == 3) {//门店
            if (!empty($member['parent_id'])) {
                $pid = $member['parent_id'];
                $member_p = MemberModel::where('id', $pid)->find();
                if ($member_p['type'] == 1) {
                    $MemberModel->update([
                        'point' => $member_p['point'] + 1//积分加1
                    ], ['id' => $pid]);
                }
                CommonModel::add_user_point($member_p['id'], 9, 1, ['order_num' => $order_num]);
                if ($member_p['parent_id'] && $member_p['type'] == 2) {//上级是经销商
                    $MemberModel->addGold($member_p['id'], $price, $order_num, 3);
                }
            }

        }

        return 1;
    }




    //增加佣金和业绩
    public static function add_account($user_id, $money, $order_num)
    {


        $MemberModel = new MemberModel();
        $user = $MemberModel->where('id', $user_id)->find();
        if (empty($user['pid'])) return 0;
        $p_user_id = $user['pid'];
        $user_id = $user['id'];
        $p_user = $MemberModel->where('id', $p_user_id)->find();
        $vip_info = VipModel::where('id', $p_user['vip_id'])->find();
        if ($p_user && $p_user['vip_id'] > 0) {
            //一级分销
            //增加佣金
            AccountLogModel::add_account_log($p_user_id, 1, 100, $money * $vip_info['commission_rate1'] * 0.01, ['order_num' => $order_num, 'from_uid' => $user_id]);
            //增加积分
            AccountLogModel::add_account_log($p_user_id, 2, 200, $money * $vip_info['commission_rate1'] * 0.01, ['order_num' => $order_num, 'from_uid' => $user_id]);

            //二级分销
            $sp_user = $MemberModel->where('id', $p_user['pid'])->find();
            if (!empty($sp_user)) {
                if ($sp_user['vip_id'] == 0) return false;
                $vip_info = VipModel::where('id', $sp_user['vip_id'])->find();
                $sp_user_id = $sp_user['id'];
                //增加佣金
                AccountLogModel::add_account_log($sp_user_id, 1, 109, $money * $vip_info['commission_rate2'] * 0.01, ['order_num' => $order_num, 'from_uid' => $user_id]);
                //增加积分
                AccountLogModel::add_account_log($sp_user_id, 2, 209, $money * $vip_info['commission_rate2'] * 0.01, ['order_num' => $order_num, 'from_uid' => $user_id]);
                //无限级分销 增加贡献度
                $ssp_user = $MemberModel->field('id,pid')->where('id', $sp_user['pid'])->find();
                if (!empty($ssp_user)) {
                    self::add_contribute($ssp_user['id'], $money, $order_num, $user_id);
                }

            }
        }


    }

    //添加贡献度 无限级分销
    public static function add_contribute($user_id, $money, $order_num, $from_uid, $level = 1)
    {
        $MemberModel = new MemberModel();
        $ContributeModel = new ContributeModel();
        $user = $MemberModel->field('id,pid')->where('id', $user_id)->find();
        $Contribute = $ContributeModel->where('level', $level)->find();
        if ($user) {
            //增加业绩
            $price = $Contribute['ratio'] * $money * 0.01;
            AccountLogModel::add_account_log($user['id'], 3, 300, $price, ['order_num' => $order_num, 'from_uid' => $user_id]);
            if (!empty($user['pid'])) {
                //递归
                self::add_contribute($user['pid'], $money, $order_num, $from_uid, $level + 1);
            }
        }

        return true;
    }

}