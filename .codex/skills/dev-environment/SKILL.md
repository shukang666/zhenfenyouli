---
name: dev-environment
description: "本机开发环境与生产库操作约定。覆盖远程连库（本机 124.223.56.252、服务器 127.0.0.1）、PHP 7.4 / MySQL 5.7、php.exe 绝对路径、PowerShell 坑、ThinkCMF 临时脚本引导、生产库 DDL/DML 与部署是否生效的验证、data/ 被 git 忽略。当本机查库、跑一次性 PHP 脚本、改生产数据、验证后台改动未生效、或 SQL 语法报错时使用。"
---

# 开发/生产环境数据库连接差异

ThinkCMF 命令行临时脚本全文：[thinkcmf-cli-script.md](thinkcmf-cli-script.md)  
改生产库并验证代码是否已部署：[prod-db-change-and-deploy-verify.md](prod-db-change-and-deploy-verify.md)

## 适用场景

当需要在本机（开发环境）直接读取/查询数据库以排查数据问题、验证字段、确认关联关系时使用。代码运行时（无论是本地 PHP 跑起来还是生产服务器跑）由项目的 `.env` / config 自动决定连接地址，本 skill 仅用于 **agent 主动查库取数** 的场景。

## 运行时版本（关键）

| 组件 | 版本 | 影响 |
|------|------|------|
| **PHP** | **7.4** | 语法上限 PHP 7.4，不能用 PHP 8.x 特性（如 `readonly`、`enum`、` mixed` 类型、`str_contains` 等）；ThinkCMF 6 的写法兼容 |
| **MySQL** | **5.7.44** | **不支持** `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`（该语法需 MySQL ≥ 8.0.1）。涉及加字段/改字段前，需先 `SELECT information_schema.COLUMNS` 判断是否存在，再用动态 PREPARE 执行（参考 `data/sql/order_trial_enhance.sql` 的写法） |

遇到"SQL 语法错误"时，优先排查是否误用了 MySQL 8+ 才支持的语法。

### 本机 PHP 可执行文件（命令行跑脚本时用）

- **`php` 不在 PATH**，直接敲 `php xxx.php` 会报 `无法将"php"项识别为 cmdlet...`。
- 本机 phpstudy 扩展目录实测只有 `d:/phpstudy_pro/Extensions/php/php7.3.4nts/`，命令行需用**绝对路径**：

  ```powershell
  cd d:/phpstudy_pro/WWW/xiayuxuan.com; d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe xxx.php
  ```

- 用命令行引导 ThinkCMF 跑临时脚本的写法见 [thinkcmf-cli-script.md](thinkcmf-cli-script.md)。

## 连接地址差异（关键）

| 环境 | MySQL 主机 | 库名 / 用户名 / 密码 |
|------|-----------|---------------------|
| 生产服务器 | `127.0.0.1`（服务器本机 MySQL） | 与项目 `.env` 配置一致，不变 |
| 本地开发机 | **`124.223.56.252`**（远程连生产库；本机 `127.0.0.1` 不开 MySQL） | 库名/用户名/密码与生产一致，不变 |

## 约束

- 本机开发环境 **不能** 用 `127.0.0.1` 或 `localhost` 连 MySQL（本机未启动 MySQL 服务），必须用远程地址 `124.223.56.252`。
- 生产服务器上代码仍按 `127.0.0.1` 连接（数据库部署在同一台机器上），**不要** 改项目 `.env` 文件里的连接地址，否则上线后会影响生产。
- 该远程地址仅用于本地取数验证，**不要** 写进任何提交的代码或配置文件中。
- 连接库时如需密码/库名，从项目根目录的 `.env` 文件读取（不要把账号密码写进本 skill）。

## 取数工具偏好（推荐：临时 PHP 脚本）

**首选：写一个临时 PHP(PDO) 脚本放 `data/` 下跑，跑完删除。**
理由：`data/` 被 git 忽略（不会误提交），且 PDO 能直接读 `.env`，稳定可靠。

```php
// data/_tmp_query.php —— 用完即删
$env = parse_ini_file(__DIR__ . '/../.env');
$pdo = new PDO(
    "mysql:host={$env['DATABASE_HOSTNAME']};port={$env['DATABASE_HOSTPORT']};dbname={$env['DATABASE_DATABASE']};charset=utf8mb4",
    $env['DATABASE_USERNAME'], $env['DATABASE_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
foreach ($pdo->query('SELECT ... LIMIT 50') as $row) { print_r($row); }
```

**不要**用 PowerShell 直接调 `mysql.exe`，实测坑很多：

| 坑 | 现象 | 规避 |
|---|---|---|
| `& '路径'` 调外部 exe | `&` 解析不稳定，报「此时不应有 &。」 | 改用临时 PHP 脚本，或 `cmd /c "..."` |
| `-h124.223.56.252` 紧贴写法 | 参数被**截断成 `-h 124`**（连到错误主机） | 用长参数：`--host=IP --port=3306 --user=U --password=P --database=D` |
| 多语句 `-e "A; B"` | 执行不可靠 / 只跑第一条 | 一条一条执行，或走 PDO |
| `\G` 结束符 | Windows 下不生效 | 用常规 `SELECT ... ;` 输出 |

其它：
- 大表查询务必加 `LIMIT`，避免拖慢生产库。
- 执行完**必须回查**（把 `SELECT` 结果打印出来）确认改动生效，详见 [prod-db-change-and-deploy-verify.md](prod-db-change-and-deploy-verify.md)。

## 开发机终端环境（PowerShell）

本机默认终端是 **PowerShell**，不是 `cmd.exe`，存在以下约束：

| 问题 | 说明 | 正确写法 |
|------|------|---------|
| `&&` 不识别 | 传统 cmd 的 `&&` 链式命令在 PowerShell 中无效 | 用 `;` 分隔命令：`cd /d some/dir; command` |
| `>` / `"` 转义 | PowerShell 对 `>` `"` 等字符易误解析 | 单引号包裹；`2>$null` 抑制错误 |
| `findstr /c:"xxx"` 中文乱码 | 中文编码问题 | 直接用 PowerShell `Select-String` 等 |
| `head` / `tail` 不存在 | Unix 命令报 `无法将"head"项识别为 cmdlet...` | 改用 `Select-Object -First N` / `-Last N` |
| `net start xxx` 中文输出 | 可能乱码但不影响执行 | 用 `; "done"` 之类确保结束 |

### 用 PowerShell 脚本批量改写项目文件（三个坑）

| 问题 | 现象 | 正确做法 |
|------|------|---------|
| **`$` 变量被吞** | 直接在工具里跑 `powershell -Command "... $p ..."`，`$p`/`$c` 被外层 shell 展开为空，报「表达式缺少 / 意外的标记」类解析错误 | 把脚本写入临时 `.ps1` 文件，再 `powershell -NoProfile -ExecutionPolicy Bypass -File xxx.ps1` 执行，**用完删除脚本** |
| **写回丢 BOM** | `[System.IO.File]::WriteAllLines/WriteAllText` 默认按 UTF-8 **无 BOM** 写，把项目里带 BOM 的文件写坏，`git diff` 首行出现 BOM 差异 | 显式传编码 `New-Object System.Text.UTF8Encoding($true)`；或字节级补 `EF BB BF` |
| **读文件剥 BOM** | `[System.IO.File]::ReadAllText` 会自动剥掉 BOM，用 `$c.StartsWith([char]0xFEFF)` 判断「有没有 BOM」**永远为 false**，判断失效 | 用 `ReadAllBytes` 检查前 3 字节是否为 `0xEF,0xBB,0xBF` |

字节级判断 / 补 BOM 的可靠写法：

```powershell
$p = 'd:\...\some.vue'
$bytes = [System.IO.File]::ReadAllBytes($p)
if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
    'BOM already present'
} else {
    $out = New-Object byte[] ($bytes.Length + 3)
    $out[0] = 0xEF; $out[1] = 0xBB; $out[2] = 0xBF
    [System.Array]::Copy($bytes, 0, $out, 3, $bytes.Length)
    [System.IO.File]::WriteAllBytes($p, $out)
    'BOM restored'
}
```

本项目 `小程序/*.vue` 等文件多为 **UTF-8 with BOM**，脚本改写后务必回 `git diff` 确认没引入 BOM 噪音（复核方法见 `prod-db-change-and-deploy-verify` 的「本机改动复核」）。

## 小程序源码目录被系统级加密时的排查方法

`小程序/pages` 等源码目录在本机被**系统级加密保护**（工具读取失败/不可读）时的替代分析路径：

| 手段 | 说明 |
|------|------|
| **读编译产物** | 改用 `小程序/unpackage/dist/dev/mp-weixin/` 下的 `.js` / `.wxml`（HBuilderX / 微信开发者工具编译输出，与源码一一对应），足以还原页面 `data`、`methods`、模板绑定 |
| **查 git 对象库** | git 仓库不受目录加密影响，`git log` / `git show <commit>:<path>` 可读历史版本，用于判断某功能是「**从未实现（断链）**」还是「**最近被改坏**」（对比初始提交） |

> 编译产物是**快照**：能改源码时优先改源码，本方法主要用于「源码读不到」或「确认功能何时/是否被引入」的排查场景。

## `data/` 目录与 git（部署关键）

`data/.gitignore` 内容为 `*` + `!.gitignore` → **`data/` 下所有内容默认被 git 忽略**，
仅以下 7 个文件被强制纳入版本管理：

```
data/.gitignore
data/config/database.php
data/config/template.php
data/config/wxapp.php
data/install.lock
data/lang/zh-cn/admin_menu.php
data/route/route.php
```

推论（都是踩过的坑）：

- **`data/sql/*.sql` 不会随部署上传** → 所有建表 / 菜单 / 数据变更 SQL 必须在**生产库手工执行一次**。
- **`data/config/console.php`（think 命令注册）也被忽略** → 本地改它不会同步到服务器；
  命令注册应放在 git 跟踪的 `app/<app>/command.php`（控制台会自动扫描），不要依赖 console.php。
- `data/runtime/`、`data/runtime_cli/` 也是被忽略的缓存目录。
- 反过来，`data/route/route.php`、`data/lang/zh-cn/admin_menu.php`、`data/config/*.php` 是跟踪的，会随部署生效。

## 重要的事

- 可以在 skill / memory / 文档里写明文数据库账号密码，以为这个已被git取消监控，只有程序员能看见。
