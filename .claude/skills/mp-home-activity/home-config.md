---
name: "mp-home-config"
description: "小程序首页动态配置模式。记录首页功能入口、走马灯通知、商品列表等如何从后台动态配置（cmf_set_option）并在前端展示的完整实现模式。当需要新增/修改首页可配置项、或在其他页面实现类似后台配置+前端动态渲染的功能时调用。"
---

# 小程序首页动态配置模式

## 适用场景

本项目首页的数据（功能入口、走马灯通知、商品列表等）全部由后台配置驱动，前端只负责读取和渲染。当需要：
- 在首页新增可配置的功能模块
- 在小程序其他页面实现"后台配置 + 前端动态渲染"的模式
- 修改首页现有配置项的逻辑

## 核心技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 后台存储 | `cmf_set_option($name, $value)` / `cmf_get_option($name)` | ThinkCMF 配置选项，存 cmf_option 表 |
| PC 后台管理 | Controller + Template | 服务端渲染 PHP 模板 |
| 小程序接口 | `BasicController::findSetting()` | 读取 option 并返回 JSON |
| 小程序前端 | uni-app Vue 组件 | 异步拉取配置并渲染 |

## 配置项清单

| 配置项名 | 说明 | 存储格式 | 前端使用 |
|---------|------|---------|---------|
| `home_entries` | 首页功能入口（4个） | JSON 二维数组 | `pages/index/index.vue` |
| `home_notice` | 首页走马灯通知文字 | 纯文本字符串 | `pages/index/index.vue` |
| `home_banner` | 首页 Banner 图 | JSON 数组 | `pages/index/index.vue` |

> 注意：首页除上述**后台可配置项**外，还有若干**硬编码模块**（顶部 logo 导航栏、活动搜索+扫码入口、问候/快捷打卡卡、热门活动横滑、兑换商品网格）。这些不走 `cmf_set_option`，改动位置与布局约定见 skill `mp-index-layout`。

## 1. 后台 Controller 模式

### 简单字符串型（cmf_set_option）

适用于单一文本/数字配置：

```php
// app/admin/controller/HomeNoticeController.php
class HomeNoticeController extends AdminBaseController
{
    public function index()
    {
        $option_name = 'home_notice';
        if ($this->request->isPost()) {
            $notice = $this->request->param('notice', '');
            $notice = trim($notice);
            $res = cmf_set_option($option_name, $notice, true);  // true=存为字符串
            if ($res !== false) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        } else {
            $notice = cmf_get_option($option_name);
            if (empty($notice) || !is_string($notice)) {
                $notice = '';
            }
            $this->assign('notice', $notice);
            return $this->fetch();
        }
    }
}
```

### JSON 数组型（cmf_set_option + JSON_UNESCAPED_UNICODE）

适用于结构化数据（如入口列表）：

```php
// app/admin/controller/HomeEntriesController.php
class HomeEntriesController extends AdminBaseController
{
    public function index()
    {
        $option_name = 'home_entries';
        if ($this->request->isPost()) {
            $data = $this->request->param('entries/a');  // /a 强制数组
            $clean = [];
            foreach ($data as $item) {
                $clean[] = [
                    'key'  => trim($item['key'] ?? ''),
                    'name' => trim($item['name'] ?? ''),
                    'desc' => trim($item['desc'] ?? ''),
                    'image'=> trim($item['image'] ?? ''),
                    'url'  => trim($item['url'] ?? ''),
                    'type' => trim($item['type'] ?? 'navigateTo'),
                ];
            }
            // JSON_UNESCAPED_UNICODE：保存中文不转义
            $res = cmf_set_option($option_name, $clean, true);
            if ($res !== false) {
                $this->success('保存成功');
            } else {
                               $this->error('保存失败');
            }
        } else {
            $entries = cmf_get_option($option_name);
            if (empty($entries) || !is_array($entries) || count($entries) !== 4) {
                // 默认 fallback（与前端保持一致）
                $entries = [
                    ['key' => 'active',    'name' => '社区活动', 'desc' => '参加低碳环保活动', 'image' => '', 'url' => '/pages/low_carbon/low_carbon', 'type' => 'switchTab'],
                    ['key' => 'health',    'name' => '健康测试', 'desc' => '关注身体健康',     'image' => '', 'url' => '/pages/health/health',         'type' => 'switchTab'],
                    ['key' => 'mall',      'name' => '积分商城', 'desc' => '环保好礼兑换',     'image' => '', 'url' => '/pages/mall/mall',             'type' => 'switchTab'],
                    ['key' => 'community', 'name' => '社区互动', 'desc' => '分享绿色生活',     'image' => '', 'url' => '/pages/sjhd/sjhd',             'type' => 'navigateTo'],
                ];
            }
            $this->assign('entries', $entries);
            return $this->fetch();
        }
    }
}
```

## 2. 后台模板模式

### 简单表单模板（通知配置）

```html
<!-- public/themes/admin_fengiy/admin/home_notice/index.html -->
<form class="js-ajax-form" method="post" action="{:url('HomeNoticeController/index')}">
    <div class="form-group">
        <label>通知内容</label>
        <input type="text" class="form-control" name="notice" 
               value="{$notice}" placeholder="留空则不显示通知栏">
        <small class="text-danger">通知将显示在小程序首页 Banner 下方</small>
    </div>
    <button type="submit" class="btn btn-primary">保存</button>
</form>
```

> 关键点：表单带 `js-ajax-form` 类名，前端自动 ajax 提交

### JSON 数组配置模板（入口配置）

```html
<!-- public/themes/admin_fengiy/admin/home_entries/index.html -->
<form class="js-ajax-form" method="post" action="{:url('HomeEntriesController/index')}">
    <volist name="entries" key="k" id="vo">
    <div class="entry-item" style="border:1px solid #eee; padding:15px; margin-bottom:15px;">
        <h5>入口 <span>{$k}</span></h5>
        <div class="form-group">
            <label>标识 key</label>
            <input type="text" class="form-control" name="entries[{$k}][key]" value="{$vo.key}">
        </div>
        <div class="form-group">
            <label>名称</label>
            <input type="text" class="form-control" name="entries[{$k}][name]" value="{$vo.name}">
        </div>
        <div class="form-group">
            <label>图片URL</label>
            <input type="text" class="form-control" name="entries[{$k}][image]" value="{$vo.image}">
        </div>
        <div class="form-group">
            <label>跳转URL</label>
            <input type="text" class="form-control" name="entries[{$k}][url]" value="{$vo.url}">
        </div>
        <div class="form-group">
            <label>跳转类型</label>
            <select name="entries[{$k}][type]" class="form-control">
                <option value="navigateTo" <if condition="$vo.type eq 'navigateTo'">selected</option>>
                <option value="switchTab" <if condition="$vo.type eq 'switchTab'">selected</option>>
            </select>
        </div>
    </div>
    </volist>
    <button type="submit" class="btn btn-primary">保存</button>
</form>
```

## 3. 小程序接口层（BasicController）

共用 `BasicController::findSetting()` 方法读取 option：

```php
// api/wxapp/controller/BasicController.php
// 已有方法：根据 option_name 读取配置
public function findSetting()
{
    $name = $this->request->param('name', '');
    $value = cmf_get_option($name);
    
    // 根据不同配置项做URL补全
    if ($name === 'home_entries' && is_array($value)) {
        foreach ($value as $idx => $item) {
            if (!empty($item['image'])) {
                // 后台传的图片路径可能是相对路径，需补全为完整URL
                $value[$idx]['image'] = cmf_get_asset_url($item['image']);
            }
        }
    }
    
    $this->success('获取成功', $value);
}
```

### 图片 URL 补全规则

后台配置的图片字段，在接口返回前端前必须做 URL 补全：

```php
// 单个字段补全
$value = cmf_get_asset_url($value);

// 二维数组里某个字段补全
foreach ($value as $idx => $item) {
    if (!empty($item['image'])) {
        $value[$idx]['image'] = cmf_get_asset_url($item['image']);
    }
}
```

> `cmf_get_asset_url()` 函数会自动判断：如果是完整 URL（含 http）则原样返回，否则拼接站点 URL。

## 4. 小程序前端层

### API 封装

```js
// 小程序/api/home.js
findSetting(data) {
    return this.$u.get('/wxapp/basic/findSetting', data)
},
```

### Vue 页面调用

```vue
<!-- 小程序/pages/index/index.vue -->
<script>
export default {
    data() {
        return {
            homeNotice: '',       // 走马灯通知文本
            entryList: [],        // 功能入口列表
            bannerList: [],       // Banner图列表
        }
    },
    onLoad() {
        this.getHomeNotice()
        this.getHomeEntries()
        this.getBannerList()
    },
    methods: {
        // 拉取走马灯通知
        async getHomeNotice() {
            try {
                let res = await this.$u.api.findSetting({ name: 'home_notice' })
                this.homeNotice = (res.data || '').toString()
            } catch (e) {
                // 接口失败不显示通知栏
            }
        },
        // 拉取首页入口配置
        async getHomeEntries() {
            try {
                let res = await this.$u.api.findSetting({ name: 'home_entries' })
                if (Array.isArray(res.data) && res.data.length === 4) {
                    this.entryList = res.data
                }
                // 接口失败时 entryList 保持数组的 fallback 默认值
            } catch (e) {
                // 接口失败不影响，保留 fallback
            }
        },
        // 核心入口跳转
        goEntry(item) {
            if (item.type === 'switchTab') {
                this.$u.route({ url: item.url, type: 'switchTab' })
            } else {
                this.$u.route(item.url)
            },
        },
    }
}
</script>

<template>
    <!-- 走马灯通知栏（后台 home_notice 配置，留空则不显示） -->
    <view class="notice-bar-wrap" v-if="homeNotice">
        <u-notice-bar :list="[homeNotice]" :volume-icon="true" 
                     :more-icon="false" :close-icon="false" :speed="2000">
        </u-notice-bar>
    </view>

    <!-- 核心功能入口：左侧大卡 + 右侧上两小块 + 右下横条 -->
    <view class="entry-grid">
        <!-- 遍历 entryList 渲染入口卡片 -->
    </view>
</template>
```

## 5. 添加新配置项的完整步骤

需求："在首页新增一个可配置的 XX 模块"

### Step 1: 小程序接口层

在 `BasicController.php` 的 `findSetting` 方法里补充新配置项的 URL 补全逻辑（如需要）

### Step 2: PC 后台 Controller

1. 新建 `app/admin/controller/XxxController.php`
2. 继承 `AdminBaseController`
3. 实现 `index()` 方法（GET 显示配置、POST 保存配置）

### Step 3: PC 后台模板

新建 `public/themes/admin_fengiy/admin/xxx/index.html`：
- 表单带 `js-ajax-form` 类名
- input name 与 Controller 接收的参数名对齐

### Step 4: 小程序前端

1. 在 `data()` 里新增配置字段
2. 在 `onLoad()` 里调用 `findSetting({ name: 'xxx_option' })`
3. 在模板里根据配置渲染 UI

## 6. 配置一致性约束

- **后台 fallback 与前端 fallback 必须一致**：接口失败时两端要有相同的默认值
- **JSON 字段写前后端同步**：后台保存的 JSON 结构变了，前端解析代码也要同步改
- **图片字段必须做 URL 补全**：后台传相对路径，接口返回前必须调 `cmf_get_asset_url()`
- **缓存注意**：`cmf_set_option` 会写 cmf_option 表，运行时有缓存，改完配置要清缓存才能生效

## 7. 不要做的事

- ❌ 前端 fallback 与后端 fallback 不一致 → 导致界面错乱
- ❌ 后台接口忘记 JSON_UNESCAPED_UNICODE → 中文变 \uxxxx
- ❌ 后台保存 JSON 但前端按字符串解析 → 取不到字段
- ❌ 图片URL不补全 → 小程序里显示不出来
- ❌ `findSetting` 返回空数组/null 时前端不做 fallback 判断 → 白屏或报错

## 相关 Git 提交

- `f13cb95` - 新增小程序首页入口配置功能（HomeEntries）
- `bb43d8a` - 新增小程序首页走马灯通知配置功能（HomeNotice）
- `03c79a1` - 修复图片链接补全逻辑的判断错误
- `80a3a3f` - 重构首页四宫格功能入口为动态配置式
- `aedb5f1` - 重构小程序首页商品列表为双列网格布局
- `cf95e60` - 重构首页功能入口布局为左大卡+右侧组合样式
