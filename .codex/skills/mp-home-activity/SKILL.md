---
name: mp-home-activity
description: "小程序首页与活动全栈。覆盖首页动态配置与模块顺序、活动 Tab 标签栏（cmf_active_tab）、活动置顶、详情页 hero/弹层、活动显示形式（配置式/Web式 H5 web-view）、扫码参加与活动小程序码、学分类-考试-打卡门禁、垃圾分类打卡入口两步引导（场景→种类→拍照）、新手引导。当改首页、活动列表/详情、Web 式活动 URL、扫码签到、置顶、打卡资格、打卡前引导/上报类型预选、新手引导，或排查活动不展示/二维码串码时使用。"
---

# 首页 + 活动

先读 `project-map` 分清 `小程序/`、`api/wxapp/`、`app/admin/`。本模块经常 **三端同改**。

## 关键文件

| 端 | 路径 |
|----|------|
| 首页 | `小程序/pages/index/index.vue` |
| 活动 Tab | `小程序/pages/low_carbon/low_carbon.vue` |
| 活动详情 | `小程序/pages/low_carbon/lsxf/details-new.vue` |
| 接口 | `api/wxapp/controller/ActiveController.php` |
| 后台活动 | `app/admin/controller/ActiveController.php`、`public/themes/admin_fengiy/admin/active/` |
| 活动标签后台 | `/admin/active_tab/index.html`、表 `cmf_active_tab` |

## 按场景读原文

| 场景 | 读 |
|------|-----|
| 首页 banner / 入口 / 通知 / 商品从后台 option 出 | [home-config.md](home-config.md) |
| 首页从上到下模块顺序、hello-card、z-paging | [index-layout.md](index-layout.md) |
| 活动页标签、折叠侧栏、绑定分类、`cmf_active_tab` | [activity-tabbar-layout.md](activity-tabbar-layout.md) |
| 活动置顶 `is_top` | [activity-pin.md](activity-pin.md) |
| 详情 hero 只展示积分 + 参与人数 | [active-detail-hero.md](active-detail-hero.md) |
| 详情打卡/签到/表单弹层状态机 | [active-detail-popup.md](active-detail-popup.md) |
| `join_type=2` 扫码参加、列表隐藏、扫码进详情 | [active-scan-join.md](active-scan-join.md) |
| 显示形式：配置式 vs Web 式（H5 + web-view） | [active-web-display.md](active-web-display.md) |
| 活动小程序码互相覆盖（OSS 路径未带活动 ID） | [active-qrcode-fix.md](active-qrcode-fix.md) |
| 未学习/未考试不能打卡（首页、活动页、学习页） | [learn-exam-clock-gate.md](learn-exam-clock-gate.md) |
| 打卡入口两步引导（场景→种类→拍照）、上报类型预选、record/choice 拆分 | [clock-hub-wizard.md](clock-hub-wizard.md) |
| 活动 Tab Banner 总入口 vs 三场景深链、`scene-clock-flow` 参数 | [scene-clock-entry-routes.md](scene-clock-entry-routes.md) |
| 新手引导（首页底部教程入口；其它 Tab 右下角吉祥物，步骤见 pageGuides.js） | [new-user-guide.md](new-user-guide.md) |

## 业务要点（读原文前先对齐）

- **显示形式**（`cmf_active.display_mode`）：`1` 配置式走详情页；`2` Web 式只配 `web_url`，小程序 web-view 打开，见 [active-web-display.md](active-web-display.md)。
- **扫码参加**（`cmf_active.join_type=2`）：小程序列表/推荐隐藏，只能扫后台生成的活动码进详情；详情主按钮是拍照签到，不走普通打卡流程。
- **小程序码**：`CommonModel::setShareImage` 的 `$image_name` 必须区分活动 ID，禁止写死 `'active'`，否则后建活动覆盖先建活动的码。
- **学考门禁**：`is_learn=1` 且 `is_answer=1` 才能打卡。判断散落在首页 `goToClock`、活动页 `onShow`、学习页按钮，改一处要核对另外两处。这和「七贤岭学考融合」（`qxl-learn-exam`）不是同一套，七贤岭见 `knowledge-config` / [qixianling.md](../knowledge-config/qixianling.md)。
- **详情弹层**：`tipShow` / `sign_clock_show` / `actionFormShow` 关闭走 methods；`actionTrigger` 可能为 null，模板里不要直接 `__map`。uView/崩溃细节见 `mp-conventions`。

通用组件、分包、图片 URL 见 `mp-conventions`。
