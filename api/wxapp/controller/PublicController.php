<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\member\MemberModel;
use cmf\lib\Storage;
use Mpdf\Mpdf;
use plugins\ali_sms\AliSmsPlugin;
use plugins\tencentcloud_sms\TencentcloudSmsPlugin;
use think\facade\Db;
use think\facade\Log;
use cmf\controller\RestBaseController;
use think\Model;
use wxapp\aes\WXBizDataCrypt;
use think\Validate;
use api\wxapp\model\CommonModel;
use cmf\lib\Upload;
use think\Image;

class PublicController extends AuthController
{


    /**
     * @OA\Post(
     *     tags={"小程序-登录"},
     *     summary="小程序静默登录",
     *     path="/wxapp/public/wxapplogin",
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     */
    public function wxapplogin()
    {

        $code = $this->request->param('code');
        $vcode = $this->request->param('vcode');
        if (empty($code)) $this->error('code不能为空');
        $appId = config('wxapp.appid');
        $appSecret = config('wxapp.secret');
        if (empty($appId)) {
            $this->error('appid不存在');
        }
        $response = cmf_curl_get("https://api.weixin.qq.com/sns/jscode2session?appid=$appId&secret=$appSecret&js_code=$code&grant_type=authorization_code");
        $response = json_decode($response, true);
        if (!empty($response['errcode'])) {
            $this->error('授权失败，请重新授权!');
        }
        $openid = $response['openid'];
        $member = new MemberModel();
        $findUserInfo = $member->where('openid', $openid)->find();
        if (empty($findUserInfo)) {
            $id = $member->insert([
                'openid' => $openid,
                'create_time' => time(),
            ], true);
            $member->where('id', $id)->update([
                'true_name' => '微信用户_' . $id,
                'nickname' => '微信用户_' . $id,
                'avatar' => !empty(cmf_get_option('set_config')['app_logo']) ? cmf_get_asset_url(cmf_get_option('set_config')['app_logo']) : 'https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132',
            ]);
            $user = $member->find($id);
            $this->success("登录成功!", $user);
        } else {
            $this->success("登录成功!", $findUserInfo);
        }


    }


    /**
     * 账号密码登录
     * @OA\Post(
     *tags={"小程序-登录"},
     *path="/wxapp/public/user_login",
     *@OA\Parameter(name="mobile",description="账号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="password",description="密码",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function user_login()
    {
        $mobile = $this->request->param('mobile');
        $password = $this->request->param('password');
        $memberModel = new MemberModel();
        $member = $memberModel->where('mobile', $mobile)->find();
        if (empty($member)) $this->error('账号不存在');
        if ($password != $member['password']) $this->error('密码错误');
        $this->success('登录成功', $member);
    }


    /**
     * @OA\Post(
     *     tags={"小程序-登录"},
     *     summary="h5授权登录",
     *     path="/wxapp/public/h5_login",
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="state",
     *         in="query",
     *         description="state",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Parameter(
     *         name="vcode",
     *         in="query",
     *         description="推广码，有就传",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function h5_login()
    {
        $code = $this->request->param('code');

        $state = $this->request->param('state');

        $state_arr = explode('/', $state);


        $http = $state_arr[0] . '//';
        $host = $state_arr[2];
        $vcode = end($state_arr);
        $login_info = $this->getH5LoginInfo($code, $vcode);

        if ($login_info['code'] == 1) {
            $user = $login_info['user'];
            header('Location:' . $http . $host . '/h5/#/pages/public/login?openid=' . $user['openid']);
        } else {
            $this->error('登陆失败', $login_info);
        }
    }

    public function getH5LoginInfo($code, $vcode)
    {

        $appid = config('wxapp.appid');
        $secret = config('wxapp.secret');

        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $secret . "&code=" . $code . "&grant_type=authorization_code";
        $return = curl_get($url);
        $data = json_decode($return, true);
        if (!isset($data['errcode'])) {
            $h5_access_token = $data['access_token'];
            $openid = $data['openid'];

            $user_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $h5_access_token . '&openid=' . $openid . '&lang=zh_CN';
            $user_return = curl_get($user_url);
            $user_data = json_decode($user_return, true);

            if (!isset($data['errcode'])) {

                $userId = $this->addUser($openid, $vcode, '', $user_data);

                $user = Db::name('member')->field('id,nickname,avatar,mobile,openid')->where('id', $userId)->find();
                return ['code' => 1, 'user' => $user];
            } else {
                return ['code' => $data['errcode'], 'msg' => $data['errmsg']];
            }
        } else {
            return ['code' => $data['errcode'], 'msg' => $data['errmsg']];
        }
    }

    public function addUser($openid, $vcode, $unionid = '', $user_data = [])
    {

        $MemberModel = new MemberModel();

        if (!empty($unionid)) {
            $isUser = $MemberModel->where('unionid', $unionid)->find();
        } else {
            $isUser = $MemberModel->where('openid', $openid)->find();
        }

        $data_i = [];
        $data_i['last_login_time'] = time();
        $data_i['openid'] = $openid;
        $data_i['unionid'] = $unionid;

        $pid = 0;
        $spid = 0;
        if (!empty($vcode) && $vcode != 'undefined') {
            $p_info = $MemberModel->field('id,pid')->where(['vcode' => $vcode])->find();

            if (!empty($p_info)) {
                $pid = $p_info['id'];
                $spid = $MemberModel->where(['id' => $pid])->value('pid');
            }
        }

        if ($isUser) {
            $userId = $isUser['id'];
            if ($isUser['status'] == 0) {
                $data_i['status'] = 1;
                if (empty($isUser['vcode'])) {
                    $data_i['vcode'] = CommonModel::get_vcode();
                    $data_i['pid'] = $pid;
                    $data_i['spid'] = $spid;
                }
            }
            $isUser->save($data_i);
        } else {

            $data_i['create_time'] = time();
            $data_i['status'] = 1;
            $data_i['vcode'] = CommonModel::get_vcode();
            $data_i['pid'] = $pid;
            $data_i['spid'] = $spid;
            if (!empty($user_data)) {
                $data_i['nickname'] = $user_data['nickname'];
                $data_i['avatar'] = $user_data['headimgurl'];
            }
            $userId = $MemberModel->insertGetId($data_i);
            if (empty($user_data)) {
                CommonModel::updateUserSn($userId);
            }
        }

        return $userId;
    }

    /**
     * @OA\Post(
     *     tags={"小程序-登录"},
     *     summary="微信授权获取手机号",
     *     path="/wxapp/public/decrypt_phone",
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="encrypted_data",
     *         in="query",
     *         description="encrypted_data",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="iv",
     *         in="query",
     *         description="iv",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *        @OA\Parameter(
     *         name="vcode",
     *         in="query",
     *         description="vcode",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *        @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="分享的用户id",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function decrypt_phone()
    {
        $code = $this->request->param('code');
        $encrypted_data = $this->request->param('encrypted_data');
        $user_id = $this->request->param('user_id');
        $active_id = $this->request->param('active_id');
        $iv = $this->request->param('iv');
        $vcode = $this->request->param('vcode');
        if (empty($code) || empty($encrypted_data) || empty($iv)) {
            $this->error('数据不能为空');
        }

        $appId = config('wxapp.appid');
        $appSecret = config('wxapp.secret');

        if (empty($appId)) {
            $this->error('appid不存在');
        }

        $response = cmf_curl_get("https://api.weixin.qq.com/sns/jscode2session?appid=$appId&secret=$appSecret&js_code=$code&grant_type=authorization_code");

        $response = json_decode($response, true);
        if (!empty($response['errcode'])) {
            $this->error('登陆失败!', $response);
        }

        $openid = $response['openid'];
        $sessionKey = $response['session_key'];
        $unionid = $response['unionid'] ?? '';

        $pc = new WXBizDataCrypt($appId, $sessionKey);

        $errCode = $pc->decryptData($encrypted_data, $iv, $wxUserData);

        if ($errCode != 0) {
            $this->error($errCode);
        }

        $user_phone = $wxUserData['purePhoneNumber'];

        $MemberModel = new MemberModel();
        $map = [];
        $map[] = ['mobile', '=', $user_phone];
        $is = $MemberModel->where($map)->find();

        if (!empty($is)) {
            $MemberModel->where($map)->save(['openid' => $openid, 'unionid' => $unionid]);
        } else {
            //添加
            $userId = $this->addUser($openid, $vcode, $unionid);
            $MemberModel->where(['openid' => $openid])->save(['mobile' => $user_phone]);

            //新注册用户且存在分享用户，分享用户增加积分
            if(!empty($user_id)){
                $setting = cmf_get_option('set_config');
                $carbon = $setting['share_point'];
                AccountLogModel::add_account_log($user_id, 2, 223, $carbon, ['new_user_id' => $userId]);


//                $active_info = ActiveModel::where('id',$active_id)->find();
//                if(!empty($active_info) && $active_info['type'] == 1 && $active_info['share_point'] > 0){
//                    $carbon = $active_info['share_point'];
//                    AccountLogModel::add_account_log($user_id, 2, 223, $carbon, ['new_user_id' => $userId]);
//                }
            }
        }
        $this->success('获取成功', CommonModel::getUserByOpenid($openid));

    }


    /**
     * @OA\Post(
     *     tags={"小程序-登录"},
     *     summary="重置密码",
     *     path="/wxapp/public/reset_password",
     *     @OA\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="密码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="验证码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function reset_password()
    {
        $code = $this->request->param('code');
        $password = $this->request->param('password');
        $mobile = $this->request->param('mobile');

        if (empty($mobile) || empty($code)) {
            $this->error('不能为空');
        }

        if (empty($password) || strlen($password) < 6) {
            $this->error('密码要大于6位');
        }

        $res = cmf_check_verification_code($mobile, $code, true);

        if (!empty($res)) {
            $this->error($res);
        }

        $Model = new MemberModel();

        $where = [];
        $where[] = ['mobile', '=', $mobile];
        $user = $Model->where($where)->find();

        if (!empty($user)) {
            $Model->where(['id' => $user['id']])->save(['password' => cmf_password($password)]);
            $this->success('修改成功');
        } else {
            //注册
            $this->error('您还未注册，请先注册');
        }

    }

    /**
     * 短信验证码登录
     * @OA\Post(
     *     tags={"小程序-登录"},
     *     path="/wxapp/public/mobile_login",
     *     @OA\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="验证码code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function mobile_login()
    {
        $password = $this->request->param('password');
        $mobile = $this->request->param('mobile');
        $code = $this->request->param('code');

        if (empty($mobile)) $this->error('手机号不能为空');
        if (empty($code) && empty($password)) $this->error('不能为空');

        $Model = new MemberModel();
        $where = [];
        $where[] = ['mobile', '=', $mobile];
        if (!empty($code)) {

            $res = cmf_check_verification_code($mobile, $code, true);
            if (!empty($res)) {
                $this->error($res);
            }
        } else {
            $where[] = ['password', '=', cmf_password($password)];
        }
        $user = $Model->where($where)->find();
        if (!empty($user)) {
            //已经存在直接登陆
            $this->success('登陆成功', CommonModel::getUserByOpenid($user['openid']));
        } else {
            $this->error('账号或密码错误');
        }
    }


    /**
     * @OA\Post(
     *     tags={"小程序-基础"},
     *     summary="上传图片、视频、文件",
     *     path="/wxapp/public/upload_asset",
     *      @OA\Parameter(
     *         name="filetype",
     *         in="query",
     *         description="默认image,其他video，audio，file",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *   @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     */
    public function upload_asset()
    {
        // $this->checkUser();
        session('user.id', 1);
        if ($this->request->isPost()) {
            $uploader = new Upload();
            $fileType = $this->request->param('filetype', 'image');
            $uploader->setFileType($fileType);

            $result = $uploader->upload();
            if ($result === false) {
                $this->error($uploader->getError());
            } else {
                if (!empty($result['filepath'])) {
                    $result['url'] = cmf_get_image_url($result['filepath']);
                } elseif (!empty($result['url'])) {
                    $result['url'] = cmf_get_asset_url($result['url']);
                }
                unset($result['name']);
                unset($result['id']);
                unset($result['preview_url']);
                $this->success('上传成功!', $result);
            }
        } else {
            $this->error('非法传参');
        }
    }


    /**
     * 获取手机号验证码
     * @OA\Post(
     *     tags={"小程序-基础"},
     *     path="/wxapp/loan/send_sms",
     *     @OA\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="mobile",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="1：登录验证码",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function send_sms()
    {
        $mobile = $this->request->param('mobile');
        $type = $this->request->param('type', 1);
        $sms_type = $this->request->param('sms_type', 1);//1腾讯，2：阿里
//        if (empty($type)) {
//            $this->error("请选择验证码类型！");
//        }
//
        $mobile = trim($mobile);
        if (empty($mobile)) {
            $this->error("手机号不能为空！");

        }


        $code = cmf_get_verification_code($mobile);
        $params = array(
            "mobile" => $mobile,
            "code" => $code
        );

        if ($sms_type == 1) {
            $sms = new TencentcloudSmsPlugin();
        } else {
            $sms = new AliSmsPlugin();
        }
        cmf_verification_code_log($mobile, $code);
        $result = $sms->sendMobileVerificationCode($params);
        if ($result == 'Ok') {
            $this->success('发送成功！');
        } else {
            $this->error($result);
        }
    }


    /**
     * @OA\Get(
     *      tags={"小程序-基础"},
     *      summary="用户-静默授权-公众号",
     *     path="/wxapp/public/user_empower",
     *      @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="state",
     *         in="query",
     *         description="state",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function user_empower(MemberModel $memberModel)
    {
        $code = $this->request->param('code');
        $type = $this->request->param('type', 1);
        if (empty($code)) {
            $this->error('code不能为空');
        }

        $state = $this->request->param('state');
        $state_arr = explode('/', $state);
        $http = $state_arr[0] . '//';
        $host = $state_arr[2];
        $appid = config('wxapp.appid');
        $secret = config('wxapp.secret');
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $secret . "&code=" . $code . "&grant_type=authorization_code";
        $return = curl_get($url);
        $data = json_decode($return, true);
        if (!isset($data['errcode'])) {
            Log::write($data, '静默授权');
            $openid = $data['openid'];
//            $memberModel->where('id', $this->user_id)->update(['openid' => $openid]);
            header('Location:' . $http . $host . '/h5/#/pages/user/index?openid=' . $openid);
            $this->success('授权成功');
        } else {
            $this->error('授权失败');
        }
    }


    /**
     * 工作账号登录（APP端外勤人员使用）
     * 复用后台管理员账密，自动创建小程序用户（随机openid/手机号/昵称）
     * @OA\Post(
     *     tags={"小程序-登录"},
     *     summary="工作账号登录",
     *     path="/wxapp/public/work_login",
     *     @OA\Parameter(name="username",description="后台账号",in="query",required=true,@OA\Schema(type="string")),
     *     @OA\Parameter(name="password",description="后台密码",in="query",required=true,@OA\Schema(type="string")),
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     */
    public function work_login()
    {
        $username = $this->request->param('username', '');
        $password = $this->request->param('password', '');

        if (empty($username) || empty($password)) {
            $this->error('账号和密码不能为空');
        }

        // 查询后台管理员账号
        $admin = Db::name('user')
            ->where('user_login', $username)
            ->where('status', 1)
            ->find();

        if (empty($admin)) {
            $this->error('账号不存在或已禁用');
        }

        // 验证密码（ThinkCMF 后台密码加密方式）
        if (!cmf_compare_password($password, $admin['user_pass'])) {
            $this->error('密码错误');
        }

        // 生成唯一标识
        $workOpenid = 'work_' . md5($admin['id'] . '_' . $username);
        $MemberModel = new MemberModel();

        // 检查该工作账号是否已绑定过小程序用户
        $existUser = $MemberModel->where('openid', $workOpenid)->find();
        if (!empty($existUser)) {
            $this->success('登录成功', $existUser);
        }

        // 首次登录：自动创建小程序用户
        $vcode = CommonModel::get_vcode();
        $mobile = '1' . rand(3, 9) . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        $nickname = '外勤_' . substr($admin['user_login'], 0, 4) . '_' . rand(100, 999);

        $userId = $MemberModel->insertGetId([
            'openid'      => $workOpenid,
            'nickname'    => $nickname,
            'true_name'   => $nickname,
            'mobile'      => $mobile,
            'avatar'      => cmf_get_asset_url(cmf_get_option('set_config')['app_logo'] ?? ''),
            'create_time' => time(),
            'status'      => 1,
            'vcode'       => $vcode,
        ]);

        $user = $MemberModel->field('id,nickname,avatar,mobile,openid,true_name,community_id,point,level')
            ->find($userId);

        $this->success('登录成功', $user);
    }

    /**
     * 测试
     * @OA\Get (
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/ceshi",
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     */
    public function ceshi()
    {

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
        ]);
        $html = $this->pdf_html();

        $mpdf->WriteHTML($html);

        /*// ===================== 水印开始 =====================
        $text = "通航未来就业部";
        $size = 50;
        $alpha = 0.3;
        $angle = 45;

        // 获取总页数
        $totalPages = $mpdf->page;

        // 逐页绘制水印
        for ($page = 1; $page <= $totalPages; $page++) {
            $mpdf->page = $page; // 切换到当前页

            // 绘制整页水印
            for ($y = 40; $y < 800; $y += 130) {
                for ($x = 30; $x < 600; $x += 150) {
                    $mpdf->SetFontSize($size);
                    $mpdf->SetAlpha($alpha);
                    $mpdf->SetTextColor(136, 136, 136);

                    $mpdf->Rotate($angle, $x, $y);
                    $mpdf->Text($x, $y, $text);
                    $mpdf->Rotate(0);
                }
            }

            // 🔑 关键：只在非最后一页时才新增一页
            if ($page < $totalPages) {
                $mpdf->AddPage();
            }
        }

        // 恢复默认
        $mpdf->SetAlpha(1);
        $mpdf->SetTextColor(0, 0, 0);
        // ===================== 水印结束 =====================*/

        $mpdf->Output('航空专业定向人才实习就业推荐协议.pdf', 'D');
        exit();
    }

    //生成pdf文件
    public function pdf($image = ''){
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
        ]);
        $html = $this->pdf_html($image);

        $mpdf->WriteHTML($html);


        $alioss_config = cmf_get_plugin_config('Oss');
        $base_dir = !empty($alioss_config)?$alioss_config['dir'].'/':'';
        $scene = time() . rand(1000,9999);

        $path = "upload/pdf_file/" . $scene . ".pdf";
        $rel_path = $base_dir . "pdf_file/" . md5(time()). $scene . ".pdf";

        $mpdf->Output($path, 'F');

        //上传Oss储存
        $storage = cmf_get_option('storage');
        $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
        $storage->upload($rel_path, $path);

        return $rel_path;
    }

    //pdf的html内容
    public function pdf_html($image = ''){
        $image = empty($image)?'':cmf_get_asset_url($image);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>【真分】社区环保志愿督导服务与兼职劳务协议</title>
    <style>
        body {
            font-family: "SimSun", "宋体", serif;
            line-height: 1.8;
            max-width: 900px;
            margin: 40px auto;
            padding: 0 30px;
            font-size: 16px;
        }
        h1 {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 30px;
        }
        .intro {
            text-indent: 2em;
            margin-bottom: 25px;
        }
        h2 {
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0 10px;
        }
        p {
            text-indent: 2em;
            margin: 8px 0;
        }
        .list-item {
            text-indent: 0;
            padding-left: 2em;
            margin: 6px 0;
        }
        .sign-area {
            margin-top: 60px;
        }
        .sign-line {
            margin: 15px 0;
        }
        .sign-note {
            margin-top: 30px;
        }
    </style>
</head>
<body>
<h1>【真分】社区环保志愿督导服务与兼职劳务协议</h1>

<p class="intro">致尊敬的志愿督导员：</p>
<p class="intro">垃圾分类不仅是践行绿色低碳生活的必然要求，更是构建“居民自治、社区共建”现代化基层治理体系的重要一环。为了全面提升本社区的垃圾分类成效，打造美好宜居的社区环境，同时充分肯定并合法保障每一位参与环保督导工作居民的劳动权益，我们诚邀您加入【真分】环保行动。您通过小程序参与的每一次垃圾分类动作，都是对美好生活的贡献。为规范协作机制，保障双方权益，特制定本协议：</p>

<h2>一、 角色定位与工作内容</h2>
<p>您即日起正式成为【真分】志愿督导员。您的核心职责为以公益视角，协助记录并反馈点位分类情况，共同维护社区环境。为全面推进环保工作，您的具体工作内容包括但不限于以下多种形式：</p>
<p class="list-item">1. 点位督导巡查：依照标准记录并反馈各分类点位的实际运行情况；</p>
<p class="list-item">2. 督导活动支持：参与并协助社区开展的各类垃圾分类线下活动；</p>
<p class="list-item">3. 线上督导宣教：通过线上渠道参与垃圾分类的宣传、引导与教育工作；</p>
<p class="list-item">4. 平台指派的垃圾分类其他相关工作。</p>

<h2>二、 规范引导与兼职协作</h2>
<p>为保障社区环保工作的高效有序，双方建立“基于标准化任务”的兼职协作关系。为确保巡查动作的有效性，平台将通过小程序为您发布明确的巡查任务指引与标准操作流程。请您按照平台的专业指导开展巡查工作。您的巡查工作将以平台下发的具体任务指标为准。</p>

<h2>三、 劳务报酬与结算形式</h2>
<p>我们高度重视您的每一份付出。当您按照规范完成对应的任务动作后，平台将依据您的实际工作量核发相应的服务激励。</p>
<p>报酬性质说明：双方明确约定，为确保持续的高质量协作，您完成平台下发任务后所获取的收益，在财务与法律定性上，均属于平台向您支付的“兼职劳务报酬”。</p>
<p>支付形式：平台向您支付的劳务报酬将包含积分、实物、现金等多种形式。具体支付形式将因当时阶段所开展的相应动作及平台任务规则而定。</p>
<p>若当期报酬以积分形式发放，您可凭该积分在平台积分商城兑换健康物资，作为对您劳动付出的回馈；相应积分的发放或物资的领取，即视为当次劳务报酬的结清。</p>

<h2>四、 凭证确认</h2>
<p>您进行确认即代表您认可上述协作模式。本协议及您在小程序内完成的任务记录、积分流向数据，将作为您参与环保事业并获取劳动激励的合法凭证。</p>

<div class="sign-area">
    <p class="sign-line">甲方：数康(大连)数据科技有限公司 &nbsp;&nbsp;乙方（兼职督导员）：________________________</p>
</div>

<div class="sign-note">
    <p>签署说明： 本协议自乙方在小程序前端点击“同意”签字确认之日起生效。</p>
</div>

<div style="position: absolute;bottom: 700px;left: 160px;z-index: 999;pointer-events: none;">
    <img src="https://xcxkf052.aubye.com/upload/image/seal.png" style="width: 160px; max-width: 160px; height: auto;" alt="透明图">
</div>

<div style="position: absolute;bottom: 810px;right: 120px;z-index: 999;pointer-events: none;">
    <img src="$image" style="width: 160px; max-width: 160px; height: auto;" alt="透明图">
</div>
</body>
</html>
HTML;
        return $html;
    }

    //pdf的html内容
    public function pdf_html_show($image = ''){
        $image = empty($image)?'':cmf_get_asset_url($image);
        $img_string = '';
        if(!empty($image)){
            $img_string = '<img src="' . $image . '" style="width: 50px; max-width: 50px; height: auto;" alt="透明图">';
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>【真分】社区环保志愿督导服务与兼职劳务协议</title>
    <style>
        body {
            font-family: "SimSun", "宋体", serif;
            line-height: 1.8;
            width: 900px;
            margin: 40px auto;
            padding: 0 30px;
            font-size: 16px;
        }
        h1 {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 30px;
        }
        .intro {
            text-indent: 2em;
            margin-bottom: 25px;
        }
        h2 {
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0 10px;
        }
        p {
            text-indent: 2em;
            margin: 8px 0;
        }
        .list-item {
            text-indent: 0;
            padding-left: 2em;
            margin: 6px 0;
        }
        .sign-area {
            margin-top: 60px;
        }
        .sign-line {
            margin: 15px 0;
            position: relative;
        }
        .sign-note {
            margin-top: 30px;
        }
    </style>
</head>
<body>
<h1>【真分】社区环保志愿督导服务与兼职劳务协议</h1>

<p class="intro">致尊敬的志愿督导员：</p>
<p class="intro">垃圾分类不仅是践行绿色低碳生活的必然要求，更是构建“居民自治、社区共建”现代化基层治理体系的重要一环。为了全面提升本社区的垃圾分类成效，打造美好宜居的社区环境，同时充分肯定并合法保障每一位参与环保督导工作居民的劳动权益，我们诚邀您加入【真分】环保行动。您通过小程序参与的每一次垃圾分类动作，都是对美好生活的贡献。为规范协作机制，保障双方权益，特制定本协议：</p>

<h2>一、 角色定位与工作内容</h2>
<p>您即日起正式成为【真分】志愿督导员。您的核心职责为以公益视角，协助记录并反馈点位分类情况，共同维护社区环境。为全面推进环保工作，您的具体工作内容包括但不限于以下多种形式：</p>
<p class="list-item">1. 点位督导巡查：依照标准记录并反馈各分类点位的实际运行情况；</p>
<p class="list-item">2. 督导活动支持：参与并协助社区开展的各类垃圾分类线下活动；</p>
<p class="list-item">3. 线上督导宣教：通过线上渠道参与垃圾分类的宣传、引导与教育工作；</p>
<p class="list-item">4. 平台指派的垃圾分类其他相关工作。</p>

<h2>二、 规范引导与兼职协作</h2>
<p>为保障社区环保工作的高效有序，双方建立“基于标准化任务”的兼职协作关系。为确保巡查动作的有效性，平台将通过小程序为您发布明确的巡查任务指引与标准操作流程。请您按照平台的专业指导开展巡查工作。您的巡查工作将以平台下发的具体任务指标为准。</p>

<h2>三、 劳务报酬与结算形式</h2>
<p>我们高度重视您的每一份付出。当您按照规范完成对应的任务动作后，平台将依据您的实际工作量核发相应的服务激励。</p>
<p>报酬性质说明：双方明确约定，为确保持续的高质量协作，您完成平台下发任务后所获取的收益，在财务与法律定性上，均属于平台向您支付的“兼职劳务报酬”。</p>
<p>支付形式：平台向您支付的劳务报酬将包含积分、实物、现金等多种形式。具体支付形式将因当时阶段所开展的相应动作及平台任务规则而定。</p>
<p>若当期报酬以积分形式发放，您可凭该积分在平台积分商城兑换健康物资，作为对您劳动付出的回馈；相应积分的发放或物资的领取，即视为当次劳务报酬的结清。</p>

<h2>四、 凭证确认</h2>
<p>您进行确认即代表您认可上述协作模式。本协议及您在小程序内完成的任务记录、积分流向数据，将作为您参与环保事业并获取劳动激励的合法凭证。</p>

<div class="sign-area">
    <p class="sign-line">
    <span>甲方：数康(大连)数据科技有限公司</span>
    <div style="position: absolute;top: -10px;left: 50px;z-index: 999;pointer-events: none;">
        <img src="https://xcxkf052.aubye.com/upload/image/seal.png" style="width: 80px; max-width: 80px; height: auto;" alt="透明图">
    </div>
    
    <span>乙方（兼职督导员）：________________________</span>
    <div style="position: absolute;top: -10px;right: 350px;z-index: 999;pointer-events: none;">
        $img_string
    </div>
    </p>
</div>

<div class="sign-note">
    <p>签署说明： 本协议自乙方在小程序前端点击“同意”签字确认之日起生效。</p>
</div>

</body>
</html>
HTML;

        return $html;
    }


}
