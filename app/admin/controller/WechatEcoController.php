<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

/**
 * PC 后台 - 微信生态
 *
 * 把项目里用到 / 将来要用的微信各平台开发者后台收在同一个菜单下，方便直达。
 *
 * 现状（2026-09）：
 *   - 「小程序」「微信支付」是真正在跑的，配置在 data/config/wxapp.php，本页只读展示已配置的 AppID / 商户号；
 *   - 「服务号」「订阅号」「视频号」「开放平台」「企业微信」还没有可用账号，
 *     页面只做纯前端演示：可以看平台能做什么、需要准备哪些配置项。
 *
 * 纯演示，不读写业务数据、不调用任何微信接口：表单可以填，但保存按钮只会提示
 * 「演示页面，配置不会被保存」。等哪个平台拿到账号了，把 platforms() 里的演示配置
 * 换成真实表单 + 落库逻辑即可，页面结构不用动。
 */
class WechatEcoController extends AdminBaseController
{
    /**
     * 平台清单（key 同时作为 action 名与小程序码，不要随意改）
     */
    protected function platforms()
    {
        // 读取项目已有配置，用来判断「已接入 / 未接入」（只读，不做任何修改）
        $miniAppId = (string)config('wxapp.appid');    // 小程序 AppID
        $mchId     = (string)config('wxapp.mch_id');   // 微信支付商户号
        $wxAppId   = (string)config('wxapp.wx.appid'); // 公众号（服务号）AppID
        $notifyUrl = cmf_get_domain() . '/api/wxapp/pay/notify';

        return [
            'mini_program' => [
                'action'   => 'mini_program',
                'name'     => '小程序',
                'tagline'  => '居民端主入口，所有打卡、活动、商城都在这里跑',
                'icon'     => 'mobile',
                'group'    => '居民端',
                'status'   => $miniAppId !== '' ? 'active' : 'pending',
                'summary'  => '居民扫码或搜索进入的就是这个小程序：垃圾分类学习与打卡、社区活动报名与签到、'
                    . '积分商城兑换、优惠券领取、论坛互动。后台的这些配置最终都作用在它上面。',
                'abilities' => [
                    ['name' => '微信登录', 'desc' => 'wx.login 拿到的 code 换 openid / session_key，是整套用户体系的基础。'],
                    ['name' => '订阅消息推送', 'desc' => '打卡提醒、活动开班与中奖通知，需要先在公众平台配好消息模板。'],
                    ['name' => '小程序码', 'desc' => '活动专属码（扫码参加 / 扫码签到）、商户码，报名和签到都靠它区分入口。'],
                    ['name' => '微信支付', 'desc' => '积分商城下单、订单退款，由「微信支付」平台提供能力。'],
                    ['name' => '文件上传', 'desc' => '打卡照片、活动反馈图片、商家入驻资质，都需要配置合法的上传域名。'],
                ],
                'config' => [
                    ['label' => 'AppID', 'value' => $miniAppId, 'placeholder' => 'wx 开头的 18 位字符', 'hint' => '来自 data/config/wxapp.php 的 appid'],
                    ['label' => 'AppSecret', 'secret' => true, 'hint' => '密钥出于安全考虑不在后台回显，需要时去服务器改配置文件'],
                    ['label' => '消息模板 ID', 'value' => '', 'placeholder' => '如：打卡提醒模板 ID', 'hint' => '订阅消息模板，未配置时无法推送'],
                    ['label' => '业务域名 / 服务器域名', 'value' => '', 'placeholder' => '在公众平台「开发管理」里配置', 'hint' => '图片、接口、下载域名都要在这里登记'],
                ],
                'consoles' => [
                    ['name' => '微信公众平台', 'url' => 'https://mp.weixin.qq.com'],
                ],
                'docs' => [
                    ['name' => '小程序开发文档', 'url' => 'https://developers.weixin.qq.com/miniprogram/dev/framework/'],
                ],
            ],

            'pay' => [
                'action'   => 'pay',
                'name'     => '微信支付',
                'tagline'  => '积分商城下单、退款、用户提现走这里',
                'icon'     => 'credit-card',
                'group'    => '居民端',
                'status'   => $mchId !== '' ? 'active' : 'pending',
                'summary'  => '积分商城的实物订单与优惠券购买通过微信支付（JSAPI）下单，'
                    . '退款走微信退款接口，居民提现走企业付款到零钱。',
                'abilities' => [
                    ['name' => 'JSAPI 下单', 'desc' => '小程序内调起支付，订单号、金额、openid 三者绑定。'],
                    ['name' => '退款', 'desc' => '订单取消 / 售后退款，需要 API 证书签名。'],
                    ['name' => '提现', 'desc' => '余额提现走企业付款到零钱，注意有额度与开通条件限制。'],
                    ['name' => '支付结果回调', 'desc' => '支付成功后微信回调后端改订单状态，回调地址必须是 https 且可公网访问。'],
                ],
                'config' => [
                    ['label' => '商户号 mch_id', 'value' => $mchId, 'placeholder' => '10 位数字商户号', 'hint' => '来自 data/config/wxapp.php 的 mch_id'],
                    ['label' => 'APIv3 密钥', 'secret' => true, 'hint' => '密钥不出服务器、不在后台回显'],
                    ['label' => 'API 证书', 'secret' => true, 'hint' => 'apiclient_cert.pem / apiclient_key.pem，放在服务器 wx_pem 目录'],
                    ['label' => '支付回调地址', 'value' => $notifyUrl, 'placeholder' => '', 'hint' => '微信支付结果通知地址，需与服务器实际路由一致'],
                ],
                'consoles' => [
                    ['name' => '微信支付商户平台', 'url' => 'https://pay.weixin.qq.com'],
                ],
                'docs' => [
                    ['name' => '微信支付 APIv3 文档', 'url' => 'https://pay.weixin.qq.com/wiki/doc/apiv3/index.shtml'],
                ],
            ],

            'service_account' => [
                'action'   => 'service_account',
                'name'     => '服务号',
                'tagline'  => '微信里的 H5 登录与消息触达（待开通）',
                'icon'     => 'comments',
                'group'    => '消息与触达',
                'status'   => $wxAppId !== '' ? 'active' : 'pending',
                'summary'  => '认证服务号能做网页授权：在微信里打开 H5 时静默取到 openid / unionid，'
                    . '同时支持客服消息、模板消息。项目配置文件里的公众号 AppID 目前是空的，说明还没接入。',
                'abilities' => [
                    ['name' => '网页授权登录', 'desc' => '在微信内置浏览器打开页面时无需输入账号即可识别用户，适合做 H5 活动页。'],
                    ['name' => '模板消息 / 订阅通知', 'desc' => '活动开始、审核结果、积分变动等主动推送。'],
                    ['name' => '客服与菜单', 'desc' => '自定义菜单、关键词自动回复、人工客服接待。'],
                    ['name' => '带参数二维码', 'desc' => '不同渠道用不同二维码，用来统计宣传落地效果。'],
                ],
                'config' => [
                    ['label' => 'AppID', 'value' => $wxAppId, 'placeholder' => 'wx 开头的 18 位字符', 'hint' => '对应 data/config/wxapp.php 中 wx.appid'],
                    ['label' => 'AppSecret', 'secret' => true, 'hint' => '密钥不在后台回显'],
                    ['label' => '网页授权域名', 'value' => '', 'placeholder' => '如 www.example.com', 'hint' => '需下载校验文件放到网站根目录'],
                    ['label' => '服务器配置 Token / EncodingAESKey', 'value' => '', 'placeholder' => '接收微信事件推送时必填', 'hint' => '配置后微信的事件（关注、扫码等）才会推给后端'],
                ],
                'consoles' => [
                    ['name' => '微信公众平台', 'url' => 'https://mp.weixin.qq.com'],
                ],
                'docs' => [
                    ['name' => '公众号开发文档', 'url' => 'https://developers.weixin.qq.com/doc/offiaccount/Getting_Started/Overview.html'],
                ],
            ],

            'subscription_account' => [
                'action'   => 'subscription_account',
                'name'     => '订阅号',
                'tagline'  => '内容运营与图文群发（待开通）',
                'icon'     => 'newspaper-o',
                'group'    => '消息与触达',
                'status'   => 'pending',
                'summary'  => '订阅号主要用来做内容运营，每天可以群发一次图文。'
                    . '注意它没有网页授权、模板消息等高级接口权限，所以不会承担登录、支付这类功能，'
                    . '只用来发垃圾分类科普、活动预告，并在图文里挂小程序卡片导流。',
                'abilities' => [
                    ['name' => '图文群发', 'desc' => '每天一次机会，用来发布科普内容和活动预告（提前排期很重要）。'],
                    ['name' => '图文挂小程序卡片', 'desc' => '读完图文可以直接点进小程序报名或打卡。'],
                    ['name' => '基础数据统计', 'desc' => '阅读量、转发量，用来判断哪类内容居民更愿意看。'],
                ],
                'config' => [
                    ['label' => 'AppID', 'value' => '', 'placeholder' => 'wx 开头的 18 位字符', 'hint' => '订阅号与小程序、服务号是各自独立的 AppID'],
                    ['label' => 'AppSecret', 'secret' => true, 'hint' => '密钥不在后台回显'],
                    ['label' => '运营负责人', 'value' => '', 'placeholder' => '如：宣传部某某', 'hint' => '仅作登记，发图文前需人工确认内容'],
                ],
                'consoles' => [
                    ['name' => '微信公众平台', 'url' => 'https://mp.weixin.qq.com'],
                ],
                'docs' => [
                    ['name' => '公众号开发文档', 'url' => 'https://developers.weixin.qq.com/doc/offiaccount/Getting_Started/Overview.html'],
                ],
            ],

            'channels' => [
                'action'   => 'channels',
                'name'     => '视频号',
                'tagline'  => '短视频与直播，给活动和商城导流（待开通）',
                'icon'     => 'video-camera',
                'group'    => '内容与流量',
                'status'   => 'pending',
                'summary'  => '微信视频号用来发活动短视频、做活动直播。'
                    . '最有价值的一点是可以把小程序挂到视频和直播间里 —— 居民刷到视频就能直接进来报名、兑换。',
                'abilities' => [
                    ['name' => '短视频', 'desc' => '活动花絮、打卡示范、垃圾分类小知识，可以复用公众号图文素材。'],
                    ['name' => '直播挂小程序', 'desc' => '直播间里挂小程序入口，边看边报名 / 边看边买。'],
                    ['name' => '直播间发券', 'desc' => '配合优惠券做直播专属福利，注意券的库存要提前加。'],
                    ['name' => '粉丝与私信', 'desc' => '评论区、私信互动，需要安排人值守。'],
                ],
                'config' => [
                    ['label' => '视频号名称', 'value' => '', 'placeholder' => '如：XX 社区环保', 'hint' => '对外展示的名字'],
                    ['label' => '视频号 ID（channels_id）', 'value' => '', 'placeholder' => '视频号助手可查', 'hint' => '做接口对接时才需要'],
                    ['label' => '绑定的小程序', 'value' => '', 'placeholder' => '需先有开放平台账号', 'hint' => '挂载小程序要在开放平台完成绑定'],
                    ['label' => '运营负责人微信号', 'value' => '', 'placeholder' => '发布与管理需要绑定管理员', 'hint' => '仅作登记'],
                ],
                'consoles' => [
                    ['name' => '视频号助手', 'url' => 'https://channels.weixin.qq.com'],
                ],
                'docs' => [
                    ['name' => '视频号开发文档', 'url' => 'https://developers.weixin.qq.com/doc/channels/'],
                ],
            ],

            'open_platform' => [
                'action'   => 'open_platform',
                'name'     => '开放平台',
                'tagline'  => '打通各端用户身份、跨应用能力的总入口（待开通）',
                'icon'     => 'plug',
                'group'    => '账号与打通',
                'status'   => 'pending',
                'summary'  => '开放平台是所有「跨应用」能力的总入口。'
                    . '把小程序、服务号绑定到同一个开放平台账号后，微信会给出 unionid，'
                    . '这样同一个人在小程序、公众号里的身份就能对上，不用各存一套用户。',
                'abilities' => [
                    ['name' => 'unionid 打通', 'desc' => '多端识别同一个人，是做会员体系、积分互通的前提。'],
                    ['name' => '网站 / App 微信登录', 'desc' => 'PC 站或 App 上直接微信扫码登录。'],
                    ['name' => '绑定公众号与小程序', 'desc' => '公众号菜单跳小程序、小程序内引导关注公众号，都依赖这里完成绑定。'],
                    ['name' => '第三方平台代开发', 'desc' => '将来要给多个社区各开一套小程序时才需要，现在不用管。'],
                ],
                'config' => [
                    ['label' => '开放平台 AppID', 'value' => '', 'placeholder' => 'wx 开头的 18 位字符', 'hint' => '开放平台的 AppID 与小程序、公众号的都不一样'],
                    ['label' => 'AppSecret', 'secret' => true, 'hint' => '密钥不在后台回显'],
                    ['label' => '已绑定的小程序', 'value' => '', 'placeholder' => '绑定后才能拿 unionid', 'hint' => '账号主体需与开放平台一致'],
                    ['label' => '已绑定的公众号', 'value' => '', 'placeholder' => '', 'hint' => '同上'],
                ],
                'consoles' => [
                    ['name' => '微信开放平台', 'url' => 'https://open.weixin.qq.com'],
                ],
                'docs' => [
                    ['name' => '开放平台文档', 'url' => 'https://developers.weixin.qq.com/doc/oplatform/'],
                ],
            ],

            'work' => [
                'action'   => 'work',
                'name'     => '企业微信',
                'tagline'  => '给工作人员用的协同工具（待开通）',
                'icon'     => 'building-o',
                'group'    => '内部协同',
                'status'   => 'pending',
                'summary'  => '面向社区管理员、督导员、商户这些「内部人员」。'
                    . '现在待审核打卡、异常提醒只能靠站内消息，看的人不一定及时；'
                    . '接上企业微信后可以直接推到工作人员的企业微信里。',
                'abilities' => [
                    ['name' => '应用消息推送', 'desc' => '待审打卡、举报、异常预警直接推给对应负责人。'],
                    ['name' => '通讯录同步', 'desc' => '把督导员名单同步成企业微信成员，按片区建部门。'],
                    ['name' => '客户联系 / 微信客服', 'desc' => '居民加工作人员的企业微信，问题跟进不断线。'],
                    ['name' => '审批与内部打卡', 'desc' => '内部请假、物资领用等流程可以走企业微信自带审批。'],
                ],
                'config' => [
                    ['label' => '企业 ID（corpid）', 'value' => '', 'placeholder' => '企业信息页可查', 'hint' => '企业微信管理后台获取'],
                    ['label' => '应用 Secret', 'secret' => true, 'hint' => '密钥不在后台回显'],
                    ['label' => 'AgentID', 'value' => '', 'placeholder' => '自建应用的 ID', 'hint' => '一个自建应用一个 AgentID'],
                    ['label' => '通讯录同步 Secret', 'secret' => true, 'hint' => '仅在需要同步成员时使用'],
                ],
                'consoles' => [
                    ['name' => '企业微信管理后台', 'url' => 'https://work.weixin.qq.com'],
                ],
                'docs' => [
                    ['name' => '企业微信开发文档', 'url' => 'https://developer.work.weixin.qq.com/document/'],
                ],
            ],
        ];
    }

    /** 微信生态总览 */
    public function index()
    {
        $platforms = $this->platforms();

        $activeCount = 0;
        foreach ($platforms as $p) {
            if ($p['status'] === 'active') {
                $activeCount++;
            }
        }

        $this->assign('platforms', $platforms);
        $this->assign('platformCount', count($platforms));
        $this->assign('activeCount', $activeCount);
        $this->assign('pendingCount', count($platforms) - $activeCount);

        return $this->fetch('index');
    }

    /** 小程序 */
    public function mini_program()
    {
        return $this->render_platform('mini_program');
    }

    /** 微信支付 */
    public function pay()
    {
        return $this->render_platform('pay');
    }

    /** 服务号 */
    public function service_account()
    {
        return $this->render_platform('service_account');
    }

    /** 订阅号 */
    public function subscription_account()
    {
        return $this->render_platform('subscription_account');
    }

    /** 视频号 */
    public function channels()
    {
        return $this->render_platform('channels');
    }

    /** 开放平台 */
    public function open_platform()
    {
        return $this->render_platform('open_platform');
    }

    /** 企业微信 */
    public function work()
    {
        return $this->render_platform('work');
    }

    /**
     * 渲染单个平台页（所有平台共用 platform.html）
     *
     * @param string $key platforms() 里的 key
     */
    protected function render_platform($key)
    {
        $platforms = $this->platforms();

        if (!isset($platforms[$key])) {
            $this->error('平台不存在');
        }

        $platform = $platforms[$key];

        $this->assign('platforms', $platforms);
        $this->assign('platform', $platform);
        $this->assign('currentKey', $key);
        $this->assign('configItems', $platform['config']);
        $this->assign('abilities', $platform['abilities']);
        $this->assign('consoles', $platform['consoles']);
        $this->assign('docs', $platform['docs']);

        return $this->fetch('platform');
    }
}
