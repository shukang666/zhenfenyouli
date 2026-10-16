---
name: mp-render-crash-inline-event
description: "小程序「内联事件整体失效 / render 崩溃」陷阱排查。记录控制台报 `Page \"xxx\" does not have a method \"e0/e1/e2\"`、多个按钮/遮罩同时点了没反应、弹窗关不掉的真正根因：uni-app 把内联事件（@click=\"a = false\"）编译成挂在 vm 上的匿名 handler（_vm.e0/e1），且只在首次 render 的 `if (!_vm._isMounted)` 里注册；一旦首次 render 抛错（典型：模板对初值为 null 的根变量点号访问，如 `v-for=\"x in actionTrigger.forms\"` 而 `actionTrigger: null`），handler 永远不会注册，运行时 handleEvent 取不到方法即抛错。含编译产物核对方法、__map 实参求值坑、computed 兜底 + 方法引用两层修复模式。当遇到『点了没反应』『does not have a method e1』『内联事件失效』『弹窗按钮点不动』且与组件事件链路无关时调用。"
---

# 小程序「内联事件整体失效」与 render 崩溃陷阱

> 一句话：**页面首次 render 一旦抛错，该页所有内联事件（`@click="x = y"`）会永久失效**，表现为点按钮报 `does not have a method "e1"`，而页面数据看起来完全正常。

## 1. 现象

- 控制台报：`Error: Page "pages/low_carbon/lsxf/details-new" does not have a method "e1"`（组件内报 `Component "..."`）。
- 报错栈特征：`at ci.handleEvent [as __e] (...)`。
- 关键辨识点：方法名是 **`e0` / `e1` / `e2` 这类无名序号**，源码里根本搜不到；且同一页面内**所有**内联事件一起失效（多个按钮同时点不动）。
- 迷惑点：页面数据、弹窗显隐全部正常，只是"一点就报错 / 点了没反应"。

## 2. 机制（uni-app mp 编译产物）

模板里的内联事件会被编译成挂在 vm 上的**匿名 handler**：

```js
// 小程序/unpackage/dist/dev/mp-weixin/pages/<页面路径>.js
var l1 = _vm.__map(_vm.actionTrigger.forms, function (field, fIdx) { ... });  // ① v-for 编译出的 __map
...
if (!_vm._isMounted) {            // ② 只在「首次 render」注册内联事件 handler
  _vm.e0 = function ($event) { _vm.tipShow = false };
  _vm.e1 = function ($event) { $event.stopPropagation(); _vm.tipShow = false };
}
```

点击时按名字从 vm 上取：

```js
// 小程序/unpackage/dist/dev/mp-weixin/common/vendor.js （handleEvent）
var handlerCtx = this.$vm;
if (handlerCtx.$options.generic) handlerCtx = getContextVm(handlerCtx) || handlerCtx;
var handler = handlerCtx[methodName];        // methodName = 'e1'
if (!isFn(handler)) {
  var _type = this.$vm.mpType === 'page' ? 'Page' : 'Component';
  throw new Error(`${_type} "${path}" does not have a method "${methodName}"`);
}
```

关键链条：

1. `①` 位于 `②` **之前**。① 一旦抛异常 → render 中断 → `e0/e1` 永远不注册；
2. render 抛错后 `_isMounted` 照样变 `true`，**后续 render 也不会补注册** → 永久失效；
3. 点击时 `handlerCtx['e1']` 为 `undefined` → 抛 `does not have a method "e1"`。

## 3. 高危写法（触发条件）

**模板里对「初值为 `null` / `undefined` 的根变量」直接点号访问**，最典型的是 `v-for`：

```vue
<!-- data: { actionTrigger: null }   ← 初值为 null -->
<view v-for="(field, fIdx) in actionTrigger.forms" :key="fIdx">
```

编译成 `_vm.__map(_vm.actionTrigger.forms, ...)`，**实参求值 `null.forms` 当场抛 TypeError**。

⚠️ 注意：`__map` **本身对 null 是容错的**（`common/vendor.js` 实现：`Array.isArray` / `isObject` / `typeof === 'number'` 三分支，否则 `return []`），但救不了**实参求值阶段**的 `null.forms`。所以 `__map(null)` 不报，`__map(null.forms)` 必报。

同类写法都会触发（凡是在 render 期求值的都算）：

- `v-for="... in X.Y"`、`v-if="X.Y"`、`v-show="X.Y"`、`{{X.Y}}`、`:prop="X.Y"`；
- 根级 data 字段初值写成 `X: null`（`X: undefined` 同理）。

✅ 对照：`X: {}` / `X: []`（空对象/空数组）**安全** —— `X.Y` 只得到 `undefined`，`__map(undefined)` 返回 `[]`。

## 4. 为什么这个 bug 一直隐身

mp 端 `data` 字段走 setter **直接 setData 到视图**，**不依赖 render 成功**。所以：

- 页面内容照常渲染、弹窗照常弹出、详情接口数据照常更新；
- 只有 render 里的"编译期变量"（`$root.l1` 等）和内联 handler 受影响；
- 表现出来就是「页面看着好好的，某个按钮点了没反应」。

## 5. 排查步骤

1. **看报错名字**：`e0/e1/e2` 这类序号 = 内联事件失效，优先怀疑 render 崩溃，不要去源码里找 `e1`。
2. **源码定位 null 点号访问**：列出该页模板里所有 `X.y`（重点 `v-for="... in X.y"`），逐个对照 `data` 里 `X` 的初值，找 `null` / `undefined`。
3. **编译产物核对**（可选，但最直接）：路径 `小程序/unpackage/dist/dev/mp-weixin/pages/<页面路径>.js`，搜 `_vm.e1 = function`，看它是否在 `if (!_vm._isMounted)` 内、前面是否有 `_vm.__map(_vm.*.*)`。

   ```powershell
   # 注意：Get-Content 不带 -Encoding 会被命令安全策略拦截，改用 Select-String / [regex]
   Select-String -Path .\unpackage\dist\dev\mp-weixin\pages\xxx\yyy.js -Pattern '_vm\.e1 = function' -Context 0,5
   [regex]::Matches((Get-Content .\unpackage\dist\dev\mp-weixin\pages\xxx\yyy.js -Raw), '.{0,90}\be[01]\b.{0,90}') | % { $_.Value }
   ```
4. **全库体检**（修完顺手做）：搜 `v-for="\([^)]*\) in [a-zA-Z_]\w*\.`，核对每个根变量的初值。
   - 本项目实测（2026-09）：模板侧根变量只有 `pages/low_carbon/lsxf/details-new.vue` 的 `actionTrigger: null` 是 null 初值，其余页面根变量都是 `{}`；
   - 但**内联赋值事件有 62 处、分布在约 60 个页面** —— 这些页面一旦 render 抛错，按钮会集体失灵。

## 6. 修复模板（两层，都要做）

**第一层：根治 render 抛错** —— 点号访问换成 computed 兜底：

```js
computed: {
    // 初值为 null 的根变量，模板一律走这个数组
    actionFormFields() {
        return (this.actionTrigger && this.actionTrigger.forms) || [];
    }
}
```
```vue
<view class="form-field" v-for="(field, fIdx) in actionFormFields" :key="fIdx">
```

**第二层：加固内联事件** —— 改成方法引用，handler 从 `$options.methods` 取，不再依赖首次 render 是否成功：

```vue
<u-mask :show="tipShow" @click="closeTip">
    <view class="success-modal" @tap.stop.prevent>
        <view class="success-btn" @tap.stop="closeTip">我知道了</view>
    </view>
</u-mask>
```
```js
methods: {
    closeTip() {
        this.tipShow = false;
    }
}
```

两层都做才稳：第一层消除崩溃源头，第二层保证以后再有别的 render 异常也不会"整页按钮失灵"。

## 7. 不要做的事

- ❌ 看到 `does not have a method "e1"` 就去源码找 `e1` 方法 —— 它是编译产物内部命名，源码里不存在。
- ❌ 误判成 uView 组件事件转发 / 节流 / 遮挡问题（那是另一类，见 `mp-uview-components`）—— 先确认控制台报的是不是 `eN`。
- ❌ data 字段初值写 `null` 又在模板里点号访问；初值统一给 `{}` / `[]`。
- ❌ 只把内联事件改成方法引用就收工 —— **首次 render 的异常还在**，依赖 render 的展示（`$root.xxx`）仍然不更新。
- ❌ 把 `@click="tipShow = false"` 当作"保险写法"照抄到新页面 —— 它正是本陷阱的高危写法。

## 相关技能

- `mp-uview-components`：uView 组件自身行为与「弹窗关不掉」的组件链路排查（与本文是两条不同链路）。
- `mp-active-detail-popup`：本陷阱在活动详情页的实例（`tipShow` 弹窗 + `actionTrigger`）。
- `mp-page-element-migrate`：跨页面搬运模板时，容易把「内联事件 + null 初值」一并搬过去。
- `dev-environment`：小程序源码目录受限时，改用编译产物 `unpackage` 排查的思路。
