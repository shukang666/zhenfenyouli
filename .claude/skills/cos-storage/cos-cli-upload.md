---
name: "cos-cli-upload"
description: "本机 CLI 将任意本地图片/文件 PUT 到腾讯云 COS（不经过后台表单）。用于运营切图、小程序 static 资源、一次性迁移。"
---

# CLI 上传本地文件到 COS

桶、键前缀、`public_base_url` 见 [SKILL.md](SKILL.md)。密钥只读项目根 **`.env` 的 `COS_*`**（经 `data/cos_migrate.config.php` 加载），**勿**写进 skill 或提交到 git。

## 脚本

| 文件 | 作用 |
|------|------|
| `data/cos_upload_lib.php` | 签名 + `cos_put_object_file()` |
| `data/cos_put_local_file.php` | **单文件 / JSON 批量**上传 |
| `data/sync_static_to_cos.php` | 整目录 `public/static` → `static/` |

本机 PHP（phpstudy，不在 PATH）：

```powershell
cd d:/phpstudy_pro/WWW/xiayuxuan.com
d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe data/cos_put_local_file.php --help
```

## `cos_put_local_file.php` 用法

### 单文件 + 指定对象键（推荐：小程序打包静态图）

对象键用 **`static/...`**，与 `settings.js` 的 `staticAssetBase`（`…/static/`）一致。

```powershell
d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe data/cos_put_local_file.php `
  --key=static/spot-modules/example.png `
  "小程序/pages/临时/example.png"
```

成功行格式：`ok\t对象键\t公网 URL`。若远程已存在且大小相同会 `skip`（加 `--force` 强制覆盖）。

大于 512KB 的文件默认在 **stderr 显示单行进度条**（`[====----] 42%  240.0/564.0 MB`）；不需要时用 `--no-progress`。

### 不指定 `--key`（后台业务图风格）

自动生成：`xcxkf052/admin/YYYYMMDD/{md5}.{ext}`，与库表 `file_path` 习惯一致；小程序读时用 `resolveAssetShowUrl` / `resolveHomeHotThumb`。

```powershell
d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe data/cos_put_local_file.php "path/to/photo.jpg"
```

### 批量（JSON 映射）

```json
[
  { "local": "小程序/pages/临时/a.png", "key": "static/foo/a.png" },
  { "local": "public/static/img/b.png", "key": "static/img/b.png" }
]
```

```powershell
d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe data/cos_put_local_file.php --map=data/your_map.json
```

`--dry-run` 只打印键与 URL，不 PUT。

## 小程序引用约定

| 对象键 | 前端写法 |
|--------|----------|
| `static/...` | `settings.staticAssetBase + '相对路径'`（无重复 `static/` 段） |
| `xcxkf052/...` | 存相对键字符串，`resolveHomeHotThumb(thumb)` 或 `$u.func.resolveAssetUrl()` |

**不要**在代码里 `require()` 大体积运营图；上传 COS 后删 `pages/临时` 引用，减主包/分包体积。

## 自检

1. 浏览器或 `curl -I` 打开脚本输出的 URL，应 **200**。
2. 桶需公有读（与线上一致）；403/404 查键是否多了 `/upload/` 段。
3. 改 Tab 首页缓存相关资源时，按需 `refresh=1` 或递增 Tab 缓存版本（见 SKILL.md）。

## 与后台上传的关系

| 方式 | 何时用 |
|------|--------|
| 后台 / `upload_asset` | 运营在后台、小程序用户上传 |
| **`cos_put_local_file.php`** | 开发机切图、批量 static、脚本迁移 |
| **`sync_static_to_cos.php`** | 已放进 `public/static` 的全量同步 |
