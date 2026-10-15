---
name: mp-scene-clock-entry-routes
description: "活动 Tab 真分打卡入口：Banner 总入口（选场景）与三场景快捷深链（等同选场景后进入 hub）。路由参数、后台 Banner 配置、与 scene-clock-flow / ljfldk 分工。"
---

# 真分场景打卡入口路由

## 页面与职责

| 页面 | 路径 | 作用 |
|------|------|------|
| 活动 Tab | `pages/low_carbon/low_carbon` | Banner、本周推荐置顶、**三场景快捷入口** |
| 打卡主流 | `pages/activity/scene-clock-flow` | 选场景（`mode=theme`）或单场景 hub 内：摆桶/种类/引导/拍照 |
| 低碳工作台 | `pages/flhd/ljfldk` | 旧版两步引导入口；选场景后同样跳进 `scene-clock-flow?hub=` |

统一跳转封装：`小程序/common/sceneClockEntry.js`（`openSceneClockTheme` / `openSceneClockHub` / `parseBannerSceneClockEntry`）。

## 两条入口，一条后续链路

```text
Banner / 本周推荐置顶 / url=scene-clock-intro|zhenfen
  → scene-clock-flow?mode=theme
  → flowStep=theme_scenes（为什么要分 + 选场景三卡片）
  → pickThemeHub(hub) → bootstrapHub(hub) → ensureExamGate → 场景内打卡流

活动 Tab「场景快捷入口」家庭 / 办公 / 沿街
  → scene-clock-flow?hub=home|office|street
  → 跳过 theme_scenes，直接进入 bootstrapHub(同上 hub)
```

**结论**：三场景入口 = 用户在选场景页点对应卡片之后的状态；不是旧版 `home-scene` → `ljfldk` 分叉。学考门禁在 hub 内由 `ensureExamGate`（及选场景页 `onShow` 的 `ensureThemeSceneGate`）处理，与 [learn-exam-clock-gate.md](learn-exam-clock-gate.md) 一致。

场景元数据与 hub 内步骤见 [clock-hub-wizard.md](clock-hub-wizard.md) 及 `common/sceneClockHub.js`。

## URL 参数约定（scene-clock-flow）

| 参数 | 含义 |
|------|------|
| `mode=theme` 或 `entry=intro` | 进入 **选场景**（`theme_scenes`），不立刻加载 hub |
| `hub=home\|office\|street` | **深链** 到对应场景，等价 `pickThemeHub` 之后 |

`hub` 会经 `normalizeClockHubKey` 规范化；无效 hub  toast 后返回。

## 活动 Tab 触点

| UI | 代码 | 路由 |
|----|------|------|
| 顶部 Banner | `handleBannerTap` + `parseBannerSceneClockEntry` | 见下表「后台 Banner url」 |
| 本周推荐第一张置顶卡 | `onWeeklyCardTap`（`WEEKLY_ZHENFEN_PIN`） | `mode=theme` |
| 三场景卡片 | `gotoHomeScene` / `gotoOfficeBuilding` / `gotoStreetShop` | `hub=home` / `office` / `street` |

`handleBannerTap` 规则：

- `scene-clock-intro`、`zhenfen`、含 `scene-clock-intro` → **总入口** `mode=theme`
- 完整路径含 `scene-clock-flow` 且无 `hub=` → 统一 **总入口**（避免旧配置跳过选场景）
- 含 `scene-clock-flow?hub=xxx` → **场景深链**（与 Tab 三入口一致）
- 别名 `scene-clock-hub:home`、`zhenfen-hub:office` → **场景深链**

## ljfldk

`navigateScene` 在登录、打卡前学习+测验（`showSceneHub`）、小区通过后，应使用 `openSceneClockHub`，与活动 Tab 深链同一 URL。

## 遗留页

`pages/activity/home-scene` 若仍被链到，应 `redirectTo` 到 `scene-clock-flow?hub=home`，不要进 `ljfldk`。

## 改入口时检查清单

1. Banner 新 url 别名是否在 `parseBannerSceneClockEntry` 注册
2. 新入口是否应走 `theme` 还是 `hub`
3. 是否只需改 `sceneClockEntry.js`，避免在多个页面手写 query 字符串
4. 学考门禁是否仍由 `scene-clock-flow` 统一拦截（不要在 Tab 三入口重复弹不同文案，除非产品明确要求）
