---
name: cos-storage
description: "腾讯云 COS 全站图床：上传/读 URL、ThinkCMF TencentCos 插件、迁移脚本、小程序拦截器。桶 shukang-budget、键 xcxkf052/ 与 static/。改后台/小程序图片读写、迁移、URL 含多余 /upload/ 时调用。"
---

# 腾讯云 COS（本项目全站图床）

## 桶与 URL 规则（必读）

| 项 | 值 |
|----|-----|
| region | `ap-shanghai` |
| bucket | `shukang-budget-1319203539` |
| 公网根 `public_base_url` | `https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com` |
| **业务上传对象键** | `xcxkf052/...`（与库 `file_path` 一致，**无** `upload/` 段） |
| **打包静态图键** | `static/...`（原 `public/static/`，小程序 `staticAssetBase`） |
| **正确 URL 示例** | `…myqcloud.com/xcxkf052/admin/20250605/xxx.jpeg` |
| **错误 URL** | `…myqcloud.com/upload/xcxkf052/...`（404，需 fix 脚本或 finalize 修） |

密钥与桶名写在项目根 **`.env` 的 `COS_*`**（`data/cos_migrate.config.php` 仅转发读取，勿在 data 里再写一份密钥）。

---

## 运行时架构（读写都走 COS）

### 后端

| 组件 | 作用 |
|------|------|
| **`public/plugins/tencent_cos/`** | ThinkCMF 存储驱动：上传 PUT 到 COS，`getUrl` 返回 COS 公网 URL |
| **`cmf_resolve_storage_setting()`** | `vendor/thinkcmf/cmf/src/common.php`：插件配置齐全时 **默认 TencentCos**（覆盖 Local/Oss） |
| **`cmf_storage_for_assets()`** | 读图/下载/富文本替换用 Storage，不走 Local 单例 |
| **`cmf_get_asset_url()`** | 统一出口：`doGetAssetUrl` + **`cmf_finalize_public_asset_url`**（admin 旧链、去 COS 多余 `/upload/`） |
| **`cmf_get_image_url()` / 后台 `cmf_get_image_preview_url()`** | 已委托 `cmf_get_asset_url` |
| **`vendor/thinkcmf/cmf/src/lib/Upload.php`** | 后台/小程序 **`upload_asset`**：先落盘 `public/upload` 再 **`Storage->upload` → COS**，返回 URL 用 `cmf_get_asset_url` |

启用存储（生产一次）：

```bash
php data/enable_cos_storage.php
```

后台 **设置 → 文件存储** 应为 **腾讯云 COS**；插件配置与 `cos_migrate.config.php` 一致。

### 小程序

| 项 | 位置 |
|----|------|
| 上传接口 | `settings.js` → `uploadUrl`（仍 POST 到 `admin…/api/wxapp/public/upload_asset`，**服务端写 COS**） |
| 读图补全 | `utils/common.js` → `deepResolveAssetUrls`（**禁止**给 COS URL 插入 `/upload/`；`stripErroneousUploadInCosUrl`） |
| 静态前缀 | `staticUploadBase` / `staticAssetBase` → COS 根 |

微信 **downloadFile** 合法域名：`shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com`。

---

## 迁移与修库脚本

| 命令 | 作用 |
|------|------|
| `php data/migrate_to_cos.php` | `public/upload` → COS + 改库 URL |
| `php data/migrate_to_cos.php --db-only` | 只改库 |
| `php data/migrate_to_cos.php --fix-wrong-upload-segment` | 库/COS URL 去掉 `…/upload/xcxkf052/` |
| `php data/sync_static_to_cos.php` | `public/static` → COS 键 `static/` |
| **`php data/cos_put_local_file.php`** | **单文件 / JSON 批量** PUT（切图、static 运营图）；见 [cos-cli-upload.md](cos-cli-upload.md) |
| `php data/migrate_to_cos.php --verify` | 抽测 |

`cos_key_prefix` 迁移配置 **留空**；勿填 `upload/`。

---

## 开发约定

1. **库表**尽量存相对键 `xcxkf052/...` 或 `static/...`，不要存带 `/upload/` 的 COS 全 URL。
2. **接口出参**图片字段用 **`cmf_get_asset_url()`**，不要手拼 `admin…/upload/`。
3. **小程序**不要硬编码 `admin…/upload/`；包内静态用 COS `static/` 或 `staticAssetBase`。
4. 首页 Tab 等有 **TabHomeCache**；改 URL 逻辑后递增 `BasicController` 里 `cacheKey['v']`，或请求 `refresh=1`。
5. 富文本：`cmf_replace_content_file_url` 已用 `cmf_storage_for_assets()`。

---

## 排查

| 现象 | 方向 |
|------|------|
| URL 含 `…cos…/upload/xcxkf052` | 跑 `--fix-wrong-upload-segment`；查小程序 `common.js` 是否发版 |
| 仍 admin/upload | `enable_cos_storage`；插件 Secret 是否为空导致回退 Local |
| 上传成功但读 404 | COS 桶公有读；键是否与 URL 一致（无 upload 前缀） |
| static 图 404 | 跑 `sync_static_to_cos.php` |
| 接口旧 URL | Tab 缓存 `refresh=1` 或清 ThinkPHP cache |

---

## 外勤工作台（Pad 督导监测）

| 项 | 说明 |
|----|------|
| 上传 | `POST /admin/field_worker/upload` → `FieldWorkerController::upload()` → `cmf\lib\Upload` |
| 读图 | `cmf_get_asset_url`；勿用 `site_url/upload` |
| 前端 | `public/themes/admin_fengiy/admin/field_worker/monitor.html` 用接口 `url` + `ASSET_UPLOAD_BASE`（COS 根）兜底 |
| 详情 | 见 **`field-worker-workpad`** skill「图片上传与 COS」 |

废弃页 `public/field-worker.html` 若仍使用，应走 `/wxapp/public/upload_asset`（同样经 Upload 写 COS），勿单独落本地。

---

## 相关文件

- `data/enable_cos_storage.php` — 写 `cmf_option.storage` + 插件 config
- `data/cos_upload_lib.php` — 上传签名
- `data/cos_put_local_file.php` — CLI 上传本地文件（[cos-cli-upload.md](cos-cli-upload.md)）
- `.cursor/skills/mp-conventions/image-url-complete.md` — 接口补全字段约定（COS 时代）
