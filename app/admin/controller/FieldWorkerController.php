<?php
// +----------------------------------------------------------------------
// | 外勤工作台 - 录入端（复用后台账密体系）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\BaseController;
use think\facade\Db;

/**
 * 外勤工作台入口
 * 外勤人员在后台登录页切「外勤工作台」标签登录（FIELD_WORKER_* session）
 * 仍继承 BaseController，避免套上后台框架壳
 */
class FieldWorkerController extends BaseController
{
    protected $admin_id;

    protected function initialize()
    {
        parent::initialize();
        $this->admin_id = session('FIELD_WORKER_ID');
    }

    /**
     * 加载模板输出（关键！）
     * 本控制器继承 BaseController 而非 AdminBaseController（避免后台登录拦截），
     * 但 BaseController 的 fetch() 不做模板路径解析，无扩展名的模板名会被
     * Think 视图驱动按默认规则解析到站点根目录 view/admin/ 下而报错。
     * 这里复刻 AdminBaseController 的 fetch + parseTemplate，
     * 先解析出带 .html 后缀的主题模板路径（如 themes/admin_fengiy/admin/field_worker/login.html），
     * 驱动见到带扩展名就会跳过自己的路径推算，直接 is_file 命中。
     */
    protected function fetch($template = '', $vars = [], $config = [])
    {
        $template = $this->parseTemplate($template);
        return $this->view->fetch($template, $vars, $config);
    }

    /**
     * 自动定位模板文件（复刻 AdminBaseController::parseTemplate）
     * 返回相对入口目录(public/)的路径：themes/<后台主题>/admin/<controller>/<action>.html
     */
    protected function parseTemplate($template)
    {
        $request = $this->request;

        if (strpos($template, '@')) {
            // 跨模块调用
            list($app, $template) = explode('@', $template);
        }

        $cmfAdminThemePath    = config('template.cmf_admin_theme_path');
        $cmfAdminDefaultTheme = cmf_get_current_admin_theme();
        $themePath            = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}/";

        $app = isset($app) ? $app : $this->app->http->getName();

        $depr = config('view.view_depr');
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

    /**
     * 初始化视图配置（关键！）
     * 本控制器继承 BaseController 而非 AdminBaseController（避免后台登录拦截），
     * BaseController 不设置模板替换字符串，模板里的 __STATIC__ / __TMPL__ 不会替换。
     * 这里复刻 AdminBaseController 的视图配置。
     */
    protected function _initializeView()
    {
        $cmfAdminThemePath    = config('template.cmf_admin_theme_path');
        $cmfAdminDefaultTheme = cmf_get_current_admin_theme();

        $themePath = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}";

        $root = cmf_get_root();

        // 使 cdn 设置生效（与 AdminBaseController 行为保持一致）
        $cdnSettings = cmf_get_option('cdn_settings');
        if (empty($cdnSettings['cdn_static_root'])) {
            $viewReplaceStr = [
                '__ROOT__'     => $root,
                '__TMPL__'     => "{$root}/{$themePath}",
                '__STATIC__'   => "{$root}/static",
                '__WEB_ROOT__' => $root
            ];
        } else {
            $cdnStaticRoot  = rtrim($cdnSettings['cdn_static_root'], '/');
            $viewReplaceStr = [
                '__ROOT__'     => $root,
                '__TMPL__'     => "{$cdnStaticRoot}/{$themePath}",
                '__STATIC__'   => "{$cdnStaticRoot}/static",
                '__WEB_ROOT__' => $cdnStaticRoot
            ];
        }

        $viewBase = WEB_ROOT . $themePath . '/';

        $this->view->engine()->config([
            'view_base'          => $viewBase,
            'tpl_replace_string' => $viewReplaceStr
        ]);
    }

    /**
     * 是否已登录
     */
    protected function isLoggedIn()
    {
        return !empty($this->admin_id);
    }

    /**
     * 要求已登录工作台（FIELD_WORKER_ID，与后台 ADMIN_ID 分开）
     */
    protected function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            session('__LOGIN_BY_CMF_ADMIN_PW__', 1);
            $loginUrl = $this->fieldLoginUrl();
            if ($this->request->isPost() || $this->request->isAjax()) {
                $this->error('请先登录', $loginUrl);
            } else {
                return $this->redirect($loginUrl);
            }
        }
    }

    /**
     * 外勤登录页地址。必须带 entry=field（服务端能读到），不能只靠 #field（浏览器不会发给服务器）。
     */
    protected function fieldLoginUrl()
    {
        return (string) url('admin/Public/login') . '?entry=field';
    }

    protected function assignWorker()
    {
        $this->assign('workerName', session('FIELD_WORKER_NAME') ?: '');
    }

    /**
     * 独立登录页已下线，跳到后台登录页的「外勤工作台」标签
     */
    public function login()
    {
        session('__LOGIN_BY_CMF_ADMIN_PW__', 1);
        if ($this->isLoggedIn()) {
            return redirect(url('admin/FieldWorker/index'));
        }
        return $this->redirect($this->fieldLoginUrl());
    }

    /**
     * 工作台登录（后台登录页「外勤工作台」标签提交）
     */
    public function doLogin()
    {
        if (!$this->request->isPost()) {
            $this->error('非法登录!');
        }

        $name = $this->request->param('username', '', 'trim');
        $pass = $this->request->param('password', '', 'trim');

        if (empty($name)) {
            $this->error('请输入用户名');
        }
        if (empty($pass)) {
            $this->error('请输入密码');
        }

        if (strpos($name, "@") > 0) {
            $where[] = ['user_email', '=', $name];
        } else {
            $where[] = ['user_login|mobile', '=', $name];
        }
        $where[] = ['user_type', 'in', [1, 3]];

        $user = Db::name('user')->where($where)->find();

        if (empty($user)) {
            $this->error('账号或密码错误');
        }

        if (!cmf_compare_password($pass, $user['user_pass'])) {
            $this->error('账号或密码错误');
        }

        if ($user['id'] != 1 && $user['user_status'] == 0) {
            $this->error('该账号已被禁用');
        }

        session('FIELD_WORKER_ID', $user['id']);
        session('FIELD_WORKER_NAME', $user['user_login']);

        $this->success('登录成功', url('admin/FieldWorker/index'));
    }

    /**
     * 退出工作台，回到登录页外勤标签
     */
    public function logout()
    {
        session('FIELD_WORKER_ID', null);
        session('FIELD_WORKER_NAME', null);
        session('__LOGIN_BY_CMF_ADMIN_PW__', 1);
        return redirect($this->fieldLoginUrl());
    }

    /**
     * 工作台首页 - 功能入口
     */
    public function index()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        $this->assignWorker();
        return $this->fetch('field_worker/index');
    }

    /**
     * 督导监测（点位巡查录入，原首页）
     */
    public function monitor()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        $lastCommunityId = 0;
        $last = Db::name('supervision_monitor')
            ->where('user_id', $this->admin_id)
            ->order('id desc')
            ->field('community_id')
            ->find();
        if (!empty($last['community_id'])) {
            $lastCommunityId = intval($last['community_id']);
        }

        $this->assignWorker();
        $this->assign('lastCommunityId', $lastCommunityId);
        return $this->fetch('field_worker/monitor');
    }

    /**
     * 个人信息
     */
    public function profile()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        $user = Db::name('user')
            ->where('id', $this->admin_id)
            ->field('id,user_login,user_nickname,user_email,mobile,last_login_time')
            ->find();
        if (empty($user)) {
            $user = [];
        }

        $lastLoginText = '—';
        if (!empty($user['last_login_time'])) {
            $lastLoginText = date('Y-m-d H:i', intval($user['last_login_time']));
        }

        $this->assignWorker();
        $this->assign('user', $user);
        $this->assign('lastLoginText', $lastLoginText);
        return $this->fetch('field_worker/profile');
    }

    /**
     * 数据统计（督导监测）
     */
    public function stats()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        $payload = [
            'all'  => $this->collectMonitorStats(0),
            'mine' => $this->collectMonitorStats(intval($this->admin_id)),
        ];

        $this->assignWorker();
        $this->assign('statsJson', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP));
        return $this->fetch('field_worker/stats');
    }

    /**
     * 修改密码
     */
    public function changePassword()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }

        $oldPassword = $this->request->param('old_password', '', 'trim');
        $password    = $this->request->param('password', '', 'trim');
        $rePassword  = $this->request->param('re_password', '', 'trim');

        if ($oldPassword === '') {
            $this->error('请输入原密码');
        }
        if ($password === '') {
            $this->error('请输入新密码');
        }
        if (strlen($password) < 6) {
            $this->error('新密码至少 6 位');
        }
        if ($password !== $rePassword) {
            $this->error('两次输入的新密码不一致');
        }

        $user = Db::name('user')->where('id', $this->admin_id)->find();
        if (empty($user)) {
            $this->error('账号不存在');
        }
        if (!cmf_compare_password($oldPassword, $user['user_pass'])) {
            $this->error('原密码不正确');
        }
        if (cmf_compare_password($password, $user['user_pass'])) {
            $this->error('新密码不能与原密码相同');
        }

        Db::name('user')->where('id', $this->admin_id)->update([
            'user_pass' => cmf_password($password),
        ]);

        $this->success('密码已修改');
    }

    /**
     * 所属社区列表（与小程序 /wxapp/forum/get_community_parent_list 同源）
     * 返回 member_community_parent 表的 id, name
     */
    public function communityParentList()
    {
        $list = Db::name('member_community_parent')
            ->field('id,name')
            ->order('id desc')
            ->select();

        $this->success('ok', '', $list);
    }

    /**
     * 搜索小区/点位（复用 search_community 逻辑，返回 name + parent_name）
     */
    public function searchCommunity()
    {
        $keyword = trim($this->request->param('keyword', ''));
        $page    = intval($this->request->param('page', 1));
        $pageSize = intval($this->request->param('page_size', 20));

        // 去除特殊符号（与小程序 search_community 一致）
        $pureKeyword = preg_replace('/[^\p{Han}a-zA-Z0-9]/u', '', $keyword);

        $ignoreSymbols = [' ','　','·','•','‧','・','∙','･','.','．','。',',','，','-','–','—','－','_','~','～','(',')','（',')','[',']','【',']','“','”','‘','’'];
        $nameExpr = 'c.name';
        foreach ($ignoreSymbols as $symbol) {
            $nameExpr = "REPLACE({$nameExpr}, '{$symbol}', '')";
        }

        $query = Db::name('member_community')->alias('c')
            ->leftJoin('member_community_parent p', 'p.id = c.pid')
            ->field('c.id, c.name, c.pid, p.name as parent_name');

        if ($pureKeyword !== '') {
            $query->whereRaw("{$nameExpr} LIKE :keyword", ['keyword' => '%' . $pureKeyword . '%']);
        }

        $query->order('c.list_order asc');

        $total = $query->count();
        $list = $query->limit(($page - 1) * $pageSize, $pageSize)->select();

        // 注意：BaseController::success($msg, $url, $data)，数据必须放第三参数
        $this->success('ok', '', [
            'data'      => $list,
            'total'     => $total,
            'last_page' => ceil($total / $pageSize),
            'page'      => $page,
        ]);
    }

    /**
     * 上传图片
     */
    public function upload()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }

        $file = $this->request->file('file');
        if (!$file) {
            $this->error('请选择文件');
        }

        // cmf\lib\Upload 写 cmf_asset 表时通过 cmf_get_current_user_id() 取 session('user.id')，
        // 外勤登录用的是自定义 session key，取不到会导致 asset.user_id 为 null（SQLSTATE 1048）。
        // 这里同步当前外勤账号 id（cmf_user 表 id）到 session('user.id')。
        session('user.id', $this->admin_id);

        $uploader = new \cmf\lib\Upload();
        $uploader->setFileType('image');
        $result = $uploader->upload();

        if ($result === false) {
            $this->error($uploader->getError());
        }

        if (!empty($result['filepath'])) {
            $result['url'] = cmf_get_image_url($result['filepath']);
        } elseif (!empty($result['url']) && strpos($result['url'], 'http') !== 0) {
            $result['url'] = cmf_get_domain() . (strpos($result['url'], '/') === 0 ? '' : '/') . ltrim($result['url'], '/');
        }

        unset($result['name'], $result['id'], $result['preview_url']);
        // 注意：BaseController::success($msg, $url, $data)，数据必须放第三参数
        $this->success('上传成功!', '', $result);
    }

    /**
     * 提交点位巡查（督导监测表单）
     * 字段与必填规则完全对齐小程序 /wxapp/supervision_monitor/post
     * （api\wxapp\controller\SupervisionMonitorController::post）
     */
    public function submit()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }

        $communityId   = intval($this->request->param('community_id', 0));
        $communityName = trim($this->request->param('community_name', ''));
        $unitName      = trim($this->request->param('unit_name', ''));
        $unitType      = intval($this->request->param('unit_type', 0));
        $checkDate     = trim($this->request->param('check_date', ''));
        $address       = trim($this->request->param('address', ''));
        $contact       = trim($this->request->param('contact', ''));
        $contactPhone  = trim($this->request->param('contact_phone', ''));

        // 必填校验（与小程序接口一致）
        if ($communityId <= 0 || empty($communityName)) {
            $this->error('请选择所属社区');
        }
        if (empty($unitName)) {
            $this->error('请填写单位名称');
        }
        if (!in_array($unitType, [1, 2, 3, 4])) {
            $this->error('请选择单位类型');
        }
        if (empty($checkDate)) {
            $this->error('请选择日期');
        }
        if (empty($address)) {
            $this->error('请填写地址');
        }

        // 是否类字段（1=是 0=否，未选择按 0 存，与小程序接口一致）
        $bool = function ($name) {
            return $this->request->param($name, 0) == 1 ? 1 : 0;
        };

        $data = [
            'user_id'              => $this->admin_id,
            'community_id'         => $communityId,
            'community_name'       => $communityName,
            'unit_name'            => $unitName,
            'unit_type'            => $unitType,
            'check_date'           => $checkDate,
            'address'              => $address,
            'contact'              => $contact,
            'contact_phone'        => $contactPhone,
            'has_waste'            => $bool('has_waste'),
            'waste_correct'        => $bool('waste_correct'),
            'waste_flow'           => trim($this->request->param('waste_flow', '')),
            'door_photo'           => trim($this->request->param('door_photo', '')),
            'inner_photo'          => trim($this->request->param('inner_photo', '')),
            'publicity_photo'      => trim($this->request->param('publicity_photo', '')),
            'inner_qualified'      => $bool('inner_qualified'),
            'need_rectify'         => $bool('need_rectify'),
            'inner_rectify_photo'  => trim($this->request->param('inner_rectify_photo', '')),
            'point_photo'          => trim($this->request->param('point_photo', '')),
            'point_qualified'      => $bool('point_qualified'),
            'point_rectify_photo'  => trim($this->request->param('point_rectify_photo', '')),
            'manage_photo'         => trim($this->request->param('manage_photo', '')),
            'manage_qualified'     => $bool('manage_qualified'),
            'manage_rectify_photo' => trim($this->request->param('manage_rectify_photo', '')),
            'transport_photo'      => trim($this->request->param('transport_photo', '')),
            'catering_photo'       => trim($this->request->param('catering_photo', '')),
            'accuracy_photo'       => trim($this->request->param('accuracy_photo', '')),
            'overall_evaluation'   => trim($this->request->param('overall_evaluation', '')),
            'submitter_name'       => session('FIELD_WORKER_NAME') . '（工作Pad）',
            'create_time'          => time(),
        ];

        Db::name('supervision_monitor')->insert($data);

        $this->success('已提交');
    }

    /**
     * 列表页 — JSON接口供前端Tab加载
     */
    public function records()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        $page   = intval($this->request->param('page', 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $total = Db::name('supervision_monitor')
            ->where('user_id', $this->admin_id)
            ->count();

        $list = Db::name('supervision_monitor')
            ->where('user_id', $this->admin_id)
            ->field('id, community_name, unit_name, check_date, door_photo, inner_photo, publicity_photo')
            ->order('id desc')
            ->limit($offset, $limit)
            ->select();

        // 注意：BaseController::success($msg, $url, $data)，数据必须放第三参数
        $this->success('ok', '', [
            'list'      => $list,
            'total'     => $total,
            'page'      => $page,
            'totalPage' => ceil($total / $limit),
        ]);
    }

    /**
     * 督导监测统计（userId=0 为全部）
     */
    protected function collectMonitorStats($userId)
    {
        $unitNames = [1 => '公共机构', 2 => '经营场所', 3 => '沿街商铺', 4 => '经营区域'];
        $today = date('Y-m-d');
        $monthPrefix = date('Y-m');
        $trendStart = date('Y-m-d', strtotime('-29 days'));

        $total = intval($this->monitorQuery($userId)->count());
        $todayCount = intval($this->monitorQuery($userId)->where('check_date', $today)->count());
        $monthCount = intval($this->monitorQuery($userId)->whereLike('check_date', $monthPrefix . '%')->count());
        $padCount = intval($this->monitorQuery($userId)->whereLike('submitter_name', '%工作Pad%')->count());
        $rectifyCount = intval($this->monitorQuery($userId)->where('need_rectify', 1)->count());

        $unitRows = $this->toRows(
            $this->monitorQuery($userId)->field('unit_type, COUNT(*) as c')->group('unit_type')->select()
        );
        $unitMap = [];
        foreach ($unitRows as $row) {
            $unitMap[intval($row['unit_type'])] = intval($row['c']);
        }
        $units = [];
        foreach ($unitNames as $type => $name) {
            $units[] = ['name' => $name, 'value' => isset($unitMap[$type]) ? $unitMap[$type] : 0];
        }

        $communityRows = $this->toRows(
            $this->monitorQuery($userId)
                ->field('community_name, COUNT(*) as c')
                ->group('community_name')
                ->order('c desc')
                ->limit(8)
                ->select()
        );
        $communities = [];
        foreach ($communityRows as $row) {
            $name = trim(strval($row['community_name']));
            $communities[] = [
                'name'  => $name !== '' ? $name : '未填写',
                'value' => intval($row['c']),
            ];
        }

        $agg = $this->monitorQuery($userId)
            ->fieldRaw('SUM(CASE WHEN inner_qualified=1 THEN 1 ELSE 0 END) AS inner_yes, SUM(CASE WHEN point_qualified=1 THEN 1 ELSE 0 END) AS point_yes, SUM(CASE WHEN manage_qualified=1 THEN 1 ELSE 0 END) AS manage_yes, SUM(CASE WHEN waste_correct=1 THEN 1 ELSE 0 END) AS waste_yes, SUM(CASE WHEN has_waste=1 THEN 1 ELSE 0 END) AS waste_has')
            ->find();
        if (empty($agg)) {
            $agg = [];
        }
        $rates = [
            ['name' => '内部环境合格', 'yes' => intval(isset($agg['inner_yes']) ? $agg['inner_yes'] : 0)],
            ['name' => '投放点合格', 'yes' => intval(isset($agg['point_yes']) ? $agg['point_yes'] : 0)],
            ['name' => '点位管理合格', 'yes' => intval(isset($agg['manage_yes']) ? $agg['manage_yes'] : 0)],
            ['name' => '厨余正确分出', 'yes' => intval(isset($agg['waste_yes']) ? $agg['waste_yes'] : 0)],
        ];
        foreach ($rates as &$rate) {
            $rate['total'] = $total;
            $rate['pct'] = $total > 0 ? round($rate['yes'] * 1000 / $total) / 10 : 0;
        }
        unset($rate);

        $trendMap = [];
        $trendRows = $this->toRows(
            $this->monitorQuery($userId)
                ->where('check_date', '>=', $trendStart)
                ->field('check_date, COUNT(*) as c')
                ->group('check_date')
                ->select()
        );
        foreach ($trendRows as $row) {
            $trendMap[$row['check_date']] = intval($row['c']);
        }
        $trend = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime('-' . $i . ' days'));
            $trend[] = [
                'date'  => $d,
                'label' => date('m-d', strtotime($d)),
                'value' => isset($trendMap[$d]) ? $trendMap[$d] : 0,
            ];
        }

        return [
            'total'     => $total,
            'today'     => $todayCount,
            'month'     => $monthCount,
            'pad'       => $padCount,
            'mini'      => max(0, $total - $padCount),
            'rectify'   => $rectifyCount,
            'units'     => $units,
            'communities' => $communities,
            'rates'     => $rates,
            'trend'     => $trend,
            'wasteHas'  => intval(isset($agg['waste_has']) ? $agg['waste_has'] : 0),
        ];
    }

    protected function monitorQuery($userId)
    {
        $query = Db::name('supervision_monitor');
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }
        return $query;
    }

    protected function toRows($result)
    {
        if ($result === null || $result === false) {
            return [];
        }
        if (is_object($result) && method_exists($result, 'toArray')) {
            return $result->toArray();
        }
        return array_values((array) $result);
    }
}
