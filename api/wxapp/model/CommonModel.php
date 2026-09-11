<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace api\wxapp\model;

use app\admin\model\AuthAccessModel;
use app\admin\model\member\MemberModel;
use app\admin\model\RoleUserModel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use think\facade\Db;
use think\Model;
use think\facade\Cache;
use PHPMailer\PHPMailer\PHPMailer;
use cmf\lib\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CommonModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = '';

    /**
     * 根据token获取用户user_info
     */
    static public function getUserInfoByToken($token, $field = '*')
    {
        $Model = new MemberModel();
        if (cmf_get_new_option('use_default_token') == 1) {
            if (empty($token)) {
                $token = $Model->where(['id' => 1])->value('token');
            }
        };
        return $Model->field($field)->where(['token' => $token])->find();
    }

    /**
     * 根据openid获取用户user_info
     */
    static public function getUserInfoByOpenid($openid, $field = '*')
    {
        $Model = new MemberModel();
        $user = [];

        if (!empty($openid)) {
            $user = $Model->field($field)->where(['openid' => $openid])->find();
        } else {
            if (cmf_get_new_option('use_default_token') == 1) {
                $user = $Model->where(['id' => 1])->find();
            }
        }
        return $user;
    }

    /**
     * 根据openid获取用户user_info
     */
    static public function getUserByOpenid($openid)
    {
        $user = self::getUserInfoByOpenid($openid, 'openid,id,true_name,nickname,mobile,avatar');
        if (empty($user)) return [];
        return $user;
    }


    /**
     * 根据user_id获取用户user_info
     */
    static public function getUser($user_id,$field = 'openid,id,true_name,nickname,mobile,avatar')
    {
        $user = MemberModel::where('id',$user_id)->field($field)->find();
//        if (!empty($user['avatar'])) $user['avatar'] = cmf_get_asset_url($user['avatar']);
        if (empty($user)) return [];
        return $user;
    }



    static function getLocalAddress($lon, $lat)
    {
        $latlon = $lat . ',' . $lon;
        $key = config('wxapp.tx_map_key');
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?location=" . $latlon . "&key=" . $key . "&get_poi=0";
        $result = file_get_contents($url);
        $result = json_decode($result, true);
        if ($result['status'] == 0) {
            return $result['result']['address'];
        }else{
            return false;
        }
    }


    /**
     * 获取用户角色
     */
    static public function getUserRoleByUserId($user_id): array
    {
        $Model = new RoleUserModel();
        $map['user_id'] = $user_id;
        return $Model->where($map)->column('role_id');
    }

    /**
     * 获取用户的的菜单
     */
    static public function getUserMenuByUserId($user_id): array
    {
        $role_id_arr = CommonModel::getUserRoleByUserId($user_id);
        $AuthAccess = new AuthAccessModel();
        $map = [];
        $map[] = ['role_id', 'in', $role_id_arr];
        return $AuthAccess->where($map)->column('menu_id');
    }

    /**
     * 获取字符串的图片
     */

    static public function getStringImageUrl($image_url)
    {
        if (!empty($image_url)) {
            if (is_array($image_url)) {
                $result = implode(',', $image_url);
            } else {
                $that_image_url = $image_url;
                $decode_image_url = json_decode($image_url, true);
                if (empty($decode_image_url)) {
                    $result = $that_image_url;
                } else {
                    if (is_array($decode_image_url)) {
                        $result = implode(',', $decode_image_url);
                    } else {
                        $result = $decode_image_url;
                    }
                }
            }
        } else {
            $result = '';
        }
        return $result;
    }

    /**
     * 获取数组形式的的全路径图片
     */

    static public function getArrImageFullUrl($image_url): array
    {
        $show_image_url = [];
        if (!empty($image_url)) {
            $image_url_arr = explode(',', $image_url);
            foreach ($image_url_arr as $value) {
                $show_image_url[] = cmf_get_asset_url($value);
            }
        }

        return $show_image_url;
    }

    /**
     * 获取一个的的全路径图片
     */

    static public function getOneImageFullUrl($image_url)
    {
        $show_image_url = '';
        if (!empty($image_url)) {
            $image_url_arr = explode(',', $image_url);
            $image_arr = [];
            foreach ($image_url_arr as $value) {
                $image_arr[] = cmf_get_asset_url($value);
            }
            $show_image_url = $image_arr[0];
        }
        return $show_image_url;
    }

    /**
     * 获取编辑时的全路径和半路径
     * $num 1取一维
     */
    static function getEditImageUrl($image_path, $num = 0): array
    {
        $image_url_arr = [];
        if (!empty($image_path)) {
            if ($num == 1) {
                $image_url_arr['show_image'] = cmf_get_asset_url($image_path);
                $image_url_arr['image'] = $image_path;
            } else {
                $image_url = explode(',', $image_path);

                foreach ($image_url as $key => $value) {
                    $image_url_arr[$key]['show_image'] = cmf_get_asset_url($value);
                    $image_url_arr[$key]['image'] = $value;
                }
            }
        }
        return $image_url_arr;
    }

    /**
     * 获取item的订单model
     */
    static function getItemModel($order_num)
    {
        $OrderModel = new OrderPayModel();
        $order_all_info = $OrderModel->where(['order_num' => $order_num])->find();
        if (empty($order_all_info)) {
            return false;
        }
        $type = $order_all_info['type'];
        if ($type == 1) {
            //订单
            $Model = new ShopOrderModel();
            $order_info = $Model->where(['order_num' => $order_num])->find();
        }else{
            $Model = new ShopOrderModel();
            $order_info = $Model->where(['pay_order_num' => $order_num])->find();
        }
        //elseif ($type == 2) {
//            $Model = new BalanceOrderModel();
//        } elseif ($type == 3) {
//            $Model = new VipOrderModel();
//        }

        $return['model'] = $Model;
        $return['order_info'] = $order_info;
        $return['order_all_info'] = $order_all_info;
        $return['type'] = $type;
        return $return;
    }

    /**
     * 添加总订单
     *
     */
    static function addOrderPay($order_num, $type, $price = 0, $user_id = 0): bool
    {
        $OrderModel = new OrderPayModel();
        $data = [];
        $data['order_num'] = $order_num;
        $data['type'] = $type;
        $data['price'] = $price;
        $data['user_id'] = $user_id;
        $OrderModel->save($data);
        return true;
    }

    /**
     * 生成唯一订单号
     */
    static function createOrderNumber()
    {
        $str = date('YmdsH') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $OrderModel = new OrderPayModel();
        $is = $OrderModel->where(['order_num' => $str])->find();
        if (!empty($is)) {
            $str = self::createOrderNumber();
        }
        return substr($str, 0, -1);
    }

    /**
     * 获取随机字符串
     * @param $length :长度
     * @param int $type : 1全随机，2字母，3数字
     * @return string
     */
    static function randomKeys($length, int $type = 1): string
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $full_length = 61;
        if ($type == 2) {
            $pattern = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
            $full_length = 51;
        } elseif ($type == 3) {
            $pattern = '1234567890';
            $full_length = 9;
        }
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $pattern[mt_rand(0, $full_length)];    //生成php随机数
        }
        return $key;
    }

    /**
     * 发送邮件
     * @param $emailAddress
     * @param $title
     * @param $message
     * @return bool|string
     * @throws \PHPMailer\PHPMailer\Exception
     */
    static function sendEmail($emailAddress, $title, $message)
    {
        $mail = new PHPMailer();

        //3.设置属性，告诉我们的服务器，谁跟谁发送邮件
        $mail->IsSMTP();            //告诉服务器使用smtp协议发送
        $mail->SMTPAuth = true;        //开启SMTP授权
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->Host = config('wxapp.email.host');
        $mail->From = config('wxapp.email.form');    //发送者的邮件地址
        $mail->FromName = config('wxapp.email.formName');        //发送邮件的用户昵称
        $mail->Username = config('wxapp.email.user');    //登录到邮箱的用户名
        $mail->Password = config('wxapp.email.password');        //第三方登录的授权码，在邮箱里面设置
        //编辑发送的邮件内容
        $mail->IsHTML(true);            //发送的内容使用html编写
        $mail->CharSet = 'utf-8';        //设置发送内容的编码
        $mail->Subject = $title;//设置邮件的标题
        $mail->MsgHTML($message);    //发送的邮件内容主体
        $mail->AddAddress($emailAddress);    //收人的邮件地址
        //调用send方法，执行发送
        $result = $mail->Send();
        if ($result) {
            //写入数据库
            return true;
        } else {
            return $mail->ErrorInfo;
        }
    }

    /**
     * 获取vcode
     */
    static function get_vcode($userid = '')
    {
        $Model = new MemberModel();
        $map[] = ['id', '=', $userid];
        $rmdcode = $Model->where($map)->find();
        if (!empty($rmdcode["vcode"])) {
            return $rmdcode["vcode"];
        } else {
            $vcode = self::get_ma();
            $where[] = ['vcode', '=', $vcode];
            $result = $Model->where($where)->find();
            while ($result) {
                $vcode = self::get_ma();
                $where = array();
                $where[] = ['vcode', '=', $vcode];
                $result = $Model->where($where)->find();
            }
            return $vcode;
        }
    }

    static function get_ma(): string
    {
        return chr(rand(97, 122)) . chr(rand(97, 122)) . rand(1000, 9999);
    }

    /**
     * 上传到阿里云
     */
    static function uploadAliOss($local_file_path, $ali_file_path): bool
    {

        $storage = cmf_get_option('storage');
        $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
        $storage->upload($ali_file_path, $local_file_path);
        if (file_exists($local_file_path)) {
            unlink($local_file_path);
        }
        return true;
    }


    /**
     * 获取小程序码
     * @param $id scene值
     * @param string $shareUrl
     * @param $file_path
     * @return bool
     */
    static function setShareImage($scene, $image_name, $dz = 'dz1111', string $shareUrl = 'pages/index/index', $dir = 'userShare', $env_version = 'release', $width = '430')
    {
        $access_token = self::getAccessToken();
        if (!file_exists('upload/' . $dir)) mkdir('upload/' . $dir);
        $full_dir = 'upload/' . $dir . '/' . date('Ymd');
        if (!file_exists($full_dir)) mkdir($full_dir);
        $local_file = $full_dir . '/' . $image_name;
        $imgUrl = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $res = [
            'scene' => $scene,
            'page' => $shareUrl,
            'check_path' => false,
            'env_version' => $env_version,//体验版为 trial
            'width' => $width
        ];
        $res = json_encode($res);
        $qrcode = curl_post($imgUrl, $res);
        file_put_contents($local_file, $qrcode);

        $ali_file_path = $dz . '/' . $dir . '/' . date('Ymd') . '/' . $image_name;
        self::uploadAliOss($local_file, $ali_file_path);
        return $ali_file_path;
    }

    /**
     * 获取access_token
     */
    static function getAccessToken()
    {

        $access_token = Cache::get('access_token');

        if (empty($access_token)) {
            //获取accessToken
            $appid = config('wxapp.appid');
            $secret = config('wxapp.secret');

            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;

            $arr = curl_get($url);

            $data = json_decode($arr, true);

            if (isset($data['access_token'])) {
                Cache::set('access_token', $data['access_token'], 3600);

                return $data['access_token'];
            } else {
                return false;
            }
        } else {
            return $access_token;
        }
    }

//获取微信公众号用户信息
    static function getUserInfo($openid)
    {
        $accessToken = CommonModel::getWxAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $accessToken . "&openid=" . $openid . "&lang=zh_CN";
        $result = curl_get($url);
        return json_decode($result, true);
    }

    public static function getWxAccessToken()
    {
        $access_token = Cache::get('wx_access_token');
        if (empty($access_token)) {
            //获取accessToken
            $appid = config('wxapp.wx.appid');
            $secret = config('wxapp.wx.secret');
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
            $arr = curl_get($url);

            $data = json_decode($arr, true);

            if (isset($data['access_token'])) {
                Cache::set('wx_access_token', $data['access_token'], 3600);
                return $data['access_token'];
            } else {
                return $data;
            }
        } else {
            return $access_token;
        }
    }

    /**
     * 获取公众号支付等ticket
     * @return false|mixed
     */
    static function getJsapiTicket()
    {

        $jsapi_ticket = Cache::get('jsapi_ticket');

        if (empty($jsapi_ticket)) {
            $assess_token = self::getAccessToken();

            $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $assess_token . '&type=jsapi';

            $arr = curl_get($url);

            $data = json_decode($arr, true);

            if (isset($data['ticket'])) {
                Cache::set('jsapi_ticket', $data['ticket'], 3600);
                return $data['ticket'];
            } else {
                return false;
            }
        } else {
            return $jsapi_ticket;
        }
    }

    /** * 生成签名, $KEY就是支付key * @return $result */
    static function makeSign($params): string
    {
        //签名步骤一：按字典序排序数组参数
        ksort($params);

        $string = '';
        if (!empty($params)) {
            $array = array();
            foreach ($params as $key => $value) {
                $array[] = $key . '=' . $value;
            }
            $string = implode("&", $array);
        }
        //签名步骤二：在string后加入KEY
        // $string = $string . "&key=".$KEY;
        //签名步骤三：MD5加密
        $string = sha1($string);
        //签名步骤四：所有字符转为大写
        return strtoupper($string);
    }

    /**
     * 获取缩略图路径
     */
    static function getThumbImagePath($filepath): string
    {
        $path_arr = explode(',', $filepath);
        $image_name_arr = explode('.', end($path_arr));
        $thumb_image_name = $image_name_arr[0] . '_thumb.' . $image_name_arr[1];
        //$thumb_path = $path_arr[0].'/'.$path_arr[1].'/'.$thumb_image_name;
        array_pop($path_arr);
        array_push($path_arr, $thumb_image_name);
        return implode('/', $path_arr);
    }


    /** 生成二维码
     * @param $text : 数据信息
     * @param $image_name : 图片名称
     * @return string
     * @throws
     */
    static function createQrcode($text, $image_name, $dir = 'qrcode', $dz = 'dz1111'): string
    {
        require_once '../vendor/phpqrcode/phpqrcode.php';
        $level = "Q";
        //二维码图片每个黑点的像素，默认4
        $size = '10';
        //二维码边框的间距，默认2
        $padding = 2;
        $full_dir = './upload/' . $dir . '/' . date('Ymd');

        if (!file_exists($full_dir)) mkdir($full_dir);

        $local_path = $full_dir . '/' . $image_name;

        //生成二维码图片
        \QRcode::png($text, $local_path, $level, $size, $padding, false);
        $ali_file_path = $dz . '/' . $dir . '/' . date('Ymd') . '/' . $image_name;
        self::uploadAliOss($local_path, $ali_file_path);
        return $ali_file_path;
    }

    /**
     * 获取地址
     */
    static function getAreaName($code)
    {
        $Model = new AreaModel();
        return $Model->where(['code' => $code])->value('name');
    }

    /**
     * 更新用户
     */
    static function updateUser($user_id, $data): bool
    {
        $UserModel = new MemberModel();
        $UserModel->where(['id' => $user_id])->strict(false)->save($data);
        return true;
    }

    /**
     * 写入用户编号 昵称 头像
     */
    public static function updateUserSn($userId): bool
    {
        //编号
        $sn = $userId + 864238;

        $data_n = [];
        $data_n['sn'] = $sn;
        $data_n['nickname'] = '微信用户_' . $userId;
//        $data_n['true_name'] = '微信用户_'.$userId;
        $data_n['avatar'] = 'https://oss.ausite.cn/dz1111/avatar.png';

        $MemberModel = new MemberModel();
        $MemberModel->where(['id' => $userId])->save($data_n);
        return true;

    }

    /**
     * 发送模板信息
     * @param $template_id
     * @param $openid
     * @param $data
     * @param string $page_path
     * @param string $url
     * @return bool
     */
    static function pushTemplate($template_id, $openid, $data, string $page_path = '', string $url = ''): bool
    {

        /* $data = [
             'first'=>['value'=>'发货通知'],
             'keyword1'=>['value'=>'发货通知'],
             'keyword2'=>['value'=>'发货通知'],
             'keyword3'=>['value'=>'发货通知'],
             'remark'=>['value'=>'发货通知'],
         ];*/

        $access_token = CommonModel::getAccessToken();
        $curl_url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access_token;

        //模板消息
        $template = [
            'touser' => $openid,  //用户openid
            'template_id' => $template_id, //在公众号下配置的模板id
            'topcolor' => "#7B68EE",
            'data' => $data
        ];

        if (!empty($page_path)) {
            $template['miniprogram'] =
                [
                    'appid' => config('wxapp.appid'),
                    'pagepath' => $page_path
                ];
        }
        if (!empty($url)) {
            $template['url'] = $url;
        }
        $json_template = json_encode($template);
        curl_post($curl_url, $json_template);
        return true;

    }

    /**
     * 导出excel表
     * $data：要导出excel表的数据，接受一个二维数组
     * $name：excel表的表名
     * $head：excel表的表头，接受一个一维数组
     * $key：$data中对应表头的键的数组，接受一个一维数组
     * 备注：此函数缺点是，表头（对应列数）不能超过26；
     *循环不够灵活，一个单元格中不方便存放两个数据库字段的值
     */
   static public function outData($name = '测试表', $data = [], $head = [], $keys = [], $types = [], $image_array = [])
    {
        // PhpSpreadsheet 在大数据量导出时会消耗大量内存（每个单元格都会缓存对象），
        // 默认 256M 经常不够用导致 "Allowed memory size of ... bytes exhausted"。
        // 这里临时取消内存限制，导出结束后进程会自动退出释放内存。
        // 之前曾因 `new CommonModel()->outData()` 触发 ThinkPHP Model __call
        // 转发到 db()，进而查询不存在的 cmf_common_model 表，故此处必须静态调用。
        @ini_set('memory_limit', '-1');
        @set_time_limit(0);

        /* $filename = '签到列表'.date('Y-m-d');
         $head = ['用户ID','昵称','姓名','手机号','单位','项目名称'];//设置表头
         $keys = ['uid','nickname','username','mobile','name','proj_name'];//设置表头
         $CommonModel->outData($filename,$list,$head,$keys);*/

        $count = count($head);  //计算表头数量
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ======PhpSpreadsheet内存优化配置======
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false); // 关闭公式预计算，大幅省内存
        $writer->setIncludeCharts(false);

        // 「列序号 -> 列名」和列宽提前算好，每列只算一次：
        // 原实现把 chr() 和 getColumnDimension()->setWidth(20) 放在单元格循环里，
        // 每写一个单元格就重复调用一次，2.7 万行 × 7 列 ≈ 19 万次无意义的重复对象调用，
        // 是导出耗时的主要来源之一（列宽本来就每列固定 20，设置一次即可）。
        $colLetters = [];
        for ($i = 0; $i < $count; $i++) {
            $colLetters[$i] = strtoupper(chr(65 + $i)); //数字转字母从65开始
            $sheet->getColumnDimension($colLetters[$i])->setWidth(20); //固定列宽
        }

        for ($i = 0; $i < $count; $i++) {
            //循环设置表头：
            $sheet->setCellValue($colLetters[$i] . '1', $head[$i]);
        }

        /*--------------开始从数据库提取信息插入Excel表中------------------*/
        foreach ($data as $key => $item) {
            //循环设置单元格：
            //$key+2,因为第一行是表头，所以写到表格时   从第二行开始写
            $row_no = $key + 2;
            for ($i = 0; $i < $count; $i++) {
                $value = $item[$keys[$i]];
                if(!empty($types) && $types[$i] == 2 && !empty($value)){

                    $objDrawing = new Drawing();
                    $objDrawing->setPath($value);//这里拼接 . 是因为要在根目录下获取
                    // 设置宽度高度
                    $objDrawing->setHeight(30);//照片高度
                    $objDrawing->setWidth(30); //照片宽度
                    /*设置图片要插入的单元格*/
                    $objDrawing->setCoordinates($colLetters[$i] . $row_no);
                    // 图片偏移距离
                    $objDrawing->setOffsetX(0);
                    $objDrawing->setOffsetY(0);
                    $objDrawing->setWorksheet($sheet);

                    $sheet->getDefaultRowDimension()->setRowHeight(50);//列高
                }else{
                    $sheet->setCellValue($colLetters[$i] . $row_no, $value);
                }
            }
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
        header('Cache-Control: max-age=0');
//        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');//删除清空：

        // =====修复：使用register_shutdown_function，脚本全部结束后删除临时图片=====
        register_shutdown_function(function() use ($image_array){
            foreach($image_array as $file){
               if(is_file($file)){
                   @unlink($file);
               }
            }
        });

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);
        gc_collect_cycles();

//        $spreadsheet->disconnectWorksheets();
//
//        foreach($image_array as $k=>$v){
//            unlink($v);
//        }
//        unset($spreadsheet);
        exit;
    }


    /**
     * excel 导入
     * @param $file_info | 文件信息
     * @param $headerRow | 导入列设置
     * @param $Model | 导入模型
     * @return array 返回参数 code=1 成功
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function excelImport($file_info, $headerRow, $Model): array
    {
        //获取表格的大小，限制上传表格的大小5M
        if ($file_info['size'] > 5 * 1024 * 1024) return ['code' => 0, 'msg' => '文件大小不能超过5M', 'data' => ''];

        //限制上传表格类型
        $fileExtendName = substr(strrchr($file_info["name"], '.'), 1);

        if ($fileExtendName != 'xls' && $fileExtendName != 'xlsx') return ['code' => 0, 'msg' => '必须为excel表格', 'data' => ''];

        if (is_uploaded_file($file_info['tmp_name'])) {
            // 有Xls和Xlsx格式两种
            if ($fileExtendName == 'xlsx') {
                $objReader = IOFactory::createReader('Xlsx');
            } else {
                $objReader = IOFactory::createReader('Xls');
            }

            $filename = $file_info['tmp_name'];

            $objPHPExcel = $objReader->load($filename);    //$filename可以是上传的表格，或者是指定的表格

            $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet

            $highestRow = $sheet->getHighestRow();        // 取得总行数

            // $highestColumn = $sheet->getHighestColumn(); // 取得总列数

            //循环读取excel表格，整合成数组。如果是不指定key的二维，就用$data[i][j]表示。
            for ($j = 2; $j <= $highestRow; $j++) {
                if (empty($objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue())) {
                    continue;
                }

                foreach ($headerRow as $k => $v) {
                    $data[$j - 2][$v['rowName']] = $objPHPExcel->getActiveSheet()->getCell($v['intToChr'] . $j)->getValue();
                    if (is_object($data[$j - 2][$v['rowName']])) $data[$j - 2][$v['rowName']] = $data[$j - 2][$v['rowName']]->__toString();
                }
            }

            if (empty($data)) return ['code' => 0, 'msg' => '数据为空', 'data' => ''];

            $Model->insertAll($data);

            return ['code' => 1, 'msg' => '导入成功', 'data' => ''];
        }
        return ['code' => 0, 'msg' => '上传失败', 'data' => ''];
    }

    /**
     * excel
     * @param array $data 数据data
     * @param array $headerRow 首行数据data
     * @param array $conf [fileName] string 文件名 | [format] string 文件格式后缀 xls
     * @param array $subtitle [rowName] string 列名 | [acrossCells] string 跨越列数
     * @return void
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function excelExport(array $data, array $headerRow, array $conf = [], array $subtitle = [])
    {
        /*$headerRow = [
            ['rowName' => 'ID', 'rowVal' => 'id', 'width' => 10],
            ['rowName' => '下单时间', 'rowVal' => 'create_time', 'width' => 30],
            ['rowName' => '订单号', 'rowVal' => 'order_num', 'width' => 30],
            ['rowName' => '订单状态', 'rowVal' => 'is_status', 'width' => 30],
            ['rowName' => '位号信息', 'rowVal' => 'title', 'width' => 30],
            ['rowName' => '预约时间', 'rowVal' => 'time', 'width' => 30],
            ['rowName' => '支付时间', 'rowVal' => 'pay_time', 'width' => 30],
            ['rowName' => '支付金额', 'rowVal' => 'amount', 'width' => 30],
            ['rowName' => '核销码', 'rowVal' => 'cav_code', 'width' => 30],
            ['rowName' => '身份证号', 'rowVal' => 'id_card', 'width' => 30],
            ['rowName' => '姓名', 'rowVal' => 'username', 'width' => 30],
            ['rowName' => '电话', 'rowVal' => 'phone', 'width' => 30],
        ];
        $this->Export($list, $headerRow, ['fileName' => '订单列表']);*/

        //生成文件信息
        if (isset($conf['fileName']) && $conf['fileName']) {
            $fileName = $conf['fileName'];
        } else {
            $fileName = date('YmdHis') . rand(1000, 9999) . rand(1000, 9999);
        }
        $format = $conf['format'] ?? 'Xlsx'; //'Xls'
        $newExcel = new Spreadsheet(); //创建一个新的excel文档

        $objSheet = $newExcel->getActiveSheet(); //获取当前操作sheet的对象
        $objSheet->setTitle($fileName); //设置当前sheet的标题

        $sort = 0;
        //首行标题
        if (isset($conf['fileName']) && $conf['fileName']) {
            //首行标题
            $styleArray = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ];
            $objSheet->setCellValue('A1', $fileName);
            $objSheet->getStyle('A1')->applyFromArray($styleArray);

            $length = count($headerRow) - 1;
            $objSheet->mergeCells('A1:' . $this->intToChr($length) . '1');
            $sort = 1;
        }

        /** 副标题列 */
        if ($subtitle) {
            $subSort = 0;
            foreach ($subtitle as $key) {
                $objSheet->setCellValue($this->intToChr($subSort) . "2", $key['rowName'] ?? ' ');
                $endSort = $subSort + $key['acrossCells'] - 1;
                $objSheet->mergeCells($this->intToChr($subSort) . "2:" . $this->intToChr($endSort) . "2");
                $subSort = $endSort + 1;
            }
            $sort = 2;
        }

        /** 字段渲染列 */
        $sort += 1;
        $sheetConfig = false; //根据 $headerRow 配置数据读取方式
        //标题栏设置 & 行宽设置
        $headerRowCount = count($headerRow);
        for ($i = 0; $i < $headerRowCount; $i++) {
            $rowLetter = $this->intToChr($i);

            //设置第一栏的标题
            if (is_array($headerRow[$i])) {
                $sheetConfig = true;
                $objSheet->setCellValue($rowLetter . $sort, $headerRow[$i]['rowName']);
                $newExcel->getActiveSheet()->getColumnDimension($rowLetter)->setWidth($headerRow[$i]['width']);
            } else {
                $objSheet->setCellValue($rowLetter . $sort, $headerRow[$i]);
                $newExcel->getActiveSheet()->getColumnDimension($rowLetter)->setWidth(30);
            }
        }

        //样式设置 - 水平、垂直居中
        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $newExcel->getActiveSheet()->getStyle('A1:' . $this->intToChr($headerRowCount) . (count($data) + 2))->applyFromArray($styleArray);

        //第二行起，每一行的值
        $row_key = $sort + 1;
        if (!$sheetConfig) {
            foreach ($data as $key) {
                foreach ($key as $val) {
                    $rowLetter = $this->intToChr($key);
                    $objSheet->setCellValue($rowLetter . $row_key, $val);
                }
                $row_key++;
            }
        } else {
            foreach ($data as $rowVal) {
                foreach ($headerRow as $key => $val) {
                    $rowLetter = $this->intToChr($key);
                    $objSheet->setCellValue($rowLetter . $row_key, $rowVal[$val['rowVal']]);
                }
                $row_key++;
            }
        }

        // $format只能为 Xlsx 或 Xls
        if ($format == 'Xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } elseif ($format == 'Xls') {
            header('Content-Type: application/vnd.ms-excel');
        }

        //直接输出文件
        header("Content-Disposition: attachment;filename=" . $fileName . '.' . strtolower($format));
        header('Cache-Control: max-age=0');
        $objWriter = IOFactory::createWriter($newExcel, $format);
        $objWriter->save('php://output');
        exit;
    }

    public function intToChr($num)
    {
        $data = [];
        for ($i = 0; $i <= 701; $i++) {
            $y = ($i / 26);
            if ($y >= 1) {
                $y = intval($y);
                $data[$i] = chr($y + 64) . chr($i - $y * 26 + 65);
            } else {
                $data[$i] = chr($i + 65);
            }
        }
        return $data[$num];
    }

    /**
     * 下载资源
     * @param $file_url
     * @param $save_to
     */
    static function downloadFile($file_url, $save_to)
    {
        $content = file_get_contents($file_url);
        file_put_contents($save_to, $content);
    }

    /**
     * 加密或解密
     * @param string $str 要加密的数据
     * @param string $aes_key 秘钥
     * @param string $type 0加密 1解密
     *
     */
    static function EncryptOrDecrypt(string $str, string $aes_key, $type = 0)
    {
        if ($type == 0) {
            $data = openssl_encrypt($str, 'AES-128-ECB', $aes_key, OPENSSL_RAW_DATA);
            return base64_encode($data);
        } else {
            return openssl_decrypt(base64_decode($str), 'AES-128-ECB', $aes_key, OPENSSL_RAW_DATA);
        }
    }


    /**
     * 二维数组根据某个字段排序
     * @param array $array 要排序的数组
     * @param string $keys 要排序的键字段
     * @param string $sort 排序类型  SORT_ASC     SORT_DESC
     * @return array 排序后的数组
     */
    static function arraySort(array $array, string $keys, $sort = SORT_ASC): array
    {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);
        return $array;
    }

    /**
     * 根据cate_id获取名称
     * @param $cate_id
     * @return string
     */
    static function getCateName($cate_id): string
    {
        if (empty($cate_id)) return '';
        $ZlCateModel = new ZlCateModel();
        $cate_id_arr = explode(',', trim($cate_id, ','));
        $new_name_arr = [];
        foreach ($cate_id_arr as $cate_id_item) {
            $new_name_arr[] = $ZlCateModel->where(['id' => $cate_id_item])->value('cate_name');
        }
        return implode(',', $new_name_arr);
    }

    /**获取分类列表
     * @param $pid
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static function getCateList($pid): \think\Collection
    {
        $ZlCateModel = new ZlCateModel();
        return $ZlCateModel->where(['pid' => $pid, 'status' => 1])->order('list_order')->select();
    }

    /**
     * 获取角色名称
     *
     */
    static function getGroupName($group = -1)
    {

        $group_arr = [1 => '承办人', 2 => '供应商'];
        if ($group == -1) {
            return $group_arr;
        } else {
            return !empty($group_arr[$group]) ? $group_arr[$group] : '';
        }
    }

    /**
     * 替换换行符
     * @param $content
     * @return array|string|string[]
     */
    static function replaceRN($content)
    {
        return str_replace(["\r\n", "\r", "\n"], "<br>", $content);
    }

    /**
     * 获取审核状态
     * @param int $status
     * @return string|string[]
     */
    static function getCheckStatusName(int $status = -1)
    {
        $arr = [0 => '待审核', 1 => '已通过', 2 => '已拒绝'];
        if ($status == -1) {
            return $arr;
        } else {
            return $arr[$status] ?? '未知';
        }
    }

    /**
     * 地址转换为坐标
     */
    static function search_address($address)
    {
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?key=" . config('wxapp.tx_map_key') . "&address=" . $address;
        $result = file_get_contents($url);
        $result = json_decode($result, true);
        if ($result['status'] == 0) {
            return ['lon' => $result['result']['location']['lng'], 'lat' => $result['result']['location']['lat']];
        } else {
            return false;
        }
    }

    static function getLocalCity($lon, $lat)
    {
        $return = ['city_code' => 410100, 'city_name' => '郑州市'];
        if (empty($lon) || empty($lat)) {
            return $return;
        }
        $latlon = $lat . ',' . $lon;
        $key = config('wxapp.tx_map_key');
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?location=" . $latlon . "&key=" . $key . "&get_poi=0";
        $result = file_get_contents($url);
        $result = json_decode($result, true);

        if ($result['status'] == 0) {
            $data = $result['result'];

            $ad_info = $data['ad_info'];
            if (empty($ad_info)) {
                return $return;
            }

            $adcode = $ad_info['adcode'];

            $AreaModel = new AreaModel();
            $area_info = $AreaModel->where(['code' => $adcode])->find();
            if (empty($area_info)) {
                return $return;
            }

            if ($area_info['level'] == 3) {
                $pid = $area_info['pid'];
            } elseif ($area_info['level'] == 2) {
                $pid = $area_info['id'];
            }

            $city_info = $AreaModel->where(['id' => $pid])->find();
            $city_code = $city_info['code'];
            return ['city_code' => $city_code, 'city_name' => $city_info['name']];

        } else {
            return $return;
        }
    }


    //腾讯评论过滤
    static function checkContent($openid, $content): bool
    {

        $url = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=' . self::getAccessToken();

        $param = [
            'content' => $content,
            'version' => 2,
            'scene' => 2,
            'openid' => $openid,
        ];

        $res = curl_post($url, json_encode($param, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $res = json_decode($res, true);

        if ($res['errcode'] == 0 && !empty($res['result'] && $res['result']['label'] == 100)) {
            return true;
        } else {
            return false;
        }
    }

    //获取省市区名字
    static function setAreaName($data)
    {
        $data['province_name'] = self::getAreaName($data['province_code']);
        $data['city_name'] = self::getAreaName($data['province_code']);
        $data['county_name'] = self::getAreaName($data['province_code']);
        $address = $data['address'] ?? '';
        $data['full_address'] = $data['province_name'] . $data['city_name'] . $data['county_name'] . $address;
        return $data;
    }

    //姓名为空时取昵称的值
    static function changeTrueNameFromNickname($item)
    {
        if (empty($item['true_name'])) {
            $item['true_name'] = $item['nickname'];
        }
        return $item;
    }


    //获取两点之间距离
    static function getDistance($lng1, $lat1, $lng2, $lat2, $unit = 2, $decimal = 2)
    {
        $EARTH_RADIUS = 6370.996; // 地球半径系数
        $PI = 3.1415926535898;

        $radLat1 = $lat1 * $PI / 180.0;
        $radLat2 = $lat2 * $PI / 180.0;

        $radLng1 = $lng1 * $PI / 180.0;
        $radLng2 = $lng2 * $PI / 180.0;

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $distance = $distance * $EARTH_RADIUS * 1000;

        if ($unit === 2) {
            $distance /= 1000;
        }

        return round($distance, $decimal);
    }

    //获取腾讯两点之间导航距离
    public static function getTxDistance($lng1, $lat1, $lng2, $lat2)
    {
        $from = $lat1 . ',' . $lng1;
        $to = $lat2 . ',' . $lng2;
        $url = 'https://apis.map.qq.com/ws/distance/v1/matrix/?mode=bicycling&from=' . $from . '&to=' . $to . '&key=KONBZ-MJ2CW-YTERS-O2VZZ-Q653O-3LBMU';

        $return = json_decode(curl_get($url), true);

        if ($return['status'] == 0) {
            $distance = round($return['result']['rows'][0]['elements'][0]['distance'] / 1000, 2);
        } else {
            $distance = 0;
        }

        return $distance;
    }


    //图片转base64
    public function base64EncodeImage($image_file): string
    {
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        return 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
    }


    //获取抽奖机会
    public static function getDrawChance($prize_arr)
    {

        /*  $prize_arr = array(
                 '0' => array('id' => 1, 'prize' => '家电', 'v' => 2),
                 '1' => array('id' => 2, 'prize' => '数码相机', 'v' => 5),
                 '2' => array('id' => 3, 'prize' => 'iPad', 'v' => 13),
                 '3' => array('id' => 4, 'prize' => 'LED显示器', 'v' => 15),
                 '4' => array('id' => 5, 'prize' => 'U盘', 'v' => 25),
                 '5' => array('id' => 6, 'prize' => '键盘', 'v' => 30),
                 '6' => array('id' => 7, 'prize' => '鼠标垫', 'v' => 10),
        );*/
        $proArr = [];
        foreach ($prize_arr as $val) {
            $proArr[$val['id']] = $val['v'];
        }

        $rid = '';
        $proSum = array_sum($proArr);
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $rid = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }

        $res['yes'] = $prize_arr[$rid - 1]['prize'];
        unset($prize_arr[$rid - 1]);
        shuffle($prize_arr);
        $prize_arrcount = count($prize_arr);
        $pr = [];
        for ($i = 0; $i < $prize_arrcount; $i++) {
            $pr[] = $prize_arr[$i]['prize'];
        }
        $res['no'] = $pr;
        //抽奖结果
        return $res['yes'];
    }

    /**
     * Notes: 微信公众号发送模版 跳转小程序
     * User: bian
     * Date: 2024/4/26
     * Time:17:03
     * @return void
     */

    public function get_wx_msg($touser)
    {
        $accessToken = CommonModel::getWxAccessToken();
        // 发送模板消息
        $templateData = array(
//            'touser' => 'oD8J_6FwZUnVJ6R3CzfDPPVLq1Vo', // 接收消息的用户openid
            'touser' => $touser, // 接收消息的用户openid
            'template_id' => 'q_Lj2JxaWmDrgCpSDi9d5Z0Ul-oSgvP6VHp_3wVo4ZI', // 模板消息id
//            'miniprogram' => array(
//                'appid' => 'wx5d7c4478817aa117',
//                'pagepath' => 'pages/offerPrice/offerDetail',
//            ),
            'data' => array(
                'thing8' => array(
                    'value' => '你好，你有一条新消息',
                    'color' => '#173177'
                ),
                'thing20' => array(
                    'value' => '你好，你有一条新消息',
                    'color' => '#173177'
                ),
                'thing13' => array(
                    'value' => '你好，你有一条新消息',
                    'color' => '#173177'
                ),
                'time14' => array(
                    'value' => date('Y-m-d H:i:s'),
                    'color' => '#173177'
                ),
            )
        );

        $templateJson = json_encode($templateData);
        $sendTemplateUrl = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $accessToken;
        $response = curl_post($sendTemplateUrl, $templateJson);
        $responseData = json_decode($response, true);
        return $responseData;
//        $this->success('运行成功！', $responseData);

    }

    //推送小程序消息
    public static function pushWxappTemplate($openid, $template_id, $push_data, $page = '/pages/index/index')
    {
//        $MemberModel =  new MemberModel();
//        $openid = $MemberModel->where(['id'=>$user_id])->value('wx_openid');
//        var_dump($openid);

        if (empty($openid)) return false;
        $access_token = CommonModel::getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=" . $access_token;
        //模板id
        $data = [];

        $data['touser'] = $openid;
        $data['template_id'] = $template_id;
//        if (empty($push_data)) $push_data = '/pages/index/index';
        $data['page'] = $page;  //"/pages/index/index" ;
        $data['data'] = $push_data;
        /*$data['data'] = [
            "thing1"=>['value' => $push_data['act_name']],
            "date2"=>['value' => $push_data['date']],
            "thing3"=>['value' => $push_data['address']],
            'character_string7'=>['value'=> $push_data['balance']],
            'character_string8'=>['value'=> $push_data['sn']],
        ];*/
        $data['miniprogram_state'] = 'trial';   //跳转小程序类型：developer为开发版；trial为体验版；formal为正式版；默认为正式版
        $data = json_encode($data);
        return curl_post($url, $data);
    }


    /**
     * Notes:分红包
     * User: bian
     * Date: 2024/5/27
     * Time:10:31
     * @return array
     */

    function redPackage($total, $num)
    {
        $min = 0.01; // 最小金额
        $max = $total / $num * 2; // 最大金额
        $result = array();

        for ($i = 0; $i < $num - 1; $i++) {
            $rand = mt_rand($min * 100, $max * 100) / 100;
            $result[] = $rand;
            $total -= $rand;
        }

        $result[] = $total;
        shuffle($result);
        return $result;
    }


    public function get_test2()
    {
        $total = 50; // 总金额
        $num = 5; // 人数
        $result = redPackage($total, $num);
        print_r($result);

    }


    //补充路径
    static public function getOne($data, $one = [], $array = [],$content=[])
    {
        foreach ($one as &$v) {
            if (!empty($data[$v])) {
                $data[$v] = cmf_get_asset_url( $data[$v]);
            }
        }
        foreach ($array as &$value) {
            if (!empty($data[$value])) {
                $arr = json_decode($data[$value], true);
                foreach ($arr as &$item) {
                    $item = cmf_get_asset_url($item);
                }
                $data[$value] = $arr;
            }
        }
        foreach ($content as &$item) {
            if (!empty($data[$item])) {
                $data[$item] = htmlspecialchars_decode( $data[$item]);
            }
        }

        return $data;

    }

    //多条数据补充路径
    static public function getMany($date, $one = [], $array = [],$content=[])
    {
        foreach ($date as &$v) {
            $v = self::getOne($v, $one, $array,$content);
        }
        return $date;
    }


    static public function getJson($data, $array = [])
    {
        foreach ($array as &$v) {
            if (!empty($data[$v])) {
                $data[$v] = json_encode($data[$v]);
            }
        }
        return $data;
    }


    static public function getDate($data, $array = [],$format = 'Y-m-d')
    {
        foreach ($array as &$v) {
            if (!empty($data[$v])) {
                $data[$v] = date($format,$data[$v]);

            }
        }
        return $data;
    }


    public static function getLonLatDistanceField($lng,$lat): string
    {
        return "round(ACOS(SIN(({$lat} * 3.1415) / 180 ) *SIN((lat * 3.1415) / 180 ) +COS(({$lat} * 3.1415) / 180 ) * COS((lat * 3.1415) / 180 ) 
        *COS(({$lng} * 3.1415) / 180 - (lng * 3.1415) / 180 ) ) * 6380, 2) distance";
    }
}
