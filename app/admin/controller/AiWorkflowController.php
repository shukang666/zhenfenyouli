<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;
use think\facade\Cache;

/**
 * PC 后台 - AI 工作流
 *
 * 实际可用的 AI 助手功能集合，帮助运营人员高效完成日常工作：
 * 1. AI文案工坊 - 生成活动标题、通知文案、推送消息
 * 2. 数据问答 - 自然语言查询统计数据
 * 3. 内容审核 - 违规内容检测
 * 4. 用户分群 - 用户行为分析
 * 5. 运营简报 - 自动生成周报
 */
class AiWorkflowController extends AdminBaseController
{
    // 看板缓存键（复用 MainController 的缓存键）
    const CACHE_KEY = 'admin_home_dashboard_v1';
    const CACHE_TTL = 60;

    /** 主页面 - 展示所有 AI 工具入口 */
    public function index()
    {
        $this->assign('title', 'AI 工作流');
        return $this->fetch('index');
    }

    /** AI 文案工坊页面 */
    public function copywriting()
    {
        return $this->fetch('copywriting');
    }

    /** 数据问答页面 */
    public function dataChat()
    {
        // 获取一些基础统计数据用于初始展示
        $stats = $this->getQuickStats();
        $this->assign('stats', $stats);
        return $this->fetch('data_chat');
    }

    /** 内容审核页面 */
    public function contentAudit()
    {
        return $this->fetch('content_audit');
    }

    /** 用户分群分析页面 */
    public function userSegment()
    {
        $segments = $this->analyzeUserSegments();
        $this->assign('segments', $segments);
        return $this->fetch('user_segment');
    }

    /** 运营简报页面 */
    public function weeklyReport()
    {
        $report = $this->generateOperationReport();
        $this->assign('report', $report);
        $this->assign('report_json', json_encode($report, JSON_UNESCAPED_UNICODE));
        return $this->fetch('weekly_report');
    }

    // ==================== AJAX 接口 ====================

    /**
     * 生成文案（AJAX）
     * POST 参数：
     *   - type: 文案类型 title/detail/notice/push
     *   - activity_type: 活动类型
     *   - theme: 活动主题
     *   - target_audience: 目标人群
     *   - tone: 语气风格 formal/warm/fun
     */
    public function generateCopy()
    {
        if (!$this->request->isAjax()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $type = $this->request->param('type', 'title', 'trim');
        $activityType = $this->request->param('activity_type', '', 'trim');
        $theme = $this->request->param('theme', '', 'trim');
        $targetAudience = $this->request->param('target_audience', '全体居民', 'trim');
        $tone = $this->request->param('tone', 'warm', 'trim');

        if (empty($theme)) {
            return json(['code' => 0, 'msg' => '请输入活动主题或关键词']);
        }

        $result = $this->doGenerateCopy($type, $activityType, $theme, $targetAudience, $tone);

        return json(['code' => 1, 'data' => $result]);
    }

    /**
     * 数据问答（AJAX）
     * POST 参数：
     *   - question: 用户自然语言问题
     */
    public function answerData()
    {
        if (!$this->request->isAjax()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $question = $this->request->param('question', '', 'trim');
        if (empty($question)) {
            return json(['code' => 0, 'msg' => '请输入您的问题']);
        }

        $result = $this->doAnswerData($question);

        return json(['code' => 1, 'data' => $result]);
    }

    /**
     * 内容审核（AJAX）
     * POST 参数：
     *   - content: 待审核的文本内容
     */
    public function auditContent()
    {
        if (!$this->request->isAjax()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $content = $this->request->param('content', '', 'trim');
        if (empty($content)) {
            return json(['code' => 0, 'msg' => '请输入需要审核的内容']);
        }

        $result = $this->doAuditContent($content);

        return json(['code' => 1, 'data' => $result]);
    }

    /**
     * 刷新用户分群数据（AJAX）
     */
    public function refreshSegments()
    {
        if (!$this->request->isAjax()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $segments = $this->analyzeUserSegments();

        return json(['code' => 1, 'data' => $segments]);
    }

    /**
     * 获取最新运营简报数据（AJAX）
     */
    public function refreshReport()
    {
        if (!$this->request->isAjax()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $report = $this->generateOperationReport();

        return json(['code' => 1, 'data' => $report]);
    }

    // ==================== 核心逻辑 ====================

    /**
     * 文案生成引擎
     */
    private function doGenerateCopy(string $type, string $activityType, string $theme, string $targetAudience, string $tone): array
    {
        $templates = $this->getCopyTemplates();
        $activityLabels = [
            'garbage' => '垃圾分类',
            'health' => '健康知识',
            'environment' => '环保公益',
            'volunteer' => '志愿服务',
            'learning' => '学习答题',
            'lottery' => '抽奖活动',
            'survey' => '问卷调查',
        ];

        $activityName = $activityLabels[$activityType] ?? '社区活动';

        switch ($type) {
            case 'title':
                return $this->generateTitles($activityName, $theme, $tone);
            case 'detail':
                return $this->generateDetail($activityName, $theme, $targetAudience, $tone);
            case 'notice':
                return $this->generateNotice($activityName, $theme, $tone);
            case 'push':
                return $this->generatePush($activityName, $theme);
            default:
                return ['items' => [], 'raw' => ''];
        }
    }

    /**
     * 生成活动标题候选
     */
    private function generateTitles(string $activityName, string $theme, string $tone): array
    {
        $titles = [];

        $titles[] = [
            'style' => '直接型',
            'text' => "{$activityName}：{$theme}活动开始啦"
        ];
        $titles[] = [
            'style' => '号召型',
            'text' => "【{$activityName}】{$theme}——一起来参与吧"
        ];
        $titles[] = [
            'style' => '激励型',
            'text' => "参与{$theme}，{$activityName}积分等你拿"
        ];

        if ($tone === 'fun') {
            $titles[] = [
                'style' => '轻松型',
                'text' => "嘿！{$theme}活动开始咯，{$activityName}一起嗨"
            ];
            $titles[] = [
                'style' => '互动型',
                'text' => "你还在等什么？{$theme}活动等你来挑战！"
            ];
        } elseif ($tone === 'formal') {
            $titles[] = [
                'style' => '通知型',
                'text' => "关于开展{$theme}{$activityName}的通知"
            ];
        }

        return [
            'type' => 'title',
            'items' => $titles,
            'tips' => '建议选择简洁明了的标题，突出活动亮点'
        ];
    }

    /**
     * 生成活动详情文案
     */
    private function generateDetail(string $activityName, string $theme, string $targetAudience, string $tone): array
    {
        $tpl = $this->getCopyTemplates()['detail'][$tone] ?? $this->getCopyTemplates()['detail']['warm'];

        $text = str_replace(
            ['{activity_name}', '{theme}', '{target}', '{start_date}', '{end_date}'],
            [$activityName, $theme, $targetAudience, date('n月j日'), date('n月j日', strtotime('+30 day'))],
            $tpl
        );

        return [
            'type' => 'detail',
            'items' => [
                ['label' => '正文版本', 'text' => $text]
            ],
            'tips' => '可直接复制到活动详情，根据实际时间修改起止日期'
        ];
    }

    /**
     * 生成首页通知文案
     */
    private function generateNotice(string $activityName, string $theme, string $tone): array
    {
        $notices = [];
        $notices[] = [
            'label' => '简短版（走马灯推荐）',
            'text' => "🎉 {$activityName}｜{$theme}火热进行中，速来参与 >>"
        ];
        $notices[] = [
            'label' => '正式版',
            'text' => "【重要通知】{$theme}{$activityName}已开始，欢迎广大居民积极参与。详情请点击查看。"
        ];
        $notices[] = [
            'label' => '活泼版',
            'text' => "✨ {$theme}活动开始啦！{$activityName}等你来挑战，丰富奖励等着您~"
        ];

        return [
            'type' => 'notice',
            'items' => $notices,
            'tips' => '走马灯文案建议控制在30字以内'
        ];
    }

    /**
     * 生成推送消息文案
     */
    private function generatePush(string $activityName, string $theme): array
    {
        $pushes = [];
        $pushes[] = [
            'label' => '订阅消息模板',
            'text' => "{{first.DATA}}\n活动主题：{$theme}\n活动类型：{$activityName}\n开始时间：{{keyword1.DATA}}\n结束时间：{{keyword2.DATA}}\n{{remark.DATA}}"
        ];
        $pushes[] = [
            'label' => '服务通知（小程序）',
            'text' => "【{$activityName}】{$theme}活动提醒\n\n您关注的{$activityName}有新活动啦！快来查看吧~\n\n活动时间：开始时间 - 结束时间"
        ];

        return [
            'type' => 'push',
            'items' => $pushes,
            'tips' => '推送文案需符合微信模板消息格式要求'
        ];
    }

    /**
     * 数据问答引擎
     */
    private function doAnswerData(string $question): array
    {
        $p = config('database.connections.mysql.prefix');
        $q = strtolower($question);
        $today0 = strtotime(date('Y-m-d'));
        $ago7 = strtotime(date('Y-m-d', strtotime('-6 day')));
        $ago30 = strtotime(date('Y-m-d', strtotime('-29 day')));

        $result = [
            'question' => $question,
            'answer' => '',
            'data' => [],
            'chart_type' => null,
        ];

        // 打卡次数相关
        if ((strpos($q, '打卡') !== false) && (strpos($q, '今天') !== false || strpos($q, '今日') !== false)) {
            $today = Db::name('forum')->where('delete_time', 0)->where('create_time', '>=', $today0)->count();
            $yesterday = Db::name('forum')->where('delete_time', 0)->where('create_time', '>=', $today0 - 86400)->where('create_time', '<', $today0)->count();
            $diff = $today - $yesterday;
            $diffText = $diff >= 0 ? "增加 {$diff}" : "减少 " . abs($diff);
            $result['answer'] = "今日（{$today})";
            $result['data'] = ['today' => $today, 'yesterday' => $yesterday];
            return $result;
        }

        // 近7天打卡趋势
        if (strpos($q, '打卡') !== false && (strpos($q, '近7') !== false || strpos($q, '一周') !== false || strpos($q, '本周') !== false || strpos($q, '7天') !== false)) {
            $sql = "SELECT FROM_UNIXTIME(create_time,'%Y-%m-%d') d, COUNT(*) c FROM {$p}forum WHERE delete_time = 0 AND create_time >= {$ago7} GROUP BY d ORDER BY d";
            $rows = Db::query($sql);
            $total = array_sum(array_column($rows, 'c'));
            $result['answer'] = "近7天打卡总次数 {$total}";
            $result['data'] = $rows;
            $result['chart_type'] = 'line';
            return $result;
        }

        // 垃圾分类分布
        if (strpos($q, '垃圾分类') !== false || (strpos($q, '垃圾') !== false && strpos($q, '分类') !== false)) {
            $sql = "SELECT s.name, COUNT(*) c FROM {$p}forum a LEFT JOIN {$p}basic_select s ON s.id = a.cate_id WHERE a.delete_time = 0 AND a.type = 2 AND a.create_time >= {$ago30} GROUP BY a.cate_id ORDER BY c DESC";
            $rows = Db::query($sql);
            $result['answer'] = "近30天垃圾分类分布";
            $result['data'] = $rows;
            $result['chart_type'] = 'bar';
            return $result;
        }

        // 用户相关
        if (strpos($q, '用户') !== false && (strpos($q, '新增') !== false || strpos($q, '今天') !== false || strpos($q, '今日') !== false)) {
            $today = Db::name('member')->where('create_time', '>=', $today0)->count();
            $result['answer'] = "今日新增用户 {$today}";
            $result['data' ] = ['today_users' => $today];
            return $result;
        }

        if (strpos($q, '用户') !== false && (strpos($q, '总数') !== false || strpos($q, '多少') !== false || strpos($q, '注册') !== false)) {
            $total = Db::name('member')->count();
            $today = Db::name('member')->where('create_time', '>=', $today0)->count();
            $week = Db::name('member')->where('create_time', '>=', $ago7)->count();
            $result['answer'] = "注册用户总数 {$total}";
            $result['data'] = ['total' => $total, 'today' => $today, 'week' => $week];
            return $result;
        }

        // 积分相关
        if (strpos($q, '积分') !== false && (strpos($q, '发放') !== false || strpos($q, '多少') !== false)) {
            $todayPoint = Db::name('user_account_log')->where('delete_time', 0)->where('account_type', 2)->where('change_amount', '>', 0)->where('create_time', '>=', $today0)->sum('change_amount');
            $weekPoint = Db::name('user_account_log')->where('delete_time', 0)->where('account_type', 2)->where('change_amount', '>', 0)->where('create_time', '>=', $ago7)->sum('change_amount');
            $result['answer'] = "今日积分发放 {$todayPoint}";
            $result['data' ] = ['today_point' => $todayPoint, 'week_point' => $weekPoint];
            return $result;
        }

        // 活动相关
        if (strpos($q, '活动') !== false && (strpos($q, '报名') !== false || strpos($q, '签到') !== false)) {
            $signToday = Db::name('active_sign')->where('create_time', '>=', $today0)->count();
            $signWeek = Db::name('active_sign')->where('create_time', '>=', $ago7)->count();
            $result['answer'] = "活动签到 今日 {$signToday}，本周 {$signWeek}";
            $result['data'] = ['today' => $signToday, 'week' => $signWeek];
            return $result;
        }

        // 订单相关
        if (strpos($q, '订单') !== false) {
            $todayOrder = Db::name('shop_order')->where('delete_time', 0)->where('create_time', '>=', $today0)->count();
            $pending = Db::name('shop_order')->where('delete_time', 0)->where('status', 'in', [2, 14])->count();
            $result['answer'] = "今日订单 {$todayOrder}";
            $result['data' ] = ['today_orders' => $todayOrder, 'pending' => $pending];
            return $result;
        }

        // 默认兜底
        $result['answer'] = '';
        return $result;
    }

    /**
     * 内容审核引擎
     */
    private function doAuditContent(string $content): array
    {
        $result = [
            'content' => $content,
            'risk_level' => 'low',
            'risk_label' => '低风险',
            'issues' => [],
            'suggestions' => [],
        ];

        $issues = [];
        $riskScore = 0;

        // 广告引流检测
        $adPatterns = ['加微信', '加QQ', '扫一扫', '关注公众号', '领取红包', '免费领', '点击链接'];
        foreach ($adPatterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                $issues[] = ['type' => 'ad', 'desc' => "疑似广告引流内容：包含「{$pattern}」"];
                $riskScore += 30;
            }
        }

        // 敏感词检测
        $sensitiveWords = ['垃圾', '滚', '骗子', '脑残', '傻', '操'];
        foreach ($sensitiveWords as $word) {
            if (strpos($content, $word) !== false) {
                $issues[] = ['type' => 'sensitive', 'desc' => "包含敏感词「{$word}」"];
                $riskScore += 20;
            }
        }

        // 联系方式检测
        if (preg_match('/1[3-9]\d{9}/', $content)) {
            $issues[] = ['type' => 'contact', 'desc' => '疑似包含手机号码'];
            $riskScore += 25;
        }

        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content)) {
            $issues[] = ['type' => 'contact', 'desc' => '疑似包含电子邮箱'];
            $riskScore += 25;
        }

        // 过长内容警告
        if (mb_strlen($content) > 500) {
            $issues[] = ['type' => 'length', 'desc' => '内容过长，建议控制在500字以内'];
            $riskScore += 5;
        }

        // 风险等级
        if ($riskScore >= 50) {
            $result['risk_level'] = 'high';
            $result['risk_label'] = '高风险';
        } elseif ($riskScore >= 20) {
            $result['risk_level'] = 'medium';
            $result['risk_label'] = '中风险';
        }

        $result['issues'] = $issues;
        $result['risk_score'] = $riskScore;

        if (empty($issues)) {
            $result['suggestions'][] = '未检测到明显问题，可正常使用';
        } else {
            $result['suggestions'][] = '建议人工复核上述标记内容后再发布';
        }

        return $result;
    }

    /**
     * 用户分群分析
     */
    private function analyzeUserSegments(): array
    {
        $p = config('database.connections.mysql.prefix');
        $today0 = strtotime(date('Y-m-d'));
        $ago7 = strtotime(date('Y-m-d', strtotime('-6 day')));
        $ago30 = strtotime(date('Y-m-d', strtotime('-29 day')));

        $totalUsers = Db::name('member')->count();
        if ($totalUsers == 0) {
            return ['total' => 0, 'segments' => []];
        }

        // 活跃用户：最近7天有打卡
        $activeUserIds = Db::name('forum')->where('delete_time', 0)->where('create_time', '>=', $ago7)->group('user_id')->column('user_id');
        $activeCount = count($activeUserIds);

        // 检查是否有积分记录来判断是否完成过任务
        $pointUserIds = Db::name('user_account_log')->where('create_time', '>=', $ago30)->where('change_amount', '>', 0)->group('user_id')->column('user_id');
        $pointCount = count($pointUserIds);

        // 新用户（7天内注册）
        $newUsers = Db::name('member')->where('create_time', '>=', $ago7)->count();

        // 计算各群体
        $inactiveCount = max(0, $totalUsers - $activeCount - $newUsers);
        // 从未积分（长期未参与）
        $neverActive = max(0, $totalUsers - $pointCount);

        return [
            'total' => $totalUsers,
            'update_time' => date('Y-m-d H:i:s'),
            'segments' => [
                [
                    'name' => '活跃用户',
                    'desc' => '最近7天有打卡记录',
                    'count' => $activeCount,
                    'percent' => round($activeCount / $totalUsers * 100, 1),
                    'color' => '#52c41a',
                    'icon' => 'fa-fire',
                    'suggestion' => '核心用户群体，优先推送新活动',
                ],
                [
                    'name' => '新用户',
                    'desc' => '7天内新注册',
                    'count' => $newUsers,
                    'percent' => round($newUsers / $totalUsers * 100, 1),
                    'color' => '#1890ff',
                    'icon' => 'fa-user-plus',
                    'suggestion' => '推送新手任务和引导活动',
                ],
                [
                    'name' => '沉默用户',
                    'desc' => '注册但近期未参与',
                    'count' => $inactiveCount,
                    'percent' => round($inactiveCount / $totalUsers * 100, 1),
                    'color' => '#faad14',
                    'icon' => 'fa-clock-o',
                    'suggestion' => '发送召回推送或优惠券',
                ],
                [
                    'name' => '未参与用户',
                    'desc' => '从未获得过积分',
                    'count' => $neverActive,
                    'percent' => round($neverActive / $totalUsers * 100, 1),
                    'color' => '#bfbfbf',
                    'icon' => 'fa-user-o',
                    'suggestion' => '建议推送简单入门任务',
                ],
            ],
        ];
    }

    /**
     * 生成运营简报
     */
    private function generateOperationReport(): array
    {
        $p = config('database.connections.mysql.prefix');
        $today0 = strtotime(date('Y-m-d'));
        $ago7 = strtotime(date('Y-m-d', strtotime('-6 day')));
        $ago30 = strtotime(date('Y-m-d', strtotime('-29 day')));

        // 基础数据
        $totalUsers = Db::name('member')->count();
        $todayUsers = Db::name('member')->where('create_time', '>=', $today0)->count();
        $weekUsers = Db::name('member')->where('create_time', '>=', $ago7)->count();

        $todayClock = Db::name('forum')->where('delete_time', 0)->where('create_time', '>=', $today0)->count();
        $weekClock = Db::name('forum')->where('delete_time', 0)->where('create_time', '>=', $ago7)->count();
        $monthClock = Db::name('forum')->where('delete_time', 0)->where('create_time', '>=', $ago30)->count();

        $todaySign = Db::name('active_sign')->where('create_time', '>=', $today0)->count();
        $weekSign = Db::name('active_sign')->where('create_time', '>=', $ago7)->count();

        $todayOrders = Db::name('shop_order')->where('delete_time', 0)->where('create_time', '>=', $today0)->count();

        $todayPoints = Db::name('user_account_log')->where('delete_time', 0)->where('account_type', 2)->where('change_amount', '>', 0)->where('create_time', '>=', $today0)->sum('change_amount');
        $weekPoints = Db::name('user_account_log')->where('delete_time', 0)->where('account_type', 2)->where('change_amount', '>', 0)->where('create_time', '>=', $ago7)->sum('change_amount');

        // 计算周环比
        $prevWeekUsers = Db::name('member')->where('create_time', '>=', $ago7 - 604800)->where('create_time', '<', $ago7)->count();
        $weekUserGrowth = $prevWeekUsers > 0 ? round(($weekUsers - $prevWeekUsers) / $prevWeekUsers * 100, 1) : 0;

        // 洞察生成
        $insights = [];
        if ($weekUserGrowth > 10) {
            $insights[] = ['type' => 'positive', 'text' => "本周用户增长 {$weekUserGrowth}%，增长势头良好"];
        } elseif ($weekUserGrowth < -10) {
            $insights[] = ['type' => 'negative', 'text' => "本周用户增长下降 " . abs($weekUserGrowth) . "%，需关注获客渠道"];
        } else {
            $insights[] = ['type' => 'normal', 'text' => "本周用户增长稳定，增长率 {$weekUserGrowth}%"];
        }

        if ($todayClock > 0) {
            $insights[] = ['type' => 'normal', 'text' => "今日打卡 {$todayClock} 次，社区参与度" . ($todayClock > 50 ? '较高' : '一般')];
        }

        if ($weekSign > 0) {
            $insights[] = ['type' => 'positive', 'text' => "本周活动签到 {$weekSign} 次，活动参与度" . ($weekSign > 20 ? '活跃' : '有待提升')];
        }

        if ($todayOrders > 0) {
            $insights[] = ['type' => 'normal', 'text' => "今日商城订单 {$todayOrders} 笔，兑换热度正常"];
        }

        // 趋势数据
        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day0 = $today0 - $i * 86400;
            $day1 = $day0 + 86400;
            $date = date('n/j', $day0);
            $clocks = Db::name('forum')->where('delete_time', 0)->where('create_time', '>=', $day0)->where('create_time', '<', $day1)->count();
            $users = Db::name('member')->where('create_time', '>=', $day0)->where('create_time', '<', $day1)->count();
            $signs = Db::name('active_sign')->where('create_time', '>=', $day0)->where('create_time', '<', $day1)->count();
            $trendData[] = ['date' => $date, 'clock' => $clocks, 'user' => $users, 'sign' => $signs];
        }

        // 行动建议
        $actions = [];
        if ($weekUserGrowth < -10) {
            $actions[] = ['icon' => 'fa-bullhorn', 'color' => '#f56c6c', 'text' => '用户增长明显下滑，建议加强推送触达与外部引流'];
        } elseif ($weekUserGrowth > 10) {
            $actions[] = ['icon' => 'fa-fire', 'color' => '#67c23a', 'text' => '增长势头良好，可乘势上新活动扩大战果'];
        } else {
            $actions[] = ['icon' => 'fa-balance-scale', 'color' => '#2D8CF0', 'text' => '用户增长平稳，建议优化活动质量提升留存'];
        }
        $actions[] = ['icon' => 'fa-users', 'color' => '#faad14', 'text' => '关注沉默用户召回，推送个性化活动推荐'];
        $actions[] = ['icon' => 'fa-gift', 'color' => '#4facfe', 'text' => '商城订单稳定，可考虑上架新品提升兑换率'];
        if ($todaySign < 10) {
            $actions[] = ['icon' => 'fa-calendar', 'color' => '#ff9c6e', 'text' => '今日活动签到较少，建议增加活动宣传'];
        }
        $actions[] = ['icon' => 'fa-recycle', 'color' => '#43e97b', 'text' => '持续关注垃圾分类打卡质量，引导更多居民参与'];

        return [
            'update_time' => date('Y-m-d H:i:s'),
            'period' => '截至 ' . date('Y年n月j日'),
            'stats' => [
                ['label' => '注册用户总数', 'value' => $totalUsers, 'icon' => 'fa-users'],
                ['label' => '本周新增用户', 'value' => $weekUsers, 'icon' => 'fa-user-plus'],
                ['label' => '今日打卡', 'value' => $todayClock, 'icon' => 'fa-check-circle-o'],
                ['label' => '本周打卡', 'value' => $weekClock, 'icon' => 'fa-calendar-check-o'],
                ['label' => '今日活动签到', 'value' => $todaySign, 'icon' => 'fa-flag-checkered'],
                ['label' => '今日订单', 'value' => $todayOrders, 'icon' => 'fa-shopping-cart'],
                ['label' => '今日积分发放', 'value' => $todayPoints, 'icon' => 'fa-diamond'],
                ['label' => '本周积分发放', 'value' => $weekPoints, 'icon' => 'fa-gift'],
            ],
            'week_growth' => $weekUserGrowth,
            'insights' => $insights,
            'trend_data' => $trendData,
            'actions' => $actions,
        ];
    }

    /**
     * 获取快速统计（用于初始展示）
     */
    private function getQuickStats(): array
    {
        $today0 = strtotime(date('Y-m-d'));
        return [
            'today_clock' => Db::name('forum')->where('delete_time', 0)->where('create_time', '>=', $today0)->count(),
            'total_users' => Db::name('member')->count(),
            'today_users' => Db::name('member')->where('create_time', '>=', $today0)->count(),
            'today_sign' => Db::name('active_sign')->where('create_time', '>=', $today0)->count(),
            'today_orders' => Db::name('shop_order')->where('delete_time', 0)->where('create_time', '>=', $today0)->count(),
        ];
    }

    /**
     * 文案模板库
     */
    private function getCopyTemplates(): array
    {
        return [
            'detail' => [
                'formal' => "{activity_name}活动通知\n\n尊敬的{target}：\n\n为推进社区建设，现就开展{theme}活动通知如下：\n\n一、活动主题\n{theme}\n\n二、活动时间\n{start_date}至{end_date}\n\n三、参与方式\n通过小程序进入活动页面，按指引完成相关任务即可。\n\n四、奖励说明\n完成活动任务可获得相应积分，积分可至商城兑换商品。\n\n欢迎广大居民积极参与！\n\n",
                'warm' => "🌟 {activity_name}·{theme} 开始了！\n\n亲爱的邻居们：\n\n{activity_name}新活动来啦！这次我们准备了{theme}主题活动，{X}开始，诚邀您参与！\n\n✨ 活动亮点：\n• 简单有趣，轻松参与\n• 完成任务赢积分\n• 积分可兑换精美礼品\n\n活动时间：{start_date} - {end_date}\n\n快来加入我们吧，期待与您一起度过有意义的时光~\n\n",
                'fun' => "🎉 好消息好消息！\n\n{activity_name}带着新活动来咯！这次的主题是——「{theme}」\n\n⏰ 活动时间：{start_date}起，一直到{end_date}\n\n🎁 怎么玩？打开小程序，按提示完成任务，积分蹭蹭涨，好礼兑不停！\n\n准备好了吗？一起冲鸭！💥\n\n",
            ],
        ];
    }
}
