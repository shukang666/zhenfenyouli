---
name: prod-db-change-and-deploy-verify
description: "在本机开发环境对生产库做数据变更，并验证「代码是否已部署到服务器」的操作流程。记录用临时 PHP(PDO) 脚本执行 DDL/DML（避开 PowerShell 调 mysql.exe 的坑）、变更后必须回查校验、用 curl 状态码判断服务器上控制器是否存在（302=存在 / 404=不存在），以及 data/ 目录被 git 忽略导致 SQL 与部分配置不会随部署同步的注意事项；并记录用户报「后台改了没生效」时先确认访问环境、用 git 判断是「没开发」还是「已开发未部署」的排查顺序。当需要改生产库数据或表结构、确认改动是否真的生效、或排查「本地改了但线上没变化」时调用。"
---

# 生产库变更与部署验证

## 前提（重要）

本项目**本机开发环境的 `.env` 直连生产库**（`DATABASE_HOSTNAME = 124.223.56.252`），所以：

- 在本地「查库」看到的就是生产数据；在本地「改库」**等于直接改生产**，执行前务必确认清楚。
- 数据库连接信息从根目录 `.env` 读取，key 为：
  `DATABASE_HOSTNAME` / `DATABASE_HOSTPORT` / `DATABASE_DATABASE` / `DATABASE_USERNAME` / `DATABASE_PASSWORD` / `DATABASE_CHARSET`（前缀 `DATABASE_PREFIX = cmf_`）。
- **不要把生产地址/账号写进任何会被提交的代码或配置**。

### `data/` 目录的 git 事实（关键）

`data/.gitignore` 内容为 `*` + `!.gitignore` → **`data/` 下所有内容默认被 git 忽略**，
只有 7 个文件被强制纳入版本管理：

```
data/.gitignore
data/config/database.php
data/config/template.php
data/config/wxapp.php
data/install.lock
data/lang/zh-cn/admin_menu.php
data/route/route.php
```

推论：

- **`data/sql/*.sql` 不会随 git 部署** → 建表 / 改数据的 SQL 必须在生产库**手工执行一次**。
- **`data/config/console.php`（think 命令注册）也被忽略** → 本地改它不会同步到服务器，需在服务器上同步改。
- 反之 `data/config/*.php`（database/template/wxapp）、`data/route/route.php`、`data/lang/zh-cn/admin_menu.php` 是跟踪的，会随部署走。
- `data/runtime/`、`data/runtime_cli/` 也被忽略（缓存目录）。

## 一、改生产库：用临时 PHP(PDO) 脚本

**不要**用 PowerShell 直接调 `mysql.exe`（参数会被截断、`&` 解析不稳，详见 `dev-environment`）。
在 `data/` 下建一个临时脚本（该目录已被忽略，不会误提交），跑完删除：

```php
<?php
// data/_tmp_exec.php  —— 用完即删
$env = parse_ini_file(__DIR__ . '/../.env');
$dsn = "mysql:host={$env['DATABASE_HOSTNAME']};port={$env['DATABASE_HOSTPORT']};"
     . "dbname={$env['DATABASE_DATABASE']};charset={$env['DATABASE_CHARSET']}";
$pdo = new PDO($dsn, $env['DATABASE_USERNAME'], $env['DATABASE_PASSWORD'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$sql = <<<'SQL'
DELETE FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='CronTask';
INSERT INTO `cmf_admin_menu` (`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
VALUES (0,1,1,10000,'admin','Algo','index','','算法中心','flask','说明文案');
SQL;

foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $one) {
    $pdo->exec($one);
}

// 回查校验（关键：不回查就等于没验证）
$rows = $pdo->query("SELECT id,app,controller,action,name,icon FROM cmf_admin_menu WHERE app='admin' AND controller IN ('CronTask','Algo')")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
```

执行：`php data/_tmp_exec.php`（或显式用项目 PHP：`& '<php.exe路径>' data/_tmp_exec.php`）。

约定：

- SQL 写成**幂等**（先 `DELETE` 再 `INSERT` / `IF NOT EXISTS` / `NOT EXISTS` 子查询），重复执行不产生脏数据。
- 每条语句执行后**必须 `SELECT` 回查**并打印，**不要因为 `exec()` 没报错就认为成功**。
- 用完**删除临时脚本**，不在仓库里留垃圾文件。

> MySQL 5.7 限制：不支持 `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`，加字段前先查 `information_schema.COLUMNS` 再动态 `PREPARE`（见 `dev-environment`）。

## 二、验证「代码是否已部署到服务器」

> **用户报「后台没生效 / 你是不是改错页面了」时，先按这个顺序排除，别急着改代码：**
>
> 1. **确认访问环境**：问清用户浏览器是**线上域名**还是**本地 127.0.0.1**。线上不会自动同步本地改动。
> 2. **确认页面归属**：用 URL→控制器→模板规则核对是不是同一个页面（见 `admin-page-locator`）。
> 3. **确认代码状态**：`git log --oneline -1` / `git branch -vv` / `git status`，判断是「**没开发**」还是「**已开发未部署**」。
>    （本项目实例：功能早已在 `dev` 分支提交并 push，用户线上看不到 → 属**未部署**，不是没开发。）
> 4. **缓存**：`Ctrl+F5` 强刷 + 清服务器 `runtime/temp/*`；页面右键"查看源代码"搜关键字，验证模板是否真的更新。

本地改了控制器/模板，但**代码不会自动同步到服务器**。用 HTTP 状态码判断：

```bash
curl -I "https://<域名>/admin/algo/index.html"
```

| 状态码 | 含义 |
|---|---|
| **302**（跳转后台登录页） | 控制器**存在**（被登录拦截）→ 代码已部署 |
| **404** | 控制器**不存在** → 代码没部署，或菜单指向了已删除的控制器 |
| 200 | 页面正常返回（已登录 / 免登场景） |

这正是本次「菜单还在、点进去 404」的定位手法：菜单行在 DB（已生效），但控制器代码没到服务器。

## 三、收尾清单

改完生产库 / 代码后逐项确认：

- [ ] SQL 已在生产库执行，并 `SELECT` 回查确认
- [ ] 代码已部署到服务器（curl 状态码验证）
- [ ] 菜单行与控制器一致（见 `pc-admin-menu-manage`）
- [ ] 服务器侧手工项：系统 crontab、`data/runtime` 缓存、后台角色权限
- [ ] 临时脚本已删除

## 四、本机改动复核（git diff）

改完文件后、交付前，用 git 核对改动是否「干净」：

| 场景 | 命令 | 说明 |
|---|---|---|
| 看整体状态 | `git status --porcelain` | 输出两列：**第 1 位 = index（暂存区）状态，第 2 位 = 工作区状态**。`M ` = 已暂存，` M` = 未暂存 |
| 看未暂存改动 | `git diff` | 改动被 `git add` 过之后**这里会是空的**，别误以为「没改到」 |
| 看已暂存改动 | `git diff --cached` | 上面为空时用它 |
| 看相对 HEAD 的全部改动 | `git diff HEAD` | 交付前最推荐，一次看全 |
| 中文路径 | `git diff -- "小程序/pages/xxx.vue"` | 输出里中文路径显示为八进制转义（`\345\260\217...`），属正常 |

**最小 diff 原则**：交付前 diff 里应当**只有本次的逻辑改动**。常见噪音是文件编码 / BOM 变化（脚本改写导致，见 `dev-environment`），出现时应恢复 BOM 让 diff 干净，而不是把编码差异一起提交。

## 相关 skill

- 环境连接差异与 PowerShell/mysql 坑：`dev-environment`
- 菜单与控制器同步：`pc-admin-menu-manage`
- 后台新增页面：`pc-admin-new-page`
