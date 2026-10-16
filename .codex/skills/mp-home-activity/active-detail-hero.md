---
name: mp-active-detail-hero
description: "活动详情页（小程序/pages/low_carbon/lsxf/details-new.vue）hero 快速统计区的字段绑定，以及后端详情接口 api/wxapp/controller/ActiveController.php::get_new_active_detail 的返回字段与参与人数计数口径。hero 只展示两项：积分奖励(info.send_point) 与 人已参与(info.active_count)，已移除『X 天挑战』(day_count)。含关键坑：打卡类活动在详情接口里会自动创建报名记录，active_count 必须在自动报名之后统计，否则少算本人且与列表接口口径不一致。当需要改活动详情页统计展示、排查『参与人数显示 0』『积分奖励不显示』『统计项多了/少了』或核对详情接口字段时调用。"
---

# 活动详情页 hero 快速统计区与详情接口字段

页面：`小程序/pages/low_carbon/lsxf/details-new.vue`
接口：`api/wxapp/controller/ActiveController.php::get_new_active_detail`（`/wxapp/active/get_new_active_detail`，参数 `active_id`）

## 1. hero 统计区：只保留两项

位置：模板顶部 `hero-section > hero-content > hero-stats`（封面标题下方）。

| 展示项 | 绑定字段 | 说明 |
|---|---|---|
| 积分奖励 | `info.send_point` | 活动配置的积分，未配置时显示 0 |
| 人已参与 | `participantsCount`（computed） | 见第 2 节，最终取 `info.active_count` |

结构（两项之间一个 `stat-divider`）：

```html
<view class="hero-stats">
  <view class="stat-item">
    <text class="stat-value">{{info.send_point || 0}}</text>
    <text class="stat-label">积分奖励</text>
  </view>
  <view class="stat-divider"></view>
  <view class="stat-item">
    <text class="stat-value">{{participantsCount}}</text>
    <text class="stat-label">人已参与</text>
  </view>
</view>
```

⚠️ 已移除的第三项「X 天挑战」（`{{info.day_count || 7}}` + `天挑战`）：

- `day_count` 详情接口**根本不返回**，前端写死默认 7，展示的是假数据；
- 打卡天数不属于公开统计。
- **不要再加回来**。若产品要新增统计项，先确认接口有对应字段再展示，禁止用假默认值撑 UI。

## 2. computed participantsCount

```js
// 参与人数 - 由后端 detail 接口返回真实报名人数
participantsCount() {
  return this.info.active_count || 0;
},
```

历史 bug：原先绑的是 `this.info.sign_count`（后端不存在该字段）→ 永远显示 0。

排查口诀：**页面某个数字恒为 0 → 先看接口有没有返回这个字段，再看前端绑的名字对不对。**

## 3. 详情接口关键返回字段（`get_new_active_detail`）

| 字段 | 含义 |
|---|---|
| `send_point` | 活动积分奖励 |
| `active_count` | 参与人数（报名记录数），见第 4 节 |
| `sign_id` / `is_sign` | 当前用户报名记录 id / 是否已报名 |
| `is_sign_clock` | 今天是否已打卡 |
| `is_clock` | 1=打卡类，0=报名类 |
| `join_type` | 1=小程序参加，2=扫码参加 |
| `role` | explode 成数组 |
| `thumb` / `badge_image` / `content` | 图片 URL 补全 / HTML 反转义 |

## 4. 关键坑：参与人数必须在「自动报名」之后统计

详情接口对**打卡类活动**（`is_clock == 1`）有副作用：当前用户没有报名记录时，会自动 `ActiveSIgnModel::create()` 补一条报名记录。

因此 `active_count` 的 `count()` **必须放在自动报名逻辑之后**（即 `sign_id`/`is_sign` 赋值之后）：

```php
$item['sign_id'] = empty($sign_info)?0:$sign_info['id'];
$item['is_sign'] = empty($sign_info)?0:1;

//参与人数（必须在自动报名之后，否则少算当前用户本人）
$item['active_count'] = ActiveSIgnModel::where('active_id', $item['id'])->count();
```

- 放在前面：打卡用户首次进详情时自己那条自动报名记录还没创建 → **少算 1 人**。
- 与列表接口 `get_new_active_list` 保持同一口径（列表里同样是报名记录数 + `avatar_array` 前 5 头像）。
- 模型类名坑：报名模型是 **`ActiveSIgnModel`**（`SIgn`，大写 I、小写 gn），不是 `ActiveSignModel`，照抄勿"纠正"。

## 5. 排错速查

| 现象 | 原因 |
|---|---|
| 详情页参与人数恒为 0 | 接口没返回 `active_count`，或前端绑了不存在的 `sign_count` |
| 参与人数比实际少 1（打卡活动） | `active_count` 被放在自动报名之前 |
| 积分奖励不显示 / 显示 0 | 活动未配置 `send_point`，或前端绑错字段 |
| 又多出「X 天挑战」一项 | 被加回来了；`day_count` 无接口数据，应删除 |
| 详情与列表参与人数不一致 | 两处口径不同（详情漏了自动报名后的重算） |

## 相关技能

- `active-scan-join`：扫码参加活动在详情页的差异化行为。
- `mp-active-detail-popup`：详情页弹窗与打卡/报名状态流转。
- `mp-image-url-complete`：图片 URL 补全规则。
- `business-data-model`：活动 / 报名 / 打卡表结构。
