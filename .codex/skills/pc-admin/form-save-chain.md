---
name: pc-admin-form-save-chain
description: "PC 后台表单 → 控制器 → 数据表字段保存链路与『改了不生效』排查。记录 ThinkCMF 后台 ToolController 基类的通用保存流程（request->param() 全量 → setParamData 归一化 → Model::create/save）、ActiveController::updatePost 的字段处理（布尔字段归一化、日期转时间戳、小程序码生成），以及字段能否落库取决于表单 name 与表字段是否一致。当新增/修改后台表单字段、字段保存后不生效，或排查『后台设置了但前端没变化』时调用。"
---

# PC 后台表单 → 控制器 → 表字段保存链路

以活动后台 `app/admin/controller/ActiveController.php` 为例。

## 1. 继承关系

`ActiveController extends ToolController`（`app/admin/controller/ToolController.php`）。

`ToolController` 提供通用 CRUD：
- `setAddPost($params)` → `$this->model->save($params)`
- `setEditPost($params)` → `$this->model->find($params['id'])->save($params)`
- `setParamData($item)` → `$this->setData($item, 1)`（子类可重写 `setData` 做字段转换）

## 2. 保存流程（`updatePost`）

```
$this->request->param()          // 拿到表单全部字段（input name 即字段名）
  → 布尔字段归一化：is_clock/is_badge/is_month_recommend = empty?0:1
  → 日期字段转时间戳：clock_start_time/clock_end_time = strtotime(...)
  → setParamData($params)        // 子类 setData 再做转换（如 date）
  → empty($params['id'])
       ├─ 是（新增）：ActiveModel::create($params) + 生成小程序码写 qr_code
       └─ 否（编辑）：无 qr_code 则补生成；setEditPost($params) 全量 save
```

## 3. 字段能否保存的判定

- **表单 `name` 必须与数据表字段名完全一致**，否则 `param()` 拿到的键名对不上，该字段被忽略或写错列。
- **编辑时只有出现在 `param()` 里的字段会被 `save()`**；表单里没有的字段不会被清空/覆盖。
- 需要类型/格式转换的字段（布尔、时间戳、JSON、逗号拼接）必须在控制器显式处理（如 `role` 用 `implode(',')`）。

## 4. 「后台设置了但没生效」排查清单

1. 表单 `name` 是否与表字段一致（最常见）。
2. 该字段是否真的在 `update.html` 表单里（不是只在详情页展示）。
3. 控制器是否对它做了覆盖/归一化（如布尔 `empty?0:1`）。
4. 表里字段是否存在、类型是否匹配（如 `join_type` 字段是否已加）。
5. 是否有缓存（`cmf_set_option` 类配置有缓存，需清缓存）。
6. 跑的是不是改后的代码（本机改完未部署/未同步到生产）。
7. 模板是否裸读 `$item.xxx` 导致新增模式报 `未定义变量`（见 skill `active-scan-join`）。

## 相关技能

- `active-scan-join`：活动参加方式（join_type）表单与保存。
- `pc-admin-add-column`：列表页加列（表头/循环/导出同步）。
- `pc-admin-search-export`：后台搜索与导出。
