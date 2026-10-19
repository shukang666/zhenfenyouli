---
name: ai-talk
description: "免登录 AI 讨论组 Web 应用（public/ai_talk）。群聊展示、全员共享 JSON 聊天记录、每人发言后按最近 30 条调智谱 glm-4.7-flash；与 PC 后台账号/ThinkCMF 路由分离。改讨论组 UI、接口、模型、存储或排查 AI 429/SSL/密钥时调用。"
---

# AI 讨论组（public/ai_talk）

## 定位

在站点 `public/` 下独立 H5，**不经过** `/admin`、不进 `cmf_admin_menu`、不依赖小程序 `openid`。外观是群聊；逻辑是：**任意用户发一条 → 服务端追加用户消息 → 取最近 N 条（默认 30）拼成智谱对话 → 追加助手回复 → 全员可见**。昵称仅存在浏览器 `localStorage`（`ai_talk_nickname`），服务端只信请求体里的 nickname 字段做展示与 `[昵称]: 正文` 格式化。

与 `public/static/mp-active-web/` 活动 H5 类似：静态页 + 同目录 PHP 接口，Web 服务器对存在的 `.php` 直出（ThinkCMF 的 `public/router.php` 不会吞掉 `public/ai_talk/api.php`）。

## 目录与配置

| 路径 | 作用 |
|------|------|
| `public/ai_talk/index.html` | 群聊 UI、轮询、localStorage 昵称 |
| `public/ai_talk/api.php` | JSON 接口入口 |
| `public/ai_talk/bootstrap.php` | `.env` 加载、存储锁、智谱 HTTP |
| `data/ai_talk.config.php` | 模型、上下文条数、单条字数、落盘条数上限 `storage_cap`、系统提示词、存储路径 |
| `data/runtime/ai_talk_room.json` | 运行时聊天记录（自动创建） |

密钥与 SSL 开关在**项目根 `.env`**（勿提交公开仓库）：

| 变量 | 说明 |
|------|------|
| `AI_TALK_API_KEY` | 智谱 Bearer Token，必填 |
| `AI_TALK_KEY_ID` | 可选，默认 `product-shukang`，会带 `X-Api-Key-Id` 请求头 |
| `AI_TALK_SSL_VERIFY` | 可选；未设时若 `COS_CURL_SSL_VERIFY=false` 则 curl 不校验证书（phpStudy 本机常见） |

访问：`https://{域名}/ai_talk/` 或 `/ai_talk/index.html`。

## HTTP 接口

统一 JSON：`{ "code": 1|0, "msg": "...", "data": ... }`。

### GET `api.php?action=history&since_id=0`

- `since_id=0`：返回全部消息。
- `since_id>0`：只返回 `id > since_id` 的消息（前端轮询用）。
- `data.messages`：数组；`data.last_id`：当前存储中最后一条 id（0 表示空）。

### POST `api.php?action=send`

Body JSON：`{ "nickname": "展示名", "content": "正文" }`。

流程（在**文件独占锁**内完成，避免并发写乱序）：

1. 校验并清洗 nickname（空→「访客」，最长 20）、content（非空，最长见 `max_message_len`）。
2. 追加 `role=user` 消息（字段：`id`, `role`, `nickname`, `content`, `ts`）。
3. `ai_talk_build_glm_messages`：system + 最近 `max_history` 条；用户行格式 `[昵称]: 内容`，助手行原样。
4. POST `https://open.bigmodel.cn/api/paas/v4/chat/completions`，`model` 来自配置（默认 `glm-4.7-flash`），`temperature` 0.75。
5. 追加 `role=assistant`，`nickname` 固定「讨论助手」。
6. 若总条数 > `storage_cap`（默认 200），只保留最后 N 条落盘（**不影响**单次请求的 30 条上下文，仅限制文件体积）。
7. 返回 `data.user` 与 `data.assistant` 两条新记录。

失败时 `code=0`，`msg` 为异常信息（含智谱 HTTP 4xx/5xx 体前 200 字、429 限流等）。

## 前端行为

- 首次/改昵称：遮罩输入，写入 `localStorage`。
- 进入后 `history` 全量拉取；之后每 2.5s `history&since_id=lastId`（发送中暂停轮询逻辑上的重复由 `sending` 标志处理）。
- 发送：POST send，本地追加 user + assistant；Enter 发送，Shift+Enter 换行。
- 气泡：`role=assistant` 为助手样式；`user` 且 nickname 与本地相同视为「我」右对齐。

## 后端实现要点

- **不走 ThinkCMF**：`bootstrap.php` 自读 `.env` 到内存数组（**禁止 `putenv`**，部分主机禁用会导致 Warning 污染 JSON），再与 `$_ENV` 合并；`define CMF_ROOT/CMF_DATA` 后 `require data/ai_talk.config.php`。`api.php` 入口 `display_errors=0` 且输出 JSON 前清空 output buffer。
- **存储**：单文件 JSON `{ "messages": [ ... ] }`；写与「读-改-写」发送路径用 `flock(LOCK_EX)`；历史只读用 `LOCK_SH`，避免读到半写文件。
- **智谱响应**：取 `choices[0].message.content`；空则抛错并带上 `error.message`（若有）。
- **无 DB、无 session**：扩展多房间时需改 `storage_file` 或加 `room` 参数分文件，并自行考虑滥用（频率限制、鉴权）——当前未做。

## 禁止混改

| 错误 | 正确 |
|------|------|
| 把 Key 写进 `index.html` 或前端 JS | 只放 `.env`，仅 `bootstrap.php` 请求智谱 |
| 接到 `api/wxapp` 并走 `AuthController` | 保持 `public/ai_talk` 独立 |
| 用后台 `ADMIN_ID` 做身份 | 免登录产品，昵称客户端自报 |
| 在 `app/admin` 加菜单当主入口 | 直接 URL 访问；若要运营入口可做外链说明页 |

## 排查

| 现象 | 方向 |
|------|------|
| 前端 `Unexpected token '<'` / 非 JSON | 多为 PHP Warning 混在响应里；查是否旧版用了 `putenv`、或接口 404 返回 HTML；部署最新 `bootstrap.php` |
| 未配置 AI_TALK_API_KEY | 检查 `.env` 与 php-fpm/Apache 是否读到同一工作目录 |
| SSL certificate problem | 本机设 `AI_TALK_SSL_VERIFY=false` 或对齐 `COS_CURL_SSL_VERIFY` |
| HTTP 429 / 1305 访问量过大 | 智谱限流，稍后重试或换时段 |
| 多人看不到彼此消息 | 确认同一服务器上的 `data/runtime/ai_talk_room.json`；多机部署需改存储（Redis/DB） |
| 发送后无助手回复 | 看接口 `msg`；查 curl 超时（120s）与智谱账单/模型名 |

## 扩展建议（未实现）

- 多房间：`?room=` + `data/runtime/ai_talk_{room}.json`，room 白名单。
- 流式输出：智谱 `stream:true` + SSE，前端逐字渲染。
- 清空记录：受保护的管理 action 或 CLI 删 json。
