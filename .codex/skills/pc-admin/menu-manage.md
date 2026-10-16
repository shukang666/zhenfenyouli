---
name: pc-admin-menu-manage
description: "PC 后台菜单（cmf_admin_menu）管理与「菜单↔控制器」同步指南。记录侧栏菜单的取数/渲染/URL 生成机制（直读 DB 不走缓存、URL 由 app+controller+action 生成、lang 取不到回落 name、icon 只存 FontAwesome 简写、只渲染三层）、菜单增删改的幂等 SQL 写法、菜单与权限表 cmf_auth_access 的同步关系，以及由已知浏览器 URL 反查菜单/控制器/模板的排查流程。当需要新增/替换/删除后台菜单，或排查「菜单还在但点进去 404」「菜单不显示」「菜单图标不显示」「非超管看不到菜单」「某个后台 URL 对应哪个控制器/模板」时调用。"
---

# PC 后台菜单管理（cmf_admin_menu）

## 一句话

后台侧栏菜单**完全由数据库表 `cmf_admin_menu` 驱动**（`app\admin\model\AdminMenuModel::menuTree()` 每次直读 DB），
菜单的跳转地址由该行的 `app + controller + action` 拼出来。

**所以「控制器删了/改名了，但菜单行没动」→ 菜单还在、点进去 404。菜单与控制器必须成对维护。**

---

## 取数与渲染链路（已核实源码）

```
app/admin/controller/IndexController::index()
  └─ (new AdminMenuModel)->menuTree()                  // 直读 DB，无缓存
       └─ getTree(0) → adminMenu($parentId)
            ├─ where(['parent_id'=>$pid,'status'=>1])->order('list_order ASC')
            ├─ 权限过滤：超管 id=1 / id=14 直接通过
            │            其余走 cmf_auth_all_check($adminId, strtolower("app/controller/action"))
            ├─ action 以 public_ 开头 → 免检
            ├─ action 形如 ajax_xxx_ → 剥成 xxx 再校验
            └─ URL = url("{$app}/{$controller}/{$action}", $paramsArr)   // controller 经 ucwords()

模板 public/themes/admin_fengiy/admin/index/index.html
  ├─ 菜单名：$menu_name = lang($menu['lang']);
  │          $menu_name = ($menu['lang'] == $menu_name) ? $menu['name'] : $menu_name;
  └─ 图标：  <i class="fa fa-{$menu.icon|default='desktop'}"></i>
```

要点：

- **不走缓存**：`IndexController` 里的菜单缓存代码已被注释掉（`menuTree()` 直接查库），改完 DB 刷新浏览器即生效，**不用清 `data/runtime`**。
  （`AdminMenuModel::menuCache()` / `Cache::set('Menu', ...)` 只被**角色授权页** `RbacController` 使用，与侧栏显示无关。）
- **只渲染三层**：`getTree()` 里判断 `$Level <= 3`，第四层及以下不显示。
- 模型生成的 `lang` key = `strtoupper(app_controller_action)`，如 `admin/Algo/index` → `ADMIN_ALGO_INDEX`。

---

## 表结构 `cmf_admin_menu`

| 字段 | 说明 |
|---|---|
| `id` | 主键 |
| `parent_id` | 父菜单 id，**顶级菜单为 0** |
| `type` | 本项目菜单统一填 `1` |
| `status` | `1` 显示 / `0` 隐藏 |
| `list_order` | 同级排序（升序） |
| `app` | 应用名，本项目后台固定 `admin` |
| `controller` | 控制器名，与 `app/admin/controller/` 下的控制器对应 |
| `action` | 方法名，**必须与控制器方法名完全一致**（下划线不能丢） |
| `param` | URL 附加参数，形如 `type=2`（可空） |
| `name` | 菜单显示名（**`lang()` 取不到时就用它**） |
| `icon` | **只存 FA 简写**，如 `flask` / `user` / `clock-o`（不带 `fa fa-`） |
| `remark` | 备注 |

---

## 图标（FontAwesome 4）

主题输出的是 `<i class="fa fa-{icon}"></i>`，所以：

- ✅ 存 `flask` → 渲染成 `fa fa-flask`
- ❌ 存 `fa-flask` → 渲染成 `fa fa-fa-flask`（**图标不显示且不报错**，很难发现）

注意：

- `cogs` 已被「设置」占用。
- 可选图标名可查 `public/static/font-awesome/css/font-awesome.min.css`（FontAwesome 4）。
- 提示：压缩版 CSS 用 ripgrep 搜类名可能搜不到，**不要据此断定「该图标不存在」**。

---

## 菜单名与语言包（可选，容易误解）

- `data/lang/zh-cn/admin_menu.php` 是**菜单名的多语言包**（该文件被 git 跟踪，会随部署同步），key 形如 `'ADMIN_ALGO_INDEX' => '算法中心'`。
- 模板逻辑是 `lang(key)` 取不到就**回落 DB 的 `name`**，所以**不写语言包也不影响显示**，只是菜单名直接读 DB。
- **重点：菜单的「存在与否」不在这张语言包里，而在 DB 表 `cmf_admin_menu` 里。** 增删菜单要去改 DB，不是改语言包。

---

## 权限同步（`cmf_auth_access`）

- `AdminMenuModel::adminMenu()` 对非超管按 `cmf_auth_all_check($adminId, strtolower("app/controller/action"))` 过滤菜单。
- **超管 id=1（创建者）和 id=14（测试账号）免检**，所以超管永远能看到新菜单。
- 其他角色要看到菜单，需在「角色管理 → 设置角色权限」勾选，数据落在 `cmf_auth_access`（`role_id` + `rule_name` + `type='admin_url'`），`rule_name` 就是小写的 `admin/controller/action`。
- **改了 controller / action 就要同步改权限记录**，否则老角色会突然看不到菜单。

---

## 新增 / 替换 / 删除菜单：幂等 SQL 模板

本项目菜单历史上都用 SQL 直接维护（后台虽内置 `admin/menu/index` 菜单管理页，但项目里都是走 `data/sql/*.sql`）。
**注意：`data/sql/*.sql` 被 git 忽略（见 `dev-environment`），不会自动上传服务器，必须在生产库手工执行。**

### 替换一个控制器（真实案例：下线 CronTask、上线 Algo）

```sql
-- 先删子菜单，再删自身（避免留孤儿菜单）
DELETE FROM `cmf_admin_menu` WHERE `parent_id` = <旧菜单id>;
DELETE FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='CronTask';

-- 先删同名再插入 → 整段可重复执行
DELETE FROM `cmf_admin_menu` WHERE `app`='admin' AND `controller`='Algo';
INSERT INTO `cmf_admin_menu`
(`parent_id`,`type`,`status`,`list_order`,`app`,`controller`,`action`,`param`,`name`,`icon`,`remark`)
VALUES
(0,1,1,10000,'admin','Algo','index','','算法中心','flask','算法中心：项目中代码注册的算法清单与详情');

-- 校验（应只看到 1 条 Algo，且无 CronTask）
SELECT id,parent_id,status,app,controller,action,name,icon FROM `cmf_admin_menu`
WHERE `app`='admin' AND `controller` IN ('CronTask','Algo');
```

### 挂到已有父菜单下（**不要写死 parent_id**，生产库 id 可能与本地不一致）

```sql
INSERT INTO `cmf_admin_menu` (`parent_id`,`app`,`controller`,`action`,`param`,`name`,`status`,`list_order`)
SELECT
    (SELECT id FROM `cmf_admin_menu` WHERE `controller`='carbon' AND `action`='default' LIMIT 1),
    'admin','forum','delete_log_index','','删除记录',1,10
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT 1 FROM `cmf_admin_menu` WHERE `controller`='forum' AND `action`='delete_log_index') t
);
```

约定：顶级菜单 `list_order` 常用 `10000`（与「系统消息」「签到积分配置」同档）。

---

## 排查：菜单还在，点进去 404

按顺序检查：

1. **控制器/方法是否真的存在**：`app/admin/controller/<Controller>.php` 里有没有对应方法（方法名必须下划线，不能驼峰）。
2. **菜单行的 `controller` / `action` 是否与之一致**（下划线最容易漏）。
3. **代码是否已部署到服务器**：本地改了、服务器没更新 → 一样 404。用状态码判定：
   - `curl -I https://<域名>/admin/algo/index.html` → **302（跳登录）= 控制器存在**
   - → **404 = 控制器不存在**（代码没部署，或菜单指向了已删除的控制器）
4. **模板是否存在**：`public/themes/admin_fengiy/admin/<控制器下划线名>/<action>.html`。模板缺失**不是 404**（走模板回退/报错），别和上面混为一谈。
5. **非超管看不到菜单** → 查 `cmf_auth_access`（超管 id=1/14 免检）。

---

## 由 URL 反查页面归属（改页面前的第一步）

已知用户给的后台 URL（如 `/admin/active_clock/index.html`），要确认改哪个文件才生效：

- URL 段 `<controller下划线>` → 控制器 `app/admin/controller/<Controller>.php`（`active_clock` → `ActiveClockController`）
- → 模板 `public/themes/admin_fengiy/admin/<controller下划线>/<action>.html`
- 菜单归属查 `cmf_admin_menu`：**按 `controller` / `action` 查，表里没有 `url` 字段**（直接查 `url` 会报 `Unknown column 'url'`）

完整排查（含"用户说改了没生效"时的环境/分支/缓存排查顺序）见 `admin-page-locator`。

---

## 相关 skill

- 新增后台页面的完整三件套（控制器 + 模板 + 菜单）：`pc-admin-new-page`
- 生产库改数据 + 部署验证：`prod-db-change-and-deploy-verify`
- `data/` 目录与 git 部署的关系、终端坑：`dev-environment`
