---
name: "mp-image-url-complete"
description: "小程序图片链接补全逻辑。记录小程序接口返回数据中图片 URL 的补全规则，包括 cmf_get_asset_url 函数的使用、相对路径与绝对路径判断、二维数组批量补全等。当需要处理小程序图片 URL 或修复图片不显示问题时调用。"
---

# 小程序图片链接补全逻辑

> **2026-09 起全站 COS**：公网 URL 为 `https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/...`（**无** `/upload/`）。后端统一 `cmf_get_asset_url()`；小程序 `http.interceptor` → `deepResolveAssetUrls`（见 `cos-storage` skill）。勿再拼 `admin…/upload/`。

## 适用场景

本项目后台配置的图片字段（如首页入口图片、活动缩略图、Banner 图等）在存储时多为相对键（如 `xcxkf052/admin/xxx.png`），接口需补全为 COS 完整 URL。当需要：
- 在接口返回数据前补全图片 URL
- 处理不确定是相对路径还是完整 URL 的图片字段
- 修复小程序图片不显示的问题

## 核心函数：cmf_get_asset_url()

### 函数定义

```php
// ThinkCMF 公共函数
function cmf_get_asset_url($path, $domains = '')
```

### 行为逻辑

| 输入 | 输出 | 说明 |
|------|------|------|
| 完整 URL（含 `http://` 或 `https://`） | 原样返回 | 无需补全 |
| 相对路径（如 `/upload/xxx.png`） | 拼接站点 URL | 如 `https://www.xiayuxuan.com/upload/xxx.png` |
| 空字符串 | 返回空字符串 | 不处理 |

### 返回值示例

```php
cmf_get_asset_url('https://cdn.xxx.com/img.png');  
// 返回: 'https://cdn.xxx.com/img.png' (原样)

cmf_get_asset_url('/upload/2026/09/test.png');  
// 返回: 'https://www.xiayuxuan.com/upload/2026/09/test.png'

`cmf_get_image_preview_url()`（后台缩略图）同样：**只有以 `http` 或 `/` 开头才当 URL**。字段若是 JSON 数组（如整改提交 `images` = `["https://oss..."]`），整段以 `[` 开头，会被拼到 OSS 成 `https://oss.ausite.cn/[`。先 `json_decode` 再逐张 preview。整改任务见 `volunteer-task`。

## 1. 接口层补全模式

### 模式一：单个字段补全

最简单的场景，只需补全一个字段：

```php
// 获取数据后，补全单个图片字段
$list = Model::select();
foreach ($list as &$item) {
    if (!empty($item['image'])) {
        $item['image'] = cmf_get_asset_url($item['image']);
    }
}
```

### 模式二：二维数组批量补全

适用于配置项是二维数组的场景（如首页入口列表）：

```php
// api/wxapp/controller/BasicController.php

public function findSetting()
{
    $name = $this->request->param('name', '');
    $value = cmf_get_option($name);
    
    // 根据不同配置项做对应的 URL 补全
    if ($name === 'home_entries' && is_array($value)) {
        // 二维数组：每个子项里的 image 字段都要补全
        foreach ($value as $idx => $item) {
            if (!empty($item['image'])) {
                $value[$idx]['image'] = cmf_get_asset_url($item['image']);
            }
        }
    }
    
    $this->success('获取成功', $value);
}
```

### 模式三：列表数据批量补全

活动列表、商品列表等含图片的场景：

```php
// api/wxapp/controller/ActiveController.php

public function get_new_active_list()
{
    // ... 查询数据 ...
    
    $list = ActiveModel::where($where)->paginate($page_size);
    
    // 批量补全每条数据的缩略图 URL
    foreach ($list as &$item) {
        if (!empty($item['thumb'])) {
            $item['thumb'] = cmf_get_asset_url($item['thumb']);
        }
        if (!empty($item['badge_image'])) {
            $item['badge_image'] = cmf_get_asset_url($item['badge_image']);
        }
    }
    
    return $this->success('获取成功', $list);
}
```

### 模式四：嵌套数组补全

订单列表里嵌套了商品数组，每个商品也有图片：

```php
foreach ($orderList as &$order) {
    // 订单层级图片
    if (!empty($order['thumb'])) {
        $order['thumb'] = cmf_get_asset_url($order['thumb']);
    }
    
    // 商品列表层级图片
    if (!empty($order['goods']) && is_array($order['goods'])) {
        foreach ($order['goods'] as &$goods) {
            if (!empty($goods['image'])) {
                $goods['image'] = cmf_get_asset_url($goods['image']);
            }
        }
    }
}
```

## 2. 判断逻辑的常见错误

### ❌ 错误写法 1：没有判断空值

```php
// 错误：空值也会调 cmf_get_asset_url
$item['image'] = cmf_get_asset_url($item['image']);
```

问题：当 `image` 为空字符串或 null 时，虽然 `cmf_get_asset_url` 会返回空，但没必要增加函数调用开销。更关键的是，空值判断可以让前端区分"没配图片"和"配了图片但加载失败"。

### ✅ 正确写法

```php
// 正确：只在非空时补全
if (!empty($item['image'])) {
    $item['image'] = cmf_get_asset_url($item['image']);
}
```

### ❌ 错误写法 2：对非图片字段调用 cmf_get_asset_url

```php
// 错误：对非 URL 字段也做补全
$item['name'] = cmf_get_asset_url($item['name']);  // name 是文本，不是 URL！
```

### ✅ 正确写法

```php
// 正确：只对图片/文件字段做补全
$imageFields = ['thumb', 'image', 'avatar', 'cover', 'badge_image'];
foreach ($imageFields as $field) {
    if (!empty($item[$field])) {
        $item[$field] = cmf_get_asset_url($item[$field]);
    }
}
```

### ❌ 错误写法 3：判断 URL 的逻辑过于严格

```php
// 错误：只判断 http，忽略了 https
if (strpos($item['image'], 'http://') === 0) {  // https 开头的也会被补全，导致 URL 重复拼接！
    // ...
}
```

### ✅ 正确写法

```php
// 正确：让 cmf_get_asset_url 自动判断
// 它的内部逻辑是：只要包含 http:// 或 https:// 就原样返回，否则拼接站点 URL
$item['image'] = cmf_get_asset_url($item['image']);
```

## 3. 前端显示逻辑

### v-if 判断避免空图片

```vue
<!-- 只在有图片时渲染 image 组件 -->
<u-image v-if="item.thumb" :src="item.thumb" width="100%" height="285"></u-image>

<!-- 或者用 fallback 占位图 -->
<u-image :src="item.thumb || '/static/no-image.png'" width="100%" height="285"></u-image>
```

## 4. 完整示例：首页入口配置

### 后台存储格式

```json
[
    {"key": "active", "name": "社区活动", "image": "/upload/2026/09/active.png", "url": "..."},
    {"key": "health", "name": "健康测试", "image": "", "url": "..."},
    {"key": "mall", "name": "积分商城", "image": "", "url": "..."},
    {"key": "community", "name": "社区互动", "image": "", "url": "..."}
]
```

### 接口补全

```php
// api/wxapp/controller/BasicController.php home_entries 补全
if ($option_name === 'home_entries') {
    foreach ($value as $idx => $item) {
        if (!empty($item['image'])) {
            $value[$idx]['image'] = cmf_get_asset_url($item['image']);
        }
    }
}
```

### 前端展示

```vue
<view v-for="entry in entryList" :key="entry.key">
    <!-- 有图片显示图片，没图片显示图标 -->
    <u-image v-if="entry.image" :src="entry.image" width="80" height="80"></u-image>
    <u-icon v-else :name="entry.icon" size="80" :color="entry.color"></u-icon>
    
    <text>{{ entry.name }}</text>
</view>
```

## 5. 补全规则总结

| 字段类型 | 是否补全 | 判断条件 | 说明 |
|---------|---------|---------|------|
| 图片字段（image/thumb/avatar/cover） | ✅ 补全 | `!empty($item['field'])` | 相对路径补全为完整 URL |
| 文件字段（file/attachment） | ✅ 补全 | `!empty($item['field'])` | 同上 |
| 文本字段（name/desc/content） | ❌ 不补全 | - | 文本不需要补全 |
| ID 字段 | ❌ 不补全 | - | 数字不需要补全 |
| 链接字段（url/link） | ❌ 不补全 | - | 链接通常是绝对路径 |

## 6. 排查图片不显示的步骤

当小程序图片不显示时，按以下步骤排查：

1. **检查接口返回**：确认返回的图片字段是完整 URL 还是相对路径
   - 如果是相对路径，在接口返回前加 `cmf_get_asset_url()` 补全
   
2. **检查空值**：确认字段值不是空字符串
   - 空字符串在 v-if 里是 false，不会渲染图片组件
   
3. **检查域名**：确认图片域名在小程序后台配置了合法域名
   - 微信公众平台 → 开发管理 → 服务器域名 → downloadFile 合法域名

4. **`<image>` 不要用远程 SVG**：浏览器能打开 COS 的 `.svg`，真机/开发者工具里 `<image src="…svg">` 常空白。运营切图请上传 **PNG/WebP**（如 `static/user/quick/xxx.png`）。

5. **检查文件是否存在**：直接打开图片 URL 应 200（COS 键无多余 `/upload/`）

## 7. 不要做的事

- ❌ 对所有字段都调 `cmf_get_asset_url`（文本/ID 字段不需要）
- ❌ 忘记判断空值就调补全函数（增加无意义开销）
- ❌ 在前端做 URL 判断和拼接（应由接口统一处理）
- ❌ 修改 `cmf_get_asset_url` 内部逻辑（会影响全局）
- ❌ 在补全后覆盖原始数据（如果需要保留原始路径，另存字段）

## 相关 Git 提交

- `03c79a1` - 修复图片链接补全逻辑的判断错误
- `f13cb95` - 新增首页入口配置，包含 home_entries 图片补全逻辑
