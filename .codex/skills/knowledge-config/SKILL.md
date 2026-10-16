---
name: knowledge-config
description: "分类知识文章 + 七贤岭学考。覆盖 cmf_knowledge 列表/详情，以及七贤岭边读边测（qxl_knowledge / qxl_quiz_question / qxl_chapter_log）。当改知识正文、分类筛选、七贤岭学考融合、篇章绑题、通关解锁，或排查知识接口 404、学考下一篇锁住时调用。"
---

# 分类知识 / 七贤岭学考

先分清两条线，**表和页面都不要混改**：

| 产品 | 表 | 小程序 | 说明 |
|------|----|--------|------|
| 学会分类知识 | `cmf_knowledge` | `pages/dtsh/knowledge` + `knowledge-detail` | 只读文章，约 500 字分段正文 |
| 分类知识挑战 | `cmf_quiz_*` | `pages/dtsh/quiz` | 每日随机 5 题，见 `quiz-challenge` |
| 七贤岭学考 | `cmf_qxl_*` | `love-qixianling` → `qxl-learn-exam` | 读完本篇考本篇，过关解锁下一篇，见 [qixianling.md](qixianling.md) |

垃圾分类「未学习/未考试不能打卡」是第三条门禁，见 `mp-home-activity` 的 learn-exam-clock-gate，**不是**七贤岭学考。

## 0. 学会分类知识

后台管理分类知识文章（标题/封面图/富文本/分类），小程序按分类浏览列表和详情。

入口：垃圾分类打卡首页 `pages/dtsh/dtsh.vue` 任务卡片，路径 `/pages/dtsh/knowledge`。

**正文约定**：详情页每篇大约 **500 字**，只用 `<p>` 分段，不要小标题、不要条目列表。改库时直接更新 `cmf_knowledge.content`。

## 1. 数据库表 `cmf_knowledge`

```sql
CREATE TABLE IF NOT EXISTS `cmf_knowledge` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '标题',
  `image` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '封面图URL',
  `content` TEXT NOT NULL COMMENT '富文本内容(HTML)',
  `category` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '分类（如可回收物/有害垃圾）',
  `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1启用 0禁用 -1已删除(软删除)',
  `list_order` INT(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `view_count` INT(11) NOT NULL DEFAULT '0' COMMENT '浏览次数',
  `create_time` INT(11) NOT NULL DEFAULT '0',
  `update_time` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分类知识内容表';
```

> 软删除用 `status=-1`，常规列表查 `status=1`，后台列表查 `status<>'-1'`（含禁用）。

## 2. PC 后台 —— `app/admin/controller/KnowledgeController.php`

### 2.1 方法列表

| 方法 | 职责 |
|---|---|
| `index()` | 知识列表（搜索/分类筛选/状态筛选/分页） |
| `add()` | 新增/编辑（GET 渲染表单，POST 保存；编辑时 `assign('data', $knowledge)`） |
| `delete()` | 软删除（status=-1） |
| `delete_all()` | 批量软删除 |
| `listOrder()` | 排序 |
| `setStatus()` | 启用/禁用 |

### 2.2 关键注意

- `add()` 必须 assign `data` 变量（和 Quiz 同理），包含 id/title/image/content/category/list_order/status
- 表单里图片上传使用 ThinkCMF 自带的 `uploadOneImage('图片上传','#knowledge-image')`，底层走 `user/Asset/webuploader`（`admin.js` 的 `openUploadDialog`）；点击绿色上传按钮就是通过 ThinkCMF 全局上传弹窗完成，返回值写入 `#knowledge-image` input，通过监听 `change` 事件同步预览。**不要自创 `admin/Upload/upload` 这类接口，项目没有这个控制器。**

### 2.3 模板

| 模板文件 | 用途 |
|---|---|
| `public/themes/admin_fengiy/admin/knowledge/index.html` | 列表页 |
| `public/themes/admin_fengiy/admin/knowledge/add.html` | 新增/编辑共用表单 |

### 2.4 菜单 SQL

```sql
INSERT INTO `cmf_admin_menu`
(`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
SELECT p.id, 1, 1, 34, 'admin', 'Knowledge', 'index', '', '分类知识配置', 'book', '管理垃圾分类知识内容'
FROM `cmf_admin_menu` p WHERE p.parent_id=0 AND p.name='垃圾分类活动' LIMIT 1;
```

## 3. 小程序 API —— `api/wxapp/controller/KnowledgeController.php`

| 接口 | 方法 | 参数 | 返回 |
|---|---|---|---|
| `get_list` | GET | category, keyword, page | list, total, has_more, categories |
| `get_detail` | GET | id | 单条详情（含完整 content）+ view_count 自增 |

**关键实现**：
- `get_list` 只返回 `id, title, image, category, view_count, create_time`（不返回 content）
- `get_detail` 查完后 `->inc('view_count')` 做浏览计数
- 图片 URL 通过 `formatImage()` 补全绝对路径（复用 `cmf_get_asset_url`）
- 继承 `AuthController`（不需要登录也能浏览，但基类提供了鉴权能力；如不需登录可改为继承 `BaseController`）

## 4. 小程序前端

### 4.1 列表页 `pages/dtsh/knowledge.vue`

**页面结构**：
1. 绿色渐变背景 + 自定义导航栏
2. 横滑分类 Tab（全部 + 动态 categories）
3. 知识卡片列表（封面图 + 标题 + 分类 + 时间 + 浏览量）
4. 下拉加载更多（翻页）
5. 空态提示

**关键 data**：
```js
list, categories, currentCategory, keyword, page, hasMore, total, loading
```

**关键方法**：
- `refreshList()` —— 重置 page 并拉数据
- `loadMore()` —— 翻页追加
- `switchCategory(cat)` —— 切换分类
- `getList()` —— 调 `$u.api.getKnowledgeList()`
- `goDetail(id)` —— 跳转 `/pages/dtsh/knowledge-detail?id=xxx`

### 4.2 详情页 `pages/dtsh/knowledge-detail.vue`

使用 `<rich-text :nodes="detail.content">` 渲染 HTML 富文本内容。

### 4.3 API 注册 `小程序/api/wxapp.js`

```js
getKnowledgeList: (params = {}) => uni.$u.get('/wxapp/knowledge/get_list', params),
getKnowledgeDetail: (params = {}) => uni.$u.get('/wxapp/knowledge/get_detail', params),
```

### 4.4 路由 `pages.json`

```json
{ "path": "knowledge", "style": { "navigationBarTitleText": "分类知识", "navigationStyle": "custom" } },
{ "path": "knowledge-detail", "style": { "navigationBarTitleText": "知识详情", "navigationStyle": "custom" } }
```

## 5. 排查指南

| 现象 | 原因 | 修复 |
|---|---|---|
| 接口 404 | 代码没部署 | 部署 `api/wxapp/controller/KnowledgeController.php` |
| 后台不显示菜单 | 菜单没插 / 非超管未授权 | 执行 2.4 SQL + 角色授权 |
| 详情 content 不显示 HTML 标签 | 用了 `{{}}` 富文本插值 | 须用 `<rich-text :nodes="...">` |
| 图片不显示 | 返回了相对路径 | 检查 `formatImage()` 补全逻辑 |
| 分类 Tab 为空 | categories 字段没填 | 后台添加时填分类名 |
| 上传按钮 404 | 模板里 `url: '{:url("admin/Upload/upload")}'`，项目没这个控制器 | 改 `doKnowledgeUpload()` + `openUploadDialog` |
| 保存后编辑回显不出图片 | DB 里 `image` 存的是相对路径(`upload/file/xxx.png`) | 模板用 `<php>$imagePreviewUrl = cmf_get_image_preview_url($data['image'] ?? '')</php>` 包装，img src 用 `{$imagePreviewUrl}` |
| 上传后预览不显示 | `uploadOneImage` 设置 input val 不触发 change 事件 | 直接调 `openUploadDialog`，在回调里同时 `$(input).val(filepath)` + `$(preview).attr('src', previewUrl).show()`；blur 也监听 |

## 6. 部署检查清单

- [ ] 建表 SQL 在生产库执行
- [ ] 菜单已插入 `cmf_admin_menu`
- [ ] `app/admin/controller/KnowledgeController.php` 已部署
- [ ] `public/themes/admin_fengiy/admin/knowledge/` 模板已部署
- [ ] `api/wxapp/controller/KnowledgeController.php` 已部署
- [ ] `小程序/pages/dtsh/knowledge.vue` + `knowledge-detail.vue` 已编译
- [ ] `小程序/api/wxapp.js` 已更新并编译
- [ ] `pages.json` 已注册 `knowledge-detail` 路由
- [ ] 非超管角色已授权

## 相关 skill

- 七贤岭学考融合：[qixianling.md](qixianling.md)
- 分类知识挑战（每日 5 题）：`quiz-challenge`
- 打卡门禁学考：`mp-home-activity` / learn-exam-clock-gate
- 生产库变更：`dev-environment`
