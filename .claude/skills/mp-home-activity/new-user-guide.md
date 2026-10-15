---
name: new-user-guide
description: 小程序新手引导。首页底部「点击查看使用教程」；其它 Tab 用右下角吉祥物点开。登录/进页不要自动弹。步骤配置在 common/pageGuides.js（非首页）和 index.vue（首页）。
---

# 小程序新手引导

## 当前约定

**不要主动触发。** 登录成功、各页 `onShow`、扫码进签到，都**禁止**自动弹教程。

两条被动入口：

| 页面 | 入口 |
|------|------|
| 首页 | 底部「点击查看使用教程」→ `openUsageGuide()` → `startNewUserGuide()` |
| 活动 / 健康 / 积分权益 / 我的 | 右下角吉祥物 `<page-guide>`，点气泡或点吉祥物 |
| 垃圾分类打卡 `pages/dtsh/dtsh` | 同样挂吉祥物，步骤 key 为 `dtsh`（无底部 Tab 步） |

可重复看。蒙层只用 `view`，不用 `cover-view`（会盖住扫码签到）。首页**不要**挂吉祥物。

## 非首页：吉祥物 + 配置

| 文件 | 职责 |
|------|------|
| `小程序/common/pageGuides.js` | 每页 `steps`（`selector` / `title` / `desc` / `prefer` / `type`） |
| `小程序/components/page-guide/index.vue` | 吉祥物、挖洞、滚动、复用 `new-user-guide` 卡片 |
| 各页模板 | `id="guide-xxx"`，末尾 `<page-guide page="low_carbon\|health\|mall\|user\|dtsh" />` |

新增一页：模板加 id → `pageGuides.js` 加 steps → 挂组件。

`selector` 空字符串：只出说明卡、不挖洞。底部五个按钮用 `type: 'tabbar'`，**按胶囊尺寸计算高亮**，不要去查组件里的 `#pg-tabbar`（坐标会偏）。页面节点用 `getCurrentPages()` 最后一页的 `$vm` 做 `createSelectorQuery().in(vm)`。目标不在屏内时 `uni.pageScrollTo`，让高亮和说明卡都落在顶栏与 Tab 之间；tabbar 步和空 selector 不滚。

气泡文案「遇到困难了吗？可以点我~」：**显示 3 秒 → 隐藏 5 秒**循环；点开教程时停，关掉后重开。

吉祥物按**屏幕**右下角（`right` + `bottom` 像素，贴在悬浮胶囊 Tab 上方），四页同一套坐标。用 `right/bottom` 锚定小人，气泡显隐时位置不变。拖动写入 `help_mascot_rb`（`{ r, b }`），切 Tab 共用。旧 key `help_mascot_pos` 不要再用。

组件必须 `virtualHost: true`，否则 `position:fixed` 相对组件插入点，会出现在页面左侧偏上且各页不一致。

## 首页

步骤在 `pages/index/index.vue` 的 `guideSteps`。`new-user-guide.vue` 只负责卡片。

当前：欢迎 → `#guide-search` → `#guide-hello` → `#guide-tabs` → `#guide-tabbar`。

## 禁止再做的事

| 不要 | 原因 |
|------|------|
| 登录写 `new_user_guide` / 首页 onShow 自动 `startNewUserGuide` | 旧主动触发 |
| `cover-view` 做蒙层 | 会盖住扫码签到 |
| 扫活动码流程里弹教程 | 和签到冲突 |
| 在 `page-guide` 里写页面级 `onShow` / `onHide` | 组件当空占位，吉祥物整只消失 |
| `pages.json` 的 `componentPlaceholder` 把 `page-guide` 映射成 `view` | 异步占位可能一直是空 view |
| 高亮 id 绑在 clip-path / transform 装饰节点上 | 挖洞位置错；活动页快捷入口用无变形的包裹层 |
| 各页不同的 `mascotBottom` 或用文档流定位 | 位置不统一，会跑到页面底部而不是屏幕右下角 |

## 关联

- 入口 skill：`mp-home-activity`
- 扫码签到：`active-scan-join.md`
- 分享封面：`mp-conventions` 的 [mp-share.md](../mp-conventions/mp-share.md)
