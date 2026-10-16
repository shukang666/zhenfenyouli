---
name: pc-admin-batch-delete
description: "PC 后台列表页「批量删除 / 批量操作」功能实现模式。记录 ThinkCMF 后台列表加复选框（js-check-all 全选 + js-check 行选）与批量删除按钮（js-ajax-submit + data-action + data-subcheck）的模板写法、控制器 delete_all() 接收 ids[] 的事务化批量删除与业务副作用处理（如扣回积分），以及 admin.js 公共 JS 机制和「孤立 </form> 复用」「别破坏搜索/导出表单」等坑。当需要给后台列表新增批量删除/批量操作，或排查批量删除按钮不显示、点了没反应时调用。"
---

# PC 后台列表「批量删除 / 批量操作」模式

## 适用场景

给 ThinkCMF 后台任意列表页（`public/themes/admin_fengiy/admin/<controller>/<action>.html` + `app/admin/controller/<Controller>.php`）新增：

- 勾选多行 → 一键批量删除
- 批量启用/禁用、批量改状态等同类"批量操作"

本项目已有同类实现可参考：`coupon/select_list.html`（简单型）、`forum/index.html`（带"删除原因弹窗"的变体）。

---

## 一、模板改动（4 处，缺一不可）

### 1. 列表外层必须有 `js-check-wrap`

```html
<div class="wrap js-check-wrap">
```

> 已有的列表页通常本来就有；若没有，全选/反选 JS 不生效。

### 2. 用 `form.js-ajax-form` 包住表格，并加"批量删除"按钮

在**原搜索 form 之后**、`<table>` 之前插入：

```html
<form class="js-ajax-form margin-top-20" action="" method="post">
    <button class="btn btn-danger btn-sm js-ajax-submit" type="submit"
            data-action="{:url('delete_all')}" data-subcheck="true">批量删除
    </button>
    <table class="table table-hover table-bordered margin-top-20">
        ...
    </table>
</form>
```

要点：

- 按钮必须是 `type="submit"` 且带 `js-ajax-submit`，`data-action` 指向控制器方法（如 `delete_all`）。
- `data-subcheck="true"` → 未勾选任何行时前端直接提示，不会发请求。
- `js-ajax-form` 必须**只包表格 + 按钮**，不要把搜索/导出表单也包进来（见下方坑）。

### 3. 表头加全选复选框

```html
<thead>
<tr>
    <th width="16"><label><input type="checkbox" class="js-check-all" data-direction="x"
                                 data-checklist="js-check-x"></label></th>
    <th width="50">ID</th>
    ...
</tr>
</thead>
```

### 4. 每行加行选复选框

```html
<tbody>
<foreach name="list" item="vo">
    <tr>
        <td><input type="checkbox" class="js-check" data-yid="js-check-y" data-xid="js-check-x"
                   name="ids[]" value="{$vo.id}"></td>
        <td>{$vo.id}</td>
        ...
    </tr>
</foreach>
</tbody>
```

- `name="ids[]"` 是控制器接收的字段名，**必须带 `[]`**。
- 全选 checkbox 的 `data-checklist="js-check-x"` 与行 checkbox 的 `data-xid="js-check-x"` 要一致（`x` 方向 = 列勾选），`data-direction` / `data-yid` 用于整列/整行勾选。

---

## 二、控制器 `delete_all()`

参考 `ActiveClockController::delete_all()`（社区活动打卡记录批量删除）：

```php
use think\facade\Db;   // 事务用

/**批量删除
 * @return void
 */
public function delete_all()
{
    $ids = $this->request->param('ids');
    if (empty($ids)) {
        $this->error('请选择要删除的记录');
    }
    if (!is_array($ids)) {
        $ids = explode(',', $ids);
    }
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (empty($ids)) {
        $this->error('请选择要删除的记录');
    }

    Db::startTrans();
    try {
        $list = $this->model->where('id', 'in', $ids)->select();
        foreach ($list as $item) {
            // 业务副作用：与「单条删除」保持一致（示例：有赠送积分则扣回）
            if (!empty($item['send_point']) && $item['send_point'] > 0) {
                AccountLogModel::add_account_log($item['user_id'], 2, 226, -$item['send_point'],
                    ['remark' => '社区活动打卡扣除']);
            }
        }
        $this->model->where('id', 'in', $ids)->delete();
        Db::commit();
        $this->success('删除成功');
    } catch (\Exception $e) {
        Db::rollback();
        $this->error('删除失败：' . $e->getMessage());
    }
}
```

要点：

- **方法名必须下划线小写**（`delete_all`），不能驼峰；URL 直接映射方法名。
- `ids` 可能传数组也可能传逗号串，统一 `array_map('intval', ...)` 过滤。
- **批量删除必须复用单条删除的业务副作用**（扣积分、写日志等），否则会出现"记录删了、积分没扣"的数据不一致。
- 用事务包裹，任一步失败整体回滚。
- 返回用 `$this->success()` / `$this->error()`（`js-ajax-form` 会读 JSON 结果弹提示）。

---

## 三、admin.js 公共机制（不用自己写 JS）

`public/static/js/admin.js` 已内置：

| 元素 | 行为 |
|---|---|
| `form.js-ajax-form` + `button.js-ajax-submit` | 序列化该 form（含 `ids[]`）AJAX POST 到 `data-action` |
| `data-subcheck="true"` | 提交前检查是否有 `input.js-check:checked`，没有则提示、不提交 |
| `input.js-check-all` + `data-checklist` / `data-direction` | 全选/反选所有 `js-check` |
| `a.js-ajax-delete` | 单条删除（点击后确认再请求 `href`） |

所以只要按上面四件套写模板，**不需要额外写 JS**。

---

## 四、关键坑

| 坑 | 说明 | 处理 |
|---|---|---|
| **页面已有一个孤立 `</form>`** | ThinkCMF 后台模板历史遗留，常见"搜索 form 已闭合、表格后面又有一个 `</form>`"的不规范写法 | 新增 `form.js-ajax-form` 后，正好让这个孤立 `</form>` 当它的闭合标签即可，**不要重复加**，也不要删错搜索表单的闭合 |
| **把搜索/导出表单一起包进 `js-ajax-form`** | 搜索表单是 `method="get"` 且含 `.ajax-export` 导出逻辑，被包进去后导出/搜索行为会错乱 | 搜索 form 单独闭合，`js-ajax-form` 只包表格与批量按钮 |
| **按钮位置** | 放在表格上方（搜索框下方）最直观 | 与导出按钮分开，避免误点 |
| **模板缓存** | 改完线上没变化时，先 `Ctrl+F5`，再清服务器 `runtime/temp/*` | 见 `prod-db-change-and-deploy-verify` |
| **只改本地没部署** | 用户看的是线上后台时，本地改动不会自动生效 | 先确认访问环境，再部署，见 `prod-db-change-and-deploy-verify` |

---

## 五、业务副作用差异（本项目两处打卡删除，别搞混）

| 场景 | 入口 | 删除行为 |
|---|---|---|
| **社区活动打卡记录** | `ActiveClockController`（菜单：社区活动 → 打卡记录） | 直接删除 + 有 `send_point` 则**扣回积分**，**不填原因、不写日志** |
| **垃圾分类打卡（垃圾打卡）** | `ForumController`（菜单：垃圾分类活动 → 垃圾打卡，`forum/index2`） | 弹窗**必填删除原因** + 扣积分 + 写 `cmf_forum_delete_log`，见 `admin-forum-delete-log` |

> 复制批量删除代码时，务必先确认目标列表属于哪种，副作用逻辑要对齐它自己的"单条删除"。

---

## 六、验证

1. 勾选若干行 → 点"批量删除" → 有确认/成功提示。
2. 不勾选直接点 → 提示"请选择要删除的记录"。
3. 点表头复选框 → 全选/全不选正常。
4. 检查业务副作用：如积分是否按记录数正确扣回。
5. 数据库回查被删记录确实不存在。

---

## 相关 skill

- 后台页面定位（URL ↔ 控制器 ↔ 模板 ↔ 菜单）：`admin-page-locator`
- 生产库改数据 + 部署验证（"改了没生效"排查）：`prod-db-change-and-deploy-verify`
- 后台列表搜索/导出（与批量删除常在同一页）：`pc-admin-search-export`
- 垃圾分类打卡删除记录（填原因 + 日志）：`admin-forum-delete-log`
- 后台菜单与控制器同步：`pc-admin-menu-manage`
