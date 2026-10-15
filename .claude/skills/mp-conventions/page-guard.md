---
name: "mp-page-guard"
description: "小程序页面拦截/守卫正确实现指南。记录 uni-app 全局 Vue.mixin 对页面生命周期钩子(onShow/onLoad/onHide)合并不可靠的陷阱，以及在各页面直接调用共享工具函数的正确模式。当需要在小程序实现全局拦截（登录态检查、信息完善强制跳转、权限校验、未读消息引导等）或用户反馈'快速切 tab 绕过检查/拦截不生效'时调用。"
---

# 小程序页面拦截/守卫正确实现指南

## 核心陷阱：全局 Vue.mixin 对页面生命周期钩子不可靠

在 uni-app（小程序端）中，**不要**用 `Vue.mixin({ onShow() {...} })` 实现全局页面拦截。

### 陷阱表现

```js
// ❌ 错误：全局 mixin 的 onShow 对页面生命周期钩子合并不可靠
// main.js
Vue.mixin({
  onShow() {
    if (needRedirect) uni.navigateTo({ url: '/pages/xxx' })
  }
})
```

实测在微信小程序中：
- 页面自身的 `onShow` 会触发
- 但全局 mixin 注入的 `onShow` 经常**不触发**或时序错乱
- 导致"全局拦截"形同虚设，用户切 tab 可绕过

### 根本原因

uni-app 的页面生命周期钩子（`onLoad`/`onShow`/`onHide`/`onReady`/`onUnload` 等）是**小程序原生页面钩子**，由小程序运行时直接派发到页面实例。Vue 标准的 mixin 钩子合并机制只对 Vue 组件钩子（`mounted`/`created` 等）可靠，对 uni-app 页面钩子的合并行为不稳定。

## 正确模式：共享工具函数 + 各页面直接调用

### 1. 抽出工具函数

```js
// 小程序/common/xxxCheck.mixin.js（文件名带 mixin 后缀沿用项目习惯，实为工具函数）
let isRedirecting = false

export function checkXxx(vm) {
  if (isRedirecting) return false
  // 判断条件，用传入的 vm 访问 vuex 的 state（isLogin/userInfo 等）
  if (!vm.isLogin || !vm.userInfo || vm.userInfo.字段) return false
  isRedirecting = true
  uni.navigateTo({
    url: '/pages/xxx',
    complete: () => { isRedirecting = false }
  })
  return true
}
```

要点：
- 传入 `vm`（页面 this），通过 `$u.mixin.js` 全局混入的 `mapState` 访问 vuex（`this.isLogin`/`this.userInfo`）
- `isRedirecting` 模块级标志防止 `navigateTo` 期间重复触发（`complete` 里复位）
- 返回布尔值，调用方据此决定是否 `return` 中断后续逻辑

### 2. 在每个需要拦截的页面 onShow 首行调用

```js
// 小程序/pages/xxx/xxx.vue
import { checkXxx } from '@/common/xxxCheck.mixin.js'

export default {
  onShow() {
    if (checkXxx(this)) return   // 必须放最前面，命中则中断后续逻辑
    // ...页面自身原有 onShow 逻辑
  }
}
```

要点：
- `if (checkXxx(this)) return` 必须放在 `onShow` **第一行**
- 已有 `onShow` 的页面，把检查插到最前；没有 `onShow` 的页面，新增一个
- 所有 tabbar 页面都要加，缺一个就有一个 tab 能绕过

## 典型案例：强制完善信息检查（tabbar 绕过修复）

### 业务背景
新用户注册后未设置小区（`community_id` 为 0/空），需强制跳转到 `pages/user/info` 完善信息后才能使用。检查原本只在首页 `onShow` 里做，用 `this.$nextTick` 延迟跳转（为避免 z-paging 同步跳转白屏）。

### 漏洞
首页 `nextTick` 延迟期间，用户快速点击 tabbar 切到"活动/健康/积分/我的"，绕过检查。

### 修复方案
1. 抽出 `小程序/common/communityCheck.mixin.js`，导出 `checkCommunity(vm)`
2. 在 4 个非首页 tabbar 页面 `onShow` 首行调用 `if (checkCommunity(this)) return`：
   - `pages/low_carbon/low_carbon.vue`（已有 onShow，插最前）
   - `pages/health/health.vue`（新增 onShow）
   - `pages/mall/mall.vue`（新增 onShow）
   - `pages/user/index.vue`（已有 onShow，插最前）
3. 首页 `pages/index/index.vue` **保留** `this.$nextTick` 跳转逻辑（避免 z-paging 同步跳转白屏），不加 `checkCommunity`

### 为什么首页不一起用同步检查？
首页用了 `z-paging` 组件，在页面渲染过程中同步 `navigateTo` 会中断 z-paging 首次渲染导致白屏。首页只能用 `nextTick` 延迟跳转。其他 tabbar 页面没有这个渲染约束，用同步检查即可堵住绕过。

## 实施清单

当需要新增/修复一个全局拦截时：

1. **判断是否页面渲染约束**：目标页面是否用了 `z-paging` / 复杂首屏组件？是 → 该页用 `nextTick` 延迟跳转；否 → 同步跳转
2. **抽工具函数**：在 `小程序/common/` 下建 `xxxCheck.mixin.js`，导出 `checkXxx(vm)`
3. **逐页加检查**：在所有 tabbar 页面 `onShow` 首行加 `if (checkXxx(this)) return`
4. **排除自身**：拦截目标页本身（如完善信息页）和登录页要排除，否则死循环
5. **不要用 `Vue.mixin({ onShow })`**：不可靠

## 适用场景

- 登录态失效强制跳登录页
- 信息未完善强制跳完善信息页（本次案例）
- 权限不足引导（如非监督员不能进督导页）
- 未读协议/隐私政策强制确认
- 任何需要在"任意 tab 页面 onShow"统一拦截跳转的场景
