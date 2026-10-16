---
name: cron-task-system
description: "xiayuxuan.com 定时任务系统全栈指南(该框架已于 2026-09 从代码中整体下线，含下线清单)。任务原本全部由代码注册(不可后台新增删除)，PC后台 cmf_admin_menu「定时任务」页管理 cmf_cron_task 表的启停/周期/param(JSON)运行配置；think cron:run 命令被系统计划任务每分钟调度。含垃圾分类打卡重复图片自动清理任务(感知哈希aHash+汉明距离判重并自动删除打卡)。当需要新增/修改定时任务、配置任务周期、排查任务未执行/判重逻辑、理解打卡图片去重，或需要下线/拆除该定时任务系统时调用。"
---

# 定时任务系统（PC 后台 + 代码注册）

> **状态：该框架已于 2026-09 从代码中整体下线**（提交 `13cc290`）。
> `app/cron/`、`CronTaskController`、`CronTaskModel`、`cron_task` 模板、`cmf_cron_task` 表均已删除；
> 其中的图片感知哈希能力已迁移到 `app/algo/`（`app/algo/ImageHashAlgo.php` + `app/algo/lib/ImagePerceptualHash.php`）。
> 本文档保留为**历史实现参考**，并在下方补充「定时任务系统下线清单」；如需重新启用，按原结构重建即可。

## 核心设计（一句话）

任务本体在**代码里写死**（不可新增/删除），后台只维护 `cmf_cron_task` 表里的
**运行配置行**（启停、周期、param JSON、运行进度 state）；`php think cron:run` 每分钟由系统计划任务触发，
遍历“启用且到期”的任务执行，结果写回该行。

新增定时任务 = 写一个实现 `CronTaskInterface` 的类 + 注册到 `TaskRegistry::TASK_CLASSES`，
后台/建表/调度全部自动适配，**不需要**改控制器/模板/数据库。

---

## 定时任务系统下线清单（本次实操，可复用）

按顺序做完，缺一项就会留下「菜单还在点进去 404」或「任务仍在被调度」这类残留：

1. **删代码**
   - `app/cron/`（`CronTaskInterface` / `TaskRegistry` / `TaskRunner` / `command/CronRun` / `task/ForumPhotoDedupTask`）
   - `app/admin/controller/CronTaskController.php`、`app/admin/model/CronTaskModel.php`
   - `public/themes/admin_fengiy/admin/cron_task/`（`index.html` / `edit.html`）
   - 若算法能力还要用，先把 `app/cron/lib/ImagePerceptualHash.php` **迁移**到 `app/algo/lib/` 再删原目录
   - 注意：删完文件后 `app/cron/`、`cron_task/` 会留下**空目录壳**（git 不跟踪空目录），需手工 `rmdir`
2. **注销命令**：从 `app/command/command.php`（app 级命令注册，git 跟踪、控制台自动扫描）移除 `\app\cron\command\CronRun::class`
3. **删表**：`DROP TABLE IF EXISTS cmf_cron_task;`
4. **处理菜单**：删除/替换 `cmf_admin_menu` 中 `controller='CronTask'` 的行（**先删子菜单再删自身**），
   否则菜单还在、点进去 404（详见 `pc-admin-menu-manage`）
5. **服务器手工项**：从系统计划任务中**删掉** `* * * * * php /网站根目录/think cron:run >> .../data/runtime/cron.log 2>&1`
6. 清 `data/runtime` 缓存，用 curl 验证旧地址已返回 404（见 `prod-db-change-and-deploy-verify`）

SQL 示例（幂等，本次落在 `data/sql/algo_center_menu.sql`）：

```sql
DROP TABLE IF EXISTS `cmf_cron_task`;
DELETE FROM `cmf_admin_menu` WHERE `parent_id` = <旧菜单id>;
DELETE FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='CronTask';
```

**下线时不要误删的能力**：打卡重复图片的**实时查重**（发布接口 `api\wxapp\controller\ForumController::add_forum` 的第一道防线）仍在生效，
本次只移除了「夜跑兜底任务」这条第二道防线。

---

## 文件结构（下线前的历史结构，供重建参考）

```
app/cron/
  ├── CronTaskInterface.php         任务接口：meta()(元信息) + run()(执行一次,引用返回state)
  ├── TaskRegistry.php              注册中心(TASK_CLASSES 写死任务列表) + 建表 + 补默认配置行
  ├── TaskRunner.php                调度/执行引擎(runDue自动跑到期任务 / runNow手动跑)
  ├── command/CronRun.php           think cron:run 命令行入口
  ├── lib/ImagePerceptualHash.php   图像感知哈希(aHash 64bit): 下载/指纹/汉明/相似度
  └── task/ForumPhotoDedupTask.php  任务①垃圾分类打卡重复图片自动清理(兜底,优先读img_hash列)
app/command/ForumHashBackfill.php              一次性存量回填命令(php think forum:hash-backfill,已注册)
app/admin/controller/CronTaskController.php     PC后台控制器
app/admin/model/CronTaskModel.php               模型(name=cron_task)
public/themes/admin_fengiy/admin/cron_task/
  ├── index.html                    列表:启停/编辑/立即执行
  └── edit.html                     编辑:周期/状态/param动态表单/进度查看
app/command/command.php             app 级命令注册(git 跟踪, 控制台自动扫描) —— 命令注册请放这里
data/config/console.php             备用命令注册(⚠ 被 data/.gitignore 忽略, 部署时不可依赖)
data/sql/cron_task.sql              建表+菜单(幂等) —— 已随下线删除
cmf_admin_menu                       菜单: 顶级「定时任务」→ admin/cron_task/index (id 20008) —— 已随下线删除
```

---

## 数据库 `cmf_cron_task`

| 字段 | 含义 |
|---|---|
| task_key | 任务标识，与代码注册 key 一致（写死，唯一） |
| title / description | 任务名 / 原理说明（展示用） |
| status | 1 启用 / 0 停用 |
| cycle_type | 1 每N分钟 / 2 每N小时 / 3 每天/每N天定点 |
| cycle_interval | 间隔 N |
| run_time | 定点时间 HH:MM（cycle_type=3 生效） |
| **param** | 任务参数 **JSON**，每个键=一个属性，含义由任务代码定义（后台按 meta.param_schema 动态渲染表单） |
| **state** | 任务运行进度 **JSON**，代码自管理（如 last_forum_id 游标） |
| running | 运行锁 1=执行中（防多进程并发） |
| last_run_time / last_run_result / next_run_time | 调度记录 |

- 表/行均可由 `TaskRegistry::ensureTable()`/`syncRows()` 自动创建补全（`cron:run` 与后台列表都会触发）。
- **首次执行**：若 state 为空，任务自己把游标锚到当前最大 id（只监管启用后的新数据，不回溯存量）。

---

## 周期与调度

`TaskRunner::runDue()` 选择：`status=1 AND running=0 AND next_run_time<=now`，原子抢锁后执行。
`TaskRunner::computeNextRun()` 计算下次：按 cycle_type 加分钟/小时，或定点模式找“距上次运行 ≥ interval 天”的未来 HH:MM。

- 手动“立即执行”（后台按钮 `run_now`）：走 `TaskRunner::runNow()`，**不改** next_run_time 自动计划，但会更新 last_run/state/result。
- 启停：`status` 置 1 时顺手把 next_run_time=now-60，让下一个调度立刻执行。

### 服务器部署（重要）

宝塔/系统计划任务每分钟调用：
```
* * * * * php /网站根目录/think cron:run >> /网站根目录/data/runtime/cron.log 2>&1
```
命令注册在 `app/command/command.php`（app 级，git 跟踪，控制台自动扫描）；命令会自动建表、补配置行再跑到期任务。

> **已下线**：`cron:run` 命令与 `app/cron/` 已删除，服务器上这行计划任务需手工移除（见「定时任务系统下线清单」）。

---

## 任务① 垃圾分类打卡重复图片自动清理

`task_key = forum_photo_dedup`，cycle 默认每天 02:00。

### 两层防重复体系（重要，与后台描述同源）

打卡发布接口 `api\wxapp\controller\ForumController::add_forum`（type=2）已是**第一道防线**：
发布时实时比对本人最近 200 条历史打卡（含已删，防连环复用），URL 完全一致或 aHash 相似度=100%
即直接 `error` 拒绝发布（前端提示换照片）；比对通过后把每张图的 aHash 指纹写入
`cmf_forum.img_hash`（JSON，与 `image` 同序，`delete_time` 软删行保留，供历史比对）。
个别图片当时下载失败则不写库，不阻塞发布。

本定时任务为**第二道兜底防线**（覆盖发布时指纹缺失/漏判的记录）。

### 原理（后台描述同源）

1. type=2 垃圾分类打卡的图片存于 `cmf_forum.image`（JSON 数组，完整 OSS URL，如 `https://oss.ausite.cn/xcxkf052/default/YYYYMMDD/xxx.jpg?watermark`）；指纹存于 `cmf_forum.img_hash`（发布/回填时已生成）。
2. 每次运行从 `state.last_forum_id` 游标开始向上取最多 `scan_step` 条未删打卡（type=2, delete_time=0）。
3. 对每条新打卡的每张图，与该用户“更早”的历史打卡（最近 `history_limit` 条，含已删记录防连环复用）图片比对：
   - 指纹**优先读 `img_hash` 列**（历史与新打卡都一样），列里缺失才下载原图实时计算兜底；
   - 先按**归一化 URL**（剥离 `?watermark` 等 query）完全一致快速判重（同一文件再次上传）；
   - 否则算 **感知哈希 aHash 64bit** 逐张算**汉明距离**，相似度 = (1 - 汉明/64)*100；
   - 相似度 ≥ `similarity_threshold`（默认 100 = 汉明0 = 完全相同）即命中。
4. 命中即自动删除该条打卡：写 `cmf_forum_delete_log`（operator_id=0 标识系统任务，reason 注明命中详情）+ 追回积分 + 软删除，与后台 `ForumController::delete_all()` 行为一致。

### 存量回填命令 forum:hash-backfill

存量打卡的 `img_hash` 为空时，历史比对照旧要逐张下载（或发布时只能做 URL 精确判重），
因此在**服务器**上执行一次性回填（幂等、可断点续跑，会先自动 ALTER 建列）：

```
php think forum:hash-backfill               # 全量，直到扫完
php think forum:hash-backfill --max=10000   # 本次只处理 N 条，分批跑
php think forum:hash-backfill --step=500    # 每批条数(默认200)
```

- 无图行直接写 `[]` 防反复扫描；任一张图下载失败的行不写，下次运行自动重试。
- 建议在服务器上用 `--max` 分多次或挂后台跑（每次运行内相同原图 URL 只下载/计算一次）。
- 回填完成后，发布实时查重与夜跑兜底均不再需要实时下载历史图。

### param JSON 键（含义/默认）

```json
{
  "similarity_threshold": 100,   // 相似度阈值%; 100=仅完全相同才删(建议勿调低防误删)
  "history_limit": 200,          // 单用户比对的历史打卡条数上限(最近N条)
  "scan_step": 50,               // 单次执行最多扫描的新打卡条数
  "delete_same_url": 1           // 图片URL与本人历史完全一致直接判重
}
```
后续新任务的 param 用同字段 JSON，每个键一个属性，后台自动渲染。

---

## 新增/修改定时任务的步骤

1. `app/cron/task/` 下新建类，实现 `CronTaskInterface`：
   - `meta()` 返回 task_key/title/description/cycle_*/param_defaults/param_schema；
   - `run(array $task, array $param, array &$state)` 返回 `['summary'=>'...']`，可修改 `$state` 写回进度。
2. 在 `TaskRegistry::TASK_CLASSES` **末尾追加类名**（代码写死即这里，后台不可增删）。
3. 无需动 DB/后台：页面自动出现该任务并补配置行；删除任务=移除类即可。
4. 执行逻辑如需事务/查库：**统一 `use think\facade\Db;`**（见下方坑点）。

---

## 坑点与约定（务必遵守）

- **ThinkCMF 后台控制器方法名必须下划线**（delete_log_index / edit_post 等），不能用驼峰；菜单 action 与方法名、`{:url()}` 必须完全一致。
- **`Db::name()->select()` 每行是数组（非模型对象）**：后台控制器里 `foreach` 逐行时直接数组下标访问，不能 `$row->toArray()`（会报 `Call to a member function toArray() on array`）；`Db::name()->find()` 同理返回数组。只有 Collection 整体才可 `->toArray()`。
- **顶级菜单 icon 存 FA 简写**：后台侧栏模板输出 `<i class="fa fa-{$menu.icon}">`，故 `cmf_admin_menu.icon` 只存 `user/leaf/clock-o` 这类**不带 `fa fa-` 前缀**的类名；若存成 `fa-clock-o` 会渲染成 `fa fa-fa-clock-o` 导致无图标且不报错。
- **CLI 事务**：`use think\Db;` 在 web 正常但在 `think cron:run`（CLI）下 **没有 startTrans**；定时任务代码一律 `use think\facade\Db;`。
- 积分记录实际表为 `cmf_user_account_log`（不是 cmf_account_log），务必通过 `api\wxapp\model\AccountLogModel::add_account_log()` 操作，别手写表名。
- 图片在**阿里云 OSS**：DB 存的是带样式 `?watermark` 的完整 URL；算指纹前用 `ImagePerceptualHash::rawUrl()` 剥 query 取原图；下载带 SSL 证书校验失败自动降级重试（服务器可能无 CA）。
- 删除打卡时 reason 前缀固定 `【系统定时任务】…自动删除…`，operator_id=0，便于与人工删除区分。
- 默认只在后台页面验证不了的改动用 CLI 自测：临时改 next_run_time=now-60 后 `php think cron:run`，测完把整行恢复/清理测试数据（勿在真实用户上造数据，用 9xxxx 高位测试用户，测后物理删干净）。
- 修改代码后需清 ThinkPHP runtime 缓存才能生效（`data/runtime`）。
- 菜单/表执行 SQL 后无需二次授权（超管可见）。

---

## 常见排查

- 任务没跑：看服务器是否配 crontab；看列表 last_run_result/next_run_time；确认 status=1、running=0。
- 立即执行按钮超时：正常情况下新老打卡都带 `img_hash`（发布时生成 + `forum:hash-backfill` 回填存量），执行只做内存比对不下载图片；若仍有大量行缺指纹（未回填/老数据），才会逐张请求 OSS 原图，耗时长属正常，可临时调小 scan_step/history_limit，或先跑完回填命令。
- 想“回扫存量”：把编辑页 state JSON 里的 last_forum_id 手工改小（0=全量）再保存即可（耗时长，谨慎）。
- 表/行缺失：运行 `php think cron:run` 会自动建表补行；或用 `data/sql/cron_task.sql`。
