<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use app\admin\model\member\MemberModel;
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
     *
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function decrypt_phone()
    {
        $code = $this->request->param('code');
        $encrypted_data = $this->request->param('encrypted_data');
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
            $this->addUser($openid, $vcode, $unionid);
            $MemberModel->where(['openid' => $openid])->save(['mobile' => $user_phone]);
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


}
