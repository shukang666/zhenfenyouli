---
name: shot-report
description: "「随手拍」问题举报。小程序举报页 + API + PC 审核 ShotReport。审核通过后调用整改任务 createFromShotReport（不要写 active_shot_rectify）。当改举报字段、审核通过/驳回、位置类型表单，或排查随手拍审核页时调用。整改任务领取/提交/照片/状态见 volunteer-task。"
---

# 「随手拍」问题举报

整改任务怎么领取、提交、审核积分、显示照片，读 **`volunteer-task`**。本文件只覆盖举报提交与后台审核。

## 流程

```
小程序 POST /wxapp/shot_report/post
  → 后台 /admin/shot_report  pass / reject
  → pass：VolunteerTaskController::createFromShotReport
  → 任务出现在 /admin/volunteer_task ，志愿者小程序领取
```

**不要再往 `cmf_active_shot_rectify` 写。** 菜单「志愿者整改清单」已删。`ShotRectifyController::index` 只 302 到 `VolunteerTask/index`。不要再插入 `ShotRectify` 菜单。

## 表 `cmf_active_shot_report`

- `user_id` / `image` / `remark`
- `status`：0 待审 / 1 通过 / 2 拒绝（这是**举报单**状态，不是整改 log 状态）
- `audit_time`
- 位置：`location_type` 1 小区（`community_*` + `point_*`）/ 2 非小区（`merchant_name` + `address`）。不拆表。表单模式见 `mp-conventions` location-type-form。

## 小程序 `小程序/pages/dtsh/report.vue`

`postShotReport` → `/wxapp/shot_report/post`。`.remark-textarea` 必须 `box-sizing: border-box`。

API `api/wxapp/controller/ShotReportController.php`：`initialize()` 必须 public；写入用 `save()` / `insertGetId()`。

## 后台 `ShotReportController`

| 方法 | 职责 |
|------|------|
| `index` | 列表（状态 / 备注 / location_type） |
| `pass` | 通过后 `createFromShotReport($report)`（按 `shot_id` 去重） |
| `reject` | 驳回 |

模板 `public/themes/admin_fengiy/admin/shot_report/index.html`：不要 `layui-form` 包 select，状态用 PHP `selected="selected"`。

通过确认文案应对齐「发到整改任务」，不要写已下线的整改清单。

自动任务标题「随手拍整改：位置」，`image` 带举报图，任务 `status=1` 可领取，积分默认 0。

## 菜单

垃圾分类活动下：`ShotReport/index`。整改任务菜单属于 `volunteer-task`。

## 排查

| 现象 | 看 |
|------|-----|
| 通过后志愿者看不到任务 | `pass()` 是否还在写 `active_shot_rectify`；任务 `status` 是否为 1 |
| 同一条拍出两条任务 | `createFromShotReport` 是否按 `shot_id` 查重 |
| 审核页 select 两遍 | 去掉 `layui-form` |
| 提交按钮挤歪 | textarea border-box |
| 点旧整改清单 404 | 保留 302 控制器，菜单不要指向 ShotRectify |

## 相关

- 整改任务 / 提交记录 / 小程序我的任务：`volunteer-task`
- 后台菜单：`pc-admin`
