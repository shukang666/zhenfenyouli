---
name: ai-talk
description: "免登录 AI 讨论组 Web 应用（public/ai_talk）。群聊展示、全员共享 JSON 聊天记录、每人发言后按最近 30 条调智谱 glm-4.7-flash；与 PC 后台账号/ThinkCMF 路由分离。改讨论组 UI、接口、模型、存储或排查 AI 429/SSL/密钥时调用。"
---

# AI 讨论组（public/ai_talk）

## 定位

在站点 `public/` 下独立 H5，**不经过** `/admin`、不进 `cmf_admin_menu`、不依赖小程序 `openid`。外观是群聊；逻辑是：**任意用户发一条 → 服务端追加用户消息 → 取最近 N 条（默认 30）拼成智谱对话 → 追加助手回复 → 全员可见**。昵称仅存在浏览器 `localStorage`（`ai_talk_nickname`），服务端只信请求体里的 nickname 字段做展示与 `[昵称]: 正文` 格式化。

与 `public/static/mp-active-web/` 活动 H5 类似：静态页 + 同目录 PHP 接口，Web 服务器对存在的 `.php` 直出（ThinkCMF 的 `public/router.php` 不会吞掉 `public/ai_talk/api.php`）。

**部署约定：** 本功能**全部落在 `public/ai_talk/`**（随 git 发布）。**不要**把配置或运行时文件放到站点根 `data/`（该目录通常不参与 git、线上也可能不可读）。

## 目录与配置

| 路径 | 作用 |
|------|------|
| `public/ai_talk/index.html` | 群聊 UI、轮询、localStorage 昵称 |
| `public/ai_talk/api.php` | JSON 接口入口 |
| `public/ai_talk/bootstrap.php` | 配置合并、`.env` 解析、存储锁、智谱 HTTP |
| `public/ai_talk/config.php` | 可提交 git 的业务配置（模型、条数、提示词等） |
| `public/ai_talk/config.local.php` | 可选密钥/覆盖（复制 `config.local.php.example`，已 gitignore） |
| `public/ai_talk/data/room.json` | 聊天记录运行时文件（gitignore，自动创建） |
| `public/ai_talk/data/.htaccess` | 禁止 HTTP 直读 json |

密钥优先级（高→低）：`$_SERVER` / 根目录 `.env` 中的 `AI_TALK_*` → `config.local.php` 的 `api_key` / `key_id` → 默认 `key_id=product-shukang`。

| 变量 / 文件 | 说明 |
|-------------|------|
| `AI_TALK_API_KEY` | 智谱 Bearer Token |
| `AI_TALK_KEY_ID` | 可选，`X-Api-Key-Id` 请求头 |
| `AI_TALK_SSL_VERIFY` | 可选；未设时若 `COS_CURL_SSL_VERIFY=false` 则 curl 不校验证书 |
| `config.local.php` | 主机读不到根 `.env` 时，在服务器复制 example 并填 `api_key` |

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
4. POST 智谱 `chat/completions`；遇 HTTP 429 / 错误码 1305 /「访问量过大」时由 `ai_talk_call_glm_with_retry` 间隔 `glm_retry_delay_ms`（默认 1s）最多重试 `glm_retry_max`（默认 10）次。`send` 先落盘用户消息再调 AI，失败时仍返回 `data.user`。
5. 追加 `role=assistant`，`nickname` 固定「讨论助手」。
6. 若总条数 > `storage_cap`（默认 200），只保留最后 N 条落盘（**不影响**单次请求的 30 条上下文，仅限制文件体积）。
7. 返回 `data.user` 与 `data.assistant` 两条新记录。

失败时 `code=0`，`msg` 为异常信息（含智谱 HTTP 4xx/5xx 体前 200 字、429 限流等）。

## 前端行为

- 首次/改昵称：遮罩输入，写入 `localStorage`。
- 进入后 `history` 全量拉取；之后每 2.5s `history&since_id=lastId`（发送中暂停轮询逻辑上的重复由 `sending` 标志处理）。
- 发送：先本地展示用户气泡 + 助手「AI输入中…」占位，POST send（可能较久）；成功后用服务端 id/正文替换占位；失败保留用户消息并在助手气泡显示错误文案。
- 气泡：`role=assistant` 与**其他昵称**的 `user` 均在左侧（`ai` / `peer`）；仅 localStorage 昵称与消息 `nickname` 一致（trim 后）的 `user` 在右侧（`mine`）。改昵称后会全量重绘列表。

## 后端实现要点

- **不走 ThinkCMF、不读 `data/`**：`config.php` + 可选 `config.local.php` 合并；`storage_file` 固定为 `AI_TALK_ROOT/data/room.json`（合并配置时忽略外部传入的 `storage_file`）。
- **`.env`**：解析到内存数组（**禁止 `putenv`**），尝试 `CMF_ROOT` 与 `AI_TALK_ROOT` 下的 `.env`，并合并 `$_ENV` / `$_SERVER['AI_TALK_API_KEY']`。
- **`api.php`**：全流程 `try/catch`；fatal 时 shutdown 仍尽量返回 JSON；`display_errors=0` 且输出 JSON 前清空 output buffer。
- **存储**：`ai_talk_storage_path()` 按序探测可写路径：`config.local.php` 的 `storage_file` → `data/storage.path` 记录 → `data/room.json` → `room.json`（与 index 同级）→ 系统 temp；首次用回退路径时会写入 `data/storage.path` 便于 history/send 一致。单文件 JSON `{ "messages": [ ... ] }`；写 `flock(LOCK_EX)`；读 `LOCK_SH`。
- **智谱响应**：取 `choices[0].message.content`；空则抛错并带上 `error.message`（若有）。
- **无 DB、无 session**：多房间需自行加 `room` 参数与分文件存储。

## 禁止混改

| 错误 | 正确 |
|------|------|
| 配置写到 `data/ai_talk.config.php` | 只用 `public/ai_talk/config.php` / `config.local.php` |
| 把 Key 写进 `index.html` 或前端 JS | `.env` 或 `config.local.php`，仅服务端请求智谱 |
| 接到 `api/wxapp` 并走 `AuthController` | 保持 `public/ai_talk` 独立 |
| 用后台 `ADMIN_ID` 做身份 | 免登录，昵称客户端自报 |

## 排查

| 现象 | 方向 |
|------|------|
| 前端 `Unexpected token '<'` / 非 JSON | PHP Warning 污染响应；查旧版 `putenv`、404 HTML；更新 `bootstrap.php` |
| HTTP 500 | 看 JSON `msg` 或服务器 error log；确认已部署完整 `public/ai_talk/` |
| 未配置 AI_TALK_API_KEY | 根 `.env` 或服务器上 `config.local.php` |
| SSL certificate problem | `AI_TALK_SSL_VERIFY=false` 或对齐 `COS_CURL_SSL_VERIFY` |
| HTTP 429 / 1305 | 智谱限流 |
| 多人看不到彼此消息 | 同一台机上的 `public/ai_talk/data/room.json`；多机需 Redis/DB |
| 发送后无助手回复 | 接口 `msg`；curl 120s 超时；模型名与密钥 |
| 无法打开聊天记录文件 | `data/` 或 `ai_talk/` 对 PHP 用户不可写；chmod 775/777 或 `config.local.php` 设 `storage_file` |

## 扩展建议（未实现）

- 多房间：`?room=` + `public/ai_talk/data/room_{slug}.json`。
- 流式：智谱 `stream:true` + SSE。
- 清空记录：受保护 action 或删 `room.json`。
