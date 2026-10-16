---
name: "pc-admin-search-export"
description: "PC后台搜索筛选与导出功能实现模式。记录后台列表页的搜索条件构建（时间范围、ID、模糊匹配忽略特殊符号）、导出功能的表头/数据同步、分页跳转功能等通用实现。当需要新增/修改后台列表的搜索条件、导出功能、或分页功能时调用。"
---

# PC后台搜索筛选与导出功能实现模式

## 适用场景

本项目 PC 后台基于 ThinkCMF6/ToolController，所有列表页遵循统一的"搜索 + 列表 + 导出"模式。当需要：
- 给现有列表新增搜索条件（时间范围、ID、下拉筛选）
- 修复导出数据与列表展示不一致的问题
- 新增导出功能
- 添加分页跳转功能

## 核心技术栈

| 组件 | 类/文件 | 说明 |
|------|---------|------|
| 基类 | `ToolController` | 提供 `setWhere()`/`setIndex()`/`outData()` 钩子方法 |
| 分页 | `Doctrine\DBAL\Query\QueryBuilder` 的 `paginate()` | ThinkPHP 分页 |
| 导出 | `CommonModel::outData()` | 统一的 Excel 静态导出方法 |
| 时间处理 | `strtotime()` | 日期字符串转时间戳 |

## 1. 搜索条件构建模式

### 标准搜索条件（在 setWhere/setIndex 方法里）

```php
// app/admin/controller/XxxController.php

// 关键字模糊搜索（单字段）
if (!empty($param['name'])) {
    $map[] = ['a.name', 'like', '%' . trim($param['name']) . '%'];
}

// ID 精确匹配
if (!empty($param['id'])) {
    $map[] = ['a.id', '=', trim($param['id'])];
}

// 类型筛选
if (!empty($param['type'])) {
    $map[] = ['a.type', '=', $param['type']];
}

// 时间范围搜索（start_time / end_time）
$start_time = $this->request->param('start_time', '', 'trim');
$end_time = $this->request->param('end_time', '', 'trim');
if ($start_time) $map[] = ['a.create_time', '>=', strtotime($start_time)];
if ($end_time) $map[] = ['a.create_time', '<=', strtotime($end_time) + 86399];  // +86399 表示当天23:59:59
```

### 多字段模糊搜索（姓名+手机号+ID）

```php
// 列表页"用户"列显示为 (ID)昵称，用户可能按 ID、昵称、手机号搜索
if (!empty($param['name'])) {
    $map[] = ['b.nickname|b.mobile|b.id', 'like', '%' . trim($param['name']) . '%'];
}
```

> ThinkPHP 的 `|` 分隔表示 OR 匹配

## 2. 小程序接口：忽略特殊符号模糊匹配

### 问题背景

小区名称可能含特殊符号（如"中海 · 华庭"），用户搜索时一般不会输入特殊符号（如搜"中海华庭"），需要忽略间隔号、空格、标点等进行模糊匹配。

### 解决方案：MySQL REPLACE 链式嵌套

```php
// api/wxapp/controller/ForumController.php
// 小区搜索接口：忽略特殊符号模糊匹配
$keyword = $this->request->param('keyword', '', 'trim');

// 1. 去除关键字中的特殊符号，仅保留中文、字母、数字
$pureKeyword = preg_replace('/[^\p{Han}a-zA-Z0-9]/u', '', $keyword);

// 2. 定义要忽略的特殊符号列表（间隔号、空格、标点、连接符等）
$ignoreSymbols = [
    ' ', '　',        // 空格(半角/全角)
    '·', '•', '‧', '・', '∙', '･',  // 间隔号
    '.', '．', '。', ',', '，',       // 点号/逗号
    '-', '–', '—', '－', '_', '~', '～',  // 连接符
    '(', ')', '（', '）', '[', ']', '【', '】',  // 括号
    '"', '"', ''', ''',  // 引号
];

// 3. 构建 MySQL REPLACE 链式表达式
$nameExpr = 'c.name';
foreach ($ignoreSymbols as $symbol) {
    $nameExpr = "REPLACE({$nameExpr}, '{$symbol}', '')";
}

// 4. 使用 whereRaw 做条件匹配
$list = $communityModel->alias('c')
    ->field('c.id, c.name')
    ->when($pureKeyword !== '', function ($query) use ($nameExpr, $pureKeyword) {
        $query->whereRaw("{$nameExpr} LIKE :keyword", ['keyword' => '%' . $pureKeyword . '%']);
    })
    ->order('c.list_order asc')
    ->paginate(['page' => $page, 'page_size' => $page_size]);
```

### 生成的 SQL 示例

```sql
name = "中海 · 华庭"

REPLACE(REPLACE(REPLACE(REPLACE(name, ' ', ''), '　', ''), '·', ''), '•', '') 
LIKE '%中海华庭%'
```

> 逐层剥掉空格、全角空格、圆点、圆点... 最终得到"中海华庭"与"中海华庭"匹配

## 3. 导出功能

### 方式一：CommonModel::outData（推荐）

适用于大多数列表导出，保持筛选、排序条件与列表一致：

```php
// 在控制器里
public function export()
{
    // 复用 setWhere/setIndex 的逻辑构建查询
    $list = $this->setIndex()->select();
    
    // 表头定义
    $head = ['订单号', '用户', '商品信息', '总价', '状态', '创建时间'];
    
    // 字段键（与 $head 按位置对应）
    $keys = ['order_num', 'user_info', 'goods_info', 'price', 'status_name', 'create_time_fmt'];
    
    // 调用统一导出
    CommonModel::outData('订单导出_' . date('Ymd'), $list, $head, $keys);
}
```

### 方式二：PhpSpreadsheet 手写单元格

适用于需要合并单元格、复杂样式的场景（如订单的嵌套表格）：

```php
// 订单导出：外层订单行 + 内层商品行
foreach ($list as $v => $item) {
    $start = $j;
    $end = $start + count($item['goods']) - 1;
    
    // 订单信息合并单元格
    $objPHPExcel->getActiveSheet()->mergeCells("A{$start}:A{$end}");
    $objPHPExcel->getActiveSheet()->mergeCells("B{$start}:B{$end}");
    // ... 其他合并的列
    
    // 订单层级字段
    $objPHPExcel->getActiveSheet()->setCellValue("A" . $start, $v['order_num']);
    $objPHPExcel->getActiveSheet()->setCellValue("B" . $start, $v['user_info']);
    
    // 订单层级拆分列：商品名称、单价、数量、优惠券、总价、状态、时间
    $objPHPExcel->getActiveSheet()->setCellValue("C" . $start, $v['coupon_price']);
    $objPHPExcel->getActiveSheet()->setCellValue("D" . $start, $v['price']);
    $objPHPExcel->getActiveSheet()->setCellValue("E" . $start, $v['status_name']);
    $objPHPExcel->getActiveSheet()->setCellValue("F" . $start, $v['create_time']);
}

// 内层：每个商品一行
foreach ($item['goods'] as $v1) {
    $objPHPExcel->getActiveSheet()->setCellValue("B" . $j, $v1['goods_name']);
    $objPHPExcel->getActiveSheet()->setCellValueExplicit("C" . $j, $v1['one_price'], \PHPExcel_Cell_DataType::TYPE_STRING);
    $objPHPExcel->getActiveSheet()->setCellValue("D" . $j, $v1['count']);
    $j++;
}
```

### 导出关键点

| 要点 | 说明 |
|------|------|
| **表头与数据键位置对应** | `$head[$i]` 对应 `$keys[$i]`，顺序必须一致 |
| **列表与导出字段同步** | 导出方法的查询条件必须和列表保持一致 |
| **合并单元格** | 嵌套结构需要合并外层单元格（如订单号合并多行商品） |
| **setCellValueExplicit** | 长数字（手机号、卡号）要用 TYPE_STRING 防止科学计数法 |
| **内存问题** | 大表导出可能 OOM，需设置 `ini_set('memory_limit', '512M')` 或分页 |

## 4. 搜索条件与导出的一致性规则

### 必须同步的代码

列表页 `setWhere()` / `setIndex()` 的每个搜索条件，导出方法里也必须同步：

```php
// 列表 index() 里的条件
$map[] = ['a.status', '=', $param['status']];
$map[] = ['a.create_time', '>=', strtotime($param['start_time'])];

// 导出方法里也必须加同样的条件！
$exportMap[] = ['a.status', '=', $param['status']];
$exportMap[] = ['a.create_time', '>=', strtotime($param['start_time'])];
```

**避免出现**：列表显示 50 条符合条件的数据，点导出却导出几万条（漏了条件）。

## 5. 模板搜索栏模式

### 后台搜索输入框模板

```html
<!-- public/themes/admin_fengiy/admin/xxx/index.html -->
<div class="search-box">
    <!-- 下拉筛选 -->
    <select name="type" class="form-control" style="width: 120px;">
        <option value="">全部类型</option>
        <option value="1" <if condition="input('request.type') eq '1'">selected</if>>选项1</option>
        <option value="2" <if condition="input('request.type') eq '2'">selected</if>>选项2</option>
    </select>
    
    <!-- ID 精确搜索 -->
    <input type="text" class="form-control" name="id" style="width: 140px;" 
           value="{:input('request.id/s','')}" placeholder="请输入用户id">
    
    <!-- 关键字模糊搜索 -->
    <input type="text" class="form-control" name="name" style="width: 140px;" 
           value="{:input('request.name/s','')}" placeholder="昵称/手机号/用户ID">
    
    <!-- 时间范围 -->
    <input type="text" class="form-control" name="start_time" style="width: 140px;" 
           value="{:input('request.start_time/s','')}" placeholder="开始日期" 
           onclick="laydate({istime: false, format: 'YYYY-MM-DD'})">
    <input type="text" class="form-control" name="end_time" style="width: 140px;" 
           value="{:input('request.end_time/s','')}" placeholder="结束日期"
           onclick="laydate({istime: false, format: 'YYYY-MM-DD'})">
    
    <!-- 搜索与重置 -->
    <button type="submit" class="btn btn-primary">搜索</button>
    <a href="{:url('index')}" class="btn btn-default">重置</a>
    
    <!-- 导出按钮 -->
    <button type="button" class="btn btn-success" onclick="exportData()">导出</button>
</div>
```

### 分页跳转功能

在分页条右侧添加一个跳转输入框：

```html
<!-- 在分页条后面 -->
<div class="pagination-jump">
    跳至 
    <input type="number" id="jump_page" min="1" max="{$page}" style="width: 60px;">
    页 
    <button onclick="jumpPage()">确定</button>
</div>

<script>
function jumpPage() {
    var page = document.getElementById('jump_page').value;
    if (page < 1 || page > {$maxPage}) return;
    var url = "{:url('index')}&page=" + page;
    // 带上当前搜索参数
    window.location.href = url;
}
</script>
```

## 6. 后台菜单配置

### 在 admin_menu 里添加菜单

```php
// data/lang/zh-cn/admin_menu.php 或直接在数据库里插入

// 新增的菜单必须有正确的 app/controller/action，否则权限校验不通过
// 示例：首页通知管理
[
    'name' => '首页通知',
    'app' => 'admin',
    'controller' => 'HomeNoticeController',
    'action' => 'index',
    'parent' => 'home_config',   // 父菜单标识
]
```

## 7. 注意事项

### 常见坑

| 问题 | 原因 | 解决方案 |
|------|------|---------|
| 导出数据比列表多 | 导出方法漏了搜索条件 | 复制 setWhere/setIndex 的条件代码 |
| 导出报内存溢出 | 数据量太大 | 分页导出或增加 memory_limit |
| 长数字变科学计数法 | Excel 自动转为数字 | 用 setCellValueExplicit + TYPE_STRING |
| 列表数据错乱 | 表头与 $keys 顺序不一致 | 逐个核对顺序 |
| 时间搜索不到数据 | 时间戳格式不一致 | 用 `strtotime()` 转换 |
| 分页页码不更新 | URL 未带当前搜索参数 | jumpPage 时带上 input 值 |

### 不要做的事

- ❌ 导出方法与列表方法用完全独立的查询（易不一致）
- ❌ 时间范围搜索时忘了 `+86399`（当天结束时间要算到 23:59:59）
- ❌ 搜索条件带 `like` 但不过滤 `%` `_`（用户输入这两个会被当通配符）
- ❌ 导出时不设 memory_limit 导致大表 OOM

## 相关 Git 提交

- `8a5760f` - 优化小区搜索匹配逻辑，支持忽略特殊符号模糊匹配
- `695d5ca` - 优化搜索匹配逻辑，支持按用户ID搜索
- `93a5295` - 新增会员搜索筛选与账户记录导出功能
- `8520c02` - 优化订单导出表格，拆分商品信息为名称单价数量列
- `49a4296` - 为用户管理页面添加导出按钮，支持导出当前筛选排序的用户数据
