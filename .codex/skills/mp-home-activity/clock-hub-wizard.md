---
name: mp-clock-hub-wizard
description: "垃圾分类打卡入口页 `pages/flhd/ljfldk` 的两步引导（选场景→选种类→拍照）与 `pages/flhd/add` 上报类型预选。场景仅前端展示不入库；种类来自 `get_select_list({type:2})` / `cmf_basic_select`；跳转带 `cate_id`+`scene`；过往记录拆到 record/choice。改打卡前引导、预选分类、列表不刷新、卡片配色对比度时调用。学考门禁见 learn-exam-clock-gate.md。"
---

# 垃圾分类打卡入口（场景引导 + 拍照）

## 页面分工（不要混回一张列表页）

| 页面 | 职责 |
|------|------|
| `pages/flhd/ljfldk` | 打卡**入口**：两步引导 + 积分/连续天数 + 学考门禁条 + 过往记录入口 |
| `pages/flhd/add` | **拍照提交**：上报类型、描述、图片 → `add_forum`（`type: 2`） |
| `pages/flhd/record` | **我的打卡**列表：`get_forum_list({ is_my: 1, type: 2 })` |
| `pages/flhd/choice` | **精选打卡**列表：`get_choice_list` |

`ljfldk` **不要**再嵌 `get_forum_list` 列表，也不要在底部再放第二个「去拍照」大按钮。

## 两步引导（`ljfldk.vue`）

**Step 1 场景**（写死在 `data.scenes`，不入库、不提交接口）：

| key | 名称 | 说明文案 |
|-----|------|----------|
| `home` | 家庭 | 厨房、客厅、卫生间里的日常投放 |
| `office` | 办公 | 工位、茶水间、会议室里的投放 |
| `street` | 沿街 | 路边、商铺门前的分类投放点 |

**Step 2 种类**：`onShow` 调 `get_select_list({ type: 2 })`。当前生产库 `cmf_basic_select`（`type=2`，`pid=0`）四条：

| id | name |
|----|------|
| 284 | 厨余垃圾 |
| 286 | 其他垃圾 |
| 283 | 可回收物 |
| 285 | 有害垃圾 |

种类名称用 `cateTone(name)` 映射样式类（`可回收`→`recycle`、`厨余`→`food`、`有害`→`harm`，其余→`other`）；`cateHint` 仅前端说明，可随运营改文案。

**跳转拍照**（登录 + 有 `community_id` 后才跳）：

```js
this.$u.route('/pages/flhd/add?cate_id=' + item.id + '&scene=' + encodeURIComponent(scene))
```

`scene` 只是拍照页展示「本次场景：家庭」；**不会**写入 `add_forum` 参数。若以后要落库，需单独加字段，不能假设已有。

## 拍照页预选上报类型（`add.vue`）

`onLoad(option)` 读 `cate_id`、`scene`（`decodeURIComponent`）。`get_select_list` 返回后走 `applyPendingCate()`，按 `cate_id` 找下标再 `pickCate(index)`——**禁止**列表加载完永远 `cate_list[0]`。

上报类型标签点击必须走 `pickCate(index)`，禁止模板内联 `@click="form.cate_id = item.id,cate_index = index"`（见 `mp-conventions` render 崩溃）。

提交成功后的列表刷新：

```js
const prev = that.$u.func.prePage()
if (prev && typeof prev.get_forum_list === 'function') {
    prev.forum_list = []
    prev.page = 1
    prev.get_forum_list()
}
```

从 `ljfldk` 进来时 `prePage` 没有 `get_forum_list`，**必须先判断**，否则提交成功会报错。

## 学考门禁（本页只做展示，不做主拦截）

`gateTip`：`!is_learn` → 去学 `flhd/list`；`!is_answer` → 去 `answer/examine?id=1`。首页/活动页的强制拦截逻辑见 [learn-exam-clock-gate.md](learn-exam-clock-gate.md)。

## 样式与对比度

场景卡、种类卡都是**白字 + 深色渐变底**（`141deg`）。浅色渐变（如 `#F4E48A→#E7B93C`、`#A0D8F0→#4BAED0`）在真机上白字几乎看不清，已改为更深一档：

| 用途 | 渐变（起→止） |
|------|----------------|
| 家庭场景 | `#D4A017` → `#A36B08` |
| 办公场景 | `#2B8BB8` → `#156A90` |
| 沿街场景 | `#18AF38` → `#0C7A24` |
| 可回收 | `#2B8BB8` → `#156A90` |
| 厨余 | `#C48A3A` → `#8B5A14` |
| 有害 | `#E75E5E` → `#B32626` |
| 其他 | `#8A959C` → `#5C656C` |

装饰圆 `::before` 用 `rgba(255,255,255,0.12)`，并加 `pointer-events: none`。通用卡片套路见 `mp-conventions` → [card-beautify.md](../mp-conventions/card-beautify.md)。

## 常见问题

| 现象 | 排查 |
|------|------|
| 拍照页上报类型总是第一项 | `add.vue` 是否在 `get_select_list` 回调里调了 `applyPendingCate`；URL 是否带了 `cate_id` |
| 提交成功控制台报错 | `prePage().get_forum_list` 未做 `typeof === 'function'` |
| 我的打卡列表空白 | 是否误在 `ljfldk` 调 `get_forum_list` 或用 tab 文案分支接口；应跳 `record` 且 `is_my: 1` |
| 场景选了但后台看不到 | 设计如此，场景仅前端引导；要统计需新字段 |
| 后台改了种类名/顺序 | 接口 `get_select_list type=2` 自动同步；`cateTone` 靠名称关键字，改名要同步改映射 |

## 相关

- [learn-exam-clock-gate.md](learn-exam-clock-gate.md) — 学→考→打卡准入
- `api/wxapp/controller/SelectController.php` — `get_select_list`
- `api/wxapp/controller/ForumController.php` — `add_forum`（`type=2` 要 `cate_id`、图片）
