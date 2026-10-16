---
name: pc-admin-update-log
description: "PC 后台「设置 → 更新记录」只读页面（cmf_update_log 表）的维护指南。核心写入规则：**每次改动（一次提交 / 一个任务）都要往 cmf_update_log 追加一条记录，无需用户提醒**；只有用户明确说「这次不要记、我来写」才跳过；只改 .codebuddy/ 下的 skill、注释、临时调试脚本不算面向用户的改动。含写前查重（历史是给用户看的，重复即噪音）、粒度约定（一条记录≈一次提交，宁可合并不要拆碎）、update_type 选取、写完必须回查。含模板标签陷阱：<if condition=\"...\"> 等以 > 结尾的标签，属性值里不能出现字面的 >（$list->isEmpty()、>= 都会导致 ParseError: unexpected '='），分页空态要用 count($list) eq 0，比较大小用 gt/lt。记录表结构、控制器/模板/菜单归属，以及最关键的「开发方（AI）如何直接把更新记录写进数据库」——含 update_type 取值、create_time 时间戳、中文编码必须走 PHP/PDO 而不能用 PowerShell 直连 mysql.exe、content 纯文本换行约定，以及 ThinkCMF 模板引擎默认 default_filter=htmlentities 的自动转义规则（模板只写 {$vo.content}，切勿再加 htmlspecialchars，否则双重转义成 &lt;pre&gt;）。当需要新增/查看更新记录、往 cmf_update_log 写数据、或排查「更新记录不显示」「中文乱码」「换行丢失」「尖括号显示成 &lt;pre&gt;」时调用。"
---

# PC 后台「更新记录」（设置 → 更新记录）

## 一句话

这是一个**只读**页面：记录由开发方（AI）**直接写数据库**，后台不提供新增 / 编辑 / 删除。
页面只负责「展示 + 类型筛选 + 关键词搜索 + 分页」。

所以「写了记录但页面看不到」几乎都是**数据没写进去**或**代码没部署**，不是页面本身的问题。

---

## ✅ 写入规则：**每次改动都要追加一条记录**

**默认动作**：每完成一次改动（一次提交 / 一个任务），就往 `cmf_update_log` **追加一条**记录。
**不需要用户再提醒，也不需要用户说「写一条更新记录」——自己写。**

| 情形 | 是否写 |
|---|---|
| 完成一次功能开发 / 修 bug / 优化 / 重构（≈ 一次提交或一个任务） | ✅ **必须写** |
| 只改了 `.codebuddy/` 下的 skill 文档、代码注释、一次性调试脚本 | ❌ 不写（不是面向用户的变化） |
| 用户明确说「这次不要记」/「我自己写」 | ❌ 跳过 |
| 同一次改动里的多个小步骤 | ✅ 合并成**一条**，不要写多条 |
| 用户说「把那条记录的文案改一下 / 删掉」 | ✅ 按用户说的改 / 删 |

**粒度**：一条记录 ≈ 一次提交。宁可合并，不要拆碎；也不要把几次不相关的改动凑成一条。

**写之前先查重**：先 `SELECT id,title,update_type,create_time FROM cmf_update_log ORDER BY create_time DESC LIMIT 10`，
同一件事已经写过就不要再写。历史记录是给用户看的，重复就是噪音。

**写完必须回查**：`SELECT` 一下刚插入的那行（id / title / update_type / create_time / content），
确认中文没乱码、时间戳有值、排序能排到最前。

> 本地 `.env` 连的就是**生产库**（`124.223.56.252`），所以「写进库」= 直接发布，
> 不需要再做什么部署动作。

> 为什么规则改成了「默认都写」：这张表的用途是**对外更新说明**，漏记一次改动，
> 用户就看不出后台到底变了什么。所以默认写、写完回查；
> 唯一需要克制的是**别重复写**（见上「查重」）。

---

## 组成一览

| 件 | 位置 |
|---|---|
| 控制器 | `app/admin/controller/UpdateLogController.php` |
| 模板 | `public/themes/admin_fengiy/admin/update_log/index.html` |
| 菜单 | `cmf_admin_menu` 里 `controller='UpdateLog'`，挂在**设置**（`Setting` / `default`，id=6）下，`list_order=60` |
| 数据表 | `cmf_update_log` |
| 建表 + 菜单 SQL | `data/sql/update_log.sql`（**`data/sql/` 被 git 忽略，不会随部署上传，必须在生产库手工执行一次**） |

页面地址：`/admin/update_log/index.html`

---

## 表结构 `cmf_update_log`

| 字段 | 类型 | 说明 |
|---|---|---|
| `id` | int(11) 自增 | 主键 |
| `version` | varchar(50) | 版本号，可空（空则页面显示 `—`） |
| `title` | varchar(200) | 更新标题（一句话说明改了啥） |
| `update_type` | varchar(20) | 类型 key，见下表 |
| `content` | text | 更新详情，**纯文本**，可含换行 |
| `create_time` | int(11) | **时间戳**（不是日期字符串），列表按它倒序 |

`update_type` 取值（映射写在控制器的 `TYPES` / `TYPE_CLASSES` 常量里）：

| key | 页面标签 | 颜色 |
|---|---|---|
| `feature` | 功能新增 | 绿 `label-success` |
| `optimize` | 体验优化 | 蓝 `label-info` |
| `fix` | 问题修复 | 橙 `label-warning` |
| `refactor` | 结构调整 | 灰 `label-default` |
| 空串 / 其他未知值 | 其他 | 灰 |

想加新类型：改控制器里的两个常量即可（**不用动表结构**）。

---

## 写记录（核心：AI 怎么把更新写进库）

### 推荐方式：临时 PHP 脚本 + PDO（中文安全）

在 `data/` 下建临时脚本（该目录被 git 忽略），跑完删除：

```php
<?php
$env = parse_ini_file(__DIR__ . '/../.env');
$pdo = new PDO(
    "mysql:host={$env['DATABASE_HOSTNAME']};port={$env['DATABASE_HOSTPORT']};dbname={$env['DATABASE_DATABASE']};charset=utf8mb4",
    $env['DATABASE_USERNAME'], $env['DATABASE_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->prepare(
    "INSERT INTO cmf_update_log (version, title, update_type, content, create_time) VALUES (?,?,?,?,?)"
);
$stmt->execute([
    '',                                    // version，可空
    '后台新增 XXX 功能',                    // title
    'feature',                             // update_type
    "第一行说明\n\n· 要点一\n· 要点二",      // content：用真实 \n 换行
    time(),                                // create_time：时间戳
]);
```

执行：

```powershell
cd d:/phpstudy_pro/WWW/xiayuxuan.com; d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe data/_tmp_x.php 2>$null
```

### 绝对不要做的事

| 错误做法 | 后果 |
|---|---|
| PowerShell 里 `mysql.exe -e "INSERT ... 中文 ..."` | **中文乱码入库**（终端编码链路问题，见 `dev-environment`） |
| 连接串漏掉 `charset=utf8mb4` | 中文变 `???` |
| `create_time` 写日期字符串 `'2026-09-11'` | 字段是 int 时间戳，会存成 `0` 或截断，排序全乱 |
| content 里写字面 `\n`（反斜杠 + n） | 页面显示成 `\n` 而不是换行 |
| 直接用 `INSERT` 但没加 `LIMIT` 的探查 SQL 在大表上跑 | 拖慢生产库（本功能表小，影响有限，但保持习惯） |

### 怎么填 `content` 才好看

页面用 `<pre>` 原样渲染，所以：

- **换行直接写 `\n`**（PHP 双引号字符串里）→ 页面真的换行
- 段落之间空一行更好读
- 项目惯例：首行一句话结论，空行后用 `· ` 开头的要点列表
- `· ` 是中文间隔号加空格，比 `-` 更符合现有记录风格
- **必须写「用户会在界面上感知到什么」**：新入口在哪、点了会看到什么、
  以及**会让用户困惑的新提示/新行为**（例：「旧页面点删除会提示未通过安全校验，刷新即恢复」）。
  这类"副作用说明"不写，用户就会把它当成 bug 来报。

---

## 页面展示的转义规则（「文本格式转换」的关键）

**ThinkCMF 的模板引擎默认就会转义变量输出** —— 这是本项目模板最容易踩的坑。

`vendor/thinkcmf/think-template/src/Template.php`：

```php
// 第 62 行：默认过滤方法，用于普通标签输出
'default_filter' => 'htmlentities',

// parseVarFunction()：只要没写 |raw，就无条件追加 default_filter
} elseif ($autoescape && !preg_match('/\|(\s)?raw(\||\s)?/i', $varStr)) {
    $varStr .= '|' . $this->config['default_filter'];
}
```

推论（**三种写法的实际结果**）：

| 模板写法 | 实际执行 | 结果 |
|---|---|---|
| `{$vo.content}` | `htmlentities($content)` | ✅ 已自动转义，**正确写法** |
| `{$vo.content\|htmlspecialchars}` | `htmlentities(htmlspecialchars($content))` | ❌ **双重转义**：`<pre>` 会显示成 `&lt;pre&gt;` |
| `{$vo.content\|raw}` | 原样输出 | ⚠️ 不转义，**有 XSS 风险，不要用** |

**所以本项目模板的约定是：直接写 `{$vo.content}`，不要手动加任何转义函数。**

因此 content 里写 `<pre>` 这类尖括号，页面会显示成**字面的 `<pre>`**（被自动转义），这是预期行为，不是 bug。

### 保留换行与缩进靠 CSS，不靠模板函数

```html
<pre class="upd-content">{$vo.content}</pre>
```

```css
.upd-content { white-space: pre-wrap; word-wrap: break-word; }
```

- `white-space: pre-wrap` → 保留换行与空格，同时长行自动折行（不撑破表格）
- **不要**用 `nl2br`：它插入的 `<br>` 会被自动转义，结果把 `<br />` 当文本显示出来

控制器里已经保证 `title` / `content` / `version` 为 null 时转成空字符串，避免 PHP 7.4 下 `htmlentities(null)` 告警。

---

## ⚠️ 模板标签陷阱：`<if condition="...">` 里不能出现字面的 `>`

**这个坑真实炸过一次**（更新记录页直接把整页打挂）。当时写的是

```html
<if condition="$list->isEmpty()">
```

编译出来的 PHP 是：

```php
<?php if(condition="$list-): ?>isEmpty()">
```

页面报 `ParseError: syntax error, unexpected '='`。

原因在引擎的标签正则（`vendor/thinkcmf/think-template/src/template/TagLib.php::getRegex()`）：

```php
// taglib_begin='<'、taglib_end='>' 都是单字符，走 $single 这一支：
$regex = $begin . '(' . $tagName . ')\b(?>[^' . $end . ']*)' . $end;
// 实际是 /<(if)\b(?>[^>]*)>/is
```

`[^>]*` 匹配到**第一个 `>`** 就停止，而 `->` 里的那个 `>` 正好就是它遇到的第一个 `>` → 标签被从中间切断。

**推论：任何以 `>` 结尾的标签（`<if>` / `<volist>` / `<foreach>` / `<include>` …），属性值里都不能出现字面的 `>`**，包括 `->` 和 `>=`。

### 安全写法对照（实测编译产物）

| 写法 | 编译产物 | 结果 |
|---|---|---|
| `$list->isEmpty()` | `if(condition="$list-): ?>isEmpty()">` | ❌ 语法错误 |
| `$a >= 1` | 同样在 `>` 处截断 | ❌ 语法错误 |
| `count($list) eq 0` | `if(count($list) == 0):` | ✅ |
| `$page gt 1` | `if($page > 1):` | ✅ **安全**——`eq/gt/lt/egt/elt` 是「先取完整标签、再替换成 `==/>/</>=/<=`」，所以条件里写 `gt` 不会有字面 `>` |
| `$isEmpty`（控制器先 assign 布尔） | `if($isEmpty):` | ✅ 最稳 |

> 判断口诀：**要在模板条件里比较大小，就用 `gt` / `lt` / `egt` / `elt` 这些关键字，不要写字面的 `>`。**

### 验证方法（不需要登录后台）

`think\Template::parse()` 是 public 的，可以离线编译再用 `php -l` 判定：

```php
<?php
require 'vendor/autoload.php';

$tpl = new \think\Template([
    'taglib_begin'    => '<',
    'taglib_end'      => '>',
    'taglib_build_in' => 'cx',
    'taglib_load'     => false,
]);

$content = '<if condition="count($list) eq 0">EMPTY</if>';
$tpl->parse($content);
file_put_contents('_check.php', $content);
```

```powershell
d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe _check.php    # 输出编译产物
d:/phpstudy_pro/Extensions/php/php7.3.4nts/php.exe -l _check.php # 判定语法
```

**校验整个模板文件**时，先去掉需要主题路径解析的 `<include file="public@header"/>` 行再 parse，然后 `php -l`，
可以一次性查出这个模板里所有标签的编译问题。

---

## 分页列表的「暂无数据」

`<empty name="list">` 对 Paginator **永远为 false**（`empty(对象)` 恒为假），
但**也不能**用 `<if condition="$list->isEmpty()">`（见上一节，会编译失败）。

正解（分页对象 `implements Countable`，`count()` 返回当前页条数，语义与 `isEmpty()` 一致）：

```html
<if condition="count($list) eq 0">
    <tr><td colspan="5" class="upd-empty">暂无更新记录</td></tr>
</if>
<volist name="list" id="vo">
    <tr> ... </tr>
</volist>
```

`<volist>` 本身能正确识别 Paginator（空集合不输出行），所以它不用改；
只有**要额外显示空态提示**时才需要上面那个 `<if>`。

---

## 排查

| 现象 | 原因 |
|---|---|
| 页面 404 | 控制器没部署到服务器（本地改了 ≠ 线上有了，见 `prod-db-change-and-deploy-verify`） |
| 报「模板文件不存在」 | 模板路径/文件名不对（应为 `admin/update_log/index.html`） |
| 页面能开但侧栏没「更新记录」 | `cmf_admin_menu` 没插行 / `status=0` / 非超管未授权（超管 id=1、14 免检） |
| 表不存在 | `data/sql/update_log.sql` 没在生产库执行 |
| 写了记录但列表看不到 | 十有八九是**写到了别的库**，或 `create_time` 为 0 排到了最后；先 `SELECT COUNT(*) FROM cmf_update_log` 确认 |
| 中文乱码 | 写入走的是 `mysql.exe` / 连接串缺 `utf8mb4`，改用上面的 PDO 脚本 |
| 换行丢了 | content 里写的是字面 `\n`，不是真实换行 |
| 尖括号显示成 `&lt;pre&gt;` 这种 | **双重转义**：模板里多写了一层 `\|htmlspecialchars`，而引擎已自动 `htmlentities` → 删掉多余的那层 |
| 尖括号在页面上显示成字面的 `<pre>` | 正常，这是引擎自动转义的预期结果 |
| **报 `ParseError: syntax error, unexpected '='`**，编译产物里能看到 `if(condition="$list-)` | `<if condition>` 属性里写了字面的 `>`（典型是 `$list->isEmpty()`）→ 改成 `count($list) eq 0`；要比较大小用 `gt`/`lt`。详见上文「模板标签陷阱」 |
| 列表为空却不显示「暂无xx」提示 | `<empty name="list">` 对 **Paginator 对象永远为 false**（`empty(对象)` 恒为假）→ 用 `<if condition="count($list) eq 0">`；**不要**用 `$list->isEmpty()`（会编译失败） |
| 模板改了但页面还报旧错 | 模板编译缓存在 `{runtime}/temp/*.php`（`think-view` 用 `$app->getRuntimePath() . 'temp/'`，文件名是 `md5(...)`）。正常情况下源模板 mtime 更新会自动重编译；仍报错就删掉该目录的编译产物 |

---

## 相关 skill

- 新增后台页面的完整流程：`pc-admin-new-page`
- 菜单增删改与 404 排查：`pc-admin-menu-manage`
- 本机查库 / 临时 PHP 脚本写法：`thinkcmf-cli-script`、`dev-environment`
- 生产库变更 + 部署验证：`prod-db-change-and-deploy-verify`
- 移动端适配：`pc-admin-mobile-responsive`
- 删除二次校验：`pc-admin-delete-confirm`
