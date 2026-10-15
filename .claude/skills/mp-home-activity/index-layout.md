---
name: mp-index-layout
description: "小程序首页 pages/index/index.vue 的结构与布局总览。记录首页自上而下的模块顺序（logo 导航栏 → 活动搜索+扫码入口 → banner 轮播 → 通知栏 → 问候/快捷打卡卡 → 四宫格入口 → 热门活动横滑 → 兑换商品双列网格）、z-paging + slot top 导航栏结构、自定义 tabbar 选中态、骨架屏最少 400ms、问候/快捷打卡卡 hello-card 的字段与逻辑（昵称 userInfo.nickname、签到 signStatus、连续打卡 todayTask 按 %7 计算），以及各模块样式约定（左右 30rpx 留白、圆角 24rpx）。当需要在首页新增/移动模块、调整首页布局顺序、修改问候/快捷打卡卡展示，或排查首页元素不显示/位置错乱时调用。"
---

# 小程序首页（index.vue）结构与布局

文件：`小程序/pages/index/index.vue`

## 1. 页面结构（自上而下）

| 顺序 | 模块 | 说明 |
|---|---|---|
| 1 | 顶部导航栏 | `z-paging` 的 `<template slot="top">` 内，`u-navbar :is-back="false"`，只放 logo 图，无返回按钮 |
| 2 | 活动搜索 + 扫码入口 | `.home-search-wrap`：左侧搜索框点击进 `/pages/low_carbon/active-search`，右侧 scan 图标 `@click.stop="onScanQrcode"` |
| 3 | Banner 轮播 | `.banner-wrap` + `u-swiper`（`height="340"` `border-radius="24"`，单位 rpx），点击 `adLink` |
| 4 | 通知栏 | `.notice-bar-wrap`，`v-if="homeNotice"`，`u-notice-bar`（后台 `home_notice` 配置驱动） |
| 5 | 问候 + 快捷打卡卡 | `.hello-card`，`v-if="isLogin && todayTask.show"`；含头像、问候语、昵称、签到按钮、快捷打卡按钮，详见第 4 节 |
| 6 | 四宫格功能入口 | `.entry-grid` + `v-for="item in entry_list"`，一行四个（后台 `home_entries` 配置驱动） |
| 7 | 热门活动 | `.section-header` 标题 + `.active-scroll`（`scroll-view scroll-x` 横滑卡片），点击进活动详情 |
| 8 | 兑换商品 | `.section-header` + `.goods-grid` 双列网格，数据来自 `z-paging` 的 `dataList` |
| 9 | 数康简介 + 使用教程 | `.home-footer`：简介卡片 + 斜切教程入口，点入口调 `openUsageGuide()` |

## 2. 关键实现点

- **滚动容器**：整页用 `<z-paging ref='paging' v-model='dataList' @query='queryList' :fixed='true' :safe-area-inset-bottom="true">`；商品分页由 `queryList(page)` → `this.$refs.paging.complete(...)` 驱动。
- **骨架屏**：`<skeleton-screen :show="isLoading" type="index">`；`loadAllData()` 用 `Promise.all` 并行拉 banner/活动/入口/通知，**最少显示 400ms** 防闪烁。
- **自定义 tabbar**：`onShow` 调 `updateTabBarSelected(0)`，通过 `this.$mp.page.getTabBar().setSelected(index)` 同步选中态。
- **入口合并逻辑**：`getHomeEntries()` 用后台配置按 `key` 与本地 `entry_list` fallback 合并，**保留默认 `svgIcon`**（后台没配图标时用默认）。默认四个入口：社区活动/健康测试/积分商城/社区互动。
- **switchTab 不能带参数**：跳活动页"本月推荐"用 `uni.setStorageSync('jump_monthly_rec', 1)` + `uni.switchTab` 传标记（`goEntry`、`adLink` 都是这个套路）。
- **强制完善信息**：`onShow` 里 `nextTick` 判断已登录但无 `community_id` → 跳 `/pages/user/info`。

## 3. 样式约定

- 页面背景 `#F5F6F8`；各区块统一**左右 30rpx 留白**，卡片圆角 **24rpx**（搜索框、banner、hello-card 一致）。
- 区块顶部间距：搜索框 `24rpx`、banner `30rpx`、通知 `20rpx`、hello-card `24rpx`、entry-grid `24rpx`；`section-header` 为 `40rpx 30rpx 24rpx`。
- 商品双列：`.goods-grid` flex-wrap + `.goods-card` 宽 `334rpx`，底部留 `160rpx` 给 tabbar。
- 搜索框右侧扫码图标用 `border-left: 1rpx solid #eeeeee` + `padding-left: 20rpx` 做分割。

## 4. hello-card 问候/快捷打卡卡（字段与逻辑）

显示条件 `v-if="isLogin && todayTask.show"`，`getUserInfoForGrowth()` 成功后才置 `todayTask.show = true`（未登录/接口失败不显示）。

| 展示位 | 字段 | 说明 |
|---|---|---|
| 头像 | `userInfo.avatar` | 空时兜底 `/static/images/default-avatar.png` |
| 问候语 | `greetingText`（computed） | 按 `greetingHour` 分段：<6 凌晨好 / <12 早上好 / <14 中午好 / <18 下午好 / <22 晚上好 / 其余 夜深了 |
| **昵称** | `userInfo.nickname` | 该位原为积分等级名（`userLevelName` + `calculateLevelName`：≥1000 社区先锋、≥300 绿色达人、其余 环保新人），**已改为直接显示真实昵称**（空时兜底「环保新人」），相关等级代码已删除 |
| 签到按钮 | `signStatus.today_signed` / `continue_days` | `getSignStatus()` 拉取；已签到置灰显示「已签到」，未签到显示「签到」 |
| 连续打卡 | `todayTask.streak` | 取 `info.clock_days \|\| info.continuous_days \|\| info.clock_count` |
| 进度 / 还差几天 | `todayTask.progress` / `daysToGoal` | 按 `streak % 7` 计算：`progress = (streak%7)/7*100`，`daysToGoal = 7 - streak%7`（整除时归 0） |

- 整卡点击（头像/问候区）跳 `/pages/user/index`（`switchTab`）；「签到」按钮用 `@click.stop` 独立处理，不触发整卡跳转。
- 「快捷打卡」按钮 → `goToClock` → `goClockCheck`（学习/考试准入判定），详见 skill `mp-learn-exam-clock-gate`。

## 5. 新增/移动模块时

1. 决定插入位置（参考第 1 节顺序），在 `<z-paging>` 内对应位置插入 `<view>`。
2. 样式复用相邻区块的留白（30rpx）与圆角（24rpx），保持视觉对齐。
3. 若数据来自接口，加进 `loadAllData()` 的 `Promise.all`。
4. 若为整页跳转且目标是 tabbar 页，用 `switchTab`（不能带参，参数用 storage）。
5. 跨页面搬迁元素见 skill `mp-page-element-migrate`。

## 相关技能

- `mp-home-config`：首页配置项（home_entries / home_notice / home_banner）的后台+接口+前端全链路。
- `mp-learn-exam-clock-gate`：首页快捷打卡的学习/考试/打卡准入链路。
- `mp-page-element-migrate`：元素跨页面迁移清单。
- `mp-notice-bar`：通知栏组件用法。
