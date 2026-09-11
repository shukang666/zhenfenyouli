<?php
// +----------------------------------------------------------------------
// | 后台首页（欢迎页 → 运营数据看板）
// +----------------------------------------------------------------------
// | 说明：
// | 1. 本文件覆盖 vendor/thinkcmf/cmf-app/src/admin/controller/MainController.php。
// |    vendor/composer/autoload_psr4.php 中 app\ 前缀的查找顺序是「项目 app/ 目录在前、
// |    vendor 在后」，所以同名类优先加载本文件，vendor 目录无需也不应该改动。
// | 2. 后台首页 iframe 地址依旧是 /admin/main/index（themes/admin_fengiy/admin/index/index.html
// |    里写死的），框架的 adminindex.js 也是靠 "/admin/main/index" 来识别首页的，因此不能换地址。
// | 3. 该地址在框架免授权白名单内（adminMainindex），所有后台角色（含非超管）都能直接看到，
// |    不需要新增菜单、不需要配权限、也不会有"首页要点两下才能进"的问题。
// +----------------------------------------------------------------------

namespace app\admin\controller;

use api\wxapp\model\ShopOrderModel;
use cmf\controller\AdminBaseController;
use think\facade\Cache;
use think\facade\Db;

class MainController extends AdminBaseController
{
    /**
     * 打卡类型名称（与后台左侧菜单一一对应：forum/index1 ~ index7）
     */
    const CLOCK_TYPE_NAMES = [
        1 => '健康论坛',
        2 => '垃圾打卡',
        3 => '点位巡检',
        4 => '桶边督导',
        5 => '互助平台',
        6 => '优秀事迹',
        7 => '环保论坛',
    ];

    // 看板缓存：默认缓存 60 秒，避免运营反复刷新时重复扫表；点"刷新数据"会强制重算
    const CACHE_KEY = 'admin_home_dashboard_v1';
    const CACHE_TTL = 60;

    /**
     * 后台首页
     */
    public function index()
    {
        $refresh = (int)$this->request->param('refresh', 0);
        $data    = self::collectStats($refresh === 1);

        $data['greet']       = self::greetText();
        $data['date_text']   = date('Y年n月j日') . ' 星期' . ['日', '一', '二', '三', '四', '五', '六'][(int)date('w')];
        $data['from_cache']  = isset($data['from_cache']) ? $data['from_cache'] : 0;

        $this->assign($data);

        return $this->fetch();
    }

    /**
     * 首页聚合数据（public static 便于命令行/其他入口复用与验证）
     *
     * 统计口径说明：
     * - 打卡（forum）统一 INNER JOIN member，与后台打卡列表 ForumController::setIndex 的 join 口径一致，
     *   列表里看不到的"用户已删除"的打卡也不会出现在首页，避免首页数字和列表对不上。
     * - 所有有软删除的表都带 delete_time = 0，与后台列表（ToolModel 自动过滤软删除）保持一致。
     * - 积分发放 = user_account_log 中 account_type = 2（积分账户）且 change_amount > 0。
     * - 订单状态分组沿用 ShopOrderModel::getStatusList() / getOrderStatusCount() 的定义：
     *   待发货 = (2, 14)，退款售后 = (12, 18, 20, 22)。
     */
    public static function collectStats(bool $forceRefresh = false): array
    {
        if (!$forceRefresh) {
            try {
                $cached = Cache::get(self::CACHE_KEY);
                if (!empty($cached) && !empty($cached['stats'])) {
                    $cached['from_cache'] = 1;
                    return $cached;
                }
            } catch (\Throwable $e) {
                // 缓存不可用（目录不可写等）不影响看板本身
            }
        }

        $t0     = microtime(true);
        $p      = config('database.connections.mysql.prefix');
        $today0 = strtotime(date('Y-m-d'));
        $ago7   = strtotime(date('Y-m-d', strtotime('-6 day')));
        $ago30  = strtotime(date('Y-m-d', strtotime('-29 day')));

        // 打卡相关统一 from 片段（口径与打卡列表一致）
        $clockFrom = "FROM {$p}forum a INNER JOIN {$p}member m ON m.id = a.user_id WHERE a.delete_time = 0";

        /* ------------------------------------------------------------------
         * 1. 概览数字：一条 UNION ALL 取回全部计数，把往返次数压到 1 次
         * ------------------------------------------------------------------ */
        $sql = "SELECT 'user_total' k, COUNT(*) v FROM {$p}member"
            . " UNION ALL SELECT 'user_today', COUNT(*) FROM {$p}member WHERE create_time >= {$today0}"
            . " UNION ALL SELECT 'user_7d', COUNT(*) FROM {$p}member WHERE create_time >= {$ago7}"
            . " UNION ALL SELECT 'user_30d', COUNT(*) FROM {$p}member WHERE create_time >= {$ago30}"
            . " UNION ALL SELECT 'clock_total', COUNT(*) {$clockFrom}"
            . " UNION ALL SELECT 'clock_today', COUNT(*) {$clockFrom} AND a.create_time >= {$today0}"
            . " UNION ALL SELECT 'clock_7d', COUNT(*) {$clockFrom} AND a.create_time >= {$ago7}"
            . " UNION ALL SELECT 'clock_user_today', COUNT(DISTINCT a.user_id) {$clockFrom} AND a.create_time >= {$today0}"
            . " UNION ALL SELECT 'active_total', COUNT(*) FROM {$p}active WHERE delete_time = 0"
            . " UNION ALL SELECT 'active_ing', COUNT(*) FROM {$p}active WHERE delete_time = 0 AND status = 1"
            . " UNION ALL SELECT 'sign_total', COUNT(*) FROM {$p}active_sign"
            . " UNION ALL SELECT 'sign_today', COUNT(*) FROM {$p}active_sign WHERE create_time >= {$today0}"
            . " UNION ALL SELECT 'aclock_total', COUNT(*) FROM {$p}active_clock"
            . " UNION ALL SELECT 'aclock_today', COUNT(*) FROM {$p}active_clock WHERE create_time >= {$today0}"
            . " UNION ALL SELECT 'order_total', COUNT(*) FROM {$p}shop_order WHERE delete_time = 0"
            . " UNION ALL SELECT 'order_today', COUNT(*) FROM {$p}shop_order WHERE delete_time = 0 AND create_time >= {$today0}"
            . " UNION ALL SELECT 'point_today', IFNULL(SUM(change_amount),0) FROM {$p}user_account_log WHERE delete_time = 0 AND account_type = 2 AND change_amount > 0 AND create_time >= {$today0}"
            . " UNION ALL SELECT 'point_7d', IFNULL(SUM(change_amount),0) FROM {$p}user_account_log WHERE delete_time = 0 AND account_type = 2 AND change_amount > 0 AND create_time >= {$ago7}"
            . " UNION ALL SELECT 'point_total', IFNULL(SUM(change_amount),0) FROM {$p}user_account_log WHERE delete_time = 0 AND account_type = 2 AND change_amount > 0"
            . " UNION ALL SELECT 'parent_total', COUNT(*) FROM {$p}member_community_parent WHERE delete_time = 0"
            . " UNION ALL SELECT 'community_total', COUNT(*) FROM {$p}member_community WHERE delete_time = 0"
            . " UNION ALL SELECT 'goods_total', COUNT(*) FROM {$p}goods WHERE delete_time = 0"
            . " UNION ALL SELECT 'shop_total', COUNT(*) FROM {$p}shop WHERE delete_time = 0"
            . " UNION ALL SELECT 'merchant_total', COUNT(*) FROM {$p}member WHERE is_merchant = 1"
            . " UNION ALL SELECT 'supervisor_total', COUNT(*) FROM {$p}member WHERE is_supervisor = 1"
            . " UNION ALL SELECT 'mch_apply_total', COUNT(*) FROM {$p}member_shop WHERE delete_time = 0"
            . " UNION ALL SELECT 'check_apply_total', COUNT(*) FROM {$p}member_check WHERE delete_time = 0"
            . " UNION ALL SELECT 'del_today', COUNT(*) FROM {$p}forum_delete_log WHERE create_time >= {$today0}";
        $stats = self::kvMap(Db::query($sql));

        /* ------------------------------------------------------------------
         * 2. 待办事项：同样一条 UNION ALL
         * ------------------------------------------------------------------ */
        $sql = "SELECT 'member_shop' k, COUNT(*) v FROM {$p}member_shop WHERE delete_time = 0 AND status = 1"
            . " UNION ALL SELECT 'shop', COUNT(*) FROM {$p}shop WHERE delete_time = 0 AND status = 1"
            . " UNION ALL SELECT 'check', COUNT(*) FROM {$p}member_check WHERE delete_time = 0 AND status = 1"
            . " UNION ALL SELECT 'goods', COUNT(*) FROM {$p}goods WHERE delete_time = 0 AND check_status = 1"
            . " UNION ALL SELECT 'order_send', COUNT(*) FROM {$p}shop_order WHERE delete_time = 0 AND status IN (2,14)"
            . " UNION ALL SELECT 'order_refund', COUNT(*) FROM {$p}shop_order WHERE delete_time = 0 AND status IN (12,18,20,22)"
            . " UNION ALL SELECT 'invoice', COUNT(*) FROM {$p}shop_order_invoice WHERE delete_time = 0 AND status = 1"
            . " UNION ALL SELECT 'withdraw', COUNT(*) FROM {$p}user_withdraw WHERE status = 0";
        $todoKv = self::kvMap(Db::query($sql));

        $todo = [
            [
                'key' => 'member_shop', 'count' => $todoKv['member_shop'] ?? 0, 'icon' => 'fa-user-plus', 'color' => '#faad14',
                'name' => '商家入驻待审', 'desc' => '商户提交入驻资料，通过后自动成为商家',
                'url' => self::link('MemberShop/index'),
            ],
            [
                'key' => 'check', 'count' => $todoKv['check'] ?? 0, 'icon' => 'fa-shield', 'color' => '#722ed1',
                'name' => '督导员申请待审', 'desc' => '小区点位巡检、桶边督导申请',
                'url' => self::link('Check/index'),
            ],
            [
                'key' => 'shop', 'count' => $todoKv['shop'] ?? 0, 'icon' => 'fa-shopping-bag', 'color' => '#1890ff',
                'name' => '店铺认证待审', 'desc' => '店铺认证资料审核',
                'url' => self::link('Shop/index'),
            ],
            [
                'key' => 'goods', 'count' => $todoKv['goods'] ?? 0, 'icon' => 'fa-cubes', 'color' => '#13c2c2',
                'name' => '商品待审', 'desc' => '商家上架的商品需要审核',
                'url' => self::link('Goods/index'),
            ],
            [
                'key' => 'order_send', 'count' => $todoKv['order_send'] ?? 0, 'icon' => 'fa-truck', 'color' => '#fa8c16',
                'name' => '待发货订单', 'desc' => '买家已兑换/付款，等待发货',
                'url' => self::link('Order/index', ['status' => 2]),
            ],
            [
                'key' => 'order_refund', 'count' => $todoKv['order_refund'] ?? 0, 'icon' => 'fa-undo', 'color' => '#ff4d4f',
                'name' => '退款售后单', 'desc' => '退款申请、退货、退款中订单',
                'url' => self::link('Order/index', ['status' => 20]),
            ],
            [
                'key' => 'invoice', 'count' => $todoKv['invoice'] ?? 0, 'icon' => 'fa-file-text-o', 'color' => '#2f54eb',
                'name' => '待开票申请', 'desc' => '用户提交的发票申请',
                'url' => self::link('OrderInvoice/index'),
            ],
            [
                'key' => 'withdraw', 'count' => $todoKv['withdraw'] ?? 0, 'icon' => 'fa-money', 'color' => '#52c41a',
                'name' => '提现待审核', 'desc' => '商家/用户余额提现申请',
                'url' => self::link('Account/withdraw'),
            ],
        ];
        $todoTotal = 0;
        foreach ($todo as $item) {
            $todoTotal += (int)$item['count'];
        }

        /* ------------------------------------------------------------------
         * 3. 近 30 天趋势（打卡量 / 新增用户 / 活动签到 / 活动报名）
         * ------------------------------------------------------------------ */
        // 注意：UNION 分支里的 GROUP BY 不能用字段别名（MySQL 5.7 会报 Unknown column），必须重复表达式
        $sql = "SELECT 'clock' t, FROM_UNIXTIME(a.create_time,'%Y-%m-%d') d, COUNT(*) c {$clockFrom} AND a.create_time >= {$ago30} GROUP BY FROM_UNIXTIME(a.create_time,'%Y-%m-%d')"
            . " UNION ALL SELECT 'user', FROM_UNIXTIME(create_time,'%Y-%m-%d'), COUNT(*) FROM {$p}member WHERE create_time >= {$ago30} GROUP BY FROM_UNIXTIME(create_time,'%Y-%m-%d')"
            . " UNION ALL SELECT 'aclock', FROM_UNIXTIME(create_time,'%Y-%m-%d'), COUNT(*) FROM {$p}active_clock WHERE create_time >= {$ago30} GROUP BY FROM_UNIXTIME(create_time,'%Y-%m-%d')"
            . " UNION ALL SELECT 'sign', FROM_UNIXTIME(create_time,'%Y-%m-%d'), COUNT(*) FROM {$p}active_sign WHERE create_time >= {$ago30} GROUP BY FROM_UNIXTIME(create_time,'%Y-%m-%d')";
        $trendMap = [];
        foreach (Db::query($sql) as $row) {
            $trendMap[$row['d']][$row['t']] = (int)$row['c'];
        }
        $trend = ['labels' => [], 'clock' => [], 'user' => [], 'aclock' => [], 'sign' => []];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} day"));
            $trend['labels'][] = date('n/j', strtotime($d));
            $trend['clock'][]  = $trendMap[$d]['clock'] ?? 0;
            $trend['user'][]   = $trendMap[$d]['user'] ?? 0;
            $trend['aclock'][] = $trendMap[$d]['aclock'] ?? 0;
            $trend['sign'][]   = $trendMap[$d]['sign'] ?? 0;
        }

        /* ------------------------------------------------------------------
         * 4. 近 30 天打卡分布：按分类（垃圾打卡四分类）+ 按类型
         * ------------------------------------------------------------------ */
        $sql = "SELECT 'cate' k, a.cate_id g, COUNT(*) c {$clockFrom} AND a.type = 2 AND a.cate_id > 0 AND a.create_time >= {$ago30} GROUP BY a.cate_id"
            . " UNION ALL SELECT 'type', a.type, COUNT(*) {$clockFrom} AND a.create_time >= {$ago30} GROUP BY a.type";
        $cateCount = [];
        $typeCount = [];
        foreach (Db::query($sql) as $row) {
            if ($row['k'] == 'cate') {
                $cateCount[(int)$row['g']] = (int)$row['c'];
            } else {
                $typeCount[(int)$row['g']] = (int)$row['c'];
            }
        }
        arsort($cateCount);
        arsort($typeCount);

        // 分类名称来自 basic_select（垃圾打卡的四个分类）
        $cateName = [];
        if (!empty($cateCount)) {
            $cateIds = array_slice(array_keys($cateCount), 0, 8);
            foreach (Db::query("SELECT id,name FROM {$p}basic_select WHERE id IN (" . implode(',', $cateIds) . ")") as $row) {
                $cateName[(int)$row['id']] = $row['name'];
            }
        }
        $catePie = [];
        foreach (array_slice($cateCount, 0, 6, true) as $cid => $c) {
            $catePie[] = ['name' => $cateName[$cid] ?? ('分类' . $cid), 'value' => $c];
        }
        $typeBar = [];
        foreach ($typeCount as $t => $c) {
            $typeBar[] = ['name' => self::CLOCK_TYPE_NAMES[$t] ?? ('类型' . $t), 'value' => $c];
        }

        /* ------------------------------------------------------------------
         * 5. 最新动态：最新打卡 / 活动报名 / 商城订单 / 注册用户
         * ------------------------------------------------------------------ */
        $latestClock = Db::query("SELECT a.id,a.user_id,a.type,a.cate_id,a.point,a.status,a.create_time,m.nickname,m.mobile,s.name AS cate_name"
            . " FROM {$p}forum a INNER JOIN {$p}member m ON m.id = a.user_id"
            . " LEFT JOIN {$p}basic_select s ON s.id = a.cate_id AND a.type = 2"
            . " WHERE a.delete_time = 0 ORDER BY a.id DESC LIMIT 8");
        foreach ($latestClock as &$v) {
            $v['type_name']   = self::CLOCK_TYPE_NAMES[(int)$v['type']] ?? '-';
            $v['status_name'] = (int)$v['status'] === 2 ? '已处理' : '待处理';
            $v['time_text']   = date('m-d H:i', (int)$v['create_time']);
        }
        unset($v);

        $latestSign = Db::query("SELECT a.id,a.user_id,a.active_id,a.create_time,m.nickname,c.name AS active_name"
            . " FROM {$p}active_sign a LEFT JOIN {$p}member m ON m.id = a.user_id LEFT JOIN {$p}active c ON c.id = a.active_id"
            . " ORDER BY a.id DESC LIMIT 8");
        foreach ($latestSign as &$v) {
            $v['active_name'] = $v['active_name'] ?: '活动已删除';
            $v['time_text']   = date('m-d H:i', (int)$v['create_time']);
        }
        unset($v);

        $orderStatusName = ShopOrderModel::getStatusName(-1);
        $latestOrder = Db::query("SELECT a.id,a.order_num,a.user_id,a.status,a.price,a.total_price,a.point,a.point_amount,a.pay_type,a.create_time,m.nickname"
            . " FROM {$p}shop_order a LEFT JOIN {$p}member m ON m.id = a.user_id"
            . " WHERE a.delete_time = 0 ORDER BY a.id DESC LIMIT 8");
        foreach ($latestOrder as &$v) {
            $v['status_name'] = $orderStatusName[(int)$v['status']] ?? '未知';
            $v['time_text']   = date('m-d H:i', (int)$v['create_time']);
            $v['pay_text']    = (int)$v['point'] > 0 ? ((int)$v['point'] . ' 积分') : (number_format((float)$v['total_price'], 2) . ' 元');
        }
        unset($v);

        $latestUser = Db::query("SELECT id,nickname,mobile,point,community_id,create_time FROM {$p}member ORDER BY id DESC LIMIT 8");
        $communityName = [];
        $cids = array_filter(array_column($latestUser, 'community_id'));
        if (!empty($cids)) {
            foreach (Db::query("SELECT id,name FROM {$p}member_community WHERE id IN (" . implode(',', array_unique($cids)) . ")") as $row) {
                $communityName[(int)$row['id']] = $row['name'];
            }
        }
        foreach ($latestUser as &$v) {
            $v['community_name'] = $communityName[(int)$v['community_id']] ?? '未选择';
            $v['time_text']      = date('m-d H:i', (int)$v['create_time']);
        }
        unset($v);

        $data = [
            'stats'        => $stats,
            'todo'         => $todo,
            'todo_total'   => $todoTotal,
            'trend'        => $trend,
            'cate_pie'     => $catePie,
            'type_bar'     => $typeBar,
            'latest_clock' => $latestClock,
            'latest_sign'  => $latestSign,
            'latest_order' => $latestOrder,
            'latest_user'  => $latestUser,
            'quick'        => self::quickLinks(),
            'cost_ms'      => (int)round((microtime(true) - $t0) * 1000),
            'update_time'  => date('Y-m-d H:i:s'),
            'from_cache'   => 0,
        ];

        try {
            Cache::set(self::CACHE_KEY, $data, self::CACHE_TTL);
        } catch (\Throwable $e) {
        }

        return $data;
    }

    /**
     * 快捷入口（运营常用功能，按使用频率排）
     *
     * mobile: 1 = 以列表浏览/审核为主，手机端可正常操作
     *         0 = 含重编辑/图片上传/资金操作，手机端体验差，驾驶舱会提示改用电脑端
     * tip:    当 mobile = 0 时展示给管理员的原因说明
     */
    private static function quickLinks(): array
    {
        return [
            ['name' => '用户列表', 'icon' => 'fa-users', 'color' => '#69c0ff', 'url' => self::link('Member/index'), 'mobile' => 1],
            ['name' => '垃圾打卡', 'icon' => 'fa-recycle', 'color' => '#52c41a', 'url' => self::link('Forum/index2'), 'mobile' => 1],
            ['name' => '打卡删除记录', 'icon' => 'fa-trash', 'color' => '#ff7875', 'url' => self::link('Forum/delete_log_index'), 'mobile' => 1],
            ['name' => '点位巡检', 'icon' => 'fa-map-marker', 'color' => '#40a9ff', 'url' => self::link('Forum/index3'), 'mobile' => 1],
            ['name' => '桶边督导', 'icon' => 'fa-hand-o-right', 'color' => '#b37feb', 'url' => self::link('Forum/index4'), 'mobile' => 1],
            ['name' => '活动列表', 'icon' => 'fa-calendar', 'color' => '#ffc069', 'url' => self::link('Active/index1'), 'mobile' => 1],
            ['name' => '活动报名', 'icon' => 'fa-user-plus', 'color' => '#5cdbd3', 'url' => self::link('ActiveSign/index'), 'mobile' => 1],
            ['name' => '活动打卡', 'icon' => 'fa-check-square-o', 'color' => '#95de64', 'url' => self::link('ActiveClock/index'), 'mobile' => 1],
            ['name' => '商品列表', 'icon' => 'fa-cubes', 'color' => '#ff9c6e', 'url' => self::link('Goods/index'), 'mobile' => 0, 'tip' => '包含多图上传与规格编辑'],
            ['name' => '订单列表', 'icon' => 'fa-list-alt', 'color' => '#ffa940', 'url' => self::link('Order/index'), 'mobile' => 1],
            ['name' => '提现管理', 'icon' => 'fa-money', 'color' => '#73d13d', 'url' => self::link('Account/withdraw'), 'mobile' => 0, 'tip' => '涉及资金审核与打款'],
            ['name' => '积分记录', 'icon' => 'fa-diamond', 'color' => '#9254de', 'url' => self::link('Account/userPointLog'), 'mobile' => 1],
            ['name' => '商家列表', 'icon' => 'fa-shopping-bag', 'color' => '#4096ff', 'url' => self::link('Shop/index'), 'mobile' => 1],
            ['name' => '商户申请', 'icon' => 'fa-user-plus', 'color' => '#f759ab', 'url' => self::link('MemberShop/index'), 'mobile' => 1],
            ['name' => '督导员申请', 'icon' => 'fa-shield', 'color' => '#36cfc9', 'url' => self::link('Check/index'), 'mobile' => 1],
            ['name' => '小区列表', 'icon' => 'fa-building-o', 'color' => '#597ef7', 'url' => self::link('Community/index'), 'mobile' => 1],
            ['name' => '社区管理', 'icon' => 'fa-sitemap', 'color' => '#ff7a45', 'url' => self::link('CommunityParent/index'), 'mobile' => 1],
            ['name' => '问卷列表', 'icon' => 'fa-question-circle', 'color' => '#ffc53d', 'url' => self::link('exam/index'), 'mobile' => 0, 'tip' => '包含题目与选项编辑'],
            ['name' => '问卷记录', 'icon' => 'fa-file-text', 'color' => '#85a5ff', 'url' => self::link('exam/gift_list'), 'mobile' => 1],
            ['name' => '优惠券', 'icon' => 'fa-ticket', 'color' => '#d3adf7', 'url' => self::link('Coupon/index'), 'mobile' => 1],
            ['name' => '轮播图', 'icon' => 'fa-image', 'color' => '#87e8de', 'url' => self::link('Slide/index'), 'mobile' => 0, 'tip' => '包含图片上传与排序'],
            ['name' => '首页入口配置', 'icon' => 'fa-th-large', 'color' => '#ffadd2', 'url' => self::link('homeEntries/index'), 'mobile' => 0, 'tip' => '为可视化配置页面'],
            ['name' => '系统消息', 'icon' => 'fa-bell', 'color' => '#ffd666', 'url' => self::link('SystemMessage/index'), 'mobile' => 1],
            ['name' => '意见反馈', 'icon' => 'fa-commenting', 'color' => '#b7eb8f', 'url' => self::link('advice/index'), 'mobile' => 1],
        ];
    }

    /**
     * 生成后台地址：优先用框架 url()，异常时回退为 /admin/控制器/方法.html
     */
    private static function link(string $path, array $params = []): string
    {
        try {
            return (string)url($path, $params);
        } catch (\Throwable $e) {
            $url = '/admin/' . $path . '.html';
            return empty($params) ? $url : $url . '?' . http_build_query($params);
        }
    }

    /**
     * UNION ALL 计数结果转 [键 => 数值]
     */
    private static function kvMap(array $rows): array
    {
        $kv = [];
        foreach ($rows as $row) {
            $kv[(string)$row['k']] = (int)$row['v'];
        }
        return $kv;
    }

    /**
     * 问候语
     */
    private static function greetText(): string
    {
        $hour = (int)date('H');
        if ($hour < 6) {
            return '凌晨好';
        }
        if ($hour < 9) {
            return '早上好';
        }
        if ($hour < 12) {
            return '上午好';
        }
        if ($hour < 14) {
            return '中午好';
        }
        if ($hour < 18) {
            return '下午好';
        }
        return '晚上好';
    }

    /**
     * 框架自带：保存首页挂件配置（插件市场会调用，保留以防插件报错）
     */
    public function dashboardWidget()
    {
        $dashboardWidgets = [];
        $widgets          = $this->request->param('widgets/a');
        if (!empty($widgets)) {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 1]);
                } else {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 0]);
                }
            }
        }

        cmf_set_option('admin_dashboard_widgets', $dashboardWidgets, true);

        $this->success('更新成功!');
    }
}
