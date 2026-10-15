---
name: volunteer-task
description: "整改任务全栈。任务清单 cmf_volunteer_task（随手拍审核通过自动发 + 后台手动发布），领取/提交 cmf_volunteer_task_log。小程序动态社区领取与提交，后台 /admin/volunteer_task 管理与提交记录审核发积分。当改整改任务、提交记录照片不显示、后台通过后小程序仍待审核、待审核重复提交、手动发布配图时调用。"
---

# 整改任务

随手拍怎么变成任务，见 `shot-report`（`pass()` → `createFromShotReport`）。**本 skill 管任务本身**：两套 status、两套图片字段、领取提交审核。

## 文件

| 端 | 路径 |
|----|------|
| 后台 | `app/admin/controller/VolunteerTaskController.php` |
| 后台模板 | `public/themes/admin_fengiy/admin/volunteer_task/{index,add,records}.html` |
| 小程序接口 | `api/wxapp/controller/VolunteerTaskController.php` |
| 小程序页 | `小程序/pages/dtsh/dtsh.vue`、`task-list.vue`、`task-submit.vue` |
| 旧清单 | `ShotRectifyController` 只 302 到本模块，不要再写 `cmf_active_shot_rectify` |

后台菜单：`VolunteerTask/index` 整改任务管理，`VolunteerTask/records` 提交记录。

## 两张表、两套 status（不要混）

### `cmf_volunteer_task`（任务本身）

| 字段 | 含义 |
|------|------|
| `shot_id` | 随手拍 ID；**0 = 手动发布**。同一 shot 只插一条 |
| `title` / `content` | 小程序可见的任务项 / 要求 |
| `image` | **问题照片**（随手拍带入，或手动上传）。一张，普通 URL |
| `point` | 审核通过发的积分；自动任务默认 0 |
| `status` | **1 启用可领取 / 0 停用**（跟提交记录 status 无关） |

手动发布：`add.html` 的 `name="image"` + `openUploadDialog`，控制器 `add()` 要写入 `image`。

### `cmf_volunteer_task_log`（领取与提交）

**必须全链路用这一套，后台不要另编 0/1/2：**

| status | 含义 | 谁改 |
|--------|------|------|
| 0 | 待提交（刚领取） | `accept` |
| 1 | 待审核 | 小程序 `submit` |
| 2 | 审核失败，可再交 | 后台 `deny` |
| 3 | 审核通过 | 后台 `approve`（发积分） |

`approve` 必须写成 **3**。写成 1 时：后台列表会显示「已通过」，小程序 `logStatusName(1)` 仍是「待审核」，积分也可能已经发了。

`submit` 只允许当前 0 或 2。`approve` / `deny` 只允许当前 1。

历史坑：曾把「通过」写成 1。库里 `status=1 AND admin_id>0` 的行应按 3 理解；迁数据时按这个口径 UPDATE。

## 两套图片（不要当一种解析）

| 字段 | 存什么 | 怎么显示 |
|------|--------|----------|
| `volunteer_task.image` | 单张问题图 | 直接 `cmf_get_image_preview_url` / `cmf_get_asset_url` |
| `volunteer_task_log.images` | **JSON 数组** `["https://oss..."]` | **必须** `VolunteerTaskController::parseLogImages()` |

`cmf_get_image_preview_url` 只认以 `http` 或 `/` 开头。JSON 字符串以 `[` 开头，会被当成相对路径拼到 OSS：**`https://oss.ausite.cn/[`**。

**禁止**模板里 `explode(',', $vo['images'])`。后台 `records()` 先 `each` 出 `images_arr` 再循环。

## 小程序「我的任务」

`goSubmit`（`dtsh.vue` / `task-list.vue`）和提交页 `task-submit.vue`：

| log.status | 行为 |
|------------|------|
| 0 | 可进提交页 |
| 1 | toast「审核中，请勿重复提交」，不进页；提交页若误入只展示审核中、无表单 |
| 2 | 可进，允许再交 |
| 3 | toast「任务已完成」 |

问题照片字段：列表 `image`，我的任务 `task_image`。预览走 methods `previewShot`，不要模板内联赋值。

接口：`/wxapp/volunteer_task/task_list|accept|my_tasks|submit`（见 `小程序/api/wxapp.js`）。

**每日限额**：同一用户自然日（当天 0 点起，`create_time`）最多 **领取 1 条**。`accept()` 拦截；`task_list` 给每条任务带 `daily_limit`。小程序文案：「每人每天最多领取 1 条」；按钮「今日已领」。同一任务仍不能重复领。

## 后台提交记录筛选

0 待提交 / 1 待审核 / 2 已拒绝 / 3 已通过。通过/拒绝按钮只出现在 **status=1**。未提交（0）不能当审核单点通过。

## 排查

| 现象 | 原因 |
|------|------|
| 照片 `https://oss.ausite.cn/[` | JSON 被当成相对路径 |
| 后台过了、小程序待审核 | `approve` 写成了 1 而不是 3 |
| 待审核还能再交 | `goSubmit` 没拦 1 |
| 手动任务没图 | `add` 表单/控制器没存 `image` |
| 点通过提示只能审待审核 | 点到了未提交的领取行，或状态已是 3 |
| 旧 `/admin/shot_rectify` | 应 302 到本列表，不要复活整改清单页 |
