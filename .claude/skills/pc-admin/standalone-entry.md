---
name: pc-admin-standalone-entry
description: "在 PC 后台体系内做「独立入口」页面（自带登录/session、不走后台登录拦截，如外勤工作台、Pad 端录入页）的完整做法与三个大坑：①控制器继承 BaseController 时必须覆写 _initializeView() 设 view_base；②后台登录页实际是 fengiy_login 的 widget.html；③独立入口与驾驶舱共用 /admin/public/login 时，session 必须分开，未登录跳转必须带服务端能读的 query（?entry=field），不能只靠 #hash，且要覆盖 PublicController::login 避免 ADMIN_ID 把人送进管理系统。当需要给后台加独立登录入口、或排查「独立页面报模板不存在」「登录页加了入口不显示」「工作台退出后又进了后台首页」时调用。"
---

# PC 后台独立入口（自带登录，不走后台拦截）

## 适用场景

在 admin 模块下做一个**免后台登录**的独立页面组：用自己的 session key 登录、页面风格独立于后台框架。实例：`FieldWorkerController`（外勤工作台，Pad 端点位巡查录入）、`OaController`（手机 OA，独立登录页 `/admin/oa/login`）。

OA 的 session 是 `OA_ID` / `OA_NAME`，未登录只跳自己的登录页，**不要**接到 `/admin/public/login`。详见 `oa-portal`。

## 三件套

| 件 | 位置（实例） |
|---|---|
| 控制器 | `app/admin/controller/FieldWorkerController.php`（继承 `cmf\controller\BaseController`，**不是** `AdminBaseController`） |
| 模板 | `public/themes/admin_fengiy/admin/field_worker/index.html`、`monitor.html`、`profile.html`、`stats.html` |
| 入口 | `/admin/public/login`（fengiy_login `widget.html` 双标签）。外勤标签 `POST FieldWorker/doLogin`，进工作台首页。无独立 `field_worker/login.html`。 |

工作台控制器仍继承 `BaseController`（独立皮肤）。登录态用 `FIELD_WORKER_ID` / `FIELD_WORKER_NAME`，与后台 `ADMIN_ID` 分开。未登录跳转前必须 `session('__LOGIN_BY_CMF_ADMIN_PW__', 1)`。详见下面坑③。

## 坑①：BaseController 不设置 view_base（本次报错根因）

`AdminBaseController::_initializeView()` 会把 `view_base` 设为 `WEB_ROOT + themes/<后台主题>/`；而 `BaseController::_initializeView()` 是**空方法**。

继承 `BaseController` 时，ThinkPHP 按默认规则解析模板：`{站点根}/view/{module}/{template}.html`。

**报错特征**（看到根目录 `view/` 路径就是它）：

```
模板文件不存在:/data/disk/<站点目录>/view/admin/field_worker/login.html
```

**修复**：在控制器里覆写 `_initializeView()`，复刻 AdminBaseController 的配置（直接抄这段）：

```php
protected function _initializeView()
{
    $cmfAdminThemePath    = config('template.cmf_admin_theme_path');
    $cmfAdminDefaultTheme = cmf_get_current_admin_theme();
    $themePath = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}";
    $root = cmf_get_root();

    // 使 cdn 设置生效（与 AdminBaseController 行为一致）
    $cdnSettings = cmf_get_option('cdn_settings');
    if (empty($cdnSettings['cdn_static_root'])) {
        $viewReplaceStr = [
            '__ROOT__' => $root, '__TMPL__' => "{$root}/{$themePath}",
            '__STATIC__' => "{$root}/static", '__WEB_ROOT__' => $root
        ];
    } else {
        $cdnStaticRoot  = rtrim($cdnSettings['cdn_static_root'], '/');
        $viewReplaceStr = [
            '__ROOT__' => $root, '__TMPL__' => "{$cdnStaticRoot}/{$themePath}",
            '__STATIC__' => "{$cdnStaticRoot}/static", '__WEB_ROOT__' => $cdnStaticRoot
        ];
    }

    $this->view->engine()->config([
        'view_base'          => WEB_ROOT . $themePath . '/',
        'tpl_replace_string' => $viewReplaceStr
    ]);
}
```

修复后 `$this->fetch('field_worker/login')` 正确解析到 `public/themes/admin_fengiy/admin/field_worker/login.html`（框架自动拼 `admin/` 模块前缀），模板里的 `__STATIC__` / `__TMPL__` 也正常替换。

**为什么不能直接继承 `AdminBaseController`**：它的 `initialize()` 有后台登录拦截（未登录 302 到 `admin/Public/login`），独立入口的登录页会被拦死。

## 坑②：后台登录页是插件渲染的，不是主题模板

后台登录页被 **fengiy_login 插件**（数康自定义登录页）通过 `adminLogin` 钩子接管：

- 实际模板：`public/plugins/fengiy_login/view/widget.html`
- 插件类：`public/plugins/fengiy_login/FengiyLoginPlugin.php`（`adminLogin()` → `fetch('widget')`）

**在登录页加任何入口/链接，必须改 `widget.html`；改 `public/themes/admin_fengiy/admin/login.html` 不生效**（那是框架默认模板，已被插件覆盖）。

识别方法：登录页右键查看源代码，看到 `__PLUGIN_TMPL__` 资源路径或「技术支持：数康科技」字样即是插件渲染。

## 坑③：共用登录页时，独立 session 会被驾驶舱 ADMIN_ID 劫持

工作台退出**只清自己的 session**，驾驶舱 `ADMIN_ID` 应保留。之后再打开工作台 URL，会跳到 `/admin/public/login`。

框架 `vendor/.../PublicController::login()`：只要 `ADMIN_ID` 非空就 `redirect(admin/Index/index)`。浏览器 `#field` **不到服务端**，PHP 不知道这是外勤入口，就会把人送进管理系统（表象像串站）。

**必须同时满足**：

1. 跳转用 query：`?entry=field`，不要只靠 `#field`。
2. 覆盖 `app/admin/controller/PublicController.php`（不要改 vendor）：`entry=field` 时即使有 `ADMIN_ID` 也渲染登录页。
3. 插件 `widget.html` 用 `location.search` 识别 `entry=field` 切外勤标签。

业务细节见 `field-worker-workpad`「两套登录态隔离」。

## 部署注意

- 同一套代码部署在**多个站点**（`admin.circular-economy.cn`、`xcxkf052.aubye.com` 等，站点根在 `/data/disk/<域名>/`），改完要同步到用户实际访问的那个站点。
- 部署后用 curl 验证控制器存在（302=存在/404=未部署），并清 `data/runtime` 缓存，见 `prod-db-change-and-deploy-verify`。

## 排查速查

| 现象 | 原因 |
|---|---|
| 报模板不存在，路径是 `{站点根}/view/admin/...` | 控制器继承了 `BaseController`，缺 `_initializeView()`（坑①） |
| 登录页加的入口不显示 | 改错了模板，登录页是 fengiy_login 插件的 `widget.html`（坑②） |
| 独立入口页 302 跳后台登录 | 误继承了 `AdminBaseController` |
| 独立入口退出后再打开，进了管理系统首页 | 跳转只有 `#hash` 没有 `?entry=`；或未覆盖 `PublicController::login`（坑③）。OA 则是误跳了 `/admin/public/login`，应只跳 `/admin/oa/login` |
| 本地正常线上报错 | 文件没同步到线上站点；多站点注意同步到用户访问的那个 |

## 相关 skill

- 常规后台页面（继承 AdminBaseController + 菜单）：`pc-admin-new-page`
- 部署验证与「本地改了线上没生效」排查：`prod-db-change-and-deploy-verify`
- 后台登录页/页面归属定位：`admin-page-locator`
- 手机 OA：`oa-portal`
- 外勤 Pad：`field-worker-workpad`
