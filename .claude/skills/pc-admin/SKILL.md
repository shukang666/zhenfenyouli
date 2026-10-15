---
name: pc-admin
description: "ThinkCMF PC 后台（admin_fengiy 服务端渲染）约定。覆盖 URL 定位控制器/模板/菜单、cmf_admin_menu、新页面三件套、列表加列、搜索导出分页、批量删除、表单保存链、经纬度粘贴、删除二次确认中间件、运营看板、窄屏适配、独立登录入口（外勤 Pad / 手机 OA）、论坛删帖扣分日志、layer/noty/artDialog 提示与确认框皮肤、以及每次用户可见改动写入 cmf_update_log。当改 /admin 页面、菜单 404、列表/导出、删除确认、后台提示弹窗、后台首页看板或更新记录时使用。"
---

# PC 后台约定

后台不是 SPA。URL `/admin/<controller_snake>/<action>.html` → 控制器 `app/admin/controller/<Camel>Controller.php` → 模板 `public/themes/admin_fengiy/admin/<controller_snake>/<action>.html`。菜单在表 `cmf_admin_menu`，**不在** `data/lang`（那只是可选译名）。

先读 `project-map`。改生产库 / 验证是否已部署见 `dev-environment` 的 [prod-db-change-and-deploy-verify.md](../dev-environment/prod-db-change-and-deploy-verify.md)。

## 按场景读原文

| 场景 | 读 |
|------|-----|
| 知道 URL，找不到文件；「改了没生效」是不是改错页 | [page-locator.md](page-locator.md) |
| 菜单增删改、点进去 404、图标不显示 | [menu-manage.md](menu-manage.md) |
| 新模块：控制器 + 模板 + 菜单 | [new-page.md](new-page.md) |
| 列表中间加一列，导出也要对齐 | [add-column.md](add-column.md) |
| 搜索 / 时间范围 / 导出 / 分页 | [search-export.md](search-export.md) |
| 勾选批量删除、批量改状态 | [batch-delete.md](batch-delete.md) |
| 表单字段保存不上 | [form-save-chain.md](form-save-chain.md) |
| 经纬度粘贴 `lng,lat` | [lnglat-input.md](lnglat-input.md) |
| 删除要填原因；curl 直 POST 仍能删 | [delete-confirm.md](delete-confirm.md) |
| 删除成功 / 登录成功 / 确认取消弹窗外观；「只挪了位置样式没变」 | [admin-toast.md](admin-toast.md) |
| `/admin/main/index` 运营看板 | [dashboard.md](dashboard.md) |
| 手机打开后台挤成一条 | [mobile-responsive.md](mobile-responsive.md) |
| 外勤 Pad 督导台登录/session | `field-worker-workpad` |
| 手机 OA 登录/工作台（`/admin/oa/login`，`OA_*` session） | `oa-portal` |
| 整改任务、提交记录照片裂成 `oss.../[`、通过后小程序仍待审核 | `volunteer-task` |
| 论坛删帖扣分 + `cmf_forum_delete_log` | [forum-delete-log.md](forum-delete-log.md) |
| **答题考试**、答题记录弹窗导出、`params.type` 报错 | [problem-exam-admin.md](problem-exam-admin.md) |
| **每次面向用户的改动写更新记录** | [update-log.md](update-log.md) |

## 必须记住的几条

1. 多数列表控制器继承 `ToolController`：`setWhere` / `setIndex` / `outData`。加列时 **thead、循环 td、导出 `$head/$keys` 三处一起改**。
2. `index2.html` 这类 URL 常常是 `redirect` 到同一个模板并带 `type`，不要去找不存在的 `index2.html`（例：`Problem/index1` → `index?type=1`，模板 `problem/index.html`；`type` 缺省会报未定义索引，见 [problem-exam-admin.md](problem-exam-admin.md)）。
3. 新页面缺一不可：控制器缺失 → 菜单点进去 **404**；模板缺失 → 报找不到模板；菜单缺失 → 页面能打开但侧栏没有。
4. 删除安全靠中间件 `app/middleware/AdminDeleteGuard.php`，前端弹层没有安全意义。
5. 看板地址必须保持 `/admin/main/index`，改控制器改 `app/admin/controller/MainController.php`（不要改 vendor 里的同名类）。
6. 独立入口若继承 `BaseController` 而不是 `AdminBaseController`，必须自己实现 `_initializeView()` 设 `view_base`，否则模板会去站点根 `view/admin/`。与驾驶舱共用登录页时，独立 session 不能靠 `#hash` 区分入口，必须 `?entry=` + 覆盖 `PublicController::login`（见 standalone-entry 坑③）。手机 OA 用自己的 `/admin/oa/login` 和 `OA_*` session，见 `oa-portal`。
7. 更新记录：任务完成后自己往 `cmf_update_log` 插一条；用 PHP/PDO 写中文；模板不要对 `content` 再 `htmlspecialchars`。

外勤 Pad 业务页见 `field-worker-workpad`。手机 OA 见 `oa-portal`。地图选点见 `mp-map-location`。随手拍审核见 `shot-report`，整改任务见 `volunteer-task`。
