---
name: mp-main-package-optimize
description: 优化微信小程序主包体积，解决主包超过 2MB 限制的问题。当小程序主包超过限制、需要缩减主包大小、需要调整分包配置时使用，也包括新增页面时的分包规划。
---

# 微信小程序主包体积优化

## 核心原则

微信小程序主包限制 **2MB**，分包限制 **20MB**（总包不超过 20MB）。主包必须包含：
- Tab 页面（`tabBar.list` 中配置的入口页面）
- 全局组件/样式/工具类
- 启动必需的其他公共代码

## 诊断步骤

### 1. 检查当前主包大小

在项目构建后检查 `unpackage/dist/dev/mp-weixin` 目录，主包包含：
- `common/` 目录（main.js, vendor.js, runtime.js）
- `pages/` 目录下的页面子目录
- `static/` 目录
- `app.js`, `app.json`, `app.wxss`

### 2. 识别主包膨胀原因

```bash
# 检查 common/vendor.js 大小（uView 等大型库的主入口）
# 检查 pages 目录，识别所有页面
# 检查 static 目录中的图片资源
```

### 3. 分析 pages.json 配置

```json
// 主包 pages 数组中的页面 → 全部进入主包
"pages": [...]  

// subPackages 中的页面 → 只在使用时加载
"subPackages": [...]
```

## 优化策略

### 策略一：减少主包页面（最有效）

**只保留 Tab 页面在主包中，其余全部移入分包。**

```json
// ✅ 正确：只有 tab 页面在主包
"pages": [
  "pages/index/index",     // 首页 tab
  "pages/user/index",      // 我的 tab
  "pages/health/health",   // 健康 tab
  "pages/mall/mall",       // 积分权益 tab
  "pages/low_carbon/low_carbon"  // 活动 tab
]

// ✅ 子页面移入对应分包
"subPackages": [
  {
    "root": "pages/health",
    "pages": ["details", "add", "participate"]
  },
  {
    "root": "pages/mall",
    "pages": ["ranking", "details", "cart", ...]
  }
  // ...
]
```

### 策略二：拆分大型依赖

- 检查 `vendor.js` 是否包含不必要的库
- uView UI 确保使用 `easycom` 按需引入，不要全量 import
- 避免在 main.js 中 import 非必需的第三方库

### 策略三：静态资源优化

- 移除 `static/` 中未使用的图片
- Base64 编码/雪碧图替代小图片
- 非首屏图片使用网络资源

## 配置模板

### pages.json 分包配置模板

```json
{
  "pages": [
    // 只放 Tab 页面
  ],
  "subPackages": [
    {
      "root": "pages/功能模块名",
      "pages": ["子页面1", "子页面2"]
    }
    // ...
  ],
  "preloadRule": {
    // 预加载规则（不影响主包大小，可保留）
    "pages/index/index": {
      "network": "wifi",
      "packages": ["pages/需要预加载的分包"]
    }
  }
}
```

## 检查清单

优化完成后验证：

- [ ] `pages.json` 中 pages 数组只包含 Tab 页面
- [ ] 所有非 Tab 页面都已移入 subPackages
- [ ] preloadRule 引用的分包路径正确
- [ ] 构建后 `unpackage/dist/build/mp-weixin` 中主包 HTML/JS 正常
- [ ] 所有页面路由跳转正常工作
- [ ] Tab 切换正常

## 常见陷阱

1. **重复声明**：同一页面不能同时在 pages 和 subPackages 中
2. **root 路径冲突**：不同 subPackages 的 root 不能重叠
3. **公共组件引用**：被 main 和 subPackage 共同引用的组件放在主包 components 目录
4. **样式隔离**：如果使用了 shared 选项，需确保兼容
5. **preloadRule 循环**：A 预加载 B，B 预加载 A 会导致问题

## xiayuxuan.com 项目特有约定

当前项目的分包结构：
- 主包：5 个 Tab 页（index, user, low_carbon, health, mall）
- 业务分包：pages/flhd（分类活动）、pages/answer（问卷）等
- 用户相关：pages/sxjk（身心健康）、pages/sxjcgj（身体监测工具）等
- 商城相关：pages/address、pages/coupon、pages/order_list 等
