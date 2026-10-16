---
name: oa-portal
description: "手机 OA 办公独立入口。复用后台管理员账密，登录态 OA_ID / OA_NAME，与驾驶舱 ADMIN_ID、外勤 FIELD_WORKER_*、PC 管理后台必须隔离。页面只做移动端，不走 /admin/public/login 双标签，也不进 cmf_admin_menu。当需要新增/修改 OA 登录、工作台、我的资料，或排查「OA 退出后又进了后台/驾驶舱/Pad」时调用。"
---

# 手机 OA 办公入口

独立入口通用坑（`view_base`、不走 `AdminBaseController`）见 `pc-admin` 的 [standalone-entry.md](../pc-admin/standalone-entry.md)。外勤 Pad 对照 `field-worker-workpad`。驾驶舱是 `MobileController`（共用 `ADMIN_ID`）。

## 功能背景

给运营在手机上办公用。账密复用 `cmf_user` 后台管理员，但 **session 必须单独一套**，不能跟电脑后台、驾驶舱、外勤 Pad 混用。界面只做移动端，不考虑 PC 布局。

## 路由与入口

| 页面 | 地址 | 说明 |
|------|------|------|
| 登录 | `/admin/oa/login` | 独立页，不走 fengiy_login |
| 登录提交 | `POST /admin/oa/doLogin` | 只写 `OA_*` |
| 工作台 | `/admin/oa/index` | 待办数量 + 今日打卡/新用户 |
| 我的 | `/admin/oa/profile` | 资料 + 改密 |
| 改密 | `POST /admin/oa/changePassword` | 对齐外勤改密规则 |
| 退出 | `/admin/oa/logout` | 只清 `OA_*` |

**不要**把 OA 做成 `fengiy_login` `widget.html` 的第三个标签。  
**不要**往 `cmf_admin_menu` 加侧栏。侧栏没有入口是故意的，用地址打开。

未登录访问工作台 / 我的：只跳 `/admin/oa/login`。**禁止**跳 `/admin/public/login`（那是驾驶舱与 Pad 共用页，有 `ADMIN_ID` 会被送进管理系统）。

## 文件结构

```
app/admin/controller/OaController.php
public/themes/admin_fengiy/admin/oa/login.html
public/themes/admin_fengiy/admin/oa/index.html
public/themes/admin_fengiy/admin/oa/profile.html
```

控制器继承 `cmf\controller\BaseController`（**不是** `AdminBaseController`），必须覆写 `fetch` / `parseTemplate` / `_initializeView`，否则模板会去站点根 `view/admin/oa/`。

## 登录态隔离（必守）

四套入口、四套登录态（驾驶舱与电脑后台目前都用 `ADMIN_ID`，OA / Pad 必须分开）：

| 入口 | session | 登录提交 | 退出清什么 | 未登录去哪 |
|------|---------|----------|------------|------------|
| 手机 OA | `OA_ID` / `OA_NAME` | `Oa/doLogin` | 只清 `OA_*` | `/admin/oa/login` |
| 外勤工作台 | `FIELD_WORKER_ID` / `FIELD_WORKER_NAME` | `FieldWorker/doLogin` | 只清 `FIELD_WORKER_*` | `/admin/public/login.html?entry=field` |
| 驾驶舱 | `ADMIN_ID` / `name` | `Mobile/doLogin` | 只清 `ADMIN_ID` | `/admin/mobile/login` |
| PC 管理后台 | `ADMIN_ID` / `name` | `Public/doLogin` | 只清 `ADMIN_ID` | `/admin/public/login` |

**禁止**：OA 登录写 `ADMIN_ID` 或 `FIELD_WORKER_*`；OA 退出清别人的 session；OA 登录写 `session('token')` 或改 `last_login_*`（会看起来像登进了电脑后台）。

即使浏览器里还有驾驶舱 `ADMIN_ID`，打开 `/admin/oa/login` 也应继续显示 OA 登录页（`login()` 只看 `OA_ID`）。

`app/admin/controller/PublicController.php`（不要改 vendor）里，`entry` 为 `field` 或 `oa` 时，有 `ADMIN_ID` 也不跳后台首页，防止以后误把 OA 接到公共登录页。

```php
$entry    = $this->request->param('entry', '');
$admin_id = session('ADMIN_ID');
$isolated = ['field', 'oa'];
if (!empty($admin_id) && !in_array((string) $entry, $isolated, true)) {
    return redirect(url("admin/Index/index"));
}
```

## 账密

与后台管理员相同：`cmf_user`，`user_type` 为 1 或 3。超管 `id=1` 始终可登；其他人看 `user_status`。不必有后台角色。

改密：校验原密码、新密至少 6 位、两次一致、不能与原密相同。密码用 `cmf_password()` 写入。

## 工作台数据

首页只展示数量，**不跳电脑后台页面**。后续审批功能接在本入口内做。

| 数字 | 来源 |
|------|------|
| 随手拍待审 | `active_shot_report` `status=0` |
| 整改待审 | `volunteer_task_log` `status=1`（待审核，见 `volunteer-task`） |
| 督导申请 | `check` `status=1`（表名是 `cmf_check`，不要写成 `member_check`） |
| 商家入驻 | `member_shop` `delete_time=0` 且 `status=1` |
| 今日打卡 | `forum` `delete_time=0` 且当天 `create_time`（与看板打卡口径一致） |
| 今日新用户 | `member` 当天 `create_time` |

## 关键注意事项

1. `Db` 必须用 `think\facade\Db`
2. `success()` 数据放第三参数：`BaseController::success($msg, $url, $data)`
3. 继承 `BaseController` 必须覆写 fetch / parseTemplate / `_initializeView`
4. 模板是 ThinkPHP：`{$var}` / `<foreach>` / `<eq>`，不要当 Vue 写
5. 登录页不要依赖 `__LOGIN_BY_CMF_ADMIN_PW__`（那是公共后台登录页的门闩；OA 自己的登录页直接打开即可）

## 调试路径

| 问题 | 排查 |
|------|------|
| TemplateNotFoundException，路径是 `{站点根}/view/admin/oa/...` | 缺 `_initializeView` / `fetch` 覆写 |
| OA 退出后再打开 OA，进了管理系统 | 未登录是否误跳了 `/admin/public/login`；`login()` 是否误看 `ADMIN_ID` |
| 登录后电脑后台也变成已登录 | `doLogin` 误写了 `ADMIN_ID` |
| 改密 500 / 原密码对不上 | 是否用 `cmf_compare_password` / `cmf_password` |
| 督导待审一直是 0 | 查的是 `check` 不是 `member_check` |

## 相关 skill

- 独立入口三坑：`pc-admin` → standalone-entry
- 外勤 Pad：`field-worker-workpad`
- 随手拍 / 整改状态：`shot-report`、`volunteer-task`
