---
name: thinkcmf-cli-script
description: "在命令行用 PHP 引导 ThinkCMF 框架跑临时脚本（查库、查菜单、批量数据处理）的模板。记录本机 php.exe 绝对路径（php 不在 PATH）、require vendor/autoload + new think\\App + initialize 的引导写法、会刷 APP_PATH/WEB_ROOT/CMF_DATA 未定义常量 Warning（可忽略）、临时脚本用后删除，以及 PowerShell 执行注意事项。当需要在本机用命令行跑一次性 PHP 脚本查库/查数据、验证字段或排查数据问题时调用。"
---

# ThinkCMF 命令行临时脚本

## 适用场景

需要在本机命令行跑一次性 PHP 脚本（查库取数、查菜单树、批量处理数据、验证字段）时使用。
比 PowerShell 直接调 `mysql.exe` 稳定得多（后者的坑见 `dev-environment`）。

---

## 一、本机 php 可执行文件（重要）

- 本机 **`php` 不在 PATH**，直接执行 `php xxx.php` 会报
  `无法将"php"项识别为 cmdlet...`。
- 本机 phpstudy 的 PHP 扩展目录实测只有：`d:/phpstudy_pro/Extensions/php/php7.3.4nts/`
- 所以命令行必须用**绝对路径**：

```powershell
cd d:/phpstudy_pro/WWW/xiayuxuan.com; d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe _tmp_script.php
```

> PowerShell 用 `;` 分隔命令（不能用 `&&`）。

---

## 二、引导模板（查库 / 查菜单）

```php
<?php
// 临时脚本，放在项目根目录或 data/ 下，用完删除
require __DIR__ . '/vendor/autoload.php';

$app = new \think\App();
$app->initialize();

// 之后可用 think\facade\Db 查库（会自动读 .env 的数据库配置）
$rows = think\facade\Db::name('admin_menu')
    ->where('name', 'like', '%打卡%')
    ->field('id,parent_id,app,controller,action,name')
    ->select()->toArray();

foreach ($rows as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo '----- done -----' . PHP_EOL;
```

要点：

- `require vendor/autoload.php` 后 `new \think\App()` + `$app->initialize()`，框架即完成引导，后续用 `think\facade\Db` 门面查库。
- 表名用 `Db::name('xxx')` 时**不带 `cmf_` 前缀**（前缀由配置自动加），查原生表名用 `Db::query('SHOW COLUMNS FROM cmf_xxx')`。
- 数据库连接由 `.env` 自动决定（本机连的是生产库 `124.223.56.252`，见 `dev-environment`），**查询务必加 `LIMIT`**。

---

## 三、会出现的 Warning（可忽略）

引导时会刷一批未定义常量警告，**不影响执行**：

```
Warning: Use of undefined constant APP_PATH ...
Warning: Use of undefined constant WEB_ROOT ...
Warning: Use of undefined constant CMF_DATA ...
```

想减少噪音可加 `2>$null`（PowerShell）：

```powershell
d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe _tmp_script.php 2>$null
```

---

## 四、约定

- 临时脚本**用完即删**，不在仓库里留垃圾文件。
- 放 `data/` 目录最安全（该目录被 git 忽略，不会误提交），但注意 `data/` 下脚本的相对路径（`__DIR__ . '/../vendor/autoload.php'`）。
- 执行前 `cd` 到项目根目录，确保 `vendor/autoload.php`、`.env` 能找到。

---

## 相关 skill

- 环境连接差异、PowerShell / mysql.exe 坑：`dev-environment`
- 后台页面定位（查菜单树的实际用途）：`admin-page-locator`
- 生产库变更与部署验证：`prod-db-change-and-deploy-verify`
