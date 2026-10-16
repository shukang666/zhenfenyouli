---
name: pc-admin-delete-confirm
description: "PC 后台「删除操作二次校验」全栈实现指南（前端弹窗 + 服务端中间件）。前端是独立文件 public/static/js/admin-delete-confirm.js，由 public/themes/admin_fengiy/public/header.html 引入（带 ?v= 版本号防缓存），在 document 捕获阶段拦截 .js-ajax-delete，弹出要求手动输入「确认」两个字的弹窗，通过后重新派发点击交回原有 artDialog 流程，并用 $.ajaxPrefilter 自动补 _dc_token。服务端关口是全局中间件 app/middleware/AdminDeleteGuard.php（注册在 app/middleware.php），会话随机令牌存 session、同步到 JS 可读 cookie cmf_dc_token，删除类请求不带/带错令牌一律拒绝——含关键坑：app/middleware.php 里 SessionInit 必须写在最外层第一位，否则守卫跑在 session 初始化之前，每次请求令牌都不同，带了正确令牌也会被拦（curl 直接 POST delete 就能删的 CSRF 漏洞就是这么堵的）。另含为什么绝不能把校验塞进 admin.js 末尾、为什么批量删除天然不受影响、为什么必须用原生 click() 而不能用 jQuery trigger、只读动作白名单（getDelivery/deleteLog/delete_log_index）、「今日不再弹出」的 localStorage 实现、window.CmfDeleteConfirm.require() 对外接口、无头 Chrome 与跨进程 PHP 脚本的验证方法。当需要修改删除校验文案/行为、给自定义删除按钮接入校验、实现服务端删除校验/防直接调接口删除，或排查「点删除没弹输入框」「弹窗关不掉」「删除被触发两次」「原始确认弹窗不见了」「绕过页面直接 POST 也能删」「带正确令牌仍被拦」时调用。"
---

# PC 后台删除二次校验（输入「确认」）

## 一句话

所有**单条删除**（`a.js-ajax-delete`）在原有确认弹窗之前，多加一道「手动输入『确认』两个字」的校验；
**批量删除不受影响**（它走的是另一条完全独立的通道）。

**但前端弹窗本身没有任何安全价值**——绕过页面直接
`curl -X POST 'https://admin.circular-economy.cn/admin/member/delete.html?id=2'`（带上管理员 Cookie）
照样能删，前端弹窗根本不会出现（同时也是一条典型的 CSRF 漏洞）。
所以真正的关口在**服务端全局中间件** `app/middleware/AdminDeleteGuard.php`，见下一节。

---

## 服务端校验（真关口）：app/middleware/AdminDeleteGuard.php

**双层结构**：

| 层 | 文件 | 职责 |
|---|---|---|
| 前端 | `public/static/js/admin-delete-confirm.js` | 弹「输入确认」窗防误点；用 `$.ajaxPrefilter` 给删除类 ajax **自动补令牌参数** |
| 服务端 | `app/middleware/AdminDeleteGuard.php` + `app/middleware.php` | 校验令牌，**不带令牌/令牌错 → 根本进不了控制器，数据不会被删** |

**令牌流转**：

1. 会话首次访问任意后台页面 → 中间件 `token()` 生成 32 位随机串存进 `session('cmf_admin_delete_token')`；
2. 普通后台页面响应时 → `syncTokenCookie()` 把它同步到一个 **JS 可读**的 cookie `cmf_dc_token`（故意不加 HttpOnly）；
3. 删除类请求 → 必须带参数 `_dc_token` 且 `hash_equals` 与 session 里的令牌一致，否则 `reject()`：
   - Ajax（`X-Requested-With: XMLHttpRequest`）→ `json(['code'=>0,'msg'=>...])`，`admin.js` 自动用 artDialog 弹 msg；
   - 直接访问 URL → 403 HTML 说明页；同时 `Log::warning` 记一条。
4. 前端 `$.ajaxPrefilter` 从 cookie 读 `cmf_dc_token` 补成 `_dc_token`，所以**正常点删除按钮完全无感知**。

**参数名/令牌名两边必须一致**：`_dc_token`（请求参数） / `cmf_dc_token`（cookie）/ `cmf_admin_delete_token`（session）。

### ⚠️⚠️ 必踩的坑：全局中间件顺序 —— `SessionInit` 必须排在最外层

`app/middleware.php` 是**项目级全局中间件**（`public/index.php` 走它，`public/api.php` 因为定义了 `APP_NAMESPACE=api` 走 `api/middleware.php`，小程序接口天然不受影响）。

加载顺序在 `vendor/thinkcmf/cmf-route/src/Http.php::loadMiddleware()`：

```php
// 先引入项目 app/middleware.php
$this->app->middleware->import(include $this->app->getBasePath() . 'middleware.php');
// 再引入 vendor/thinkcmf/cmf-{根命名空间}/src/middleware.php（即 cmf-app 自带的 SessionInit）
```

`Pipeline` 是**洋葱模型：数组越靠前越外层、越先执行**。所以如果只写
`[AdminDeleteGuard::class]`，实际队列是 `[AdminDeleteGuard, SessionInit]`，
守卫跑在 **SessionInit 外面** → 守卫里 `session(...)` 时 `Store` 还没加载 `PHPSESSID` 对应的数据
（`Store` 构造时就用随机 id 把自己 `setId()` 了）→ **每次请求都生成一个新令牌**，
和页面下发给前端的令牌永远对不上 → **「带上正确令牌」也被拦**。

**修复**：把 `\think\middleware\SessionInit::class` 显式写在 `app/middleware.php` **第一位**：

```php
return [
    \think\middleware\SessionInit::class,      // 必须在最外层，先完成 session 初始化
    \app\middleware\AdminDeleteGuard::class,
];
```

cmf-app 里重复的那份会被 `Middleware::add()` 里的 `array_unique(..., SORT_REGULAR)` 去重，不会重复执行。
（改完实测生效顺序：`think\trace\TraceDebug` → `think\middleware\SessionInit` → `app\middleware\AdminDeleteGuard`。）

> 顺带：`SessionInit::save()` 是通过 `Middleware::end()` 调用的，在 `Http::end()` 里触发。
> 任何绕过 `Http::run()/end()` 的自造调用链都不会落盘 session。

### 只读动作白名单（名字里带 del 但不是删除）

`AdminDeleteGuard::$readOnlyActions` / `$skipPaths`，判定口径 `isDeleteAction()`：

- 拦截：最后一段路径**以 `del`/`delete` 开头**（`delete`、`delete_all`、`deleteCate`…）
  或**以 `_del`/`_delete` 结尾**（`cate_delete`、`push_delete`、`answer_delete`、`block_delete`…）
- 放行（实测确认是只读页面/跳转，拦了会废掉菜单入口）：
  - `ForumController::deleteLog()` → `delete_log.html`
  - `ForumController::delete_log_index()` → `delete_log_index.html`
  - `WxOrderController::getDelivery()` → `get_delivery.html`
- **绝对不要写成「包含 del 就拦」**：`getDelivery` 就会中招。

当前后台全部 36 个含 del 的方法实测：拦截 33 个、放行 3 个（上面这三个），无漏网也无误伤；
`app/admin/controller` 下没有 `remove/clear/drop/purge` 之类的漏网命名。

### 怎么验证（不连库、不删数据、不起 web 服务）

关键是**跨进程**才测得出 session 持久化：两次 `php` 调用用文件传 cookie jar，才等价于两次真实 HTTP 请求。

```powershell
# 必须用 PHP 8.0：8.1+ 会把框架的 ArrayAccess 弃用告警抛成 ErrorException，脚本起不来
$php = 'D:\phpstudy_pro\Extensions\php\php8.0.2nts\php.exe'

# 脚本内容：define CMF_ROOT/APP_PATH → require autoload → new App()->initialize()
# → 复刻 loadMiddleware 顺序 import 两个 middleware.php
# → $app->middleware->pipeline()->send($req)->then(stub) 再 $app->middleware->end($res)  ← end 才会 save session
# → 读 Cookie 单例里的队列拿 PHPSESSID / cmf_dc_token，写进 jar 文件
& $php data/_tmp_guard_http.php page      # 请求1：GET /admin/member/index.html
& $php data/_tmp_guard_http.php delete with      # 期望「放行（会真的删除）」
& $php data/_tmp_guard_http.php delete without   # 期望「拦截」← 就是被报告的 curl 场景
& $php data/_tmp_guard_http.php delete wrong     # 期望「拦截」
# 验证完删除 data/_tmp_guard_*.php / .json
```

判定「拦没拦住」看 `$hit`：stub 终结点被走到 = 没拦。

**验证测试本身有没有效**：临时把 `SessionInit::class` 那行注释掉再跑一遍，
`page` 步骤的 `session 令牌` 与 `delete` 步骤守卫读到的令牌会**不一样**、`delete with` 变「拦截」——
这就是修复前的原始 bug，能复现说明测试是有效的。

### 部署与「改了没生效」

- 涉及文件：`app/middleware.php`（新）、`app/middleware/AdminDeleteGuard.php`（新）、
  `public/static/js/admin-delete-confirm.js`、`public/themes/admin_fengiy/public/header.html`
- 改 JS 后**务必同步把 header.html 里 `?v=` 加 1**（当前 `?v=20260921c`）
- **上线瞬间的副作用**（预期行为，会自愈）：已经打开着旧页面的管理员，
  因为旧 JS 不会补 `_dc_token`，此时点删除会被服务端拦下并提示
  「删除操作未通过安全校验…请刷新页面后…」→ 刷新页面即恢复。这是设计如此，不是 bug。

---

## ⚠️ 最重要的一条：脚本必须独立成文件，绝不能塞进 admin.js

```html
<!-- public/themes/admin_fengiy/public/header.html -->
<script src="__STATIC__/js/admin-delete-confirm.js?v=20260921c"></script>
```

**踩过的真实事故**：这段逻辑最初是**追加在 `public/static/js/admin.js` 末尾**的，
结果用户反馈「点删除直接删了，完全没有弹窗」。

根因（JS 语言特性，不是写错了）：

> **一个 `<script>` 里只要有未捕获异常，后面所有代码都不会再执行。**

`admin.js` 是一千多行的老文件，只要它任意一行抛错（实测 `Wind.css is not a function` @admin.js:312），
追加在**文件末尾**的校验代码就整段不执行 → 既没有原确认框、也没有新校验框
→ 点击 `<a href=".../delete?id=x">` 走了**链接默认跳转** → 直接删除、页面刷新。
症状看起来就是「功能没生效」。

放在文件末尾还有第二个坑：`admin.js` 是静态资源，改了之后**浏览器可能继续用缓存的旧版**，
同样表现成「改了没生效」。

独立文件 + `header.html` 引入 + `?v=` 查询串，把这两个问题一起解决：

| 问题 | 独立文件为什么能解决 |
|---|---|
| admin.js 任意位置抛错 | 两个 `<script>` 互相隔离，`admin.js` 死了也不影响本文件 |
| 浏览器缓存旧版 | 改文件后把 `?v=` 加 1，URL 变了必然重新拉取 |
| 不知道页面有没有加载 | 控制台执行 `window.CmfDeleteConfirm` 即可确认（应是对象） |

另外它被放在 `<head>` 里、**早于**页面底部的 `admin.js` 执行，捕获阶段监听注册得更早，也更稳。

> **改这个文件之后，务必同步把 header.html 里的 `?v=` 版本号 +1**，否则你自己测试时也会看到旧版。

### 实测验证方式（无头 Chrome，随时可复现）

后台 PHP 内置服务器 + Chrome `--dump-dom` 就能验证，不需要登录后台：

```powershell
# 1. 起服务
d:\phpstudy_pro\Extensions\php\php7.3.4nts\php.exe -S 127.0.0.1:8899 -t D:\phpstudy_pro\WWW\xiayuxuan.com\public
# 2. 造一个页面，按 header.html 的顺序加载 GV → jquery → wind.js → admin-delete-confirm.js，
#    放一个 <a class="js-ajax-delete">，脚本里 el.click() 后把 document.querySelector('.cmf-dc-mask') 的状态写进 title
# 3. 渲染
chrome.exe --headless=new --disable-gpu --user-data-dir=%TEMP%\t1 --virtual-time-budget=8000 --dump-dom "http://127.0.0.1:8899/xxx.html"
```

已验证的两种结果（`--user-data-dir` 必须给，否则 Chrome 复用已有实例会没有输出）：

| 场景 | 期望 |
|---|---|
| 正常 | `CmfDeleteConfirm=object`、`dialogCreated=true`、`dialogVisible=true`、无 JS 错误 |
| **故意把 `window.Wind` 破坏掉让 admin.js 抛错** | admin.js 照样报 `Wind.css is not a function`，但**弹窗依然创建且可见** |

---

## 两条通道必须分清楚（这是不误伤批量的关键）

| 通道 | 标记 | 覆盖范围 |
|---|---|---|
| **单条删除** | `class="js-ajax-delete"` + `data-href` 或 `href` | 93 个模板，**唯一**统一入口 |
| **批量删除** | `form.js-ajax-form` + `button.js-ajax-submit` + `data-action="delete_all"` + `data-subcheck` | 独立通道，**从不使用 `js-ajax-delete`** |

所以只拦截 `.js-ajax-delete` 就能天然避开批量删除——**不要**去拦截 `.js-ajax-submit` 或 `data-action`。

另有一类 `js-ajax-dialog-btn`（14 处）用于**安装 / 更新 / 启用 / 禁用 / 卸载 / 还原 / 核销**等
**非删除**操作，**不要**给它加删除校验，否则误伤。

### 覆盖范围

93 个含删除按钮的模板，最终都会经过 `header.html`：

- 92 个直接写 `<include file="public@header"/>` 或 `<include file="public/header"/>`
- 1 个（`admin/rbac/member.html`）写的是 `<admintpl file="header" />`，同样落到 `header.html`

当前后台主题只有一个（`data/config/template.php` 里 `cmf_admin_default_theme = admin_fengiy`），
所以**不需要**考虑 `admin_simpleboot3` 之类的主题回退。

---

## 拦截机制

```js
document.addEventListener('click', function (e) {
    if (bypass) return;                       // 校验通过后重新派发的那次，放行

    var $el = $(e.target).closest('.js-ajax-delete');
    if (!$el.length) return;

    if (isMutedToday()) return;               // 今日免打扰 → 走原有普通确认

    // 所有提前 return 都放在 preventDefault 之前，
    // 保证任何异常场景都不会把用户的点击吞掉
    e.preventDefault();
    e.stopPropagation();
    if (e.stopImmediatePropagation) e.stopImmediatePropagation();

    try {
        open(function () {                    // 校验通过
            bypass = true;
            try { replayClick($el); } finally { bypass = false; }
        });
    } catch (err) {
        // 弹窗建不起来时，直接放行原始点击，避免「点了没反应」
        bypass = true;
        try { replayClick($el); } finally { bypass = false; }
    }
}, true);   // ← 捕获阶段，抢在 admin.js 绑在 body 上的委托之前
```

要点：

- **必须用捕获阶段**：`admin.js` 把删除处理委托绑在 `body` 上（冒泡），捕获阶段能抢先拿到事件。
- **必须用原生事件派发**，不能用 jQuery 的 `$el.trigger('click')`：
  jQuery 的 `trigger` 会**先走一遍 jQuery 合成冒泡、再调用原生 `click()`**，导致 `body` 上的委托被触发**两次** → 原始 artDialog 弹两次。
- `bypass` 标志用 `try/finally` 保证复位。

### replayClick：顺手做了一次「原流程是否还活着」的自检

```js
function replayClick($el) {
    if (window.MouseEvent) {
        var ev = new MouseEvent('click', {bubbles: true, cancelable: true, view: window});
        var notCancelled = el.dispatchEvent(ev);
        // 返回 false = 有处理器调用了 preventDefault，说明原 artDialog 流程已接管
        if (notCancelled) {
            console.warn('[删除校验] 原有删除流程未接管本次点击（admin.js 可能执行中断，或 wind.js 未加载），已回退为链接默认跳转。');
        }
        return;
    }
    el.click();   // 老浏览器兜底：无法探测，行为与修复前一致
}
```

**在控制台看到这条 warn，就等于确认了「`admin.js` 没执行完」**——这不是本功能的 bug，
而是页面上有别的 JS 报错，应当去查那个报错（本功能已不受它影响）。

---

## 弹窗与文案

自建 DOM（类名前缀 `cmf-dc-`），**不依赖** layer / artDialog，PC 和窄屏表现一致。
样式由 JS 注入 `<style id="cmf-dc-style">`（`injectStyle()`），不要去改 layer / artDialog 皮肤指望这层跟着变。外观约定见 [admin-toast.md](admin-toast.md)。

| 位置 | 文案 |
|---|---|
| 标题 | 删除确认 |
| 主提示 | 此操作将**永久删除**该条数据，删除后无法恢复。 |
| 输入引导 | 请输入「**确认**」两个字以继续 |
| 输入框占位 | 请输入：确认 |
| 复选项 | 今日不再弹出此校验*（当天删除将只保留一次普通确认）* |
| 按钮 | 取消 / 确认删除（红色） |

交互规则：

- 必须**完全等于**「确认」（自动去首尾空格）才解锁「确认删除」按钮；输入不匹配时输入框变红
- 回车可提交；ESC / 点遮罩 / 取消 → 关闭且**不执行删除**
- 校验通过后**仍会走原有的一次普通确认**（原有行为完整保留，只是前面多一道强校验）

**改文案**：直接搜 `public/static/js/admin-delete-confirm.js` 里的 `cmf-dc-` 前缀片段（DOM 与 CSS 在同一文件）。

---

## 「今日不再弹出」

- 用 `localStorage` 的 key `cmf_delete_confirm_mute` 存**当天日期**（形如 `2026-9-11`）
- 跨天自动失效（不需要任何人清理）
- 勾选后**只跳过输入校验**，原有普通确认**仍保留**——刻意不做「点一下就静默删除」
- 想当天就恢复：浏览器控制台执行

```js
localStorage.removeItem('cmf_delete_confirm_mute')
```

---

## 给自定义删除按钮接入

页面里自己实现的删除函数（不走 `.js-ajax-delete` 的，比如活动推送的删除）可以这样接入：

```js
window.CmfDeleteConfirm.require(function () {
    // 这里写原本的删除逻辑
});
```

- 已处于「今日免打扰」时，回调会**直接执行**
- 也可用 `window.CmfDeleteConfirm.isMutedToday()` 自行判断
- `window.CmfDeleteConfirm.version` 可用来确认当前跑的是哪一版脚本

---

## 排查

| 现象 | 原因 / 处理 |
|---|---|
| **点删除没弹输入框，直接跳转删除了** | 先确认脚本有没有加载：控制台执行 `window.CmfDeleteConfirm`。<br>· `undefined` → `header.html` 的 `<script>` 没部署/被改掉，或该页面没走 header<br>· 是对象却没弹 → 看控制台有没有 `admin.js` 的报错（老版本塞在 admin.js 末尾时会因此失效；现在不会了）<br>· 也可能是 `localStorage` 里今天已被标记免打扰 |
| 改了脚本却还是旧行为 | **`?v=` 版本号没加**，浏览器用了缓存；或改完没部署 |
| 原始确认框弹了**两次** | 改用了 jQuery `trigger` 代替 `dispatchEvent` / 原生 `click()`（见上文机制） |
| 弹窗关不掉 / 点了没反应 | 检查是否有别的弹出层 `z-index` 更高；本弹窗遮罩 `z-index: 2147483000` |
| 批量删除也被拦了 | 拦截选择器写错（应只有 `.js-ajax-delete`） |
| 校验通过后删除请求没发出 | 控制台有 `原有删除流程未接管本次点击` 的 warn → `admin.js` 未执行完或 `wind.js` 未加载，此时退化为链接跳转删除（仍是正确的删除，只是会整页刷新） |
| 某一类删除不生效 | 该按钮用的可能是 `js-ajax-dialog-btn`（非删除语义）或页面自定义函数，需按上文接口主动接入 |
| 传给 AI 的任务里 `admin.js` 被改动过 | 注意现在删除校验**不在** `admin.js` 里了，别再去那个文件末尾找 |
| **绕过页面直接 POST/curl 也能删** | 服务端中间件没生效。依次查：<br>1. `app/middleware.php` 是否存在且注册了 `AdminDeleteGuard`（该文件只有 `public/index.php` 入口会加载）<br>2. 请求路径是否以 `admin/` 开头（守卫只拦后台）<br>3. 该动作是否落进 `isDeleteAction` 的判定口径 |
| **带「正确」令牌仍被拦**（提示未通过安全校验） | 几乎一定是 `app/middleware.php` 里 **`SessionInit` 没写在第一位** → 守卫跑在 session 初始化之前，每次请求都生成新令牌。<br>实测判据：两个跨进程请求打印出的令牌不一致。 |
| 上线后部分管理员点删除提示「未通过安全校验」 | **预期行为，会自愈**：他们页面里是旧版 JS（不补 `_dc_token`）。刷新页面即可；确认 `header.html` 的 `?v=` 已 +1。 |
| 删除按钮点了提示 `_dc_token` 相关 warn | 控制台 `[删除校验] 页面未取到删除校验令牌（cmf_dc_token）` → 中间件没下发 cookie（同第 1 条），或该页面没走 `header.html` |
| 只读页面（订单查询/删除记录）打不开 | 被守卫误拦。把动作名加进 `AdminDeleteGuard::$readOnlyActions`，或路径加进 `$skipPaths` |

---

## 相关 skill

- 批量删除的标记与实现（确认它为何不受影响）：`pc-admin-batch-delete`
- `admin.js` 公共 JS 机制：`pc-admin-batch-delete`
- 静态资源改动不生效的排查顺序：`prod-db-change-and-deploy-verify`
- 移动端适配（弹窗在窄屏的表现）：`pc-admin-mobile-responsive`
- 后台公共头部在哪、新增页面怎么接：`pc-admin-new-page`
- 命令行引导框架跑临时验证脚本（本 skill 的跨进程验证就是这个套路）：`thinkcmf-cli-script`
- 本机 PHP 版本/路径与 SQL 语法限制：`dev-environment`
