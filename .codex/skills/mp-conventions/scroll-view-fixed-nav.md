# 自定义顶栏 + scroll-view 双层滚动（导航「脱扣」）

## 现象

- 页面有**自定义导航栏**（`navigationStyle: custom`）+ 内层 **`scroll-view scroll-y`**。
- 滚到**最底部**还能再往下拖一点；**顶栏跟着滑出视口**。
- 再滚回**最顶部**，顶栏**不能立刻回来**，要多滑一下才复位——像导航和内容**脱扣**了。

典型踩坑页：`pages/public/shukang-about`（关于数康）。

## 根因（常叠加出现）

1. **外层页面也在滚**：未设 `disableScroll`，或 `page` / 根节点高度大于一屏。
2. **`glass-page` 壳子**：`min-height: 100vh` + `padding-bottom: $glass-tabbar-space`（Tab 留白）。**无 TabBar 的二级页**仍被撑高，整页可滚，与 `scroll-view` 形成**双滚动**。
3. **`scroll-view` 高度用 JS 扣减**（`windowHeight - statusBar - 44`）与真实 flex 布局不一致，底部留白或溢出，橡皮筋滚到**外层**。

## 标准写法（仅 scroll-view 滚，顶栏固定）

### 1. `pages.json` 本页

```json
"disableScroll": true
```

与 `navigationStyle: custom` 同页配置。

### 2. 结构：flex 列，不要 `glass-page` 当整页根（无 Tab 的独立页）

```vue
<view class="page-shell">
  <view class="page-nav" :style="{ paddingTop: statusBarPx + 'px' }">...</view>
  <scroll-view scroll-y class="page-scroll" :show-scrollbar="false">
    <!-- 正文 -->
  </scroll-view>
</view>
```

```scss
.page-shell {
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #e4e8e5; /* 或 $glass-page-bg，勿叠 glass-page 的 tabbar padding */
}
.page-nav {
  flex-shrink: 0;
  z-index: 20;
}
.page-scroll {
  flex: 1;
  height: 0; /* 必须：flex 子项 scroll-view 占满剩余高度 */
  width: 100%;
}
```

### 3. 锁定 page（**非 scoped** 样式块）

scoped 里写 `page { }` 在 uni-app 常不生效：

```scss
/* 单独 <style lang="scss">，不要 scoped */
page {
  height: 100%;
  overflow: hidden;
}
```

### 4. 环境光 / 背景

需要 `glass-page__ambient` 效果时，在 `page-shell` 内用 **`position: absolute`** 的装饰层，**不要**给无 Tab 页套完整 `glass-page`（除非去掉 tabbar 留白或改用 Tab 页布局）。

## 禁止 / 慎用

| 做法 | 问题 |
|------|------|
| 根节点 `class="glass-page"` + 内层 `scroll-view`（非 Tab 页） | 外层 min-height + tabbar padding → 双滚动 |
| 仅 JS 算 `scroll-view` 高度、不用 flex | 与安全区、导航高度不同步 |
| 顶栏放在 `scroll-view` 内部 | 顶栏随内容滚走 |
| 期望 `enhanced` / `bounces` 解决脱扣 | 不能替代 disableScroll + 单滚动容器 |

## 与 Tab 页的区别

Tab 页（如 `pages/user/index`）整页滚动、顶栏在文档流里，**可以**用 `glass-page` + tabbar 留白。

**分包二级页、custom 导航 + 长列表/长文案**：优先 **disableScroll + flex + scroll-view**，不要照搬 Tab 页的 `glass-page` 壳。

## 自检清单

- [ ] 本页 `pages.json` 已 `disableScroll: true`
- [ ] 根容器 `height: 100vh; overflow: hidden`，无多余 `min-height: 100vh` + tabbar padding
- [ ] `scroll-view` 为 `flex: 1; height: 0`
- [ ] 顶栏在 `scroll-view` **外**，`flex-shrink: 0`
- [ ] `page { overflow: hidden }` 写在**非 scoped** 样式里

## 参考实现

- 修复后：`小程序/pages/public/shukang-about.vue`
- 配置：`小程序/pages.json` → `pages/public/shukang-about` → `disableScroll`
