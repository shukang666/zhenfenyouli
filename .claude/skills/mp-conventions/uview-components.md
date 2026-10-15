---
name: mp-uview-components
description: "小程序 uView 组件行为速查与弹窗交互问题排查。记录本项目 uview-ui 组件源码位置（小程序/uview-ui/components/<name>/<name>.vue）及关键行为：u-mask 用 show 控制且点击会 emit click、关闭时 z-index=-1 仍在 DOM；u-popup 关闭态用 v-if 移除整个 DOM 不遮挡；u-button 用 @tap.stop 转发且内部 throttle 节流；u-swiper 的 border-radius/height 单位是 rpx。当需要修改 uView 组件用法，或遇到『弹窗关不掉』『弹窗按钮点不动/无反应』『弹窗被遮挡』『组件圆角/高度设置无效』等问题时调用。注意区分：若控制台报 Page/Component "..." does not have a method "eN"（e0/e1 这类无名序号），属于内联事件 handler 未注册，见 skill mp-render-crash-inline-event。"
---

# 小程序 uView 组件行为速查 & 弹窗交互排查

## 0. 组件源码位置

本项目 uView 为**源码内置**（非 npm 包），所有组件可直接阅读：

`小程序/uview-ui/components/<组件名>/<组件名>.vue`

遇到组件行为异常时，**先读组件源码确认行为**，不要凭经验猜。

## 1. 常用组件关键行为

| 组件 | 路径 | 关键行为（易踩坑点） |
|---|---|---|
| `u-mask` | `u-mask/u-mask.vue` | `show`(Boolean) 控制显隐；`show=false` 时 `z-index:-1` + `opacity:0`，**DOM 仍然存在**（不是 v-if）；内部 `@tap="click"` → `$emit('click')`，`mask-click-able` 默认 `true`，为 false 时点击不派发事件 |
| `u-popup` | `u-popup/u-popup.vue` | 根节点 `v-if="visibleSync"`：**关闭动画结束后整块 DOM 被移除**，所以关闭态不会遮挡其它元素；`mask-close-able` 控制点遮罩是否关闭；内部内容区自带 `@tap.stop.prevent` |
| `u-button` | `u-button/u-button.vue` | 内部是 `@tap.stop="click($event)"` → `$u.throttle(...)` 节流 → `$emit('click')`。**注意节流会吞掉短时间内的重复点击**；`:disabled`/`:loading` 时不触发 click |
| `u-swiper` | `u-swiper/u-swiper.vue` | `border-radius` **单位是 rpx**（默认 8，模板拼 `borderRadius: ${borderRadius}rpx`）；`height` 单位也是 rpx（默认 250）。**不要传带单位的字符串**（如 `"20rpx"` 会变成 `20rpxrpx`） |

> 结论：圆角/高度这类数字 prop 一律传**纯数字**，单位由组件内部补 rpx。

## 2. 弹窗「关不掉 / 按钮点不动」排查流程

按顺序排查，不要一上来就改代码：

0. **先看控制台报错**：若报 `does not have a method "e0/e1/e2"`（无名序号方法），**不是组件链路问题**，而是页面首次 render 抛错导致内联事件 handler 未注册 → 走 skill `mp-render-crash-inline-event`。下面几步只适用于控制台**没有**此类报错的情况。
1. **看状态绑定**：确认弹窗显隐变量（如 `tipShow`）是否被正确置 false；按钮/遮罩绑定的方法确实改了该变量。
2. **看是否被重新拉起**：搜索该变量**所有赋值处**（如签到成功后 500ms/1500ms 刷新详情的回调），排除"刚关掉又被置回 true"。
3. **看事件链路**：若按钮用 `u-button`，链路为 `@tap.stop → throttle → $emit('click')`；节流窗口或禁用态会导致点击被吞。改用原生 `view` 可绕开。
4. **看 DOM 覆盖**：关闭态组件是否仍渲染 DOM 并占高层级（`u-mask` 会；`u-popup` 不会）。检查是否有元素盖住按钮。
5. **看点击穿透**：弹窗内容区是否缺 `@tap.stop`，导致点卡片时既触发遮罩关闭又冒泡到其它元素。

## 3. 修复模板（双保险）

弹窗关闭/点击不稳时，用"遮罩可点关 + 按钮不依赖组件事件封装"的双保险写法：

```vue
<!-- 遮罩本身可点关闭（u-mask 点击会 emit click） -->
<u-mask :show="tipShow" @click="closeTip">
    <!-- 内容区 @tap.stop.prevent：阻止冒泡，避免点卡片误关 -->
    <view class="success-modal" @tap.stop.prevent>
        <view class="success-title">...</view>
        <!-- 用原生 view 而不是 u-button，绕开组件事件转发/节流 -->
        <view class="success-btn" @tap.stop="closeTip">我知道了</view>
    </view>
</u-mask>
```

要点：
- 弹窗卡片必须 `@tap.stop.prevent`，否则点卡片会冒泡到遮罩触发关闭。
- 关闭动作**一律写成方法引用**（`@click="closeTip"` 配 `methods: { closeTip() { this.tipShow = false } }`），**不要写内联赋值 `@click="tipShow = false"`** —— 内联赋值会被编译成匿名 handler `e0/e1`，且只在首次 render 成功时注册，页面一旦 render 抛错就永久失效（见 `mp-render-crash-inline-event`）。
- 关闭按钮优先用原生 `view`；若用 `u-button`，确保没有 `:disabled`/`:loading` 且不在节流窗口内。
- 遮罩也绑同一个关闭方法作为兜底（按钮一旦不可用仍能关掉弹窗）。

## 4. 不要做的事

- ❌ 给 `u-swiper` 传 `border-radius="20rpx"` → 单位重复。
- ❌ 认为"关闭的 `u-mask` 不占 DOM" → 它仍在 DOM 里（z-index:-1），排查遮挡时别忽略。
- ❌ 弹窗卡片不加 `@tap.stop` → 点内容即关闭。
- ❌ 只给按钮绑事件而没给遮罩兜底 → 组件链路一旦失效就没法关。
- ❌ 用内联赋值 `@click="tipShow = false"` 当"保险写法" → 它依赖首次 render 成功，正是 `does not have a method "eN"` 的直接来源，统一用方法引用。

## 相关技能

- `mp-active-detail-popup`：活动详情页三个弹窗的具体状态流转（本 skill 的实例页面）。
- `mp-render-crash-inline-event`：内联事件 handler 未注册（`does not have a method "eN"`）的根因与修复 —— 报 `eN` 时走这里，不要按组件链路排查。
- `mp-page-element-migrate`：跨页面迁移含 `@click.stop` 的元素时的事件绑定注意点。
