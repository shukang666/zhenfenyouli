---
name: mp-secondhand-market
description: "社区闲置（积分权益 Tab）与七贤岭绿色二手市集（活动 Tab）双端联动。线上浏览/发布闲置，线下定期市集统一交接；介绍页 secondhand-intro、路由 secondhandNav.js、activity-spot-hub flea_market 互跳。改闲置规则文案、Banner 入口、场次列表或后续接兑换/backend 时调用。"
---

# 社区闲置 × 绿色二手市集

先读 `project-map`；积分 Tab 壳层见 **mp-points-mall**；活动专题见 **mp-home-activity**、后端 `ActivitySpotService` / `FleaMarketService`。

## 业务边界（对用户怎么说）

| 模块 | 做什么 | 不做什么 |
|------|--------|----------|
| **积分权益 · 社区闲置** | 浏览他人闲置、发布自己的闲置（积分标价）；说明「下一步去市集」 | 不是官方商城下单；**不在线上完成官方积分交割** |
| **活动 · 七贤岭绿色二手集市** | 定期线下场次（时间/地点/进 Web 或配置式活动详情） | 不是闲置列表页本身；场次来自 `cmf_flea_market_session` + `spot_key=flea_market` |
| **官方积分商城** | 商家商品兑换、订单、购物车 | 与居民闲置 C2C 数据分离 |

**标准话术**：线上看货、发布、约好换什么 → **到绿色二手市集现场**，工作人员统一组织交接与积分确认。

## 页面与入口

| 路径 | 作用 |
|------|------|
| `pages/points/secondhand-intro` | **规则介绍 + 主入口聚合**（积分 Tab Banner **只进这里**）；介绍页切图均在 COS `static/mall/`：`secondhand-intro-hero.png`、`secondhand-intro-summary.png`、`secondhand-intro-steps.png`、`secondhand-intro-notice.png` |
| `pages/points/secondhand-list` | 列表 / 搜索 / 发布入口 |
| `pages/points/secondhand-publish` | 发布表单 |
| `pages/points/secondhand-detail` | 详情 +「我想要」提示去市集 |
| `pages/points/secondhand-my` | 我的发布 |
| `pages/activity/activity-spot-hub?spot=flea_market` | 市集场次列表 → 点场次进活动（Web/详情） |

## 路由常量（勿硬编码多处）

文件：`小程序/common/secondhandNav.js`

- `fleaMarketHubUrl()` → 活动二手市集 hub  
- `secondhandIntroUrl()` / `secondhandListUrl()` / `secondhandPublishUrl()` / `secondhandMyUrl()`

## 跳转关系

```
mall.vue Banner ──► secondhand-intro
                         ├──► fleaMarketHubUrl()（查场次）
                         ├──► secondhand-list（浏览）
                         └──► secondhand-publish（发布，需登录）

activity-spot-hub (flea_market)
    ├── 文案：近期市集场次 + 线下交接说明
    ├──► secondhand-intro（规则）
    └──► secondhand-list（浏览/发布）

secondhand-list ──► intro / flea hub（顶栏两行链）
```

## 数据与接口（已接后端）

| 表 | 说明 |
|----|------|
| `cmf_secondhand_item` | 闲置发布；`status` 0 待审 / 1 在售 / 2 预订 / 3 关闭 / 4 拒绝 |
| `cmf_secondhand_intent` | 「我想要」意向；现场交割后后台「交割完成」扣买家 229、加卖家 230 |

| 接口 | 路径 |
|------|------|
| 元数据 | `POST /wxapp/secondhand/meta` |
| 列表 | `POST /wxapp/secondhand/list`（仅 status=1） |
| 详情 | `POST /wxapp/secondhand/detail` |
| 发布 | `POST /wxapp/secondhand/publish`（待审） |
| 我的 | `POST /wxapp/secondhand/my_list` |
| 下架 | `POST /wxapp/secondhand/off_shelf` |
| 我想要 | `POST /wxapp/secondhand/create_intent` |

小程序：`小程序/api/secondhand.js`；文案工具仍用 `common/secondhandStore.js`（无 localStorage）。

**PC 后台**：`SecondhandItem/index` 审核；`SecondhandItem/intent_index` 交割/取消意向。DDL 见 `update/update.sql`。

市集场次：**后端** `ActivitySpotService::fleaSessionVenues()`。Web 市集页可后续加强，小程序侧互跳 + 介绍页已对齐规则。

## 改入口时

| 改什么 | 改哪里 |
|--------|--------|
| 积分 Tab 二手 Banner 去向 | `mall.vue` → `goSecondhand()` 应指向 `secondhand-intro` |
| 规则/三步说明文案 | `secondhand-intro.vue`（与详情弹窗、列表副标题保持一致） |
| 活动页闲置入口 | `activity-spot-hub.vue` 仅 `isFleaMarket` 显示 `spot-flea-bridge` |
| 新增跳转 | 优先扩展 `secondhandNav.js` |

## 后续接后端（用户未定的「兑换步骤」）

1. 闲置表 + COS 图；发布审核可选。  
2. 「我想要」→ 预约/意向单，**状态机到「待现场交割」**，积分冻结在确认到场或工作人员核销时完成。  
3. 与 `session_id` / `active_id` 关联，便于市集当天对单。  
4. 是否合并进 Web 市集页：产品选统一 H5 时，hub 仍保留场次列表，Web 负责现场流程 UI。

## 与其它 skill

- 图片 URL、分包扁平路径：`mp-conventions`  
- 商城 Tab UI：`mp-points-mall`  
- 活动打开 Web/H5：`mp-home-activity` + `activeOpen.js`
