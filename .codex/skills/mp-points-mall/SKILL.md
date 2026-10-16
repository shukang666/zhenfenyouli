---
name: mp-points-mall
description: "小程序积分权益 Tab（积分商城）与闲置积分交换。覆盖 mall.vue 顶栏（积分明细/排行/购物车，无悬浮钮）、积分卡片、搜索、分类 pill、产区地图、get_mall_tab_home、secondhand 扁平分包与本地 storage 演示。改积分 Tab、商城筛选、导航图标、二手闲置或接后端前必读本 skill。"
---

# 积分权益 Tab + 闲置交换

先读 `project-map` 定端；列表数据在 `api/wxapp/`，Tab 在 `小程序/pages/mall/`。

**UI 定稿全文（维护者主文档）** → [mall-ui-spec.md](mall-ui-spec.md)  
**分类/地图筛选逻辑** → [mall-tab-ui.md](mall-tab-ui.md)  
**闲置交换产品与技术** → [secondhand-frontend.md](secondhand-frontend.md)

## 关键文件

| 用途 | 路径 |
|------|------|
| 积分权益 Tab | `小程序/pages/mall/mall.vue` |
| 积分明细 | `小程序/pages/points/points.vue` |
| 购物车 / 商品排行 | `小程序/pages/shop/cart.vue`、`ranking.vue` |
| 地图组件 | `小程序/components/mall-china-map/mall-china-map.vue` |
| 产区映射 | `小程序/common/mallGeoMap.js`、`mallChinaCanvasPaths.js` |
| 聚合接口 | `api/wxapp/controller/GoodsController.php` → `get_mall_tab_home` |
| 实现 | `api/wxapp/service/TabHomeService.php` → `buildMallTabHome` |
| 小程序 API | `小程序/api/tab-home.js` |
| 闲置（纯前端） | `pages/points/secondhand-*.vue`、`common/secondhandStore.js` |
| 样式 | `小程序/static/styles/glass-ui.scss`（`$mall-block-gap: 24rpx` 在 mall.vue scoped 内） |

## 快速对照（易忘）

| 问题 | 答案 |
|------|------|
| Tab 页是哪个文件？ | `pages/mall/mall`，不是 points.vue |
| 购物车、排行在哪进？ | **仅顶栏右侧**：明细 → 排行(star) → 购物车；**无**右下悬浮按钮 |
| 导航为何不要 fixed？ | 避免滚动时与积分卡片叠字；见 mall-ui-spec |
| 综合文创在哪？ | 与「产区地图」同级的 pill，不是地图内按钮 |
| 闲置分包路径 | `secondhand-list` 等，文件与 `points.vue` **同级**，禁止 `secondhand/list` 目录 |
| 空状态 text+按钮换行 | `mp-conventions` → text-view-layout.md |
| 接口 first_cate | 需服务端已部署 TabHomeService 扩展 |

## 接口参数（`get_mall_tab_home`）

| 参数 | 含义 |
|------|------|
| `page` | 商品分页 |
| `second_cate` | 产区子类 ID（产区地图 / 综合文创） |
| `first_cate` | 一级商品类型 ID |
| `keyword` | 商品名搜索 |

`page=1` 且无筛选时返回：`coupon_list`、`cate_list`、`first_cate_list`。

## 与其它模块

- **社区闲置 × 绿色二手市集**（规则、互跳、Banner → 介绍页）：见 **mp-secondhand-market** skill  
- 积分 Tab 二手 Banner → `secondhand-intro`（不是直接进 list）  
- 官方兑换商品详情：`/pages/shop/details`  

## 改 UI 时

- Pill/chip：灰底 `#f2f4f3`，选中 `#e3f3ea` + `#076d3d`  
- 搜索框：毛玻璃外层 flex 居中，勿 asymmetric padding 顶偏  
- uView、图片 URL、分包： `mp-conventions`  
