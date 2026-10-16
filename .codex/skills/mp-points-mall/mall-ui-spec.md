# 积分权益 Tab · UI 与交互定稿（2026-10）

供后续维护者对照；改 `小程序/pages/mall/mall.vue` 前建议通读本文件 + [mall-tab-ui.md](mall-tab-ui.md)。

## 页面是谁

- **Tab 名称**：积分权益（`pages.json` 主包 `pages/mall/mall`，`navigationStyle: custom`）
- **不是** `pages/points/points`（那是「积分明细」子页，从顶栏或我的进入）

## 顶部导航（`u-navbar`）

| 项 | 约定 |
|----|------|
| 是否固顶 | **`is-fixed=false`**，与下方内容一起滚动，避免透明/固顶导致与积分卡片叠字 |
| 背景色 | `#e4e8e5`，与 `glass-page` 底一致 |
| 左侧 | 文案标题「积分权益」 |
| 右侧图标（从左到右，在胶囊左侧） | ① `order` → `goPointsLog` → `/pages/points/points`（需登录）<br>② `star` → `goRanking` → `/pages/shop/ranking`（无需登录）<br>③ `shopping-cart` → `goCart` → `/pages/shop/cart`（需登录） |

**已废弃（勿恢复）**

- 页面右下角 **悬浮大图** 购物车、商品排行（`mall-float-btn` / `mall-float-btn--rank`）——与顶栏重复，已删除。

## 自上而下内容区

1. **`mall-top`**：`u-navbar` + `care-pull-hint` + **积分卡片** `points-hub__card`  
   - 可用积分千分位；`onShow` 调 `getUserInfo` 刷新  
   - 卡片内链「积分明细」  

2. **`mall-body-inner`**（左右 `30rpx`，块间距 **`$mall-block-gap: 24rpx`**）  
   - **社区闲置入口** `mall-secondhand-entry` → `/pages/points/secondhand-list`  
   - **搜索** `mall-search-box` + `glass-search`：父级 `flex` 纵向居中，`u-search` 设 `margin="0"`，高度 `68rpx`，内底 `#f5f7f6`  
   - **优惠券**（有数据时）  
   - **分类白卡片** `mall-cate`：pill + 地图/文创子区，见 [mall-tab-ui.md](mall-tab-ui.md)  
   - **商品双列** `goods-section` / `goods-grid`  

3. **`tabbar-placeholder`** + `page-guide page="mall"`

## 分类与地图（摘要）

- Pill 顺序：**全部 → 产区地图 → 综合文创 →** 后台一级类（`first_cate_list`，不含「地理产区」）  
- **综合文创**与**产区地图**平级，不在地图组件里放「文创/全国」大按钮；地图模式用文案链「查看全国产区」重置  
- 地图配色：绿色同色系深浅（`mall-china-map`），非彩虹分区  

## 社区闲置（纯前端）

- 路由与字段见 [secondhand-frontend.md](secondhand-frontend.md)  
- **分包路径必须扁平**：`pages/points/secondhand-list.vue` 等，**不要** `pages/points/secondhand/list.vue`（微信编译易报缺 wxml）  
- **我的发布空状态**：父级 `flex-direction: column`，勿 `<text>` 与 `<view>` 横排——见 `mp-conventions` → [text-view-layout.md](../mp-conventions/text-view-layout.md)

## 接口（商城列表）

`get_mall_tab_home` 支持 `first_cate`、`second_cate`、`keyword`、`page`。  
**改筛选逻辑时 PC 端需部署** `GoodsController` + `TabHomeService`（`first_cate_list` 与 `first_cate` 筛选为 2026-03 增加）。

## 与其它业务区分

| 名称 | 入口 | 说明 |
|------|------|------|
| 积分权益 Tab 官方商城 | `pages/mall/mall` | 商家 `goods`、购物车、排行 |
| 社区闲置（演示） | `secondhand-list` | 本地 storage，积分标价，无真扣款 |
| 七贤岭绿色二手集市 | `activity-spot-hub?spot=flea_market` | **活动分会场**，不是 Tab 内闲置 |

## 常用改色

- 主绿：`#076d3d` / `#18af38`  
- 选中 pill 底：`#e3f3ea`  
- 页面底：`#e4e8e5`（`glass-page-bg`）  
