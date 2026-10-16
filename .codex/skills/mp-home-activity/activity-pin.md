---
name: "mp-activity-pin"
description: "活动置顶功能全栈实现。记录活动置顶功能的完整链路：数据库字段、PC后台配置、接口排序逻辑、前端置顶样式。当需要实现或修改"某类数据的置顶展示"功能时调用。"
---

# 活动置顶功能全栈实现

## 适用场景

本项目"活动置顶"功能允许在 PC 后台配置某个活动是否置顶，置顶的活动在小程序活动列表里显示"置顶"标签，并按置顶优先级排序。当需要：
- 实现某类数据的置顶功能
- 修改置顶的排序逻辑
- 调整置顶标签的样式

## 核心实现思路

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ PC后台配置   │ ──> │ 数据库存储   │ ──> │ 接口排序     │ ──> │ 前端展示     │
│ is_top 开关  │     │ is_top 字段  │     │ ORDER BY    │     │ 置顶标签样式 │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

## 1. 数据库层

### 添加 is_top 字段

```sql
ALTER TABLE cmf_active ADD COLUMN is_top tinyint(1) DEFAULT 0 COMMENT '是否置顶 0否 1是';
```

### cmf_active 表置顶相关字段

| 字段 | 类型 | 默认 | 说明 |
|------|------|------|------|
| is_top | tinyint(1) | 0 | 是否置顶：0=否，1=是 |
| list_order | int(11) | 100 | 排序权重（越小越靠前） |

## 2. PC 后台层

### 控制器

在 `setWhere()` / `setIndex()` 钩子里添加置顶排序：

```php
// app/admin/controller/ActiveController.php

// 列表查询
public function setIndex()
{
    // ... 其他代码 ...
    
    // 置顶排序：置顶优先，再按 list_order 升序、id 降序
    $this->order = 'is_top desc, list_order asc, id desc';
    
    // ... 其他代码 ...
}
```

### 模板（列表页）

在列表页表头加一列"置顶"：

```html
<!-- public/themes/admin_fengiy/admin/active/index.html -->
<thead>
    <tr>
        <th>ID</th>
        <th>活动名</th>
        <th>置顶</th>  <!-- 新增列 -->
        ...
    </tr>
</thead>
<tbody>
    <foreach name="list" item="vo">
        <tr>
            <td>{$vo.id}</td>
            <td>{$vo.name}</td>
            <td>
                <input type="checkbox" class="js-top-toggle" 
                       data-id="{$vo.id}" value="1"
                       <if condition="$vo.is_top eq 1">checked</if>>
            </td>
            ...
        </tr>
    </foreach>
</tbody>
```

### 模板（编辑页）

在编辑表单里添加置顶开关：

```html
<!-- public/themes/admin_fengiy/admin/active/update.html -->
<div class="form-group">
    <label>是否置顶</label>
    <input type="checkbox" name="is_top" value="1" 
           <if condition="$item.is_top eq 1">checked</if>>
    <small>置顶活动将在小程序列表靠前显示</small>
</div>
```

### 保存处理

在 `setAddPost()` / `setEditPost()` 里接收 is_top 参数：

```php
public function setAddPost($params)
{
    // ... 其他代码 ...
    
    // 置顶：复选框未提交时设为0
    $params['is_top'] = isset($params['is_top']) ? 1 : 0;
    
    return $params;
}
```

## 3. 小程序接口层

### 修改排序逻辑

```php
// api/wxapp/controller/ActiveController.php

public function get_new_active_list()
{
    // ... 其他查询条件 ...
    
    // 置顶排序：置顶优先，再按 list_order 升序、id 降序
    $list = ActiveModel::where($where)
        ->order('is_top desc, list_order asc, id desc')
        ->paginate($page_size);
    
    return $this->success('获取成功', $list);
}
```

### is_top 字段会随 SELECT * 返回

由于接口用 `field('a.*')` 或类似写法，`is_top` 字段会自动包含在返回数据里，不需要额外处理。

## 4. 小程序前端层

### 模板绑定置顶样式

```vue
<!-- 小程序/pages/low_carbon/low_carbon.vue -->
<view v-for="item in active_list" :key="item.id"
      class="u-radius-10 u-type-white-bg u-rela u-shadow u-m-b-30"
      :class="{'top-pinned-card': item.is_top == 1}">
    <!-- 活动卡片内容 -->
    <u-image width="100%" height="285" :src="item.thumb"></u-image>
    ...
</view>
```

> `:class="{'top-pinned-card': item.is_top == 1}"` 是动态 class 绑定，`is_top == 1` 时自动加 `top-pinned-card` 类

### 置顶标签样式

```scss
/* 活动列表卡片 */
.activity-card {
    overflow: hidden;
    position: relative;
}

/* 置顶卡片：右上角"置顶"标签 */
.top-pinned-card {
    overflow: hidden;
    position: relative;
}
.top-pinned-card::before {
    content: '置顶';
    position: absolute;
    top: 0;
    right: 0;
    z-index: 10;
    padding: 6rpx 18rpx;
    font-size: 22rpx;
    line-height: 1.4;
    color: #fff;
    background: linear-gradient(135deg, #ff6b35 0%, #ff3d00 100%);  /* 橙红渐变 */
    border-bottom-left-radius: 14rpx;
    border-top-right-radius: 10rpx;
    box-shadow: 0 2rpx 6rpx rgba(255, 61, 0, .35);
}
```

## 5. 完整排序逻辑

### 多字段排序优先级

```sql
ORDER BY is_top DESC, list_order ASC, id DESC
```

| 排序字段 | 方向 | 说明 |
|---------|------|------|
| is_top | DESC | 置顶（1）在前，非置顶（0）在后 |
| list_order | ASC | 数字越小越靠前（100 为默认值） |
| id | DESC | id 越大（越新）越靠前 |

### 效果示例

| id | name | is_top | list_order | 显示顺序 |
|----|------|--------|-----------|---------|
| 3 | 活动C | 1 | 100 | 1（置顶） |
| 1 | 活动A | 1 | 200 | 2（置顶） |
| 5 | 活动E | 0 | 100 | 3（新+靠前） |
| 2 | 活动B | 0 | 100 | 4 |
| 4 | 活动D | 0 | 200 | 5 |

## 6. 设计决策：为什么不做后端可配置？

根据项目实际情况（提交 `46d4ce6` 的决策）：

| 方案 | 适用场景 | 本项目选择 |
|------|---------|-----------|
| 静态 class 写死 | 只有固定入口卡片天然是"置顶"语义 | ✅ 采用 |
| 后端 is_top 字段 | 需要灵活控制任意活动置顶 | ❌ 未采用 |
| 后台开关配置 | 需要运营人员控制置顶状态 | ❌ 未采用 |

**结论**：当前置顶功能**不做后端可配置**。只有"垃圾分类打卡"这张固定入口卡片天然是"置顶"语义（它是写死的固定入口，永远在标签顶部），所以静态加 class 即可。

## 7. 扩展：如果未来需要后端控制

如果未来确实需要后端控制活动置顶，按以下步骤扩展：

### Step 1: 数据库加字段
```sql
ALTER TABLE cmf_active ADD COLUMN is_top tinyint(1) DEFAULT 0;
```

### Step 2: 后台模板加开关
```html
<!-- update.html -->
<input type="checkbox" name="is_top" value="1" <if condition="$item.is_top eq 1">checked</if>>
```

### Step 3: 接口排序
```php
->order('is_top desc, list_order asc, id desc')
```

### Step 4: 前端动态 class
```vue
:class="{'top-pinned-card': item.is_top == 1}"
```

## 8. 注意事项

### 置顶逻辑约束

- ✅ `is_top desc` 必须在排序最前面（置顶优先）
- ✅ 前端 `is_top == 1` 用宽松比较（`'1'` 和 `1` 都匹配）
- ✅ 后台复选框未提交时记得清空（设为 0）
- ❌ 不要只按 `is_top desc` 排序（相同置顶状态内的顺序会乱）
- ❌ 不要忘记列表 list_order 的权重设计

### 样式注意

- `top-pinned-card` 类必须有 `position: relative`（因为 ::before 用 absolute）
- ::before 的 `z-index` 要高于卡片其他内容
- `border-top-right-radius` 与卡片的圆角对齐

## 相关 Git 提交

- `46d4ce6` - 新增活动置顶功能，支持后台配置和前端置顶展示
