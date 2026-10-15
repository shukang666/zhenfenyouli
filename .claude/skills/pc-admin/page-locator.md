---
name: admin-page-locator
description: "PC 后台页面定位排查：已知浏览器 URL 反查控制器/模板/菜单，或用户报「后台某个页面要改/改了没生效」时先确认到底是哪个页面、哪份代码、哪个环境。记录 URL↔控制器↔模板映射规则（控制器驼峰转模板目录下划线小写）、cmf_admin_menu 表结构（无 url 字段，URL 由 app+controller+action 生成）、用临时脚本查菜单树定位页面归属，以及「先确认访问的是本地还是线上」的排查顺序。当需要确认某个后台页面/菜单对应哪个控制器与模板、排查后台有多个同名相似页面、或用户说改了没生效怀疑改错页面时调用。"
---

# PC 后台页面定位排查（URL ↔ 控制器 ↔ 模板 ↔ 菜单）

## 适用场景

- 用户给了一个后台页面 URL（如 `/admin/active_clock/index.html`），要确认**改哪个文件**才生效。
- 用户报"你改错页面了 / 改了没生效"，需要先确认**他看到的页面**和**我改的代码**是不是同一个。
- 后台有多个长得像的页面（如多个"打卡"入口），需要精确定位。

---

## 一、URL → 控制器 → 模板 映射规则

后台 URL 形如：

```
/admin/<controller小写下划线>/<action>.html
```

- **控制器**：`app/admin/controller/<Controller>.php`（控制器名 = URL 段的驼峰形式，如 URL `active_clock` → 控制器 `ActiveClockController`）。
- **方法**：`action` 直接对应控制器方法名（**必须下划线小写**，`delete_all` 不是 `deleteAll`）。
- **模板**：`public/themes/admin_fengiy/admin/<controller下划线小写>/<action>.html`
  - 例：`ActiveClockController::index` → `public/themes/admin_fengiy/admin/active_clock/index.html`。

> 控制器驼峰名 → 模板目录下划线小写，是定位时最容易搞错的一步。
> 本项目后台只有 `admin_fengiy` 一个主题。

### 没有侧栏菜单的独立入口

这些 URL **不在** `cmf_admin_menu` 里，查菜单表会找不到，按路径对控制器即可：

| URL | 控制器 | 说明 | skill |
|-----|--------|------|-------|
| `/admin/oa/login`、`/admin/oa/index`、`/admin/oa/profile` | `OaController` | 手机 OA，session `OA_*` | `oa-portal` |
| `/admin/field_worker/index` 等 | `FieldWorkerController` | 外勤 Pad，登录在 `?entry=field` | `field-worker-workpad` |
| `/admin/mobile/login`、`/admin/mobile/dashboard` | `MobileController` | 驾驶舱，session `ADMIN_ID` | — |

---

## 二、菜单归属：查 `cmf_admin_menu`

后台侧栏菜单**完全由数据库表 `cmf_admin_menu` 驱动**，每个菜单行记录 `app + controller + action`，URL 由此拼出。

**关键：表里没有 `url` 字段！** 直接按 `url` 查询会报
`SQLSTATE[42S22]: Column not found: 1054 Unknown column 'url'`。

表结构（本项目）：

| 字段 | 说明 |
|---|---|
| `id` / `parent_id` | 主键 / 父菜单（顶级为 0） |
| `app` | 固定 `admin` |
| `controller` | 控制器名（如 `ActiveClock`、`forum`） |
| `action` | 方法名（下划线，必须与控制器一致） |
| `param` | URL 附加参数（可空） |
| `name` | 菜单显示名（`lang()` 取不到时的回落值） |
| `type` / `status` / `list_order` / `icon` / `remark` | 见 `pc-admin-menu-manage` |

查某 URL 属于哪个菜单，按 `controller`/`action` 查即可（不是 `url`）：

```php
$menus = Db::name('admin_menu')
    ->where('controller', 'ActiveClock')
    ->where('action', 'index')
    ->select()->toArray();
// 再查其 parent_id 对应的分组名，得到「社区活动 → 打卡记录」这类路径
```

---

## 三、用临时 CLI 脚本查菜单树

用 `thinkcmf-cli-script` 的方式引导框架后查库（本机 `php` 不在 PATH，需用绝对路径 `d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe`）：

```php
<?php
require __DIR__ . '/vendor/autoload.php';
$app = new \think\App();
$app->initialize();

$rows = think\facade\Db::name('admin_menu')
    ->where('name', 'like', '%打卡%')
    ->field('id,parent_id,app,controller,action,name')
    ->select()->toArray();

foreach ($rows as $r) {
    echo "{$r['id']} | p={$r['parent_id']} | {$r['name']} | {$r['controller']}/{$r['action']}" . PHP_EOL;
}
```

再把命中的 `parent_id` 往上查一层，即可还原完整菜单路径。

> 脚本放临时位置、用完删除；详见 `thinkcmf-cli-script`。

**实例（本项目后台 4 个"打卡"入口，极易混淆）：**

| 菜单路径 | 控制器/方法 | 模板 | 说明 |
|---|---|---|---|
| 社区活动 → 打卡记录 | `ActiveClock/index` | `active_clock/index.html` | 社区活动打卡记录（报名用户/报名活动/打卡日期） |
| 社区活动 → 打卡动作 | `active_clock_action/index` | `active/clock_action_index.html` | 打卡动作/表单/推送配置 |
| 垃圾分类活动 → 垃圾打卡 | `forum/index2` | `forum/index.html` | 垃圾分类打卡帖子 |
| 自治活动 → 商户打卡 | `handle/index` | `handle/index.html` | 商户打卡 |

---

## 四、排查「用户说改了没生效 / 改错页面」的顺序

**先别急着改代码，按顺序排除：**

1. **确认访问环境**：问用户浏览器地址栏是**线上域名**还是**本地 127.0.0.1**。
   - 线上 → 本地改动不会自动生效，需部署（见 `prod-db-change-and-deploy-verify`）。
   - 本地 → 继续查页面/缓存。
2. **确认页面归属**：用上面的 URL→控制器→模板规则，核对用户说的页面到底渲染哪个模板、属于哪个菜单。
3. **确认代码状态**：`git log --oneline -1` / `git status` / `git branch -vv`
   - 功能是否**已经提交**、是否**已推送** origin？可能是"早就开发好但没部署"，而不是"没开发"（本项目就出现过：`dev` 分支已含批量删除功能且已 push，用户线上看不到 → 属未部署）。
4. **确认缓存**：`Ctrl+F5` 强刷 + 清服务器 `runtime/temp/*`。
5. **验证模板是否真的更新**：页面右键"查看源代码"，搜索刚加的关键字（如"批量删除"）。

> 教训：用户说"改错页面了"时，先证明"他看的页面 = 我改的文件"，否则会来回改错。

---

## 相关 skill

- 临时 CLI 脚本引导 ThinkCMF：`thinkcmf-cli-script`
- 后台菜单与控制器同步、菜单 404 排查：`pc-admin-menu-manage`
- 部署验证与"改了没生效"排查：`prod-db-change-and-deploy-verify`
- 环境连接差异与 PowerShell 坑：`dev-environment`
- 业务模型与后台控制器清单：`business-data-model`
- 手机 OA 独立入口：`oa-portal`
- 外勤 Pad：`field-worker-workpad`
