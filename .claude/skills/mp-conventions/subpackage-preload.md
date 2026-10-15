---
name: "mp-subpackage-preload"
description: "小程序分包加载与预加载优化。记录 pages.json 分包结构、preloadRule 预加载规则配置、微信各平台开发测试环境配置等。当需要新增分包、优化页面跳转性能、配置各平台开发环境时调用。"
---

# 小程序分包加载与预加载优化

## 适用场景

本项目使用 uni-app 的分包机制优化小程序体积和加载性能。当需要：
- 新增分包模块
- 优化页面跳转时的预加载策略
- 配置微信各平台开发测试环境
- 排查"分包预加载导致页面跳转卡顿"问题

## 1. pages.json 分包结构

### 分包配置位置

```json
// 小程序/pages.json
{
    "pages": [
        // 主包页面（首页、tabbar页等）
        { "path": "pages/index/index", "style": {...} },
        { "path": "pages/low_carbon/low_carbon", "style": {...} },
        { "path": "pages/health/health", "style": {...} },
        { "path": "pages/mall/mall", "style": {...} },
        { "path": "pages/user/index", "style": {...} }
    ],
    "subPackages": [
        {
            "root": "pages/flhd",   // 分包根目录
            "pages": [              // 分包内页面
                { "path": "ljfldk", "style": {...} },
                { "path": "list", "style": {...} }
            ]
        },
        {
            "root": "pages/zzhd",
            "pages": [...]
        },
        {
            "root": "pages/order",
            "pages": [...]
        }
    ],
    "preloadRule": {
        // 预加载规则...
    }
}
```

### 分包原则

| 分包 | 包含页面 | 说明 |
|------|---------|------|
| 主包 | 首页、tabbar页（活动/健康/积分/我的） | 必须在主包，保证首屏速度 |
| pages/flhd | 垃圾分类、答题考试 | 高频分包，首页跳转 |
| pages/zzhd | 自治活动、文明督导 | 活动页标签复用 |
| pages/order | 订单列表、地址管理 | 商城相关 |
| pages/sxjk | 健康相关 | 健康模块 |
| pages/community | 社区互动 | 社区模块 |

## 2. preloadRule 预加载规则

### 配置格式

```json
{
    "preloadRule": {
        "页面路径": {
            "network": "all|wifi",     // 触发网络条件
            "packages": ["分包root"]    // 预加载的分包名数组
        }
    }
}
```

### 当前预加载规则（已优化）

```json
"preloadRule": {
    // 首页：仅预载 wifi + 高频分包（垃圾分类）
    "pages/index/index": {
        "network": "wifi",
        "packages": ["pages/flhd"]
    },
    // 活动页：仅预载 wifi + 答题考试
    "pages/low_carbon/low_carbon": {
        "network": "wifi",
        "packages": ["pages/answer"]
    },
    // 健康页：仅预载 wifi + 答题考试
    "pages/health/health": {
        "network": "wifi",
        "packages": ["pages/answer"]
    },
    // 积分商城：仅预载 wifi + 订单
    "pages/mall/mall": {
        "network": "wifi",
        "packages": ["pages/order_list"]
    },
    // 个人中心：仅预载 wifi + 消息
    "pages/user/index": {
        "network": "wifi",
        "packages": ["pages/msg_list"]
    }
}
```

## 3. 预加载优化策略

### 优化前（已废弃）

```json
// ❌ 错误做法：预加载多个分包 + 全部网络条件
"pages/index/index": {
    "network": "all",           // ❌ 移动网络也预加载，浪费流量
    "packages": ["pages/zzhd", "pages/flhd"]  // ❌ 多个分包并发下载
},
"pages/user/index": {
    "network": "all",
    "packages": ["pages/order_list", "pages/msg_list", "pages/address", 
                 "pages/coupon", "pages/balance", "pages/points", 
                 "pages/my_comment", "pages/my_evaluation", 
                 "pages/my_questionnaire", "pages/my_reservation", 
                 "pages/settled", "pages/store"]  // ❌ 12个分包！
}
```

### 问题表现

- 移动网络下预加载大量分包导致流量浪费
- 多包并发下载抢占带宽/内存，加剧页面跳转卡顿
- 低网速时预加载包未下载完，点击跳转还是卡

### 优化策略

| 策略 | 说明 |
|------|------|
| `network: "wifi"` | 仅在 WiFi 下预加载，避免浪费移动流量 |
| 限制分包数量 | 单页面预加载包 ≤ 2 个，只预高频必需 |
| 保留核心包 | 只预当前页面最常跳转的下一级页面 |

## 4. 微信各平台开发测试环境配置

### .env 配置

```ini
# 微信开发，各平台开发测试环境（以下为演示用，实际项目需填真实值）

# 微信开放平台配置
WECHAT_APPID = testtesttesttest
WECHAT_SECRET = testtesttesttest
WECHAT_TOKEN = testtesttesttest

# 微信小程序配置
WECHAT_MINI_APPID = testtesttesttest
WECHAT_MINI_SECRET = testtesttesttest

# 微信支付配置
WECHAT_PAY_APPID = testtesttesttest
WECHAT_PAY_SECRET = testtesttesttest
WECHAT_PAY_MCHID = testtesttesttest
WECHAT_PAY_API_KEY = testtesttesttest

# 微信视频号配置
WECHAT_VIDEO_APPID = testtesttesttest
WECHAT_VIDEO_SECRET = testtesttesttest
```

### 各平台配置用途

| 配置项 | 用途 |
|--------|------|
| 微信开放平台 | PC 网页授权、移动应用登录 |
| 微信小程序 | 小程序登录、code2session |
| 微信支付 | 小程序/公众号支付 |
| 微信视频号 | 视频号小店、直播 |

## 5. 新增分包步骤

### Step 1: 在 pages.json 的 subPackages 里添加

```json
{
    "subPackages": [
        ...现有分包...,
        {
            "root": "pages/new_module",   // 新分包根目录
            "pages": [
                { "path": "index", "style": {...} },
                { "path": "detail", "style": {...} }
            ]
        }
    ]
}
```

### Step 2: 在小程序项目里新建目录和页面

```
小程序/pages/new_module/
├── index.vue
└── detail.vue
```

### Step 3: 在 preloadRule 里配置预加载（如需要）

```json
{
    "preloadRule": {
        "某个页面路径": {
            "network": "wifi",
            "packages": ["pages/new_module"]
        }
    }
}
```

### Step 4: 检查分包体积

小程序后台「代码质量」查看各分包体积，确保：
- 单个分包 ≤ 2MB
- 总分包 ≤ 20MB

## 6. 注意事项

### 分包约束

- ✅ 主包体积越小越好（首屏速度）
- ✅ tabbar 页面必须在主包
- ✅ 分包之间不能互相引用（A分包不能import B分包的组件）
- ✅ 分包可以引用主包的内容
- ✅ `preloadRule` 的 key 是页面路径，不是分包root
- ❌ 不要在里面放 tabbar 页面
- ❌ 不要预加载太多分包（导致卡顿）
- ❌ 移动网络下预加载大量内容（用户流量成本）

### 开发测试环境配置

- ⚠️ `.env` 里的微信配置仅用于开发和测试
- ⚠️ 生产环境的真实配置不要提交到 git
- ⚠️ `testtesttesttest` 是占位符，上线前需替换为真实值
- ⚠️ 不同小程序的测试环境可能用不同 AppID，注意区分

## 7. 性能调优检查清单

当发现页面跳转卡顿时：

1. **检查 preloadRule**：是否预加载了太多分包？改为 wifi + 限制数量
2. **检查分包体积**：单个分包是否超过 2MB？拆分或清理
3. **检查首屏资源**：首页是否加载了过多非首屏数据？懒加载
4. **检查图片**：是否使用了过大图片？压缩或 CDN

## 相关 Git 提交

- `2935a1e` - 调整小程序预加载规则，优化分包加载策略
- `0f23560` - 新增微信各平台开发测试环境配置项
