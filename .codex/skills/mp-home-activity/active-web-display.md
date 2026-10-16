# 活动 Web 式显示（display_mode）

## 概念

| display_mode | 名称 | 行为 |
|--------------|------|------|
| `1` | 配置式 | 现有逻辑：后台配置打卡/报名/线下等，小程序 `pages/activity/lsxf/details-new` |
| `2` | Web 式 | 后台只填 **Web 活动地址**（`web_url`），交互在 H5 内实现，小程序用 **web-view** 打开 |

数据库字段（`cmf_active`）：

- `display_mode`：`tinyint`，默认 `1`
- `web_url`：`varchar(500)`，完整 `https://...` URL

## 用户上下文传递（web_ctx）

**不要在 H5 URL 里明文拼 user_id / openid。** 采用短期签名票据：

```
小程序(已登录) → POST /wxapp/active/issue_web_context { active_id }
              ← { web_ctx, web_launch_url, expires_in }

web-view 打开 web_launch_url（已含 active_id + web_ctx）

H5 → GET /wxapp/public/active_web_bootstrap?web_ctx=...&active_id=...
   ← user 摘要、active 摘要、session.openid（供后续调 API）
```

| 项 | 说明 |
|----|------|
| 票据字段 | URL 参数 `web_ctx` |
| 有效期 | 默认 2 小时（`ActiveWebContext::TTL`） |
| 签名 | `api/wxapp/service/ActiveWebContext.php`，密钥来自 `database.authcode` / 库密码派生 |
| 未登录 | 仍可打开 H5，但无 `web_ctx`；H5 需提示「请从小程序登录后进入」 |

### 接口

| 接口 | 鉴权 | 作用 |
|------|------|------|
| `POST /wxapp/active/issue_web_context` | Header `openid` | 签发 `web_ctx`，返回 `web_launch_url` |
| `GET /wxapp/public/active_web_bootstrap` | 仅 `web_ctx` | H5 初始化，返回 `user` / `active` / `session.openid` / `api.base` |

详情接口 `get_new_active_detail` 在已登录时会多返回 **`web_launch_url`**（带 `web_ctx`），扫码/旧链进详情再 redirect 时也会带上下文。

### H5 侧用法

1. 引入 `public/static/mp-active-web/sdk.js`
2. 页面加载：`ActiveWebSdk.bootstrap()` 拿用户与活动
3. 调现有 wxapp 接口：`ActiveWebSdk.apiPost(data.api.base, data.session.openid, '/wxapp/active/...', { ... })`  
   Header 名与小程序一致：`openid`

演示页：`public/static/mp-active-web/demo.html`

## 关键文件

| 端 | 路径 |
|----|------|
| 票据 | `api/wxapp/service/ActiveWebContext.php` |
| 签发 | `api/wxapp/controller/ActiveController.php` → `issue_web_context`、`attachWebLaunchFields` |
| Bootstrap | `api/wxapp/controller/PublicController.php` → `active_web_bootstrap` |
| 模型 | `app/admin/model/active/ActiveModel.php` |
| 后台表单 | `public/themes/admin_fengiy/admin/active/update.html` |
| 小程序跳转 | `小程序/common/activeOpen.js` + `utils/common.js` → `$u.func.openActiveDetail` |
| web-view | `小程序/pages/h5/h5.vue` |
| H5 SDK | `public/static/mp-active-web/sdk.js` |

## 小程序怎么打开活动

```js
this.$u.func.openActiveDetail(item)   // Web 式且已登录时会先 issue_web_context
this.$u.func.resolveWebActivityPageUrl(detail) // 详情里的 web_launch_url
this.$u.func.buildH5WrapperPath(pageUrl)
```

## 后台配置

1. 活动编辑 → **显示形式** 选「Web式」
2. 填写 **Web 活动地址**（须 `http(s)://` 开头）
3. 列表仍用封面、名称等字段；业务逻辑在 H5

## 演示活动

```powershell
d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe data/seed_active_web_demo.php
```

## 实例：七贤岭的知识小问答

| 项 | 值 |
|----|-----|
| 活动名 | 七贤岭的知识小问答 |
| 显示形式 | Web 式 |
| 七贤岭分类 | `community_cate_id=6`（发现七贤岭）— 与普通活动相同字段 |
| H5 | `public/static/mp-active-web/qxl-knowledge-quiz/index.html` |
| 题库表 | `cmf_active_web_quiz_question`（按 `active_id` 绑定） |
| 接口 | `api/wxapp/controller/ActiveWebQuizController.php` → `status` / `start` / `reveal` / `submit` |
| 积分 | 活动 `send_point`（默认 10），**首次满分**发放一次 |
| 种子脚本 | `data/seed_qxl_knowledge_quiz_active.php` |

答题流：H5 bootstrap → 随机 5 题 → 每题选后 `reveal` 展示解析 → `submit` 计分并发首次满分积分。

## 微信小程序业务域名

web-view 域名须配置 **业务域名**（如 `admin.circular-economy.cn`）。H5 与 `/api` 同域，bootstrap 用相对 `origin + '/api'` 即可。

## 安全说明

- `web_ctx` 泄露等同于在有效期内冒充该用户调 API；勿把带 `web_ctx` 的链接外发到公网
- Bootstrap 返回的 `session.openid` 仅应在 web-view 内使用，勿写入可被第三方读到的存储
- 敏感写操作建议 H5 内二次确认，或仍跳回小程序原生页

## 迁移 SQL

见 `update/update.sql` 中 `display_mode` / `web_url` 幂等 `ALTER`。
