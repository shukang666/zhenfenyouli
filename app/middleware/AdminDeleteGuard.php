<?php
// +----------------------------------------------------------------------
// | 后台删除操作的服务端二次校验
// +----------------------------------------------------------------------

namespace app\middleware;

use Closure;
use think\facade\Log;
use think\Request;
use think\Response;

/**
 * 后台「删除类」请求守卫
 *
 * ## 为什么需要它
 *
 * 后台的「请输入确认两个字」弹窗（public/static/js/admin-delete-confirm.js）只是**前端**拦截：
 * 任何人只要拿到管理员 Cookie，直接 POST /admin/member/delete.html?id=2 就能把数据删掉——
 * 前端弹窗根本不会出现（同时也是一条典型的 CSRF 漏洞）。
 *
 * 本中间件把校验搬到服务端：
 *   1. 每个会话生成一个随机令牌，存在 session（cmf_admin_delete_token）；
 *   2. 普通后台页面响应时，把令牌同步到一个 JS 可读的 cookie（cmf_dc_token）；
 *   3. 「删除类」请求必须携带参数 _dc_token 且与 session 中的令牌一致，否则一律拒绝执行。
 *
 * 前端由 admin-delete-confirm.js 里的 $.ajaxPrefilter 自动补上该参数，
 * 所以正常点「删除」按钮的流程完全无感知；而 curl、跨站伪造（拿不到令牌）会被拦下。
 *
 * ## 拦截范围
 *
 * 只拦后台（路径以 admin/ 开头）的「删除类」动作：
 *   - 最后一段路径以 del / delete 开头：delete、delete_all、deleteCate、deleteManyGoods…
 *   - 或以 _del / _delete 结尾：cate_delete、push_delete、answer_delete、block_delete…
 * 插件后台（走 admin/plugin/index?_action=xxx）、其它应用、小程序接口一律放行。
 *
 * 注意别写成「包含 del 就拦」：WxOrderController::getDelivery（url get_delivery.html）
 * 名字里也有 del，但它是只读的查询动作，拦了会直接废掉订单查询。
 *
 * ## 排查
 *
 * 删除按钮点了报「未通过安全校验」：
 *   1. 先看日志 `runtime/log/`：`[AdminDeleteGuard] 已拦截…session= given= cookie=`。
 *      - session=(空) → 会话里没有令牌，先看 SessionInit 与 session 存储；
 *      - given=(空)   → 请求压根没带参数，脚本没生效（看下面第 3、4 条）；
 *      - 两者都有值但不相等 → 前端拿的是别的会话的令牌（多为浏览器里的残留 cookie）。
 *   2. 页面响应里有没有 Set-Cookie: cmf_dc_token=…（本中间件是否生效）；
 *   3. 浏览器控制台执行 `typeof CmfDeleteConfirm`：undefined = 脚本没被加载；
 *      再执行 `window.CMF_DC_TOKEN` 确认内联令牌有没有注入进来。
 *      这两样都由本中间件兜底注入，不依赖主题模板，所以「脚本没加载」基本只剩
 *      「响应不是 HTML」或「页面是缓存下来的旧 HTML」两种可能。
 *   4. 该动作是否属于上面「例外动作」——只读页面被误拦时加进 $readOnlyActions，
 *      临时放行整个路径可加进 $skipPaths。
 */
class AdminDeleteGuard
{
    /** 删除请求必须携带的参数名 */
    const PARAM_NAME = '_dc_token';

    /** 令牌在 session 中的键名 */
    const SESSION_KEY = 'cmf_admin_delete_token';

    /**
     * JS 读取令牌用的 cookie 名。
     * 故意不加 HttpOnly（前端要读它），内容只是一个随机串，
     * 拿到它也只能发起「本人本来就能发起的删除」，没有额外价值。
     */
    const COOKIE_NAME = 'cmf_dc_token';

    /**
     * 令牌在页面里的全局变量名。
     *
     * 守卫会把令牌内联进后台页面（window.CMF_DC_TOKEN = '…'），
     * 前端优先读它、读不到再退回 cookie cmf_dc_token。
     * 多这一层是为了不让「cookie 没下发 / JS 读不到 cookie」直接变成功能不可用。
     */
    const TOKEN_JS_FLAG = 'window.CMF_DC_TOKEN';

    /** 前端校验脚本的文件名（用来判断页面里是否已经引入过，避免重复注入） */
    const SCRIPT_NAME = 'admin-delete-confirm.js';

    /** 脚本版本号，改动该脚本后必须 +1，且与 header.html 引入处的 ?v= 保持一致 */
    const SCRIPT_VERSION = '20260911c';

    /**
     * 名字里带 delete、但其实是只读页面 / 跳转的动作，必须放行，
     * 否则会把菜单入口或列表页一起拦掉。
     *
     * ForumController::deleteLog()        —— 只是 redirect 到「删除记录」列表
     * ForumController::delete_log_index() —— 「删除记录」只读列表页（左侧菜单入口）
     *
     * @var string[]
     */
    protected $readOnlyActions = [
        'deletelog',
        'delete_log',
        'delete_log_index',
    ];

    /**
     * 完全跳过的路径前缀（小写，不含两端斜杠）。
     * 留作应急：万一某个特殊流程被误拦，把它加到这里即可放行，不用改逻辑。
     *
     * @var string[]
     */
    protected $skipPaths = [];

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed|Response
     */
    public function handle(Request $request, Closure $next)
    {
        $path = $this->normalizePath($request);

        // 不是后台请求（小程序接口、前台页面等）→ 放行
        if (!$this->isAdminRequest($path)) {
            return $next($request);
        }

        foreach ($this->skipPaths as $skip) {
            if ('' !== $skip && 0 === strpos($path, $skip)) {
                return $next($request);
            }
        }

        // 删除类请求：必须带对令牌，否则直接拒绝（不会进入控制器，数据不会被删）
        if ($this->isDeleteAction($path)) {
            $token = $this->currentToken();
            $given = (string)$request->param(self::PARAM_NAME, '');

            if ('' === $token || '' === $given || !hash_equals($token, $given)) {
                return $this->reject($request, $path, $token, $given);
            }

            return $next($request);
        }

        // 普通后台页面：确保会话里有令牌，然后既要下发 cookie，也要兜底注入进页面
        $token    = $this->token();
        $response = $next($request);

        return $this->syncTokenCookie($this->injectClientScript($response, $token, $request), $token);
    }

    /**
     * 取当前请求的路径：去掉两端斜杠并统一小写，例如 admin/member/delete.html
     *
     * 假定后台挂在 /admin/ 路径下（线上 admin.circular-economy.cn 与本地都是这样）。
     */
    protected function normalizePath(Request $request): string
    {
        $path = (string)$request->pathinfo();

        // 兜底：个别环境（如 php -S 自带服务器）取不到 PATH_INFO 时从 REQUEST_URI 取
        if ('' === $path) {
            $path = (string)parse_url((string)$request->server('REQUEST_URI'), PHP_URL_PATH);
        }

        // 兜底：兼容 ?s=/admin/member/delete 这类老式入口写法
        if ('' === $path) {
            $path = (string)$request->param('s', '');
        }

        // 去掉可能夹在路径里的入口文件：/index.php/admin/member/delete.html
        return (string)preg_replace('#^(?:.*/)?index\.php/#', '', strtolower(trim($path, '/')));
    }

    /**
     * 是否属于后台（路径第一段是 admin）
     */
    protected function isAdminRequest(string $path): bool
    {
        return 0 === strpos($path . '/', 'admin/');
    }

    /**
     * 是否是「删除类」动作（取路径最后一段、去掉 .html 之类的后缀后判断）
     *
     * 判定口径：以 del/delete 开头，或以 _del/_delete 结尾。
     * 不要用「包含 del」——getDelivery（get_delivery.html）这种只读动作会被误伤。
     */
    protected function isDeleteAction(string $path): bool
    {
        if ('' === $path) {
            return false;
        }

        $segments = explode('/', $path);
        $action   = (string)preg_replace('/\.[a-z0-9]+$/', '', (string)end($segments));

        if (in_array($action, $this->readOnlyActions, true)) {
            return false;
        }

        return 1 === preg_match('/^(del|delete)/', $action)
            || 1 === preg_match('/_(del|delete)$/', $action);
    }

    /**
     * 只读当前会话的令牌：没有就返回空串，绝不生成
     *
     * 删除请求必须用它，不能用下面的 token()——
     * 否则「session 里没有令牌」的请求会先造一个新令牌、再拿去和用户手里的旧令牌比，
     * 结果永远不匹配，日志里也看不出到底是「没令牌」还是「令牌不对」。
     */
    protected function currentToken(): string
    {
        $token = session(self::SESSION_KEY);

        return is_string($token) ? $token : '';
    }

    /**
     * 取当前会话的删除令牌，没有就生成一个存进 session（只给普通页面响应使用）
     */
    protected function token(): string
    {
        $token = $this->currentToken();

        if ('' === $token) {
            try {
                $token = bin2hex(random_bytes(16));
            } catch (\Throwable $e) {
                $token = md5(uniqid('', true) . mt_rand());
            }

            session(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /**
     * 把令牌写进一个 JS 可读的 cookie
     *
     * @param mixed $response
     * @param string $token
     * @return mixed
     */
    protected function syncTokenCookie($response, string $token)
    {
        if ($response instanceof Response && '' !== $token) {
            $response->cookie(self::COOKIE_NAME, $token, [
                'expire'   => 0,
                'path'     => '/',
                'httponly' => false,
            ]);
        }

        return $response;
    }

    /**
     * 兜底：把「内联令牌 + 校验脚本」补进后台页面的 HTML
     *
     * 为什么光靠 header.html 引入脚本不够：
     *   主题模板可能漏传、可能被换成别的主题、也可能被浏览器缓存，
     *   任何一条都会让脚本静默失效，表现就是「点删除一直提示未通过安全校验」。
     *   由守卫统一兜底注入，就把「脚本有没有被加载」和模板/缓存彻底解耦。
     *
     * 页面里已经有的部分不会重复注入（按字符串判断），所以不影响正常环境。
     *
     * @param mixed   $response
     * @param string  $token
     * @param Request $request
     * @return mixed
     */
    protected function injectClientScript($response, string $token, Request $request)
    {
        if ('' === $token || !$response instanceof Response || !method_exists($response, 'getContent')) {
            return $response;
        }

        // 只处理 HTML 页面：JSON 接口、下载响应、模板响应（output() 之前拿不到正文）一律跳过
        // 注意 Response 没有 getContentType()，只能用「是不是 Html 响应」+ 显式 Content-Type 兜底
        if (
            !$response instanceof \think\response\Html
            && false === stripos((string)$response->getHeader('Content-Type'), 'html')
        ) {
            return $response;
        }

        $content = (string)$response->getContent();

        if ('' === $content || false === stripos($content, '</head>')) {
            return $response;
        }

        $html = '';

        // 内联令牌：前端 readToken() 优先读它，读不到才退回读 cookie
        if (false === strpos($content, self::TOKEN_JS_FLAG)) {
            $html .= '<script>' . self::TOKEN_JS_FLAG . '=' . json_encode($token) . ';</script>';
        }

        // 校验脚本本身（放在 </head> 前，此时 jQuery 等依赖已就绪）
        if (false === strpos($content, self::SCRIPT_NAME)) {
            $html .= '<script src="' . $this->scriptUrl($request) . '"></script>';
        }

        if ('' !== $html) {
            $response->content((string)preg_replace('/<\/head>/i', $html . '</head>', $content, 1));
        }

        return $response;
    }

    /**
     * 前端脚本的 URL（带版本号，避免浏览器继续用缓存）
     */
    protected function scriptUrl(Request $request): string
    {
        return rtrim((string)$request->root(), '/') . '/static/js/' . self::SCRIPT_NAME . '?v=' . self::SCRIPT_VERSION;
    }

    /**
     * 令牌在日志里只留前 8 位：够定位问题，又不会把完整令牌写进日志
     */
    protected function brief(string $token): string
    {
        return '' === $token ? '(空)' : substr($token, 0, 8) . '...';
    }

    /**
     * 拒绝执行删除
     *
     * AJAX（后台删除按钮、批量删除）按 ThinkCMF 的 JSON 约定返回，
     * admin.js 收到 code=0 会自动用 art.dialog 弹出 msg；
     * 直接访问 URL 的情况则给一个说明页面。
     *
     * @param Request $request
     * @param string  $path
     * @param string  $sessionToken session 里的令牌（空串 = 会话里根本没有令牌）
     * @param string  $given        请求携带的令牌（空串 = 前端压根没带）
     * @return Response
     */
    protected function reject(Request $request, string $path, string $sessionToken = '', string $given = '')
    {
        $msg = '删除操作未通过安全校验，已阻止执行。请刷新页面后，通过列表中对应的「删除」按钮操作。';

        try {
            // 把「会话有没有令牌 / 前端有没有带 / 带的哪个」都记下来：
            // 这三个值能直接区分「脚本没加载」和「cookie 没下发」两类故障。
            Log::warning(sprintf(
                '[AdminDeleteGuard] 已拦截未经校验的删除请求：%s ip=%s session=%s given=%s cookie=%s referer=%s',
                $path,
                $request->ip(),
                $this->brief($sessionToken),
                $this->brief($given),
                $this->brief((string)$request->cookie(self::COOKIE_NAME, '')),
                (string)$request->header('referer')
            ));
        } catch (\Throwable $e) {
            // 记日志失败不能影响拦截结果
        }

        if ($request->isAjax()) {
            return json([
                'code' => 0,
                'msg'  => $msg,
                'data' => '',
                'url'  => '',
                'wait' => 3,
            ]);
        }

        $html = '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>操作已阻止</title></head>'
            . '<body style="margin:0;padding:48px 20px;background:#f5f7fa;color:#333;'
            . 'font:14px/1.7 -apple-system,BlinkMacSystemFont,\'Segoe UI\',\'Microsoft YaHei\',sans-serif;">'
            . '<div style="max-width:520px;margin:0 auto;background:#fff;border:1px solid #ebeef5;'
            . 'border-radius:8px;padding:24px;">'
            . '<h2 style="margin:0 0 12px;font-size:18px;color:#ff4d4f;">操作已阻止</h2>'
            . '<p style="margin:0 0 16px;">' . $msg . '</p>'
            . '<p style="margin:0;"><a href="javascript:history.back(-1);" style="color:#2D8CF0;'
            . 'text-decoration:none;">返回上一页</a></p></div></body></html>';

        return Response::create($html, 'html', 403);
    }
}
