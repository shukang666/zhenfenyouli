---
name: "mp-dev-mode"
description: "小程序开发者模式入口与测试页面管理。记录自定义tabbar长按触发密码验证进入开发者模式、九宫格功能入口、测试页面与主程序分离的实现方式。当需要新增/修改开发者测试页面、调整密码验证逻辑、或扩展九宫格功能时调用。"
---

# 小程序开发者模式实现指南

## 适用场景

本项目实现了独立的开发者模式，用于给领导演示和测试新功能，与小程序主程序完全分离。当需要：
- 新增开发者测试页面
- 修改开发者入口密码
- 扩展九宫格功能入口
- 调整测试页面内容

## 1. 功能概述

### 核心特性

| 特性 | 说明 |
|------|------|
| 触发方式 | 长按底部tabbar的"我的"图标2秒 |
| 密码验证 | 输入密码 `990110` 进入开发者模式 |
| 界面布局 | 九宫格按钮布局，支持扩展 |
| 页面隔离 | 测试页面与主程序完全分离，互不影响 |
| 自定义tabbar | 使用uni-app自定义tabbar替代原生tabbar实现长按监听 |

### 目录结构

```
小程序/
├── custom-tab-bar/            # 自定义tabbar目录（必须在小程序根目录）
│   └── index.vue              # 自定义tabbar组件（含长按检测+开发者弹窗）
├── pages/
│   ├── dev/                   # 开发者功能分包
│   │   ├── index.vue          # 开发者首页（九宫格）
│   │   ├── activity.vue       # 活动页面测试版
│   │   └── activity-detail.vue # 活动详情测试版
│   └── user/index.vue         # 我的页面（移除了onTabItemTab逻辑）
└── pages.json                 # 路由配置（含tabBar.custom: true）
```

## 2. 入口实现：长按"我的"图标触发开发者模式

### 2.1 实现位置

- 自定义tabbar：`小程序/custom-tab-bar/index.vue`
- 触发方式：在自定义tabbar组件中监听 `touchstart`/`touchend` 事件实现长按检测

### 2.2 触发方式说明

由于uni-app原生tabbar不支持长按事件监听，本项目使用**自定义tabbar**（`tabBar.custom: true`）来实现。

**交互流程：**
- **短按**（<2秒）：正常切换tab行为
- **长按**（≥2秒）：触发震动反馈 + 打开开发者密码弹窗

**优势：**
- 不影响页面内任何操作
- 支持长按时长检测
- 体验更好、更直观

### 2.3 pages.json 配置

```json
{
  "tabBar": {
    "custom": true,
    "color": "#7A7E83",
    "selectedColor": "#18AF38",
    "list": [
      { "pagePath": "pages/index/index", ... },
      { "pagePath": "pages/low_carbon/low_carbon", ... },
      { "pagePath": "pages/health/health", ... },
      { "pagePath": "pages/mall/mall", ... },
      { "pagePath": "pages/user/index", ... }
    ]
  }
}
```

### 2.4 自定义tabbar核心代码

```vue
<!-- custom-tab-bar/index.vue -->
<template>
  <view class="custom-tab-bar">
    <view 
      v-for="(item, index) in list" 
      :key="index" 
      class="tab-item"
      @touchstart="handleTouchStart(index)"
      @touchend="handleTouchEnd(index)"
      @touchcancel="handleTouchCancel"
    >
      <image :src="getIconPath(item, index)" class="tab-icon" mode="aspectFit"></image>
      <text :style="{ color: selected === index ? selectedColor : color }">{{ item.text }}</text>
    </view>
    
    <!-- 开发者密码弹窗 -->
    <u-mask :show="devPasswordShow">
      <view class="dev-password-modal">
        <view>🔐 开发者验证</view>
        <u-input v-model="devPassword" type="password" placeholder="请输入密码" border />
        <u-button @click="closeDevPasswordDialog">取消</u-button>
        <u-button type="primary" @click="checkDevPassword">确认</u-button>
      </view>
    </u-mask>
  </view>
</template>

<script>
export default {
  data() {
    return {
      selected: 0,
      longPressTimer: null,
      isLongPress: false,
      longPressTriggered: false,
      devPasswordShow: false,
      devPassword: '',
      // ...
    }
  },
  methods: {
    handleTouchStart(index) {
      if (index !== 4) return // 只有"我的"tab支持长按
      this.isLongPress = true
      this.longPressTimer = setTimeout(() => {
        if (this.isLongPress && !this.longPressTriggered) {
          this.longPressTriggered = true
          uni.vibrateLong()
          this.openDevPasswordDialog()
        }
      }, 2000)
    },
    handleTouchEnd(index) {
      if (index !== 4) return
      this.isLongPress = false
      clearTimeout(this.longPressTimer)
      if (!this.longPressTriggered) {
        this.switchTab(index)
      }
      this.longPressTriggered = false
    },
    switchTab(index) {
      if (index === this.selected) return
      const url = '/' + this.list[index].pagePath
      uni.switchTab({ url, success: () => { this.selected = index } })
    },
    checkDevPassword() {
      if (this.devPassword === '990110') {
        this.devPasswordShow = false
        this.devPassword = ''
        uni.navigateTo({ url: '/pages/dev/index' })
      } else {
        uni.showToast({ title: '密码错误', icon: 'none' })
        this.devPassword = ''
      }
    },
    setSelected(index) {
      this.selected = index
    }
  }
}
</script>
```

### 2.5 各tabbar页面更新选中态

由于使用自定义tabbar，每个tabbar页面需要在 `onShow` 中手动更新tabbar选中态：

```js
// 以 pages/index/index.vue 为例
onShow() {
  this.updateTabBarSelected(0) // 首页index=0
  // ...原有逻辑
},
methods: {
  updateTabBarSelected(index) {
    if (typeof this.$mp.page.getTabBar === 'function' && this.$mp.page.getTabBar()) {
      this.$mp.page.getTabBar().setSelected(index)
    }
  }
}
```

**各页面索引对应表：**

| 页面 | 路径 | 索引 |
|------|------|------|
| 首页 | pages/index/index | 0 |
| 活动 | pages/low_carbon/low_carbon | 1 |
| 健康 | pages/health/health | 2 |
| 积分权益 | pages/mall/mall | 3 |
| 我的 | pages/user/index | 4 |

## 3. 开发者首页（九宫格布局）

### 3.1 页面结构

`小程序/pages/dev/index.vue`

### 3.2 九宫格实现

```vue
<template>
  <view class="dev-container">
    <view class="dev-header">
      <view>🛠️ 开发者模式</view>
    </view>
    <view class="dev-grid">
      <view class="grid-item" @click="goToActivity">活动页面</view>
      <view class="grid-item" @click="goToActivityDetail">活动详情</view>
      <view class="grid-item disabled">敬请期待</view>
    </view>
  </view>
</template>
```

### 3.3 扩展九宫格按钮

如需新增功能按钮：
1. 在 `dev-grid` 中添加新的 `grid-item`
2. 绑定点击事件
3. 在 `methods` 中添加跳转方法

## 4. 测试页面实现

### 4.1 页面隔离原则

- 测试页面位于 `pages/dev/` 分包中
- 页面内容从主程序页面复制，但独立维护
- 测试页面右上角显示"开发测试版"标识
- 测试页面的改动不影响主程序

### 4.2 新增测试页面步骤

1. 在 `小程序/pages/dev/` 目录下创建新页面
2. 从主程序复制页面内容
3. 添加测试标识 badge
4. 在 `pages.json` 的 `subPackages` 中注册
5. 在开发者首页添加九宫格入口

## 5. pages.json 分包配置

```json
{
  "subPackages": [
    {
      "root": "pages/dev",
      "pages": [
        {
          "path": "index",
          "style": { "navigationBarTitleText": "开发者模式" }
        },
        {
          "path": "activity",
          "style": { "navigationBarTitleText": "活动页面(测试)" }
        },
        {
          "path": "activity-detail",
          "style": { "navigationBarTitleText": "活动详情(测试)" }
        }
      ]
    }
  ]
}
```

## 6. 当前测试页面清单

| 页面 | 路径 | 说明 | 对应主程序页面 |
|------|------|------|---------------|
| 活动页面 | `/pages/dev/activity` | 活动tabbar页测试版 | `pages/low_carbon/low_carbon` |
| 活动详情 | `/pages/dev/activity-detail` | 活动详情页测试版 | `pages/low_carbon/lsxf/details-new` |

## 7. 常见问题

### Q1: 如何修改开发者密码？

在 `custom-tab-bar/index.vue` 的 `checkDevPassword` 方法中修改：

```js
checkDevPassword() {
  if (this.devPassword === '新密码') {  // 修改这里
    // ...
  }
}
```

### Q2: 如何修改长按触发时长？

在 `custom-tab-bar/index.vue` 的 `handleTouchStart` 方法中修改：

```js
this.longPressTimer = setTimeout(() => {
  // 触发操作
}, 3000)  // 修改为3秒
```

### Q3: 为什么使用自定义tabbar而不是原生tabbar？

因为uni-app原生tabbar不支持按钮的长按事件监听。只有自定义tabbar才能实现 `touchstart`/`touchend` 事件检测，从而支持长按2秒触发开发者模式的需求。

### Q4: 自定义tabbar有哪些注意事项？

1. 必须在小程序根目录创建 `custom-tab-bar` 文件夹
2. 需要在 `pages.json` 中设置 `tabBar.custom: true`
3. 每个tabbar页面需要手动更新选中态（调用 `this.$mp.page.getTabBar().setSelected()`）
4. 需要处理底部安全区适配（iPhone X等机型）
5. H5平台对自定义tabbar支持有限，建议仅用于小程序平台

### Q5: 测试页面如何与主程序同步？

测试页面需要手动从主程序复制更新。后续如需自动同步，可考虑：
- 使用组件抽离公共部分
- 使用 mixin 共享逻辑
- 但当前设计是故意隔离，方便独立修改测试

## 8. 注意事项

- ⚠️ 开发者模式仅在开发/演示环境使用
- ⚠️ 生产环境建议移除或隐藏入口
- ⚠️ 测试页面内容需手动与主程序同步
- ⚠️ 密码请妥善保管，不要泄露
- ⚠️ 自定义tabbar在H5平台支持有限
- ✅ 测试页面的任何改动不影响主程序
- ✅ 可随时替换测试页面内容

## 相关文件

- 自定义tabbar（含开发者入口）：`小程序/custom-tab-bar/index.vue`
- 开发者首页：`小程序/pages/dev/index.vue`
- 活动测试页：`小程序/pages/dev/activity.vue`
- 详情测试页：`小程序/pages/dev/activity-detail.vue`
- 路由配置：`小程序/pages.json`
