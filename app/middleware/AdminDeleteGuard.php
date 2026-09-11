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
 *   1. 浏览器是否加载了新版 admin-delete-confirm.js（header.html 里的 ?v= 有没有 +1）；
 *   2. 页面响应里有没有 Set-Cookie: cmf_dc_token=…（本中间件是否生效）；
 *   3. 该动作是否属于上面「例外动作」——只读页面被误拦时加进 $readOnlyActions，
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

        $token = $this->token();

        // 删除类请求：必须带对令牌，否则直接拒绝（不会进入控制器，数据不会被删）
        if ($this->isDeleteAction($path)) {
            $given = (string)$request->param(self::PARAM_NAME, '');

            if ('' === $token || '' === $given || !hash_equals($token, $given)) {
                return $this->reject($request, $path);
            }

            return $next($request);
        }

        // 普通后台页面：把令牌同步给前端，供删除请求携带
        return $this->syncTokenCookie($next($request), $token);
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
     * 取当前会话的删除令牌，没有就生成一个存进 session
     */
    protected function token(): string
    {
        $token = session(self::SESSION_KEY);

        if (empty($token) || !is_string($token)) {
            try {
                $token = bin2hex(random_bytes(16));
            } catch (\Throwable $e) {
                $token = md5(uniqid('', true) . mt_rand());
            }

            session(self::SESSION_KEY, $token);
        }

        return (string)$token;
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
     * 拒绝执行删除
     *
     * AJAX（后台删除按钮、批量删除）按 ThinkCMF 的 JSON 约定返回，
     * admin.js 收到 code=0 会自动用 art.dialog 弹出 msg；
     * 直接访问 URL 的情况则给一个说明页面。
     *
     * @param Request $request
     * @param string $path
     * @return Response
     */
    protected function reject(Request $request, string $path)
    {
        $msg = '删除操作未通过安全校验，已阻止执行。请刷新页面后，通过列表中对应的「删除」按钮操作。';

        try {
            Log::warning('[AdminDeleteGuard] 已拦截未经校验的删除请求：' . $path . ' ip=' . $request->ip());
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
