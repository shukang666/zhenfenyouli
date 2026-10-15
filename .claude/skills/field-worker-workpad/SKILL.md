---
name: field-worker-workpad
description: "外勤工作台（Pad适配）实现。后台独立入口，复用后台账密登录，用于督导监测（点位巡查）数据录入。功能/字段/必填规则与小程序 pages/dtsh/supervision-monitor 完全一致。提交人记录登录账号名+（工作Pad）标识。登录态 FIELD_WORKER_* 与驾驶舱 ADMIN_ID 必须隔离；未登录跳登录页必须带 ?entry=field。当需要新增/修改外勤录入端、督导监测表单、工作Pad提交，或排查「工作台退出后又进了管理系统后台」时调用。"
---

# 外勤工作台（Pad 工作平台）

独立登录入口的通用坑（`view_base`、不走 `AdminBaseController`）见 `pc-admin` 的 [standalone-entry.md](../pc-admin/standalone-entry.md)。

## 功能背景

外勤人员（督导员/指导员）在 Pad 上使用简化版录入端进行"督导监测"数据登记。账密复用后台账号，登录入口在 **`/admin/public/login.html` 的「外勤工作台」标签**（fengiy_login 插件），登录后直接进工作台首页，不走后台菜单。独立页面 `/admin/field_worker/login` 已下线，访问会跳到上述登录页并打开外勤标签。

---

## 路由与入口

- **登录**：`/admin/public/login.html?entry=field`（插件双标签）。切外勤标签提交 `FieldWorker/doLogin`，进 `/admin/field_worker/index`。旧地址 `/admin/field_worker/login` 302 到该页。
- **工作台首页（入口网格）**：`/admin/field_worker/index`
- **督导监测（二级页）**：`/admin/field_worker/monitor`
- **数据统计（二级页）**：`/admin/field_worker/stats`
- **后台菜单**：顶级菜单「外勤工作台」仍可直达工作台页（不是登录入口）

---

## 文件结构

```
app/admin/controller/FieldWorkerController.php       -- 主控制器（登录/提交/上传/列表）
app/admin/controller/PublicController.php            -- 覆盖框架登录：entry=field 时不因 ADMIN_ID 跳后台
api/wxapp/controller/FieldWorkerController.php       -- 小程序端的社区/搜索接口（可选）
public/themes/admin_fengiy/admin/field_worker/
  ├── index.html                                      -- 工作台首页（功能入口网格）
  ├── monitor.html                                    -- 督导监测录入（原首页表单）
  ├── profile.html                                    -- 个人信息 + 修改密码
  └── stats.html                                      -- 数据统计（canvas 图表）
data/sql/field_worker_menu.sql                        -- 后台菜单SQL
public/plugins/fengiy_login/view/widget.html          -- 登录页挂件入口
```

---

## 登录机制

### 核心思路

继承 `BaseController`（**不是** `AdminBaseController`），避免套上后台框架。工作台自己的 session，与后台 `ADMIN_ID` 分开：
- `FIELD_WORKER_ID` — 登录管理员ID
- `FIELD_WORKER_NAME` — 登录账号名（`user_login`）

未登录访问工作台时先 `session('__LOGIN_BY_CMF_ADMIN_PW__', 1)` 再跳 `/admin/public/login.html?entry=field`。外勤账号不必有后台角色。

### 两套登录态隔离（必守）

工作台和驾驶舱、手机 OA **session 不能混用**：

| 入口 | session | 登录提交 | 退出清什么 |
|---|---|---|---|
| 外勤工作台 | `FIELD_WORKER_ID` / `FIELD_WORKER_NAME` | `FieldWorker/doLogin` | 只清 `FIELD_WORKER_*` |
| 手机 OA | `OA_ID` / `OA_NAME` | `Oa/doLogin` | 只清 `OA_*` |
| 驾驶舱 / 管理系统 | `ADMIN_ID` / `name` | `Public/doLogin` 或 `Mobile/doLogin` | 只清 `ADMIN_ID` |

**禁止**：工作台登录写 `ADMIN_ID`、工作台退出清 `ADMIN_ID`、或「登录后从后台菜单进工作台」。用户明确要两套入口、两套登录态。

**复现（像串站，实际是服务端误判已登录）**：先登录工作台 → 再登录驾驶舱 → 工作台退出 → 再打开工作台地址（如 `/admin/field_worker/index`）。按理应到外勤登录页，以前会直接进管理系统后台。

**根因**：

1. 工作台退出只清 `FIELD_WORKER_*`，驾驶舱的 `ADMIN_ID` 还在（这是对的）。
2. 未登录会跳登录页。框架 `PublicController::login()` 只要 `ADMIN_ID` 非空就 `redirect(admin/Index/index)`。
3. 浏览器 `#field` **不会发给 PHP**。只靠 hash 切外勤标签时，服务端看不到「这是工作台入口」，就会把人送进驾驶舱。

**正确做法**：

- 跳转地址必须带 **query**：`url('admin/Public/login') . '?entry=field'`（`FieldWorkerController::fieldLoginUrl()`）。插件 `widget.html` 用 `location.search` 的 `entry=field` 切外勤标签。
- **不要改 vendor**。覆盖类：`app/admin/controller/PublicController.php`（与 `MainController` 同套路，`autoload_psr4` 里项目 `app/` 在 vendor 前面）。
- `login()` 里：`$entry` 为 `field` 或 `oa` 时**不要**因 `ADMIN_ID` 跳后台首页。无这类 entry 时保持原逻辑（已登录驾驶舱则进后台）。

```php
$entry    = $this->request->param('entry', '');
$admin_id = session('ADMIN_ID');
$isolated = ['field', 'oa'];
if (!empty($admin_id) && !in_array((string) $entry, $isolated, true)) {
    return redirect(url("admin/Index/index"));
}
```

### 页面层级

登录后进入 `index` 入口网格（窄屏两列、Pad 宽屏三列）。当前入口：
- **督导监测** → `monitor`（宣传品与餐饮宣传并排；宽屏 PC 右侧固定索引；Pad/手机右下角可打开表单索引，必填红星，已填绿未填灰）
- **数据统计** → `stats`（`cmf_supervision_monitor` 汇总：巡查量/单位类型/社区 Top/合格率/来源，canvas 绘图；可切换全部/我的）
- **个人信息** → `profile`（账号资料；`POST /admin/field_worker/changePassword` 改密，对齐后台设置：校验原密码、新密至少 6 位、两次一致、不能与原密相同）

### 踩坑记录

**BaseController 不解析主题路径**：`fetch()` 不做模板路径推算，必须手动覆写 `fetch()` + `parseTemplate()` + `_initializeView()`（复刻 AdminBaseController 逻辑）。

```php
protected function fetch($template = '', $vars = [], $config = [])
{
    $template = $this->parseTemplate($template);
    return $this->view->fetch($template, $vars, $config);
}
```

**parseTemplate 返回路径格式**：`themes/<后台主题>/admin/<controller>/<action>.html`

---

## 督导监测提交（submit）

### 路由
`POST /admin/field_worker/submit`

### 字段对齐

与小程序 `api\wxapp\controller\SupervisionMonitorController::post()` 100% 一致：
- 表名：`cmf_supervision_monitor`
- 必填：community_id/community_name, unit_name, unit_type(1-4), check_date, address
- 是否类字段：1=是, 0=否（未选择按0存）
- 照片字段：door_photo, inner_photo, publicity_photo, inner_rectify_photo, point_photo, point_rectify_photo, manage_photo, manage_rectify_photo, transport_photo, catering_photo, accuracy_photo（共11项）
- **多图字段**（与小程序一致）：`point_photo`、`point_rectify_photo`、`transport_photo` — 库内单 URL 或 JSON 数组，解析见 `app/common/SupervisionMonitorPhotos.php`

### 提交人标识

新增 `submitter_name` 字段区分提交来源：
- 小程序提交：`submitter_name` 为空，后台列表联表 `cmf_member` 显示微信昵称
- 工作Pad提交：`submitter_name` = `{$user_login}（工作Pad）`，如 `xiayuxuan（工作Pad）`

### 图片上传与 COS（与全站图床一致）

外勤 Pad 督导监测走 **`POST /admin/field_worker/upload`**，内部复用 `cmf\lib\Upload`（与后台商品上传相同）。存储驱动由 **`cmf_resolve_storage_setting()`** 决定：腾讯云 COS 插件配置齐全时**写 COS**，库 `cmf_asset.file_path` 与业务表存相对键 **`xcxkf052/...`**（无 `upload/` 段）。

**禁止**在外勤链路里手拼 `site_url + '/upload/'` 做预览或入库；一律：

| 场景 | 用法 |
|------|------|
| 上传接口返回预览 | `cmf_get_asset_url($rel)` |
| 提交入库前规范化 | `normalizeUploadRelativePath()` → `xcxkf052/...` |
| 记录列表/回显 | `publicUploadUrl()` → 内部 `cmf_get_asset_url` |
| 模板 JS 仅作相对键兜底 | `getAssetPublicBaseForJs()` → COS 公网根（插件就绪时） |

`monitor.html` 优先用接口返回的 `data.url`（已是 COS）；`ASSET_UPLOAD_BASE` 仅为相对 `filepath` 兜底，**不是**本地 `/upload`。

`cmf\lib\Upload` 会先落盘 `public/upload` 再 PUT COS 并 **unlink 本地副本**（ThinkCMF 标准流程，见 `cos-storage` skill）。外勤业务**不**依赖本地 `public/upload` 长期存图。

#### 上传 session 修复

`cmf\lib\Upload` 写 `cmf_asset` 时通过 `cmf_get_current_user_id()` 取 `session('user.id')`，外勤登录用的是自定义 session key，上传前需手动同步：

```php
session('user.id', $this->admin_id);
$uploader = new \cmf\lib\Upload();
```

#### 排查

| 现象 | 方向 |
|------|------|
| Pad 上传成功但预览 404 | 看返回 `url` 是否仍带 `admin…/upload/`；应改为 COS 域名 |
| 库里有路径但 COS 无对象 | 是否曾 Local 存储；跑 `data/migrate_to_cos.php` |
| 上传 500 user_id null | 是否 `session('user.id', $this->admin_id)` |

相关 skill：**`cos-storage`**（桶、迁移脚本、`enable_cos_storage.php`）。

---

## 后台展示（SupervisionMonitorController）

### 修改点

`cmf_supervision_monitor` 表新增 `submitter_name` varchar(64) DEFAULT '' 字段。

列表/详情/导出时：
- 查询增加 `r.submitter_name` 字段
- 列表展示优先级：`submitter_name` 非空 → 显示它；否则 → 联表取 `cmf_member.nickname + mobile`

### 模板修改

`index.html`（列表）和 `detail.html`（详情）中的提交人单元格改为：

```html
<!-- index.html 列表 -->
<td>
    <notempty name="vo.submitter_name">
        {$vo.submitter_name}
        <else/>
        <notempty name="vo.nickname">
            {$vo.nickname}<small style="color:#999;">({$vo.mobile})</small>
            <else/>
            已删除
        </notempty>
    </notempty>
</td>

<!-- detail.html 详情 -->
<td>
    <notempty name="item.submitter_name">
        {$item.submitter_name}
        <else/>
        <notempty name="item.nickname">
            {$item.nickname} ({$item.mobile})
            <else/>
            已删除
        </notempty>
    </notempty>
</td>
```

### 导出

`outData()` 方法中 keys 数组的 `nickname` 位置改为条件输出：

```php
// head
$head = ['ID', '提交人', ...];
// keys 处理
'-submitter_name' 存在时用它，否则用 nickname
```

---

## 社区/搜索接口

| 接口 | 路由 | 说明 |
|------|------|------|
| 社区列表 | `GET /admin/field_worker/communityParentList` | 返回 `member_community_parent` 的 id+name |
| 搜索小区 | `GET /admin/field_worker/searchCommunity` | 按名称模糊搜索 `member_community`，含 parent_name |

搜索逻辑与小程序一致：去除特殊符号后 LIKE 匹配，支持分页。

---

## 数据库变更

### submitter_name 字段

```sql
ALTER TABLE `cmf_supervision_monitor` ADD COLUMN `submitter_name` varchar(64) NOT NULL DEFAULT '' COMMENT '提交人标识（工作Pad提交时为 账号名+工作Pad）';
```

### 后台菜单

见 `data/sql/field_worker_menu.sql`。

---

## 关键注意事项

1. **Db 门面**：必须用 `think\facade\Db`，不能用 `think\Db`
2. **success() 参数**：`BaseController::success($msg, $url, $data)` 数据放第三参数
3. **BaseController 模板解析**：本控制器继承 BaseController 必须覆写 fetch/parseTemplate/_initializeView
4. **上传 session**：上传图片前手动 `session('user.id', $this->admin_id)`
5. **菜单权限**：后台菜单「外勤工作台」不需要分配权限给角色——外勤账号走独立 session，入口是 `/admin/public/login.html?entry=field`
6. **登录态隔离**：工作台未登录必须 `?entry=field`；不要用 `#field` 当服务端标记；不要把工作台和驾驶舱的 session 合成一套

---

## 调试路径

| 问题 | 排查 |
|------|------|
| TemplateNotFoundException | 检查 fetch/parseTemplate 是否正确覆写 |
| 上传 500 (user_id null) | 检查上传前是否 `session('user.id', $this->admin_id)` |
| Db::name() 报错 | 检查是否用了 `think\facade\Db` |
| 提交后无数据 | 检查 community_id 是否 > 0，check_date 格式 yyyy-mm-dd |
| 后台列表不显示 nickname | 检查 cmf_member 联表逻辑是否正确 |
| 工作台退出后再打开工作台，进了管理系统 | 跳转是否丢了 `?entry=field`；`app/admin/controller/PublicController.php` 的 `login()` 是否在 `entry=field` 或 `entry=oa` 时跳过 `ADMIN_ID` 跳转；是否误改 vendor 里的同名类 |
| 外勤和 OA / 驾驶舱串登录 | 是否误写了别人的 session key；OA 见 `oa-portal` |
