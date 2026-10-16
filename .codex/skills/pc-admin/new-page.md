---
name: pc-admin-new-page
description: "PC 后台新增一个页面/模块的完整流程（控制器 + 模板 + 菜单三件套）。记录后台模板的自动定位规则（控制器名转下划线 → admin_fengiy/admin/<名字>/<action>.html）、AdminBaseController 的 assign/fetch 用法、模板缺失时回退主题 admin_simpleboot3 的行为，以及新增后必须同步的菜单与权限收尾。当需要在 PC 后台新增功能页面/模块，或排查「后台页面 404」「报模板文件不存在」「页面能开但侧栏没入口」时调用。"
---

# PC 后台新增页面 / 模块（控制器 + 模板 + 菜单）

## 三件套

| 件 | 位置 | 缺失后果 |
|---|---|---|
| 控制器 | `app/admin/controller/XxxController.php` | 菜单点击 **404** |
| 模板 | `public/themes/admin_fengiy/admin/<控制器下划线名>/<action>.html` | 报**模板文件不存在** |
| 菜单 | `cmf_admin_menu` 表一行 | 页面能直接访问，但**侧栏没入口** |

## 控制器写法（真实样例：`app/admin/controller/AlgoController.php`）

```php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;

class AlgoController extends AdminBaseController
{
    public function index()
    {
        $list = [ /* ... */ ];
        $this->assign('list', $list);   // 模板里用 {$list}
        return $this->fetch('index');   // 定位到 <控制器下划线名>/index.html
    }

    public function detail()
    {
        $key = (string)$this->request->param('id', '');
        // ...
        $this->assign('meta', $meta);
        return $this->fetch('detail');
    }
}
```

- `$this->fetch('index')` 传的是**模板名**（相对当前控制器目录），不是完整路径；不传则默认取当前 action 名。
- `$this->assign($k, $v)` 赋值给模板；`$this->error()` / `$this->success()` 走后台弹窗提示。
- 方法名用**下划线**（如 `delete_log_index`），**不能用驼峰** —— ThinkCMF 后台约定。

## 模板自动定位规则（已核实源码）

`cmf\controller\AdminBaseController::parseTemplate()`：

```
$themePath = config('template.cmf_admin_theme_path') . cmf_get_current_admin_theme() . '/';
$file = $themePath . $app . '/' . cmf_parse_name($controller) . '/' . cmf_parse_name($action) . '.html';
```

本项目实际值：

- `config('template.cmf_admin_theme_path')` = `themes/`
- 当前后台主题**只有 `admin_fengiy`**（`public/themes/` 下没有别的 `admin_*` 主题）

所以映射是：

| 控制器 :: 方法 | 模板文件 |
|---|---|
| `AlgoController::index()` | `public/themes/admin_fengiy/admin/algo/index.html` |
| `AlgoController::detail()` | `public/themes/admin_fengiy/admin/algo/detail.html` |
| `ForumController::delete_log_index()` | `public/themes/admin_fengiy/admin/forum/delete_log_index.html` |

- `cmf_parse_name()` 把驼峰转下划线：`CronTask` → `cron_task`、`Algo` → `algo`。
- **回退行为**：当前主题目录找不到模板时，框架会去 `admin_simpleboot3` 主题下找同名文件；本项目**没有** `admin_simpleboot3` 主题，
  所以模板缺失会直接报错（**不是 404**）。

## 新增清单（照做即可）

1. 写控制器 `app/admin/controller/XxxController.php`（继承 `AdminBaseController`，方法名下划线）。
2. 建模板目录 `public/themes/admin_fengiy/admin/<下划线控制器名>/`，写 `<action>.html`
   （ThinkPHP 模板语法：`{$var}` / `<foreach>` / `<if condition="">` / `{:url()}`，**不是 Vue/React**）。
3. 往 `cmf_admin_menu` 插菜单行（幂等 SQL 见 `pc-admin-menu-manage`）。
4. 非超管角色：在「角色管理」勾选该菜单权限（超管 id=1/14 免检）。
5. 清 `data/runtime` 缓存后刷新。
6. 若本地直连生产库，**别忘了代码要部署到服务器**，用状态码验证（见 `prod-db-change-and-deploy-verify`）。

## 模板变量输出会自动转义（必读）

ThinkCMF 的模板引擎默认 `'default_filter' => 'htmlentities'`
（`vendor/thinkcmf/think-template/src/Template.php` 第 62 行），且只要没写 `|raw` 就会**无条件追加**：

| 模板写法 | 实际执行 | 结果 |
|---|---|---|
| `{$vo.content}` | `htmlentities($content)` | ✅ 已转义，**正确写法** |
| `{$vo.content\|htmlspecialchars}` | `htmlentities(htmlspecialchars(...))` | ❌ **双重转义**，会显示成 `&lt;pre&gt;` |
| `{$vo.content\|raw}` | 原样输出 | ⚠️ 有 XSS 风险 |

**结论：模板里直接写 `{$vo.xxx}` 就行，不要再手动加转义函数。**

## 模板公共部分

- 公共头部：`public/themes/admin_fengiy/public/header.html`（模板里用 `<include file="public/header" />` 之类引入）。
- 列表页的**加列**见 `pc-admin-add-column`；**搜索/导出/分页**见 `pc-admin-search-export`。

## 排查

| 现象 | 原因 |
|---|---|
| 点菜单 404 | 控制器/方法不存在，或代码没部署到服务器 |
| 报「模板文件不存在」 | 模板路径算错（控制器名→下划线）、目录名或 action 名不对 |
| 报「模板文件不存在」且路径是站点根 `view/admin/...` | 控制器继承了 `BaseController` 而非 `AdminBaseController`，未设置 `view_base` → 见 `pc-admin-standalone-entry` |
| 页面能开但侧栏没入口 | `cmf_admin_menu` 没插行 / `status=0` / 非超管未授权 |
| 改了控制器名后老菜单 404 | 菜单行的 `controller` 没同步改 |

## 相关 skill

- 菜单增删改与 404 排查：`pc-admin-menu-manage`
- 列表加列：`pc-admin-add-column`；搜索/导出/分页：`pc-admin-search-export`
- 新页面的窄屏适配（include `public@header` 即自动生效）：`pc-admin-mobile-responsive`
- 只读展示页的现成范例（含「纯文本换行 + HTML 转义」处理）：`pc-admin-update-log`
- 改在哪一端（小程序 / 小程序接口 / PC 后台）：`code-location-guide`
- **后台首页（仪表盘）是特例**：不新增页面，而是用项目 `app/admin/controller/MainController.php` 覆盖 vendor 同名类，地址固定 `/admin/main/index` 且免授权白名单、**不需要菜单** → `pc-admin-dashboard`
