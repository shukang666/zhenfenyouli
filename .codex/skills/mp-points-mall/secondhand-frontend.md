# 社区闲置 · 积分交换（已接后端）

## 产品定位

居民发布**闲置物品**，标价**积分**，在**绿色二手市集活动现场**统一交接（非线上自行交割）；与官方「积分商城」商家商品并列入口。成交与扣款**尚未接后端**。规则与互跳见 **mp-secondhand-market**；积分 Tab Banner 进 `secondhand-intro`。

## 页面路由（分包 `pages/points`）

| 路径 | 作用 |
|------|------|
| `secondhand-intro` | 规则说明 + 进市集场次 / 列表 / 发布 |
| `secondhand-list` | 集市列表 + 分类筛选 + 发布入口 |
| `secondhand-publish` | 发布/编辑（表单 + 本地选图） |
| `secondhand-detail` | 详情 + 「我想要」演示弹窗 |
| `secondhand-my` | 我的发布（含已下架演示态） |

页面文件与 `points.vue` 同级扁平命名（`pages/points/secondhand-list.vue`），避免分包内嵌目录在微信端编译不到 wxml。

## 字段（前端模型）

| 字段 | 说明 |
|------|------|
| id | 本地 uuid 或时间戳 |
| title | 标题，必填 |
| description | 描述 |
| images | 本地临时路径或占位图 URL 数组，最多 6 张 |
| point_price | 积分价格，正整数 |
| category | books / home / baby / digital / other |
| condition | new / like_new / used |
| area_text | 交接范围，如「七贤岭 XX 小区」 |
| seller_nickname / seller_avatar | 发布时从 `userInfo` 快照 |
| status | on_sale / reserved / sold |
| create_time | 时间戳 |

## 存储与接口

- 表 `cmf_secondhand_item` / `cmf_secondhand_intent`（见 `update/update.sql`）  
- 图片：`upload_asset` 存相对路径，展示 `cmf_get_asset_url`  
- 发布 → 待审；后台通过后列表可见  
- 「我想要」→ 意向单；积分在市集现场后台「交割完成」时划转（类型 229/230）  
- 互跳与规则页见 **mp-secondhand-market**  

## UI 约定

- 延续商城绿色扁平：`#076d3d` / `#e3f3ea` / 白卡片 / `24rpx` 间距  
- 列表双列卡片，首图 aspectFill，角标显示积分  
- 发布页底部主按钮「发布到社区集市」  
- **`secondhand-my` 空状态**：`sh-my__empty` 用 `flex-direction: column`；`<text>` 是 inline，不能指望与下方按钮自动换行（见 `mp-conventions/text-view-layout.md`）  

## pages.json 注册示例

分包 `root: pages/points` 内 path 为 **`secondhand-list`**（不是 `secondhand/list`），对应文件 `pages/points/secondhand-list.vue`。
