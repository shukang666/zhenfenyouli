---
name: mp-conventions
description: "小程序（uni-app + uView）通用工程约定。覆盖 uView 组件行为、内联事件导致的 e0/e1 render 崩溃、WXML data-event-opts 编译错误（v-for :key 拼接/空 @tap.stop）、custom 顶栏+scroll-view 双层滚动/导航脱扣、页面守卫 mixin 禁区、跨页迁移清单、图片 URL 补全、分享封面、卡片美化、小区选择器、按 type 切换表单、通知栏、主包 2MB 与分包预加载、开发者彩蛋页。当改小程序通用 UI/交互、弹层点不了、分包体积、图片不显示、分享截图、控制台 does not have a method e1、Bad attr data-event-opts、或 scroll-view 滚到底顶栏滑丢时使用。"
---

# 小程序通用约定

先读 `project-map` 确认改的是 `小程序/` 而不是接口。细节按场景 **再 Read 同目录文件**，不要凭记忆改 uView 源码行为。

## 出事先对号

| 现象 | 读 |
|------|-----|
| 弹层关不掉 / 按钮点了没反应，但 **不是** `e0/e1` | [uview-components.md](uview-components.md) |
| 控制台 `does not have a method "e0/e1/e2"`，整页点击失效 | [render-crash-inline-event.md](render-crash-inline-event.md) |
| 编译失败 `Bad attr data-event-opts` / `unexpected token fold` 等 | [wxml-data-event-opts.md](wxml-data-event-opts.md) |
| 切 Tab / 登录态拦截无效或乱跳 | [page-guard.md](page-guard.md) |
| 一块 UI 从 A 页搬到 B 页后样式/点击丢了 | [page-element-migrate.md](page-element-migrate.md) |
| 小程序图片裂图、相对路径；COS URL 多 `/upload/`；后台图变成 `oss.../[` | [image-url-complete.md](image-url-complete.md) + **cos-storage** skill |
| 卡片装饰圆、阴影、按压缩放 | [card-beautify.md](card-beautify.md) |
| 小区/列表选择弹层（搜索、分页、回写） | [community-selector.md](community-selector.md) |
| 一个 type 切换不同必填字段 | [location-type-form.md](location-type-form.md) |
| 首页滚动通知栏 `u-notice-bar` | [notice-bar.md](notice-bar.md) |
| 主包超 2MB、要拆分包 | [main-package-optimize.md](main-package-optimize.md) |
| 分包预加载、跳转卡 | [subpackage-preload.md](subpackage-preload.md) |
| 连点「我的」进开发者页、口令 `990110` | [dev-mode.md](dev-mode.md) |
| 转发卡片封面、分享变成截图 | [mp-share.md](mp-share.md) |
| 空状态文案与按钮挤同一行、`<text>` 后 `<view>` 不换行 | [text-view-layout.md](text-view-layout.md) |
| custom 导航 + scroll-view：滚到底还能拖、顶栏滑没、回顶要多滑一下（脱扣） | [scroll-view-fixed-nav.md](scroll-view-fixed-nav.md) |

## 必须记住的几条

1. **禁止** `@click="foo = false"` 这类模板内联赋值；必须写成 methods。首次 render 若因 `null.__map` 崩溃，handler 永远不会注册。
2. **禁止** `v-for` 使用 `:key="'fold-' + id"` 这类带连字符前缀的字符串拼接 key，以及 `@tap.stop=""` 空事件；易触发 WXML `data-event-opts` 编译错误（见 [wxml-data-event-opts.md](wxml-data-event-opts.md)）。
3. **禁止** `Vue.mixin({ onShow() { uni.navigateTo(...) } })` 做全局拦截；在页面自己的 `onShow`/`onLoad` 里调工具函数。
4. uView 是源码进仓库：`小程序/uview-ui/components/<name>/<name>.vue`。`u-mask` 关闭后 DOM 仍在（z-index:-1）；`u-popup` 关闭会 `v-if` 卸 DOM；`u-swiper` 的 `height`/`border-radius` 单位是 **rpx 数字**，不要再拼 `"20rpx"`。
5. 接口返回给小程序的图片字段用 `cmf_get_asset_url()` 补全；相对路径 `/upload/...` 在小程序里不会显示。
6. 主包只放 tabBar 页和全局依赖；次级页进分包，规则写在 `小程序/pages.json`。
7. **`<text>` 是 inline（类似 span）**：需要「文案一行、按钮下一行」时用父级 `flex-direction: column`，或文案外包 `<view>` / `text { display: block }`，不要只靠 `text-align` 或按钮 `inline-block`。见 [text-view-layout.md](text-view-layout.md)。
8. **无 Tab 的二级页**：`custom` 顶栏 + 长内容用 `scroll-view` 时，勿根节点套 `glass-page`；须 `disableScroll` + flex 壳（`scroll-view`：`flex:1;height:0`）。见 [scroll-view-fixed-nav.md](scroll-view-fixed-nav.md)。

首页/活动业务不在这里，见 `mp-home-activity`。地图见 `mp-map-location`。
