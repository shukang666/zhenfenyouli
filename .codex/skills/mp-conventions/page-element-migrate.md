---
name: mp-page-element-migrate
description: "小程序页面元素/组件跨页面迁移清单。当把一个功能块（如搜索框、扫码入口、卡片）从一个页面搬到另一个页面时，记录需要同步迁移的完整清单：模板块、scoped 样式（含 sticky/定位等位置相关属性）、methods、依赖的 data/computed、全库搜索调用方、目标页插入点与视觉对齐。当需要搬迁/复制页面 UI 元素，或迁移后出现『图标不显示』『点击无反应』『样式错位』时调用。"
---

# 小程序页面元素跨页面迁移清单

以"活动搜索+扫码入口从 `pages/low_carbon/low_carbon.vue` 迁到 `pages/index/index.vue`"为实例。

## 1. 迁移五步

| 步骤 | 内容 | 易漏点 |
|---|---|---|
| 1. 模板 | 复制目标 `<view>` 结构到新页面 | 忘了 `@click.stop`（外层是跳转时必加，否则点图标同时触发外层跳转） |
| 2. 样式 | 复制对应 scoped 样式 | 位置相关属性（`position: sticky`、`padding`、`margin`）需按新页面布局重调；圆角/留白对齐相邻元素 |
| 3. methods | 迁移事件方法（如 `onScanQrcode`） | 方法内引用的 `this.xxx` 要一并确认 |
| 4. 依赖 | 迁移用到的 `data`/`computed`/`import` | 漏了会报 undefined |
| 5. 清理 | 原页面删除或留注释说明已迁移 | 只删模板不删方法会留死代码；建议留一行注释指向新位置 |

## 2. 实例：搜索+扫码入口迁移

- 原位置：`low_carbon.vue` 活动页顶部搜索条 → 新位置：`index.vue` 顶部（banner 上方）。
- 方法 `onScanQrcode()` 整体迁移，逻辑不变（见 skill `active-scan-join`）。
- 原页面在删除处留注释：`// 活动搜索与扫码入口已移至首页 index.vue（onScanQrcode 同步迁移过去）`。
- 新页面样式 `.home-search-wrap/.home-search-box/.home-search-text/.home-search-scan` 按首页 30rpx 留白 + 24rpx 圆角对齐 banner。

## 3. 迁移后自检

- [ ] 图标/文字显示正常（样式漏拷）
- [ ] 点击触发正确方法（methods/事件绑定漏拷）
- [ ] 事件写法是方法引用（`@click="onScanQrcode"`），没把内联赋值（`@click="a = false"`）一起搬过去 —— 内联赋值编译成匿名 handler `e0/e1`，页面一旦 render 抛错就整体失效，见 `mp-render-crash-inline-event`
- [ ] 不再触发外层容器的点击（`.stop` 漏加）
- [ ] 方法内依赖的 data 存在（否则 undefined）
- [ ] 原页面无残留死代码或已注释说明去向
- [ ] 视觉与相邻模块对齐（留白/圆角/间距）
- [ ] 全库搜索该方法名，确认没有其它页面仍引用被删代码

## 4. 编辑大 Vue 模板的安全做法（踩过的坑）

搬 / 删页面里的一个模板块时，**不要**连续多次小范围 `replace_in_file` 删删改改：

- 每删一个块容易遗留多余的 `</view>` 闭合标签和错乱缩进，视觉上出现「连续多个闭合」；
- 一旦结构漂移，后续 `replace_in_file` 的 `old_str` 就再也匹配不上（还可能叠加编码损坏字符，见下）。

正确流程：

1. 先 `git checkout HEAD -- <文件>` 把文件恢复成干净基线（或确认当前状态）；
2. 用**一次性整块替换**完成目标区域改动（old_str 覆盖到完整、可唯一定位的块，含前后各一行上下文）；
3. 改完复核**标签配对与缩进**：`read_file` 看结构，必要时用脚本按行号重写该区域；
4. `git diff` 复核产物，确认只含本次逻辑改动。

编码损坏字符坑：本仓库部分文件注释含坏字节（如 `后续需要后端提供字�?`），这类行的 `old_str` 无法稳定匹配 → 直接**整块替换**覆盖该行，或改用脚本按行号重写。

## 相关技能

- `active-scan-join`：扫码入口的完整解析逻辑。
- `mp-render-crash-inline-event`：内联事件失效（`does not have a method "eN"`）与 render 崩溃陷阱。
- `mp-index-layout`：首页结构，确认插入位置与样式约定。
- `dev-environment`：脚本批量改写文件的 BOM / PowerShell 坑。
