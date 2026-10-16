---
name: admin-forum-delete-log
description: "垃圾分类打卡删除记录功能实现（纯 PC 后台新增，不改小程序）。记录管理员删除打卡时弹窗必填原因、扣除对应积分、写入 cmf_forum_delete_log 删除日志表，以及后台对删除日志的搜索、查看与导出。当需要新增/修改打卡删除流程、删除原因收集或删除日志管理功能时调用。"
---

# 垃圾分类打卡删除记录功能

## 功能背景

用户垃圾分类打卡存在低质量内容，管理员需删除并记录原因（替代外部Excel），便于后续追溯。需求：
- 删除时必填原因（后台弹窗收集）
- 删除后扣除对应积分并写入删除日志
- 后台可搜索、查看、导出删除记录
- 不改小程序，纯后台增加

---

## 数据库：`cmf_forum_delete_log`

```sql
CREATE TABLE IF NOT EXISTS `cmf_forum_delete_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `forum_id` int(11) NOT NULL COMMENT '被删除的打卡ID',
  `user_id` int(11) NOT NULL COMMENT '打卡用户ID',
  `content` text COMMENT '备份的打卡内容',
  `image` text COMMENT '备份的图片URL(逗号分隔)',
  `point` int(11) DEFAULT 0 COMMENT '删除时扣除的积分',
  `reason` varchar(500) NOT NULL COMMENT '删除原因（必填）',
  `operator_id` int(11) NOT NULL COMMENT '操作管理员ID',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_forum_id` (`forum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='论坛打卡删除记录表';
```

---

## 文件结构

```
data/sql/forum_delete_log.sql                       -- 建表+菜单SQL
app/admin/model/forum/ForumDeleteLogModel.php       -- 模型
app/admin/controller/ForumController.php            -- 删除逻辑+列表页
public/themes/admin_fengiy/admin/forum/
  ├── index.html                                    -- 列表页（弹窗收原因）
  └── delete_log.html                               -- 删除记录页（搜索/导出）
public/static/js/admin.js                           -- 公共JS（空URL修复）
```

---

## 后端关键点

### 1. 删除逻辑（事务保护）

单次删除 `delete()` 和批量 `delete_all()` 都走事务：
1. 校验 reason 非空（前端也校验，后端兜底）
2. 校验记录存在
3. 把 content/image/reason 写入 `cmf_forum_delete_log`
4. 软删除：`update(['delete_time' => time()])`
5. 若 point>0，扣除用户积分
6. commit / rollback

### 2. 删除记录列表

`ForumController::delete_log_index()`（下划线命名，非驼峰）处理真实列表，`deleteLog()` 做入口重定向。

查询：
- 关联 `member` 表取 nickname/mobile
- 搜索条件：name（昵称/手机/ID模糊）、keyword（原因模糊）、时间范围
- 支持导出（调用 `out_delete_log()` 复用 CommonModel::outData）

### 3. 命名陷阱

**ThinkCMF 控制器方法名必须用下划线格式**，不能用驼峰！
- ✅ `delete_log_index`、`delete_all`、`index2`
- ❌ `deleteLogIndex`、`deleteLog`

URL `admin/forum/delete_log_index` 直接映射方法名，与方法 `delete_log_index()` 匹配。

模板中 `{:url('delete_log_index')}` 也要用下划线。

---

## 菜单配置

菜单挂在 **垃圾分类活动（carbon/default）** 下，与垃圾打卡同级：

```sql
INSERT INTO `cmf_admin_menu` (`parent_id`, `app`, `controller`, `action`, `param`, `name`, `status`, `list_order`)
SELECT 
    (SELECT id FROM `cmf_admin_menu` WHERE `controller`='carbon' AND `action`='default' LIMIT 1) AS parent_id,
    'admin', 'forum', 'delete_log_index', '', '删除记录', 1, 10 
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM `cmf_admin_menu` WHERE `controller`='forum' AND `action`='delete_log_index') t);
```

**注意**：parent_id 通过子查询动态获取，不要写死 id，否则生产库id不一致会挂错位置。

---

## 公共函数修复（空URL）

`public/static/js/admin.js` 中 `openIframeLayer` 函数在入口加了 URL 校验：

```javascript
function openIframeLayer(url, title, options) {
    if (!url || !$.trim(url.replace(/[\u200B-\u200D\uFEFF]/g, ''))) {
        console.warn('openIframeLayer: 无效的 URL，已忽略。url=', JSON.stringify(url));
        return;
    }
    // ...
}
```

防止 layer.open 收到空 URL 导致浏览器发起无效请求（报"不支持的URL方案"）。

---

## 与「社区活动打卡删除」的区别（别搞混）

| | 垃圾分类打卡（本 skill） | 社区活动打卡记录 |
|---|---|---|
| 入口 | `ForumController`（垃圾分类活动 → 垃圾打卡，`forum/index2`） | `ActiveClockController`（社区活动 → 打卡记录） |
| 删除原因 | **弹窗必填** | 不需要 |
| 删除日志 | 写 `cmf_forum_delete_log` | **不写日志** |
| 积分 | 扣除对应积分 | 有 `send_point` 则扣回 |
| 删除方式 | 软删除 `delete_time` | 物理删除 |

> 批量删除的通用模板（复选框 + `js-ajax-submit` + `delete_all()`）见 `pc-admin-batch-delete`。

---

## 关联SQL迁移文件

`data/sql/forum_delete_log.sql` 包含：
1. 建表 `cmf_forum_delete_log`（IF NOT EXISTS，可重复执行）
2. 插入菜单项（NOT EXISTS 防重复）

如需改菜单位置，先 DELETE 旧的 forum/delete_log_index 菜单，再重新 INSERT。

---

## 调试路径

打开删除记录页报 404 时，按顺序排查：
1. 菜单 action 名 vs 控制器方法名是否完全一致（大小写、下划线）
2. 菜单 parent_id 是否正确（不是0，应是 carbon/default 的 id）
3. 用户是否有该菜单权限
4. 框架 runtime 缓存是否已清除
