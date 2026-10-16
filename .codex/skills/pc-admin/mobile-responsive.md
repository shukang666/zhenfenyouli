---
name: pc-admin-mobile-responsive
description: "PC 后台（admin_fengiy 主题）移动端 / 窄屏适配方案。记录两个改动点：样式文件 public/themes/admin_fengiy/public/assets/css/admin-responsive.css（全部规则限定在 max-width:767px 媒体查询内，PC 端零影响）与 public/header.html 里注入的一小段 JS（给宽表格套 .cmf-table-scroll 横向滚动容器并按列数设 min-width、独立打开时插入浮动返回按钮 .cmf-m-back）。含「哪些表格会被处理」「哪些会被豁免（.dash-table/.no-mobile-scroll）」「为什么不能给窄表格加 min-width」，以及排查「手机上表格压成一列一个字」「仪表盘卡片被撑破」「PC 端样式被误改」的方法。当需要改后台移动端适配、新增页面要兼容窄屏、或排查手机访问后台布局错乱时调用。"
---

# PC 后台移动端适配（admin_fengiy）

## 目标与原则

让手机 / 平板能**正常使用**后台，而不是做一个全新的移动端 UI。

1. **不隐藏业务信息**（不做"移动端精简版"），只做「竖排 + 横向滚动 + 加大点击区域」
2. **PC 端零影响** —— 所有规则都写在 `max-width: 767px` 媒体查询内，JS 也在 `innerWidth > 767` 时直接 `return`
3. 尽量少动模板：靠**公共 header + 一个 CSS 文件**覆盖全部页面，避免逐个页面改

---

## 两个改动点（只有这两处）

| 文件 | 作用 |
|---|---|
| `public/themes/admin_fengiy/public/assets/css/admin-responsive.css` | 全部窄屏样式，规则**全部在媒体查询内** |
| `public/themes/admin_fengiy/public/header.html` | ① `<link>` 引入上面的 CSS；② 注入一段 JS 做「表格套滚动容器」和「浮动返回按钮」 |

`header.html` 里那段 JS 的位置在**文件末尾**（jQuery 之后），关键逻辑：

```js
if (window.innerWidth > 767) { return; }   // PC 端直接不执行

$(function () {
    $('table.table').each(function () {
        // 豁免：仪表盘卡片内的小表格 / 显式标记的表格
        if ($table.hasClass('dash-table') || $table.hasClass('no-mobile-scroll')) return;
        if ($table.parent().hasClass('cmf-table-scroll')) return;

        var cols = $table.children('thead').find('tr').first().find('th').length;
        if (cols < 5) return;                       // 窄表格不处理

        $table.css('min-width', cols * 88 + 'px');  // 每列约 88px
        $table.wrap('<div class="cmf-table-scroll"></div>');
    });

    // 独立打开（不在后台 iframe 壳里）时补返回按钮
    if (window.self === window.top) {
        $('<a href="javascript:history.back();" class="cmf-m-back">返回</a>').appendTo('body');
    }
});
```

---

## 关键设计（改动前务必读懂）

### 1. 为什么只处理「列数 ≥ 5」的表格

仪表盘（`pc-admin-dashboard`）的卡片里有很多 **2~4 列的小表格**，
给它们设置 `min-width` 会把卡片**横向撑破**，所以：

- 列数 < 5 → **完全不处理**，让它自然收缩
- 列数 ≥ 5 → 认为需要横向滚动，套容器 + 设 `min-width`

CSS 里也刻意**没有**写全局的 `table { min-width: ... }`，就是这个原因。

### 2. 豁免类 `dash-table` / `no-mobile-scroll`

| 类名 | 用途 |
|---|---|
| `dash-table` | 仪表盘卡片内的表格，永远不套滚动容器 |
| `no-mobile-scroll` | 任何你不想被处理的自定义表格，加这个类即可豁免 |

新增页面如果表格**不应该横向滚动**，加上 `class="table no-mobile-scroll"`。

### 3. 浮动返回按钮 `.cmf-m-back` 的出现条件

只在**页面被独立打开**（`window.self === window.top`，即没嵌在后台 iframe 壳里）时才插入。
在后台正常点击菜单时页面在 iframe 内，不会出现这个按钮。

CSS 侧它默认 `display: none`，只有 `max-width:767px` 内才 `display: block`。

### 4. CSS 覆盖范围（窄屏下做了什么）

| 区块 | 处理 |
|---|---|
| 页面留白 / 字号 | `.wrap` 左右 10px、底部留 72px（避开浮动按钮） |
| `body min-width` | 强制 `0 !important` —— 有些页面（如 `rbac/member.html`）写死了 600px，窄屏会横向溢出 |
| 搜索/筛选 `form.well` | 内部控件全部 `display:block; width:100%` 竖排 |
| `.nav-tabs` | flex + 横向滚动，不折行 |
| `.btn-xs` | 放大到手指可点 |
| 编辑表单 | `col-sm-*` 兜底全宽 |
| 分页 | 允许换行、缩小内边距 |
| `layui-layer` | `max-width: 94vw` |
| `.wrap img` | `max-width:100%` 防溢出 |

---

## 覆盖范围

**所有 `include public@header` 的后台页面自动生效**（约 281 个模板）。

极少数**没有** include 公共 header 的页面不会生效，此时需要单独引入：

```html
<link href="__TMPL__/public/assets/css/admin-responsive.css" rel="stylesheet" type="text/css">
```

---

## 排查

| 现象 | 原因 / 处理 |
|---|---|
| 手机上表格被压成「一列一个字」 | ① 页面没 include `public@header`；② 视口宽度 > 767px（`meta viewport` 缺失或平板横屏）；③ 表格列数 < 5 被跳过（这是预期） |
| 仪表盘卡片被撑破、出现横向滚动 | 给卡片内小表格误加了 `min-width`，或漏了 `dash-table` 类 |
| PC 端样式也被改了 | 规则写到了媒体查询**外面** —— 必须放进 `@media (max-width: 767px)` |
| 改了 CSS 没生效 | 静态资源缓存，浏览器 `Ctrl + F5` 强刷；线上还需确认文件已部署 |
| 浮动返回按钮不出现 | 只在「独立打开 + 窄屏」时才插入，iframe 内看不到是正常的 |
| 某表格不想横向滚动 | 加 `class="no-mobile-scroll"` |

---

## 相关 skill

- 后台新增页面（新页面自动获得适配）：`pc-admin-new-page`
- 仪表盘（`dash-table` 的来源）：`pc-admin-dashboard`
- 窄屏表格相关的加列：`pc-admin-add-column`
- 静态资源改了没生效的排查顺序：`prod-db-change-and-deploy-verify`
