<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * PC 后台 - 安全加固
 *
 * 四个标签页（每个标签一个 action，模板里用 nav-tabs 链接切换，与「算法中心」一致）：
 *   index()          封锁IP    ：cmf_security_block_ip，后台维护封锁名单
 *   log_audit()      日志审计  ：cmf_security_audit_log，记录本页产生的安全事件
 *   attack_map()     攻击地图  ：按封锁名单聚合出的来源地域 / 趋势，坐标来自腾讯地图 IP 定位
 *   global_setting() 全局设置  ：存 cmf_option 的 security_global，仅保存开关与参数
 *
 * 说明：全局设置里保存的开关本身不做拦截，实际拦截需要服务器 / 网关层按这些配置执行；
 *      页面上的口径以「封锁名单 + 审计日志」为准，不虚构拦截数据。
 */
class SecurityController extends AdminBaseController
{
    /** 全局设置存放的 cmf_option 键名 */
    const OPTION_NAME = 'security_global';

    /** 腾讯地图 key（与 shop/edit.html、active/update.html 用的是同一把，GL JS 与 WebService 共用） */
    const TENCENT_MAP_KEY = 'XQABZ-Z6GWP-OWEDN-V3K2Y-W43D6-D4B5P';

    /** 封锁来源（同时也是全局设置里各防御开关的含义） */
    const SOURCES = [
        'manual'  => '手动封禁',
        'cc'      => 'CC防御',
        'spider'  => '蜘蛛防御',
        'scanner' => '扫描器过滤',
        'other'   => '其他',
    ];

    /** 审计日志事件类型 */
    const LOG_TYPES = [
        'block'   => '封锁IP',
        'release' => '解除封锁',
        'setting' => '设置变更',
    ];

    /** 审计日志级别 */
    const LOG_LEVELS = [1 => '提示', 2 => '警告', 3 => '危险'];

    /* ==================== 公共 ==================== */

    /**
     * 四个标签页共用的导航（current 传当前 action）
     */
    private function assignTabs($current)
    {
        $tabs = [
            ['action' => 'index', 'name' => '封锁IP', 'icon' => 'ban'],
            ['action' => 'log_audit', 'name' => '日志审计', 'icon' => 'file-text-o'],
            ['action' => 'attack_map', 'name' => '攻击地图', 'icon' => 'map-marker'],
            ['action' => 'global_setting', 'name' => '全局设置', 'icon' => 'sliders'],
        ];
        foreach ($tabs as &$tab) {
            $tab['active'] = $tab['action'] === $current ? 1 : 0;
            $tab['url']    = (string)url('Security/' . $tab['action']);
        }
        unset($tab);
        $this->assign('sec_tabs', $tabs);

        // 下拉选项（搜索 / 表单共用，模板里不依赖 foreach 的 key 属性）
        $sources = [];
        foreach (self::SOURCES as $value => $name) {
            $sources[] = ['value' => $value, 'name' => $name];
        }
        $this->assign('sec_source_options', $sources);

        $types = [];
        foreach (self::LOG_TYPES as $value => $name) {
            $types[] = ['value' => $value, 'name' => $name];
        }
        $this->assign('sec_type_options', $types);

        $levels = [];
        foreach (self::LOG_LEVELS as $value => $name) {
            $levels[] = ['value' => $value, 'name' => $name];
        }
        $this->assign('sec_level_options', $levels);
    }

    /**
     * 当前登录管理员名（存日志用）
     */
    private function operatorName()
    {
        $adminId = (int)cmf_get_current_admin_id();
        if ($adminId <= 0) {
            return '';
        }
        $login = (string)Db::name('user')->where('id', $adminId)->value('user_login');
        return $login !== '' ? $login : ('管理员#' . $adminId);
    }

    /**
     * 写一条安全审计日志
     */
    private function writeLog($type, $level, $ip, $region, $target, $detail)
    {
        Db::name('security_audit_log')->insert([
            'type'        => $type,
            'level'       => (int)$level,
            'ip'          => (string)$ip,
            'region'      => mb_substr((string)$region, 0, 120, 'utf-8'),
            'target'      => mb_substr((string)$target, 0, 120, 'utf-8'),
            'detail'      => mb_substr((string)$detail, 0, 500, 'utf-8'),
            'operator'    => $this->operatorName(),
            'create_time' => time(),
        ]);
    }

    /**
     * 校验 IP 填法：单个 IP、网段(192.168.1.0/24)、通配(192.168.1.*)
     */
    private function isValidIpRule($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }
        if (strpos($ip, '/') !== false) {
            $parts = explode('/', $ip, 2);
            return filter_var($parts[0], FILTER_VALIDATE_IP)
                && is_numeric($parts[1]) && $parts[1] >= 0 && $parts[1] <= 128;
        }
        if (strpos($ip, '*') !== false) {
            return (bool)preg_match('/^(\d{1,3}|\*)\.(\d{1,3}|\*)\.(\d{1,3}|\*)\.(\d{1,3}|\*)$/', $ip);
        }
        return false;
    }

    /**
     * 查 IP 归属地（腾讯地图 WebService IP 定位）
     * 失败（key 无权限、网络不通、IP 是网段等）返回 null，不影响封锁本身
     */
    private function lookupIp($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }
        $url = 'https://apis.map.qq.com/ws/location/v1/ip?ip=' . urlencode($ip) . '&key=' . self::TENCENT_MAP_KEY;
        $ctx = stream_context_create(['http' => ['timeout' => 3, 'method' => 'GET']]);
        $json = @file_get_contents($url, false, $ctx);
        if (empty($json)) {
            return null;
        }
        $res = json_decode($json, true);
        if (!is_array($res) || (int)(isset($res['status']) ? $res['status'] : -1) !== 0 || empty($res['result'])) {
            return null;
        }
        $ad  = isset($res['result']['ad_info']) ? $res['result']['ad_info'] : [];
        $loc = isset($res['result']['location']) ? $res['result']['location'] : [];
        $region = trim(
            (isset($ad['nation']) ? $ad['nation'] : '') .
            (isset($ad['province']) ? $ad['province'] : '') .
            (isset($ad['city']) ? $ad['city'] : '') .
            (isset($ad['district']) ? $ad['district'] : '')
        );
        if ($region === '' && empty($loc)) {
            return null;
        }
        return [
            'region' => $region !== '' ? $region : '未知',
            'lng'    => (float)(isset($loc['lng']) ? $loc['lng'] : 0),
            'lat'    => (float)(isset($loc['lat']) ? $loc['lat'] : 0),
        ];
    }

    /**
     * 封锁到期文案
     */
    private function expireText($row)
    {
        if ((int)$row['status'] !== 1) {
            return '已解封';
        }
        $expire = (int)$row['expire_time'];
        if ($expire <= 0) {
            return '永久';
        }
        return $expire <= time() ? '已过期' : date('Y-m-d H:i', $expire);
    }

    /* ==================== 1. 封锁IP ==================== */

    public function index()
    {
        $this->assignTabs('index');

        $keyword = trim((string)$this->request->param('keyword', ''));
        $status  = (string)$this->request->param('status', '');
        $source  = trim((string)$this->request->param('source', ''));

        $where = [];
        if ($keyword !== '') {
            $where[] = ['ip|reason|remark|region', 'like', '%' . $keyword . '%'];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }
        if ($source !== '' && isset(self::SOURCES[$source])) {
            $where[] = ['source', '=', $source];
        }

        $query = Db::name('security_block_ip')->where($where)->order('status desc, id desc');
        $list  = $query->paginate(15);
        $list->appends(['keyword' => $keyword, 'status' => $status, 'source' => $source]);

        $rows = [];
        foreach ($list as $item) {
            $item['source_name'] = isset(self::SOURCES[$item['source']]) ? self::SOURCES[$item['source']] : $item['source'];
            $item['expire_text'] = $this->expireText($item);
            $item['create_text'] = $item['create_time'] > 0 ? date('Y-m-d H:i', $item['create_time']) : '-';
            $item['has_point']   = ((float)$item['lng'] != 0 || (float)$item['lat'] != 0) ? 1 : 0;
            $rows[]              = $item;
        }

        $this->assign('list', $rows);
        $this->assign('page', $list->render());
        $this->assign('keyword', $keyword);
        $this->assign('status', $status);
        $this->assign('source', $source);
        $this->assign('stat', $this->blockStat());

        return $this->fetch('index');
    }

    /**
     * 列表顶部统计
     */
    private function blockStat()
    {
        $now   = time();
        $today = strtotime(date('Y-m-d'));

        return [
            'blocking' => Db::name('security_block_ip')->where('status', 1)->count(),
            'today'    => Db::name('security_block_ip')->where('create_time', '>=', $today)->count(),
            'hits'     => (int)Db::name('security_block_ip')->sum('hits'),
            'released' => Db::name('security_block_ip')->where('status', 0)->count(),
            'expiring' => Db::name('security_block_ip')
                ->where('status', 1)->where('expire_time', '>', 0)->where('expire_time', '<=', $now + 86400)->count(),
        ];
    }

    /**
     * 新增封锁（表单提交）
     */
    public function block_add_post()
    {
        $ip       = trim((string)$this->request->param('ip', ''));
        $reason   = trim((string)$this->request->param('reason', ''));
        $source   = trim((string)$this->request->param('source', 'manual'));
        $remark   = trim((string)$this->request->param('remark', ''));
        $duration = (int)$this->request->param('duration', 0);
        $autoGeo  = (int)$this->request->param('auto_region', 1);

        if ($ip === '') {
            $this->error('请填写要封锁的 IP');
        }
        if (!$this->isValidIpRule($ip)) {
            $this->error('IP 格式不正确，支持单个 IP、网段（192.168.1.0/24）或通配（192.168.1.*）');
        }
        if (!isset(self::SOURCES[$source])) {
            $source = 'manual';
        }
        if (Db::name('security_block_ip')->where('ip', $ip)->where('status', 1)->count() > 0) {
            $this->error('该 IP 已在封锁名单中');
        }

        $region = '';
        $lng    = 0;
        $lat    = 0;
        if ($autoGeo) {
            $geo = $this->lookupIp($ip);
            if ($geo) {
                $region = $geo['region'];
                $lng    = $geo['lng'];
                $lat    = $geo['lat'];
            }
        }

        $now  = time();
        $data = [
            'ip'          => $ip,
            'reason'      => $reason !== '' ? $reason : '手动封禁',
            'source'      => $source,
            'hits'        => 0,
            'region'      => $region,
            'lng'         => $lng,
            'lat'         => $lat,
            'status'      => 1,
            'expire_time' => $duration > 0 ? $now + $duration * 60 : 0,
            'remark'      => $remark,
            'operator'    => $this->operatorName(),
            'create_time' => $now,
            'update_time' => $now,
        ];
        Db::name('security_block_ip')->insertGetId($data);

        $this->writeLog(
            'block',
            2,
            $ip,
            $region,
            '封锁IP：' . $ip,
            '原因：' . $data['reason'] . '；来源：' . self::SOURCES[$source]
            . '；时长：' . ($duration > 0 ? $duration . ' 分钟' : '永久')
        );

        $this->success('已加入封锁名单');
    }

    /**
     * 解除封锁
     */
    public function release()
    {
        $id  = (int)$this->request->param('id', 0);
        $row = Db::name('security_block_ip')->where('id', $id)->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }
        if ((int)$row['status'] !== 1) {
            $this->error('该 IP 当前不是封锁状态');
        }

        $now = time();
        Db::name('security_block_ip')->where('id', $id)->update([
            'status'       => 0,
            'release_time' => $now,
            'update_time'  => $now,
        ]);

        $this->writeLog('release', 1, $row['ip'], $row['region'], '解除封锁：' . $row['ip'], '原封锁原因：' . ($row['reason'] !== '' ? $row['reason'] : '-'));

        $this->success('已解除封锁');
    }

    /**
     * 删除封锁记录
     */
    public function block_delete()
    {
        $id  = (int)$this->request->param('id', 0);
        $row = Db::name('security_block_ip')->where('id', $id)->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }

        Db::name('security_block_ip')->where('id', $id)->delete();

        $this->writeLog('release', 2, $row['ip'], $row['region'], '删除封锁记录：' . $row['ip'], '该记录原状态：' . $this->expireText($row));

        $this->success('已删除');
    }

    /**
     * 重新解析归属地（ajax）
     */
    public function resolve_region()
    {
        $id  = (int)$this->request->param('id', 0);
        $row = Db::name('security_block_ip')->where('id', $id)->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }

        $geo = $this->lookupIp($row['ip']);
        if (empty($geo)) {
            $this->error('解析失败：该 IP 可能不是单个 IP，或地图 key 未开通 IP 定位权限');
        }

        Db::name('security_block_ip')->where('id', $id)->update([
            'region'      => $geo['region'],
            'lng'         => $geo['lng'],
            'lat'         => $geo['lat'],
            'update_time' => time(),
        ]);

        $this->success('已解析：' . $geo['region']);
    }

    /* ==================== 2. 日志审计 ==================== */

    public function log_audit()
    {
        $this->assignTabs('log_audit');

        $keyword = trim((string)$this->request->param('keyword', ''));
        $type    = trim((string)$this->request->param('type', ''));
        $level   = (string)$this->request->param('level', '');
        $start   = trim((string)$this->request->param('start_time', ''));
        $end     = trim((string)$this->request->param('end_time', ''));

        $where = [];
        if ($keyword !== '') {
            $where[] = ['ip|region|target|detail|operator', 'like', '%' . $keyword . '%'];
        }
        if ($type !== '' && isset(self::LOG_TYPES[$type])) {
            $where[] = ['type', '=', $type];
        }
        if ($level !== '') {
            $where[] = ['level', '=', (int)$level];
        }
        if ($start !== '') {
            $where[] = ['create_time', '>=', strtotime($start . ' 00:00:00')];
        }
        if ($end !== '') {
            $where[] = ['create_time', '<=', strtotime($end . ' 23:59:59')];
        }

        $list = Db::name('security_audit_log')->where($where)->order('id desc')->paginate(20);
        $list->appends([
            'keyword'    => $keyword,
            'type'       => $type,
            'level'      => $level,
            'start_time' => $start,
            'end_time'   => $end,
        ]);

        $rows = [];
        foreach ($list as $item) {
            $item['type_name']  = isset(self::LOG_TYPES[$item['type']]) ? self::LOG_TYPES[$item['type']] : $item['type'];
            $item['level_name'] = isset(self::LOG_LEVELS[$item['level']]) ? self::LOG_LEVELS[$item['level']] : '';
            $item['time_text']  = $item['create_time'] > 0 ? date('Y-m-d H:i:s', $item['create_time']) : '-';
            $rows[]             = $item;
        }

        $today = strtotime(date('Y-m-d'));
        $this->assign('list', $rows);
        $this->assign('page', $list->render());
        $this->assign('keyword', $keyword);
        $this->assign('type', $type);
        $this->assign('level', $level);
        $this->assign('start_time', $start);
        $this->assign('end_time', $end);
        $this->assign('stat', [
            'total'    => Db::name('security_audit_log')->count(),
            'today'    => Db::name('security_audit_log')->where('create_time', '>=', $today)->count(),
            'warning'  => Db::name('security_audit_log')->where('level', 2)->count(),
            'danger'   => Db::name('security_audit_log')->where('level', 3)->count(),
        ]);

        return $this->fetch('log_audit');
    }

    /* ==================== 3. 攻击地图 ==================== */

    public function attack_map()
    {
        $this->assignTabs('attack_map');

        $days  = 14;
        $start = strtotime(date('Y-m-d', strtotime('-' . ($days - 1) . ' day')));

        // 近 14 天每天新增封锁数
        $labels = [];
        $counts = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day          = date('Y-m-d', strtotime('-' . $i . ' day'));
            $labels[]     = $day;
            $counts[$day] = 0;
        }
        $trendRows = Db::name('security_block_ip')
            ->where('create_time', '>=', $start)
            ->field("FROM_UNIXTIME(create_time,'%Y-%m-%d') AS day, COUNT(*) AS num")
            ->group('day')
            ->select();
        foreach ($trendRows as $row) {
            if (isset($counts[$row['day']])) {
                $counts[$row['day']] = (int)$row['num'];
            }
        }
        $trend = [];
        foreach ($labels as $day) {
            $trend[] = $counts[$day];
        }

        // 来源地域 TOP10
        $regionRows = Db::name('security_block_ip')
            ->where('region', '<>', '')
            ->field('region, COUNT(*) AS num, SUM(hits) AS hit_num')
            ->group('region')
            ->order('num desc')
            ->limit(10)
            ->select();
        $regionTotal = 0;
        foreach ($regionRows as $row) {
            $regionTotal += (int)$row['num'];
        }
        $regions = [];
        foreach ($regionRows as $row) {
            $regions[] = [
                'region'  => $row['region'],
                'num'     => (int)$row['num'],
                'hits'    => (int)$row['hit_num'],
                'percent' => $regionTotal > 0 ? round((int)$row['num'] * 100 / $regionTotal, 1) : 0,
            ];
        }

        // 地图打点：封锁中且已解析出坐标的记录（最多 200 个，避免地图卡）
        $markerRows = Db::name('security_block_ip')
            ->where('status', 1)
            ->where('lng', '<>', 0)
            ->where('lat', '<>', 0)
            ->field('id,ip,region,reason,hits,lng,lat,create_time')
            ->order('id desc')
            ->limit(200)
            ->select();
        $markers = [];
        foreach ($markerRows as $row) {
            $markers[] = [
                'ip'     => $row['ip'],
                'region' => $row['region'],
                'reason' => $row['reason'],
                'hits'   => (int)$row['hits'],
                'lng'    => (float)$row['lng'],
                'lat'    => (float)$row['lat'],
            ];
        }

        $weekStart = strtotime(date('Y-m-d', strtotime('-6 day')));
        $this->assign('trend_labels', $labels);
        $this->assign('trend_values', $trend);
        $this->assign('regions', $regions);
        $this->assign('markers', $markers);
        $this->assign('map_key', self::TENCENT_MAP_KEY);
        $this->assign('stat', [
            'blocking'   => Db::name('security_block_ip')->where('status', 1)->count(),
            'week'       => Db::name('security_block_ip')->where('create_time', '>=', $weekStart)->count(),
            'region_num' => count($regionRows),
            'unresolved' => Db::name('security_block_ip')
                ->where('status', 1)->where('lng', 0)->where('lat', 0)->count(),
        ]);

        return $this->fetch('attack_map');
    }

    /* ==================== 4. 全局设置 ==================== */

    /**
     * 全局设置项定义：每个设置项就是模板里的一行
     * type: switch=开关, number=数字, text=文本
     */
    private function settingSchema()
    {
        return [
            [
                'title' => '攻击防御',
                'desc'  => '针对请求层面的攻击行为做拦截',
                'items' => [
                    ['key' => 'cc_defense', 'type' => 'switch', 'name' => 'CC防御', 'level' => '高', 'default' => 0, 'desc' => '同一个 IP 在短时间内高频请求时自动拦截，缓解 CC 攻击'],
                    ['key' => 'spider_defense', 'type' => 'switch', 'name' => '蜘蛛防御（防爬虫）', 'level' => '高', 'default' => 0, 'desc' => '识别并拦截爬虫抓取；需要放行搜索引擎时，把它的 IP 加进下方白名单'],
                    ['key' => 'scanner_filter', 'type' => 'switch', 'name' => '扫描器过滤', 'level' => '中', 'default' => 0, 'desc' => '拦截常见漏洞扫描器的探测请求（如 sqlmap、nmap、AWVS）'],
                    ['key' => 'bad_ua_filter', 'type' => 'switch', 'name' => '恶意UA拦截', 'level' => '中', 'default' => 0, 'desc' => '拦截 UA 为空或命中黑名单的请求'],
                ],
            ],
            [
                'title' => '注入与脚本过滤',
                'desc'  => '对请求参数、Cookie 做内容过滤',
                'items' => [
                    ['key' => 'xss_filter', 'type' => 'switch', 'name' => 'XSS过滤', 'level' => '高', 'default' => 0, 'desc' => '过滤请求参数中的跨站脚本代码'],
                    ['key' => 'sql_filter', 'type' => 'switch', 'name' => 'SQL注入过滤', 'level' => '高', 'default' => 0, 'desc' => '过滤请求参数中的 SQL 注入特征'],
                    ['key' => 'cookie_filter', 'type' => 'switch', 'name' => 'Cookie渗透过滤', 'level' => '中', 'default' => 0, 'desc' => '过滤 Cookie 中的注入与越权特征'],
                ],
            ],
            [
                'title' => '访问安全',
                'desc'  => '账号与文件层面的防护',
                'items' => [
                    ['key' => 'brute_force', 'type' => 'switch', 'name' => '暴力破解防护', 'level' => '高', 'default' => 0, 'desc' => '后台登录失败次数超限后临时封锁来源 IP'],
                    ['key' => 'upload_check', 'type' => 'switch', 'name' => '文件上传检测', 'level' => '中', 'default' => 0, 'desc' => '拦截 php、jsp 等可执行脚本类型的上传文件'],
                    ['key' => 'hotlink', 'type' => 'switch', 'name' => '防盗链', 'level' => '低', 'default' => 0, 'desc' => '拦截不是从本站页面发起的图片、附件引用'],
                ],
            ],
            [
                'title' => '策略参数',
                'desc'  => '上面开关生效时使用的参数',
                'items' => [
                    ['key' => 'cc_rate', 'type' => 'number', 'name' => 'CC防御阈值', 'level' => '', 'default' => 60, 'unit' => '次/秒', 'desc' => '同一个 IP 每秒允许的请求数上限'],
                    ['key' => 'cc_block_minutes', 'type' => 'number', 'name' => '触发后封锁时长', 'level' => '', 'default' => 30, 'unit' => '分钟', 'desc' => '命中防御规则后自动封锁该 IP 的时长'],
                    ['key' => 'log_keep_days', 'type' => 'number', 'name' => '审计日志保留天数', 'level' => '', 'default' => 90, 'unit' => '天', 'desc' => '封锁记录与审计日志建议保留的天数，超过后可定期清理'],
                    ['key' => 'ip_whitelist', 'type' => 'text', 'name' => 'IP白名单', 'level' => '', 'default' => '', 'unit' => '', 'placeholder' => '例如：127.0.0.1,192.168.1.0/24', 'desc' => '白名单内的 IP 不参与任何拦截，多个用英文逗号分隔'],
                ],
            ],
        ];
    }

    /**
     * 已保存的设置 + 默认值补齐（新增设置项时老数据也不会缺键）
     */
    private function globalSetting()
    {
        $saved = cmf_get_option(self::OPTION_NAME);
        if (!is_array($saved)) {
            $saved = [];
        }
        foreach ($this->settingSchema() as $group) {
            foreach ($group['items'] as $item) {
                if (!array_key_exists($item['key'], $saved)) {
                    $saved[$item['key']] = $item['default'];
                }
            }
        }
        return $saved;
    }

    /**
     * 设置项定义 + 当前值（模板里一行一个）
     */
    private function schemaWithValues()
    {
        $saved  = $this->globalSetting();
        $schema = $this->settingSchema();
        foreach ($schema as &$group) {
            foreach ($group['items'] as &$item) {
                $value          = $saved[$item['key']];
                $item['value']  = $value;
                $item['checked'] = $item['type'] === 'switch' && (int)$value === 1;
            }
            unset($item);
        }
        unset($group);
        return $schema;
    }

    /**
     * 设置值文案（写日志用）
     */
    private function settingValueText($item, $value)
    {
        if ($item['type'] === 'switch') {
            return (int)$value === 1 ? '开' : '关';
        }
        return $value === '' || $value === null ? '（空）' : (string)$value . (isset($item['unit']) ? $item['unit'] : '');
    }

    public function global_setting()
    {
        $this->assignTabs('global_setting');

        if ($this->request->isPost()) {
            $input = $this->request->param('setting/a', []);
            if (!is_array($input)) {
                $input = [];
            }

            $old     = $this->globalSetting();
            $saved   = [];
            $changed = [];

            foreach ($this->settingSchema() as $group) {
                foreach ($group['items'] as $item) {
                    $key = $item['key'];
                    if ($item['type'] === 'switch') {
                        $value = (!empty($input[$key])) ? 1 : 0;
                    } elseif ($item['type'] === 'number') {
                        $value = (int)(isset($input[$key]) ? $input[$key] : 0);
                        if ($value < 0) {
                            $value = 0;
                        }
                    } else {
                        $value = trim((string)(isset($input[$key]) ? $input[$key] : ''));
                    }

                    $saved[$key]   = $value;
                    $oldValue      = isset($old[$key]) ? $old[$key] : null;
                    if ((string)$oldValue !== (string)$value) {
                        $changed[] = $item['name'] . '：' . $this->settingValueText($item, $oldValue) . ' → ' . $this->settingValueText($item, $value);
                    }
                }
            }

            $res = cmf_set_option(self::OPTION_NAME, $saved, true);
            if ($res === false) {
                $this->error('保存失败');
            }

            if (!empty($changed)) {
                $this->writeLog(
                    'setting',
                    2,
                    (string)$this->request->ip(),
                    '',
                    '安全加固-全局设置',
                    '修改 ' . count($changed) . ' 项：' . implode('；', array_slice($changed, 0, 6))
                );
            }

            $this->success('保存成功');
        }

        $this->assign('schema', $this->schemaWithValues());
        return $this->fetch('global_setting');
    }
}
