<?php
// +----------------------------------------------------------------------
// | 运营数据大屏（独立全屏页，不走后台 iframe 外壳）
// +----------------------------------------------------------------------
// | 登录态复用后台 ADMIN_ID。继承 BaseController 而非 AdminBaseController：
// | 1) 避免套上侧栏/标签外壳；2) 不走菜单权限（顶栏入口对所有已登录管理员开放，
// |    与仪表盘 /admin/main/index 一样）。视图路径需自行 parseTemplate。
// +----------------------------------------------------------------------

namespace app\admin\controller;

use api\wxapp\model\AccountLogModel;
use cmf\controller\BaseController;
use think\facade\Cache;
use think\facade\Db;

class DatascreenController extends BaseController
{
    const CACHE_KEY = 'admin_datascreen_v1';
    const CACHE_TTL = 90;

    protected function initialize()
    {
        parent::initialize();
        $adminId = session('ADMIN_ID');
        if (empty($adminId)) {
            if ($this->request->isAjax() || $this->request->isPost()) {
                $this->error('请先登录', url('admin/Public/login'));
            }
            return $this->redirect(url('admin/Public/login'));
        }
    }

    protected function fetch($template = '', $vars = [], $config = [])
    {
        $template = $this->parseTemplate($template);
        return $this->view->fetch($template, $vars, $config);
    }

    protected function parseTemplate($template)
    {
        $request = $this->request;
        if (strpos($template, '@')) {
            list($app, $template) = explode('@', $template);
        }
        $cmfAdminThemePath    = config('template.cmf_admin_theme_path');
        $cmfAdminDefaultTheme = cmf_get_current_admin_theme();
        $themePath            = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}/";
        $app                  = isset($app) ? $app : $this->app->http->getName();
        $depr                 = config('view.view_depr');
        if (0 !== strpos($template, '/')) {
            $template   = str_replace(['/', ':'], $depr, $template);
            $controller = cmf_parse_name($request->controller());
            if ($controller) {
                if ('' == $template) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . cmf_parse_name($request->action(false));
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }
        return $themePath . ($app ? $app . DIRECTORY_SEPARATOR : '') . ltrim($template, '/') . '.' . ltrim(config('view.view_suffix'), '.');
    }

    protected function _initializeView()
    {
        $cmfAdminThemePath    = config('template.cmf_admin_theme_path');
        $cmfAdminDefaultTheme = cmf_get_current_admin_theme();
        $themePath            = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}";
        $root                 = cmf_get_root();
        $cdnSettings          = cmf_get_option('cdn_settings');
        if (empty($cdnSettings['cdn_static_root'])) {
            $viewReplaceStr = [
                '__ROOT__'     => $root,
                '__TMPL__'     => "{$root}/{$themePath}",
                '__STATIC__'   => "{$root}/static",
                '__WEB_ROOT__' => $root,
            ];
        } else {
            $cdnStaticRoot  = rtrim($cdnSettings['cdn_static_root'], '/');
            $viewReplaceStr = [
                '__ROOT__'     => $root,
                '__TMPL__'     => "{$cdnStaticRoot}/{$themePath}",
                '__STATIC__'   => "{$cdnStaticRoot}/static",
                '__WEB_ROOT__' => $cdnStaticRoot,
            ];
        }
        $this->view->engine()->config([
            'view_base'          => WEB_ROOT . $themePath . '/',
            'tpl_replace_string' => $viewReplaceStr,
        ]);
    }

    public function index()
    {
        $refresh = (int)$this->request->param('refresh', 0);
        $data    = self::collect($refresh === 1);
        $set     = cmf_get_new_option('set_config');
        $this->assign('app_name', !empty($set['app_name']) ? $set['app_name'] : '社区环保');
        $this->assign('payloadJson', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP));
        return $this->fetch();
    }

    public function ajax_data()
    {
        $refresh = (int)$this->request->param('refresh', 0);
        $data    = self::collect($refresh === 1);
        return json(['code' => 1, 'data' => $data]);
    }

    /**
     * @return array
     */
    public static function collect($forceRefresh = false)
    {
        if (!$forceRefresh) {
            try {
                $cached = Cache::get(self::CACHE_KEY);
                if (!empty($cached) && !empty($cached['kpi'])) {
                    $cached['from_cache'] = 1;
                    return $cached;
                }
            } catch (\Throwable $e) {
            }
        }

        $t0     = microtime(true);
        $p      = config('database.connections.mysql.prefix');
        $today0 = strtotime(date('Y-m-d'));
        $ago7   = strtotime(date('Y-m-d', strtotime('-6 day')));
        $ago30  = strtotime(date('Y-m-d', strtotime('-29 day')));
        $clockFrom = "FROM {$p}forum a INNER JOIN {$p}member m ON m.id = a.user_id WHERE a.delete_time = 0";

        $sql = "SELECT 'user_total' k, COUNT(*) v FROM {$p}member"
            . " UNION ALL SELECT 'user_today', COUNT(*) FROM {$p}member WHERE create_time >= {$today0}"
            . " UNION ALL SELECT 'user_7d', COUNT(*) FROM {$p}member WHERE create_time >= {$ago7}"
            . " UNION ALL SELECT 'user_30d', COUNT(*) FROM {$p}member WHERE create_time >= {$ago30}"
            . " UNION ALL SELECT 'user_bind', COUNT(*) FROM {$p}member WHERE community_id > 0"
            . " UNION ALL SELECT 'clock_total', COUNT(*) {$clockFrom}"
            . " UNION ALL SELECT 'clock_today', COUNT(*) {$clockFrom} AND a.create_time >= {$today0}"
            . " UNION ALL SELECT 'clock_7d', COUNT(*) {$clockFrom} AND a.create_time >= {$ago7}"
            . " UNION ALL SELECT 'clock_30d', COUNT(*) {$clockFrom} AND a.create_time >= {$ago30}"
            . " UNION ALL SELECT 'clock_user_today', COUNT(DISTINCT a.user_id) {$clockFrom} AND a.create_time >= {$today0}"
            . " UNION ALL SELECT 'lj_total', COUNT(*) {$clockFrom} AND a.type = 2"
            . " UNION ALL SELECT 'lj_today', COUNT(*) {$clockFrom} AND a.type = 2 AND a.create_time >= {$today0}"
            . " UNION ALL SELECT 'patrol_total', COUNT(*) {$clockFrom} AND a.type = 3"
            . " UNION ALL SELECT 'patrol_today', COUNT(*) {$clockFrom} AND a.type = 3 AND a.create_time >= {$today0}"
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
            . " UNION ALL SELECT 'supervisor_total', COUNT(*) FROM {$p}member WHERE is_supervisor = 1"
            . " UNION ALL SELECT 'merchant_total', COUNT(*) FROM {$p}member WHERE is_merchant = 1"
            . " UNION ALL SELECT 'learn_user', COUNT(DISTINCT user_id) FROM {$p}waste_user WHERE delete_time = 0"
            . " UNION ALL SELECT 'quiz_total', COUNT(*) FROM {$p}quiz_log"
            . " UNION ALL SELECT 'quiz_today', COUNT(*) FROM {$p}quiz_log WHERE create_time >= {$today0}"
            . " UNION ALL SELECT 'qxl_pass', COUNT(*) FROM {$p}qxl_chapter_log WHERE passed = 1"
            . " UNION ALL SELECT 'qxl_try', COUNT(*) FROM {$p}qxl_chapter_log"
            . " UNION ALL SELECT 'task_open', COUNT(*) FROM {$p}volunteer_task WHERE status = 1"
            . " UNION ALL SELECT 'task_log', COUNT(*) FROM {$p}volunteer_task_log"
            . " UNION ALL SELECT 'shot_total', COUNT(*) FROM {$p}active_shot_report";
        $kpi = self::kvMap(Db::query($sql));

        $sql = "SELECT 'user' t, FROM_UNIXTIME(create_time,'%Y-%m-%d') d, COUNT(*) c FROM {$p}member WHERE create_time >= {$ago30} GROUP BY FROM_UNIXTIME(create_time,'%Y-%m-%d')"
            . " UNION ALL SELECT 'clock', FROM_UNIXTIME(a.create_time,'%Y-%m-%d'), COUNT(*) {$clockFrom} AND a.create_time >= {$ago30} GROUP BY FROM_UNIXTIME(a.create_time,'%Y-%m-%d')"
            . " UNION ALL SELECT 'lj', FROM_UNIXTIME(a.create_time,'%Y-%m-%d'), COUNT(*) {$clockFrom} AND a.type = 2 AND a.create_time >= {$ago30} GROUP BY FROM_UNIXTIME(a.create_time,'%Y-%m-%d')"
            . " UNION ALL SELECT 'patrol', FROM_UNIXTIME(a.create_time,'%Y-%m-%d'), COUNT(*) {$clockFrom} AND a.type = 3 AND a.create_time >= {$ago30} GROUP BY FROM_UNIXTIME(a.create_time,'%Y-%m-%d')"
            . " UNION ALL SELECT 'sign', FROM_UNIXTIME(create_time,'%Y-%m-%d'), COUNT(*) FROM {$p}active_sign WHERE create_time >= {$ago30} GROUP BY FROM_UNIXTIME(create_time,'%Y-%m-%d')"
            . " UNION ALL SELECT 'quiz', FROM_UNIXTIME(create_time,'%Y-%m-%d'), COUNT(*) FROM {$p}quiz_log WHERE create_time >= {$ago30} GROUP BY FROM_UNIXTIME(create_time,'%Y-%m-%d')"
            . " UNION ALL SELECT 'point', FROM_UNIXTIME(create_time,'%Y-%m-%d'), IFNULL(SUM(change_amount),0) FROM {$p}user_account_log WHERE delete_time = 0 AND account_type = 2 AND change_amount > 0 AND create_time >= {$ago30} GROUP BY FROM_UNIXTIME(create_time,'%Y-%m-%d')";
        $trendMap = [];
        foreach (Db::query($sql) as $row) {
            $trendMap[$row['d']][$row['t']] = (int)$row['c'];
        }
        $trend = [
            'labels' => [], 'user' => [], 'clock' => [], 'lj' => [],
            'patrol' => [], 'sign' => [], 'quiz' => [], 'point' => [],
        ];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} day"));
            $trend['labels'][] = date('n/j', strtotime($d));
            foreach (['user', 'clock', 'lj', 'patrol', 'sign', 'quiz', 'point'] as $k) {
                $trend[$k][] = isset($trendMap[$d][$k]) ? (int)$trendMap[$d][$k] : 0;
            }
        }

        $heatRows = Db::query(
            "SELECT DAYOFWEEK(FROM_UNIXTIME(a.create_time)) w, HOUR(FROM_UNIXTIME(a.create_time)) h, COUNT(*) c"
            . " {$clockFrom} AND a.create_time >= {$ago30}"
            . " GROUP BY DAYOFWEEK(FROM_UNIXTIME(a.create_time)), HOUR(FROM_UNIXTIME(a.create_time))"
        );
        $heat = [];
        foreach ($heatRows as $row) {
            $w = (int)$row['w'];
            $idx = $w === 1 ? 6 : $w - 2;
            $heat[] = [(int)$row['h'], $idx, (int)$row['c']];
        }

        $hourToday = array_fill(0, 24, 0);
        foreach (Db::query(
            "SELECT HOUR(FROM_UNIXTIME(a.create_time)) h, COUNT(*) c {$clockFrom} AND a.create_time >= {$today0} GROUP BY HOUR(FROM_UNIXTIME(a.create_time))"
        ) as $row) {
            $hourToday[(int)$row['h']] = (int)$row['c'];
        }

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
        $cateName = [];
        if (!empty($cateCount)) {
            $cateIds = array_slice(array_keys($cateCount), 0, 8);
            foreach (Db::query("SELECT id,name FROM {$p}basic_select WHERE id IN (" . implode(',', $cateIds) . ")") as $row) {
                $cateName[(int)$row['id']] = $row['name'];
            }
        }
        $catePie = [];
        foreach (array_slice($cateCount, 0, 6, true) as $cid => $c) {
            $catePie[] = ['name' => isset($cateName[$cid]) ? $cateName[$cid] : ('分类' . $cid), 'value' => $c];
        }
        $typeBar = [];
        foreach ($typeCount as $t => $c) {
            $typeBar[] = [
                'name'  => isset(MainController::CLOCK_TYPE_NAMES[$t]) ? MainController::CLOCK_TYPE_NAMES[$t] : ('类型' . $t),
                'value' => $c,
            ];
        }

        $communityBar = [];
        foreach (Db::query(
            "SELECT c.name n, COUNT(*) v FROM {$p}forum a"
            . " INNER JOIN {$p}member m ON m.id = a.user_id"
            . " INNER JOIN {$p}member_community c ON c.id = m.community_id"
            . " WHERE a.delete_time = 0 AND a.type = 2 AND a.create_time >= {$ago30} AND m.community_id > 0 AND c.delete_time = 0"
            . " GROUP BY m.community_id, c.name ORDER BY v DESC LIMIT 12"
        ) as $row) {
            $communityBar[] = ['name' => $row['n'], 'value' => (int)$row['v']];
        }

        $streetBar = [];
        foreach (Db::query(
            "SELECT p.name n, COUNT(*) v FROM {$p}member m"
            . " INNER JOIN {$p}member_community_parent p ON p.id = m.community_parent_id"
            . " WHERE m.community_parent_id > 0 AND p.delete_time = 0"
            . " GROUP BY m.community_parent_id, p.name ORDER BY v DESC LIMIT 10"
        ) as $row) {
            $streetBar[] = ['name' => $row['n'], 'value' => (int)$row['v']];
        }

        $typeNames = AccountLogModel::getAccountLogTypeName(2, -1);
        if (!is_array($typeNames)) {
            $typeNames = [];
        }
        $pointSource = [];
        foreach (Db::query(
            "SELECT type, COUNT(*) c, IFNULL(SUM(change_amount),0) s FROM {$p}user_account_log"
            . " WHERE delete_time = 0 AND account_type = 2 AND change_amount > 0 AND create_time >= {$ago30}"
            . " GROUP BY type ORDER BY s DESC LIMIT 8"
        ) as $row) {
            $tid = (int)$row['type'];
            $pointSource[] = [
                'name'  => isset($typeNames[$tid]) ? $typeNames[$tid] : ('类型' . $tid),
                'value' => (int)$row['s'],
                'count' => (int)$row['c'],
            ];
        }

        $taskStatus = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        foreach (Db::query("SELECT status, COUNT(*) c FROM {$p}volunteer_task_log GROUP BY status") as $row) {
            $taskStatus[(int)$row['status']] = (int)$row['c'];
        }
        $taskPie = [
            ['name' => '待提交', 'value' => $taskStatus[0]],
            ['name' => '待审核', 'value' => $taskStatus[1]],
            ['name' => '已驳回', 'value' => $taskStatus[2]],
            ['name' => '已通过', 'value' => $taskStatus[3]],
        ];

        $quizDist = [];
        foreach (Db::query("SELECT correct_count k, COUNT(*) c FROM {$p}quiz_log GROUP BY correct_count ORDER BY k") as $row) {
            $quizDist[] = ['name' => ((int)$row['k']) . ' 题对', 'value' => (int)$row['c']];
        }

        $funnel = [
            ['name' => '注册用户', 'value' => (int)($kpi['user_total'] ?? 0)],
            ['name' => '已绑小区', 'value' => (int)($kpi['user_bind'] ?? 0)],
            ['name' => '学过分类', 'value' => (int)($kpi['learn_user'] ?? 0)],
            ['name' => '垃圾打卡', 'value' => (int)($kpi['lj_total'] ?? 0)],
            ['name' => '活动报名', 'value' => (int)($kpi['sign_total'] ?? 0)],
        ];

        $radarMax = [
            max(1, (int)($kpi['user_7d'] ?? 0)),
            max(1, (int)($kpi['clock_7d'] ?? 0)),
            max(1, (int)($kpi['sign_today'] ?? 0) * 7 + 1),
            max(1, (int)($kpi['point_7d'] ?? 0)),
            max(1, (int)($kpi['quiz_today'] ?? 0) * 7 + 1),
            max(1, (int)($kpi['order_today'] ?? 0) * 7 + 1),
        ];
        $radar = [
            'indicator' => [
                ['name' => '7日注册', 'max' => max($radarMax[0], 10)],
                ['name' => '7日打卡', 'max' => max($radarMax[1], 10)],
                ['name' => '今日报名', 'max' => max((int)($kpi['sign_today'] ?? 0), 5)],
                ['name' => '7日积分', 'max' => max($radarMax[3], 10)],
                ['name' => '今日答题', 'max' => max((int)($kpi['quiz_today'] ?? 0), 5)],
                ['name' => '今日订单', 'max' => max((int)($kpi['order_today'] ?? 0), 5)],
            ],
            'value' => [
                (int)($kpi['user_7d'] ?? 0),
                (int)($kpi['clock_7d'] ?? 0),
                (int)($kpi['sign_today'] ?? 0),
                (int)($kpi['point_7d'] ?? 0),
                (int)($kpi['quiz_today'] ?? 0),
                (int)($kpi['order_today'] ?? 0),
            ],
        ];

        $latest = [];
        foreach (Db::query(
            "SELECT a.id, a.type, a.point, a.create_time, m.nickname"
            . " FROM {$p}forum a INNER JOIN {$p}member m ON m.id = a.user_id"
            . " WHERE a.delete_time = 0 ORDER BY a.id DESC LIMIT 12"
        ) as $row) {
            $tn = isset(MainController::CLOCK_TYPE_NAMES[(int)$row['type']])
                ? MainController::CLOCK_TYPE_NAMES[(int)$row['type']]
                : '动态';
            $nick = $row['nickname'] !== '' && $row['nickname'] !== null ? $row['nickname'] : '用户';
            $latest[] = [
                'text' => $nick . ' · ' . $tn . ' +' . (int)$row['point'] . '积分',
                'time' => date('H:i', (int)$row['create_time']),
            ];
        }

        $qxlRate = 0;
        $qxlTry  = (int)($kpi['qxl_try'] ?? 0);
        if ($qxlTry > 0) {
            $qxlRate = (int)round(((int)($kpi['qxl_pass'] ?? 0)) * 100 / $qxlTry);
        }

        $data = [
            'kpi'          => $kpi,
            'trend'        => $trend,
            'heat'         => $heat,
            'hour_today'   => $hourToday,
            'cate_pie'     => $catePie,
            'type_bar'     => $typeBar,
            'community'    => $communityBar,
            'street'       => $streetBar,
            'point_source' => $pointSource,
            'task_pie'     => $taskPie,
            'quiz_dist'    => $quizDist,
            'funnel'       => $funnel,
            'radar'        => $radar,
            'latest'       => $latest,
            'qxl_rate'     => $qxlRate,
            'update_time'  => date('Y-m-d H:i:s'),
            'cost_ms'      => (int)round((microtime(true) - $t0) * 1000),
            'from_cache'   => 0,
        ];

        try {
            Cache::set(self::CACHE_KEY, $data, self::CACHE_TTL);
        } catch (\Throwable $e) {
        }

        return $data;
    }

    private static function kvMap(array $rows)
    {
        $kv = [];
        foreach ($rows as $row) {
            $kv[(string)$row['k']] = (int)$row['v'];
        }
        return $kv;
    }
}
