---
name: "project-context"
description: "项目通用上下文与开发规范。当需要在 xiayuxuan.com (ThinkCMF6/ThinkPHP6) 项目中进行任何代码修改、新增功能、排查 bug、查询文件结构时调用。"
---

# 项目上下文（xiayuxuan.com）

## 技术栈

- **框架**: ThinkCMF 6.x 基于 ThinkPHP 6.x
- **PHP**: 项目运行在 PHP 7.3.4 nts（路径 `d:\phpstudy_pro\Extensions\php\php7.3.4nts\php.exe`）
- **数据库**: MySQL，表前缀 `cmf_`，连接配置 `data/config/database.php`
- **后台模板**: `public/themes/admin_fengiy/`，使用 Bootstrap + layui + ThinkCMF 模板引擎
- **后台静态资源**: `public/static/` (layui, js, font-awesome)
- **前台 API**: `api/wxapp/` 控制器和模型

## 关键目录

| 用途 | 路径 |
|------|------|
| 后台控制器 | `app/admin/controller/` |
| 后台模型 | `app/admin/model/` |
| 后台视图模板 | `public/themes/admin_fengiy/admin/` |
| 公共头部 | `public/themes/admin_fengiy/public/header.html` |
| 前台 API 控制器 | `api/wxapp/controller/` |
| 公共模型 | `api/wxapp/model/CommonModel.php` 等 |
| 路由 | `data/route/route.php` |
| 语言包 | `data/lang/zh-cn/admin_menu.php` |
| 运行时日志 | `data/runtime/log/YYYYMM/` |

## 后台控制器继承链

```
AdminBaseController (cmf\controller)
  └─ ToolController (app\admin\controller) - 通用增删改查基类
      └─ MemberController, ForumController, NewsController 等业务控制器
```

`ToolController` 提供：`index/update/addPost/editPost/delete/listOrder/setStatus` 等通用方法。子类通过重写 `setWhere / setIndex / setEdit / setAddPost / setEditPost / setData / get_params / outParam` 来定制。

## 模板约定

- 顶部包含：`<include file="public@header"/>`
- 结尾：`<script src="__STATIC__/js/admin.js"></script>`
- 模板占位符：`{:url('action')}`, `{$var}`, `<foreach name="list" item="vo">`, `<if condition="...">`
- 分页渲染：`{$list->render()}` 输出 Bootstrap 风格的 `<ul class="pagination">`

## 后台入口与 iframe

后台主入口 `admin/index/index.html` 通过 iframe 加载各业务页面。URL 形如：
`http://host/admin/index/index.html#/admin/<controller>/<action>.html`

页面 JS 中的 `window.location.href`、`window.location.search` 反映的是 iframe 内业务页的 URL。

## URL 生成

`url('outData', input('get.'))` 这种写法是 ThinkCMF 中把当前所有 GET 参数透传到目标 action 的常用模式。`url()` 会自动把驼峰 action 名（`outData`）转换为下划线（`out_data`）。
