---
name: mp-wxml-data-event-opts
description: "微信小程序编译报错 Bad attr data-event-opts / unexpected token。典型由 v-for 的 :key 字符串拼接（如 'fold-' + id）、空 @tap.stop=\"\"、v-for 内联传参触发 uni-app 生成非法 WXML。改 key、用 methods 索引传参、stopTapBubble 占位。排查 scene-clock-flow 等复杂页 WXML 编译失败时调用。"
---

# WXML `data-event-opts` 编译错误（unexpected token）

> 一句话：**不是微信语法写错，而是 uni-app 把 Vue 模板编译成 WXML 时，`data-event-opts` 里的 JSON 被 `:key` 拼接或空事件绑写坏了**；开发者工具报 `Bad attr data-event-opts`，常带 `unexpected token fold` 等片段。

## 1. 现象

- 微信开发者工具 / 编译日志：
  - `[WXML 文件编译错误] ./pages/xxx/yyy.wxml`
  - `Bad attr data-event-opts with message: unexpected token xxx`
- 编译产物里可见异常片段，例如：
  - `:key="'fold-' + p.id"` → `deletePointById` 的 opts 里出现 `'fold-'+p.$orig.id`、路径段 `'\'fold-\'+p.id'`
- **CSS 类名**含 `scf-settings-fold` **不会**触发此问题；出问题的是 **`:key` 表达式里的 `'fold-'` 字符串**（以及同类拼接 key）。

## 2. 高危写法（禁止 / 慎用）

### 2.1 `v-for` 的 `:key` 用「引号前缀 + 拼接」

```vue
<!-- 易炸：编译器把 fold- 写进 event 路径，WXML JSON 解析失败 -->
<view v-for="(p, mIdx) in points" :key="'fold-' + p.id">
  <text @tap.stop="deletePointById(p.id)">×</text>
</view>
```

**改法：**

```vue
<view v-for="(p, mIdx) in points" :key="p.id">
  <text @tap.stop="deletePointByIndex(mIdx)">×</text>
</view>
```

- `:key` 优先 **`id` / 纯数字 index**，避免 `'prefix-' + id`、`'item-' + idx` 等（尤其 prefix 含连字符时）。
- 若必须区分多列表同 id，用 **`'b' + p.id`** 单字母前缀，或 **`mIdx`**，不要 `'fold-'`、`'set-'` 这类在报错里会直接露出的 token。

### 2.2 空的 `@tap.stop` / `@click.stop`

```vue
<!-- 易炸：生成 data-event-opts="{{[['tap',[['',['$event']]]]]}}" -->
<view @tap.stop="">
```

**改法：**

```vue
<view @tap.stop="stopTapBubble">
```

```js
methods: {
  stopTapBubble() {}, // 仅阻止冒泡（如折叠面板内点击不关闭蒙层）
}
```

### 2.3 `v-for` 项上直接内联传 `p.id`（与复杂 key 叠加时更易炸）

优先 **传 index，在 methods 里取数组项**：

```js
deletePointByIndex(mIdx) {
  const p = (this.points || [])[mIdx]
  if (!p || p.id == null) return
  this.deletePointById(p.id)
}
```

（与 [render-crash-inline-event.md](render-crash-inline-event.md) 的「禁止复杂内联」同一思路，这里是 **编译期 WXML** 而非运行时 e0/e1。）

## 3. 排查步骤

1. 看报错 WXML 里 **第一个出现 `unexpected token` 的词**（如 `fold`）→ 在 `.vue` 里搜 **`'fold-'`**、**`:key="'`**、同类拼接。
2. 搜 **`@tap.stop=""`**、**`@click.stop=""`**。
3. 改完后 **清 `unpackage/dist` 再编译**（避免旧 wxml 缓存）。
4. 若仍失败，打开 `unpackage/dist/dev/mp-weixin/pages/.../xxx.wxml`，搜 **`data-event-opts`**，找未加引号或含 `+` 的非法段。

## 4. 项目内已踩坑位置（备忘）

- `小程序/pages/activity/scene-clock-flow.vue`：家庭选桶页「管理分类桶」折叠列表曾用 `:key="'fold-' + p.id"` + `@tap.stop=""`，已改为 `:key="p.id"`、`stopTapBubble`、`deleteResidentialPointByIndex(mIdx)`。

## 5. 与其它文档关系

| 报错 | 文档 |
|------|------|
| `does not have a method "e1"` | [render-crash-inline-event.md](render-crash-inline-event.md) |
| `Bad attr data-event-opts` | 本文 |
| uView 弹层点不了 | [uview-components.md](uview-components.md) |
