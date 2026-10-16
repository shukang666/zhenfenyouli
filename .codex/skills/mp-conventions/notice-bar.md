---
name: "mp-notice-bar"
description: "小程序通知栏走马灯组件使用指南。记录 u-notice-bar 组件的正确用法（list 模式配置）、speed 参数优化、公告读取和存储逻辑一致性等。当需要使用/修改小程序走马灯通知栏时调用。"
---

# 小程序通知栏走马灯组件使用指南

## 适用场景

本项目小程序首页使用 uView UI 的 `u-notice-bar` 组件实现走马灯通知栏。当需要：
- 在小程序页面添加滚动通知栏
- 调整通知栏滚动速度
- 修复通知栏组件使用方式问题
- 确保公告读取和存储逻辑一致

## 核心知识点

### 1. u-notice-bar 组件的正确用法

#### ❌ 错误用法（单文本模式，已废弃）

```vue
<!-- 旧版 API，不再推荐 -->
<u-notice-bar :text="noticeText" left-icon="volume" scrollable :speed="60" :duration="2000"></u-notice-bar>
```

> 旧版 `text` 属性只能传单行字符串，且 `speed`/`duration` 的单位可能因版本不同而有差异

#### ✅ 正确用法（list 模式）

```vue
<!-- 新版 API，推荐使用 -->
<u-notice-bar :list="[noticeText]" 
              :volume-icon="true" 
              :more-icon="false" 
              :close-icon="false" 
              :speed="2000">
</u-notice-bar>
```

| 属性 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `list` | Array | `[]` | 通知文本数组（单条通知时用 `[text]`） |
| `volume-icon` | Boolean | `false` | 是否显示左侧音量图标 |
| `more-icon` | Boolean | `false` | 是否显示右侧"更多"图标 |
| `close-icon` | Boolean | `false` | 是否显示右侧关闭图标 |
| `speed` | Number | `160` | 水平滚动速度（单位：rpx/秒，数值越小越慢） |

### 2. speed 参数调优

| speed 值 | 效果 | 适用场景 |
|---------|------|---------|
| `2000+` | 极快 | ❌ 太快，不适合阅读 |
| `160` | 默认速度 | uView 默认值，适中 |
| `100` | 较慢、舒适 | ✅ 推荐，适合大多数场景 |
| `60` | 很慢 | 文字较短且需要强调时 |

> ⚠️ 注意：`speed` 的单位是 **rpx/秒**（每秒移动多少像素），不是毫秒！数值越大越快。建议 `100` 是一个舒适的阅读速度。

### 3. 条件渲染

通知栏需要根据后台配置动态显示/隐藏：

```vue
<!-- v-if 判断：后台配置文字为空则不渲染通知栏 -->
<view class="notice-bar-wrap" v-if="homeNotice">
    <u-notice-bar :list="[homeNotice]" 
                  :volume-icon="true" 
                  :more-icon="false" 
                  :close-icon="false" 
                  :speed="2000">
    </u-notice-bar>
</view>
```

> `v-if="homeNotice"` 确保通知文字为空字符串时不会显示空白的通知栏容器

## 4. 小程序首页完整实现

### 前端代码

```vue
<!-- 小程序/pages/index/index.vue -->
<template>
    <view>
        <!-- 走马灯通知栏（后台 home_notice 配置，留空则不显示） -->
        <view class="notice-bar-wrap" v-if="homeNotice">
            <u-notice-bar :list="[homeNotice]" 
                          :volume-icon="true" 
                          :more-icon="false" 
                          :close-icon="false" 
                          :speed="2000">
            </u-notice-bar>
        </view>

        <!-- 其他内容 -->
    </view>
</template>

<script>
export default {
    data() {
        return {
            homeNotice: '',  // 通知文字，从后台接口获取
        }
    },
    onLoad() {
        this.getHomeNotice()
    },
    methods: {
        async getHomeNotice() {
            try {
                let res = await this.$u.api.findSetting({ name: 'home_notice' })
                this.homeNotice = (res.data || '').toString()
            } catch (e) {
                // 接口失败时不显示通知栏
            }
        }
    }
}
</script>

<style lang="scss">
.notice-bar-wrap {
    padding: 20rpx 30rpx 0;  /* 左右留白与上下区块对齐 */
}
</style>
```

### 后台 Controller

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
            $res = cmf_set_option($option_name, $notice, true);  // true=字符串格式
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

### 后台模板

```html
<!-- public/themes/admin_fengiy/admin/home_notice/index.html -->
<form class="js-ajax-form" method="post" action="{:url('HomeNoticeController/index')}">
    <div class="form-group">
        <label>通知内容</label>
        <input type="text" class="form-control" name="notice" 
               value="{$notice}" placeholder="留空则不显示通知栏">
    </div>
    <button type="submit" class="btn btn-primary">保存</button>
</form>
```

## 5. 公告读取和存储逻辑一致性

### 常见问题

| 问题 | 原因 | 解决方案 |
|------|------|---------|
| 后台改了通知但前台没更新 | 接口读取的 option_name 不对 | 确认前后端用同一个 option_name |
| 通知栏一直显示空白 | 存储时用了数组格式，读取时按字符串解析 | 统一用字符串格式存储 |
| 通知栏不显示 | `homeNotice` 赋值为空字符串 | 确保 `v-if` 判断正确 |

### 存储格式选择

| 存储格式 | `cmf_set_option` 参数 | 适用场景 |
|---------|----------------------|---------|
| 字符串 | `cmf_set_option($name, $value, true)` | 单条通知、配置文本 |
| JSON 数组 | `cmf_set_option($name, $value)` | 多条通知、结构化数据 |

> 走马灯通知用**字符串**格式存储，因为 u-notice-bar 的 list 是数组，但配置项 content 本身只是一条文本。

## 6. 多条通知的用法

如果需要展示多条滚动通知：

```vue
<!-- list 属性直接传数组（多条消息） -->
<u-notice-bar :list="noticeList" 
              :volume-icon="true" 
              :more-icon="false" 
              :close-icon="false" 
              :speed="2000">
</u-notice-bar>

<script>
export default {
    data() {
        return {
            noticeList: ['第一条通知', '第二条通知', '第三条通知'],
        }
    }
}
</script>
```

## 7. 不要做的事

- ❌ 使用老旧的 `:text` 属性（新版 uView 已废弃）
- ❌ `speed` 值太小（文字滚动太快看不清）
- ❌ 通知文字为空时仍渲染通知栏容器
- ❌ 后端存储用 JSON 但前端按字符串解析
- ❌ 忘记在样式里加 `padding` 导致通知栏与页面其他元素不对齐

## 相关 Git 提交

- `bb43d8a` - 新增小程序首页走马灯通知配置功能
- `fcfb7ae` - 调整小程序首页通知栏组件使用方式（list 模式）
- `fd08819` - 修复首页公告读取和存储逻辑不一致问题，添加 speed prop
- `d4453e2` - 修复首页公告读取和存储逻辑不一致问题
