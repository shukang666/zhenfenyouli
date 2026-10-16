---
name: pc-admin-dashboard
description: "PC 后台首页（仪表盘 / 运营数据看板）总览。记录 /admin/main/index 这个地址为什么不能改、如何用项目 app/ 下的 MainController 覆盖 vendor 同名控制器（PSR-4 查找顺序：项目 app/ 优先）、模板 public/themes/admin_fengiy/admin/main/index.html 的 8 个模块结构、UNION ALL 聚合 + 60 秒缓存的统计口径、本地 ECharts 与自建 .dash-flex 栅格、parent.openapp() 开标签页约定，以及左侧『首页』改名『仪表盘』时 index/index.html 的三处联动点。当需要修改后台首页看板指标/图表/快捷入口，或排查『首页数字和列表对不上』『看板变慢』『图表空白』『快捷入口在 iframe 里跳走了』『首页标签名改了没生效』时调用。"
---

# PC 后台首页（仪表盘 / 运营数据看板）

## 定位一览

| 项 | 值 |
|---|---|
| 访问地址 | `/admin/main/index`（后台外壳 iframe 里的首页，**不能改**） |
| 控制器 | `app/admin/controller/MainController.php`（**覆盖** vendor 里的同名类） |
| 模板 | `public/themes/admin_fengiy/admin/main/index.html` |
| 外壳/侧栏 | `public/themes/admin_fengiy/admin/index/index.html` |
| 缓存 | `admin_home_dashboard_v1`，TTL 60 秒 |

## 一、三个"不能动"的约束

1. **地址必须保持 `/admin/main/index`**
   外壳 `index/index.html` 里 iframe 地址写死；框架 `adminindex.js` 也是靠 `"/admin/main/index"` 识别"这是首页"，换地址会导致"首页要点两下才能进"。
2. **不要给首页新增菜单、不要配权限**
   该地址在框架免授权白名单内（`adminMainindex`），**所有后台角色（含非超管）都能直接看到**。它不属于 `cmf_admin_menu` 体系。
3. **不要改 vendor**
   `vendor/composer/autoload_psr4.php` 里 `app\` 前缀的查找顺序是「项目 `app/` 在前、vendor 在后」，所以同名类优先加载项目里的文件。改 vendor 会在下次 composer 更新时被覆盖。

```2:13:app/admin/controller/MainController.php
<?php
// +----------------------------------------------------------------------
// | 后台首页（欢迎页 → 运营数据看板）
// +----------------------------------------------------------------------
// | 说明：
// | 1. 本文件覆盖 vendor/thinkcmf/cmf-app/src/admin/controller/MainController.php。
```

## 二、控制器要点（`MainController`）

- `index()`：读 `refresh` 参数 → `collectStats($refresh === 1)` → assign 后 `fetch()`。
  额外 assign：`greet`（问候语，按时段）、`date_text`、`from_cache`。
- `collectStats(bool $forceRefresh)` 是 **`public static`**，刻意留出命令行/其他入口复用与**对数字验证**的口子（见本文末"验证手段"）。
- 缓存读取与写入都用 `try/catch` 包住：缓存目录不可写**不能**影响看板本身（读不到就实时算，写不进就算了）。
- `dashboardWidget()` 是框架自带方法，插件市场会调，**保留**（别删）。
- 所有跳转链接统一走 `self::link('Xxx/yyy', $params)`：内部 `url()`，异常时回退成 `/admin/Xxx/yyy.html`。

### 查询组织方式：两次 UNION ALL

概览数字（27 项）和待办计数（8 项）各自**一条 UNION ALL SQL 一次取回**，把数据库往返压到 1 次；趋势、分布再各一条。

```90:123:app/admin/controller/MainController.php
        $clockFrom = "FROM {$p}forum a INNER JOIN {$p}member m ON m.id = a.user_id WHERE a.delete_time = 0";
        ...
        $sql = "SELECT 'user_total' k, COUNT(*) v FROM {$p}member"
```

- 字段别名统一 `k` / `v`，用 `self::kvMap()` 转 `[键 => 数值]`。
- **MySQL 5.7 坑**：UNION 各分支里 `GROUP BY` **不能用 SELECT 别名**（报 `Unknown column`），必须重复写完整表达式。

### 统计口径（改数字前必读）

| 对象 | 口径 |
|---|---|
| 打卡（forum） | `INNER JOIN member` + `delete_time = 0`，与打卡列表 `ForumController::setIndex` 的 join 一致 → **用户已删除的打卡不计入**，保证首页与列表能对上 |
| 软删除 | 所有带 `delete_time` 的表都带 `delete_time = 0`（与后台列表自动过滤软删除一致） |
| 积分发放 | `user_account_log` 中 `account_type = 2`（积分账户）且 `change_amount > 0`，**不含扣减** |
| 订单状态分组 | 待发货 `(2, 14)`；退款售后 `(12, 18, 20, 22)`，沿用 `ShopOrderModel::getStatusList()` / `getOrderStatusCount()` |
| 打卡类型名 | 常量 `CLOCK_TYPE_NAMES`（1~7）与左侧菜单 `forum/index1` ~ `forum/index7` 一一对应，改菜单名要同步这里 |

模板最后一屏（"统计说明"面板）会把口径和前 4 条原样写给运营看，**改口径要连模板那段文案一起改**。

## 三、模板结构（8 个模块）

| # | 模块 | 关键点 |
|---|---|---|
| 1 | 欢迎条 | `{$greet}` / `{$date_text}` / 今日速览；"刷新数据"按钮 = `{:url('main/index',['refresh'=>1])}`；右侧显示"缓存 60 秒 / 实时统计" |
| 2 | 核心数据概览 | 8 个 `.dash-metric` 卡片（4 个一行），每张右上角指标 + 一行补充说明 |
| 3 | 待办事项 | `volist name="todo"`，计数为 0 显示灰色 0，否则 `label-danger` |
| 4 | 近 30 天趋势 | `#dash-trend`（折线，320px，4 条序列） |
| 5 | 打卡分布 | `#dash-cate`（饼，5/12 宽）+ `#dash-type`（柱，7/12 宽） |
| 6 | 最新动态 | 最新打卡 / 活动报名 / 商城订单，三列 `.dash-col-4` |
| 7 | 最新用户 + 快捷入口 | `latest_user`（5/12）+ `quick` 快捷入口（7/12，`.dash-quick-grid` 每行 6 个） |
| 8 | 统计说明 | 口径 4 条 + 性能说明 + 建议加索引的字段 |

## 四、前端约定

- **ECharts 是本地资源**，不是 CDN：`__TMPL__/public/assets/js/echarts/echarts.min.js`（约 1MB，位于 `public/themes/admin_fengiy/public/assets/js/echarts/`）。不要换 CDN。
- 图表初始化前先判空，资源缺失时整页 JS 不能跟着崩：

```554:558:public/themes/admin_fengiy/admin/main/index.html
<script type="text/javascript">
    $(function () {
        if (typeof echarts === 'undefined') {
            return;
        }
```

- **栅格是自建的**：`.dash-flex` + `.dash-col-3|4|5|7`（本页 `<style>` 内），不依赖 layui/bootstrap 的断点类，所以窄窗口不会退化成竖排。加一块就复用这套宽度类，别混用 `layui-col-*`。
- **页内所有跳转用 `parent.openapp()`** 开新标签页：
  ```html
  <a href="javascript:parent.openapp('{$vo.url}','todo{$key}','{$vo.name}');">去处理</a>
  ```
  直接写 `href="url"` 会在 iframe 内部跳走（整个后台界面被替换掉）。`todo{$key}` / `quick{$key}` 的 id 加了前缀，避免和别的标签 id 撞。
- 面板内贴边表格用 `.dash-table`；配色沿用主题渐变系（`#2D8CF0` 蓝、`#95DE64` 绿、`#FF9900` 橙等）。

## 五、把左侧"首页"改名"仪表盘"（`index/index.html` 三处联动）

外壳模板里有三处相关，**必须同名**，漏一处就会出现"点了仪表盘却显示二级标题"或标签名不一致：

| 位置 | 改什么 |
|---|---|
| 左侧栏 `nav-list` | `title="仪表盘"` + `<span class="menu-text"> 仪表盘 </span>`（原来是 `{:lang('WEBSITE_HOME_PAGE')}` / 首页） |
| 顶部标签 `task-content` | `app-name="仪表盘"` + tab 文本 `仪表盘`（原来是 `{:lang('HOME')}`） |
| 底部 JS | `if (title != '仪表盘')` —— 靠这段字符串判断要不要显示二级标题 |

```365:369:public/themes/admin_fengiy/admin/index/index.html
					if(title != '仪表盘') {
						$(".two-nav").html(title).removeClass('hidden');
					}
```

> JS 里的判断值、tab 的 `app-name`、左侧栏的 `menu-text` 三者是**硬编码字符串匹配**，不是读变量。

## 六、排查表

| 现象 | 原因 |
|---|---|
| 首页数字和列表对不上 | 口径没跟列表一致：漏 `delete_time = 0`，或打卡没 `INNER JOIN member` |
| 图表空白 / 整页 JS 失效 | `echarts.min.js` 路径被改或文件被删（应先恢复本地文件） |
| 点卡片"页面被嵌进去了"/在 iframe 内跳走 | 用了 `href` 而不是 `parent.openapp()` |
| 数据看着陈旧 | 命中 60 秒缓存，点右上"刷新数据"（`refresh=1`）即强制重算 |
| 看板变慢 | 缺索引：`cmf_forum(delete_time, create_time)`、`cmf_user_account_log(delete_time, account_type, create_time)`、`cmf_member(create_time)` |
| 非超管看不到首页 / 要点两下才进 | 改了 `/admin/main/index` 地址，或画蛇添足加了菜单 |
| 改了"仪表盘"不生效 | `index/index.html` 三处没改全；或浏览器/模板缓存 |
| 统计耗时/缓存标识不对 | `collectStats` 里 `from_cache` / `cost_ms` 的赋值被覆盖 |

## 七、验证手段

- **对数字**：用命令行脚本调 `MainController::collectStats(true)`（`public static`），和列表页数字核对（脚本写法见 `thinkcmf-cli-script`）。
- **路由是否部署**：`curl` 状态码 —— `302` 表示控制器存在（跳登录）、`404` 表示不存在（见 `prod-db-change-and-deploy-verify`）。

## 相关 skill

- 后台新增页面三件套（控制器 + 模板 + 菜单）：`pc-admin-new-page`
- 菜单与左侧栏/标签：`pc-admin-menu-manage`
- 命令行跑脚本查库/对数字：`thinkcmf-cli-script`
- 生产库变更与部署验证：`prod-db-change-and-deploy-verify`
- 表结构与业务实体：`business-data-model`
- 小程序首页入口配置（快捷入口里的"首页入口配置"）：`mp-home-config`
