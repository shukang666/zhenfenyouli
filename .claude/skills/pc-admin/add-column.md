---
name: pc-admin-add-column
description: "PC 后台列表页加列模式。记录 ThinkCMF 服务端渲染列表的加列流程：表头与循环行同步加 th/td、控制器 select 字段确认、导出方法（CommonModel::outData 的 $head/$keys 或 ExcelExporter）同步加列、重定向假页面识别等。当需要在后台列表某两列之间新增一列，或加列后列表与导出数据不一致时调用。"
---

# PC 后台列表页加列模式（备忘）

## 适用场景

需求形如："在 /admin/xxx/index.html 列表的 A 列和 B 列之间加一列 XX，数据内容=某字段"。

PC 后台是 ThinkCMF6/ThinkPHP6 服务端渲染，列表模板在 `public/themes/admin_fengiy/admin/<控制器>/<动作>.html`，控制器在 `app/admin/controller/*.php`。

## 标准修改流程

1. **先看模板**：定位表头 `<thead><tr>...</tr></thead>` 和循环行 `<foreach name="list" item="vo"><tr>...</tr></foreach>` 的 `<td>` 顺序。要插的列在表头和循环行各加一个 `<th>`/`<td>`，位置对齐。
2. **确认 select 字段**：读对应控制器 `index()` / `setIndex()` 方法，确认 join+field 是否已 select 出目标字段。
   - 大部分列表用 `->field('a.*,b.xxx,b.yyy')`，`a.*` 已含主表所有字段（含 user_id/uid 等），可直接 `{$vo.字段名}`。
   - 如果目标字段在 join 表（如 member 表的 id），且用了别名 `b.id uid`，则模板里用 `{$vo.uid}` 而不是 `{$vo.user_id}`。**注意检查实际别名**。
3. **同步导出方法**：如果列表带导出（搜索栏有"导出"按钮），outData/outData/export 方法里的 `$head`（表头数组）和 `$keys`（字段键数组）也要对应位置插入元素。否则列表有这列、导出无这列，数据不一致。
   - 导出方法分两类：
     - 用 `CommonModel::outData($filename, $list, $head, $keys)` 静态调用 → 改 $head/$keys 数组
     - 用 `ExcelExporter::excelExport($list, $headerRow, ...)` → 改 $headerRow 数组里的 rowName/rowVal
     - 直接 PHPExcel/PhpSpreadsheet 手写单元格 → 在对应 cellKey 列位置插入 setCellValue
4. **重定向别名识别**：URL 里 `/admin/forum/index2.html` 或 `/admin/account/userPointLog.html` 这种"假页面"，在控制器里其实是 `return redirect((string)url('index', ['type'=>2]))` 或 `url('userAccountLog', ['account_type'=>2])`，真正渲染的模板是 forum/index.html 或 account/user_account_log.html。**改之前必须先读控制器对应方法确认渲染哪个模板**，不要去找不存在的 index2.html/userPointLog.html。

## 字段名速查（本项目中常见的"用户ID"字段）

| 控制器/模板 | 表 | 用户ID 字段名 | 备注 |
|------------|-----|--------------|------|
| ActiveSignController → active_sign/index.html | active_sign | `user_id`（a.*） | join member |
| ActiveClockController → active_clock/index.html | active_clock | `user_id`（a.*） | join member |
| ForumController → forum/index.html (type=2 即"垃圾打卡") | forum | `user_id`（a.*，已有 `b.id uid` 别名也可用） | setIndex 里已格式化 `user_info = "({user_id}){nickname}"` |
| ExamController::gift_list → exam/gift_list.html | exam_gift | **`uid`**（不是 user_id！） | `e.*` 含 uid，join member m on e.uid=m.id |
| AccountController::userAccountLog → account/user_account_log.html | account_log | `user_id`（a.*） | join member b on a.user_id=b.id |
| OrderController::index → order/index.html | shop_order | `user_id`（a.*） | 嵌套表格结构，user_id 在外层 $item，不在 goods_item 里 |

## 注意事项

- 模板引擎是 ThinkPHP 模板，不是 Vue/React。`{$vo.field}` 是变量输出，`<foreach>` 是循环，`<if condition="">` 是条件。
- 加列后表头 `<th>` 数量必须等于循环行 `<td>` 数量，否则表格错位。
- 如果列表是嵌套表格（如 order/index.html 是外层 tr-header + tr-goods + tr-footer），新列要加在对应的内层 table（th-table/goods-item）里，不是外层 tr。
- 修改 thead 时如果发现已存在重复的 `<th>ID</th>`（之前上下文改过），要先用 Edit 合并掉重复行再加新列。
