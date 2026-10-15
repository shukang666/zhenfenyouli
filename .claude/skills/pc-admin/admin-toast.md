---
name: pc-admin-toast
description: "PC 后台轻提示与确认弹窗皮肤。覆盖 layer.msg / noty / layer.confirm / artDialog / 删除二次校验框。当要改「删除成功、登录成功、确认取消」外观，或排查「只挪了位置、样式没变」时调用。"
---

# 后台提示 / 确认框皮肤

只改外观，不改业务接口。**不要改 vendor 和 `public/static/js/layer`、`artDialog/skins` 原文件**，覆盖写在主题 CSS 里。

## 改哪几个文件

| 文件 | 作用 |
|---|---|
| `public/themes/admin_fengiy/public/assets/css/admin-toast.css` | 胶囊提示 + 确认卡片 |
| `public/themes/admin_fengiy/public/assets/js/admin-toast.js` | 给 `layer.msg` 补默认 `offset`；关掉 noty 的整屏灰罩 |
| `public/themes/admin_fengiy/public/header.html` | 后台 iframe 内页引入（改完把 `?v=` +1） |
| `public/plugins/fengiy_login/view/widget.html` | **登录页不走 header**，必须单独引入同一套 CSS/JS |
| `public/static/js/admin-delete-confirm.js` | 「请输入确认」那层是自建 DOM，样式在文件里的 `injectStyle()` |

`admin-ui.css` 只管内容区卡片/表格，不管弹层。

## 后台其实有三套弹层

| 你看到的 | 实际组件 | 典型入口 |
|---|---|---|
| 登录成功、很多页的 `layer.msg` | layui `layer.msg` | 登录 `widget.html`、业务模板 |
| 删除成功、表单保存成功 | **noty**（不是 layer） | `public/static/js/admin.js` |
| 「您确定要进行此操作吗」 | **artDialog** | `.js-ajax-delete` / `.js-ajax-dialog-btn` |
| `layer.confirm('确认审核？')` | layui `layer.confirm` | 部分审核页手写 |
| 删除前输入「确认」 | `.cmf-dc-*` | `admin-delete-confirm.js` |

只改 `layer.msg` 样式，删除成功不会变；只改 layer 确认框，点列表删除仍是 artDialog。

## ⚠️ 给 `layer.msg` 加默认参数时，不要写 `skin`

`layer.msg` 内部 `$.extend` 会用你传入的 `skin` **整段覆盖**默认值 `layui-layer-msg`。

带 `{icon:1}` 的成功提示于是只剩 `layui-layer-cmf-toast`，CSS 若写 `.layui-layer-msg` 就全部套不上——表现就是**只有位置变了，还是原来那块白方框**。已经踩过。

`admin-toast.js` 只补 `offset`，**不要补 skin**。确认框样式用 `:not(.layui-layer-msg)`，避免把胶囊提示又画成大卡片。

## ⚠️ 确认框不要给关闭按钮再画一个 `×`

关闭钮本身带 `layui-layer-ico`，再用 `:after` 画叉、或把内容区图标样式套到 `.layui-layer-ico` 上，会出现**两个关闭按钮**。已经踩过。

正确做法：

- 内容区图标只写 `.layui-layer-content .layui-layer-ico`
- `layer.confirm` 默认 `title: false`、`closeBtn: 0`（下面已有确定/取消，不必再叉）
- 拒绝原因那种 `layer.prompt` 不要套确认框的「藏关闭钮」规则

## 登录页路径

插件模板没有 `__TMPL__`，用：

`__ROOT__/themes/admin_fengiy/public/assets/css/admin-toast.css`

和同目录的 `admin-toast.js`。改皮肤后 **header 和 widget 的 `?v=` 一起加**。
