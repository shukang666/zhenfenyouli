---
name: "mp-card-beautify"
description: "小程序卡片视觉美化通用技巧。记录装饰性伪元素（::before/::after 圆形/椭圆）、渐变背景色方案与选择思路、点击缩放动画（transform: scale + transition）、卡片阴影与层次、底部横条/色块装饰、图标圆形背景等实战套路。当要让单个卡片/入口模块'更好看、更精致'、设计稿给出'圆角卡片渐变背景装饰圆'等效果时调用。"
---

# 小程序卡片视觉美化通用技巧

## 适用场景

项目中各类卡片需要视觉升级的场景：首页四宫格入口、活动页主题分类卡片、个人中心功能菜单等。

核心诉求：**在 uni-app + Vue + uView 的技术栈下，用纯 CSS（scoped）实现设计级精美的卡片视觉效果。**

## 1. 装饰性伪元素（::before / ::after 圆形/椭圆）

伪元素可以添加"浮装饰圆"而不增加 DOM 节点。典型用法：

```scss
.fb-cate-big {
    position: relative;             /* 子元素绝对定位的锚点 */
    overflow: hidden;               /* 防止装饰圆溢出容器 */
    
    // 左上大圆形装饰
    &::before {
        content: '';
        position: absolute;
        width: 200rpx;
        height: 200rpx;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 50%;
        top: -80rpx;
        left: -50rpx;
        pointer-events: none;      /* 关键：避免遮挡点击 */
    }
    
    // 右下小圆形装饰
    &::after {
        content: '';
        position: absolute;
        width: 160rpx;
        height: 160rpx;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        bottom: -40rpx;
        right: -30rpx;
        pointer-events: none;
    }
}
```

**要点**：
- `position: relative` 父元素 + `position: absolute` 伪元素，定位基准确定
- `overflow: hidden` 裁掉超出圆角外的部分，保持容器边界整洁
- `pointer-events: none` 非常重要——**不加会导致装饰圆拦截点击**，按钮点不动
- 装饰圆用 `rgba(255,255,255, alpha)` 白色半透明，适配任意彩色背景

## 2. 渐变背景色方案

### 如何选择渐变色

同色系渐变（安全、高级感）：

| 起始色 | 结束色 | 效果 |
|-------|-------|------|
| `#A4DB88` | `#18AF38` | 清新绿 |
| `#F4E48A` | `#E7B93C` | 活力黄 |
| `#F59494` | `#E75E5E` | 温柔红 |
| `#A0D8F0` | `#4BAED0` | 清透蓝 |
| `#B49BDB` | `#9472C8` | 优雅紫 |

渐变角度用 `141deg` 或 `135deg` 对角线方向，视觉流动感比 `180deg`（纯垂直）更活泼：

```scss
.fb-cate-big {
    // 从左下到右上的对角渐变
    background: linear-gradient(141deg, #A4DB88 0%, #18AF38 100%);
    border-radius: 24rpx;
}
```

### 与小卡片配色区分

一个模块内如果有多个卡片，保持**同色系但不同深浅/明度**：

```scss
.fb-cate-mini.mini-1 {
    background: linear-gradient(141deg, #A4DB88 0%, #18AF38 100%);   // 深绿
}
.fb-cate-mini.mini-2 {
    background: linear-gradient(141deg, #C5E8A0 0%, #5BC26E 100%);   // 浅绿
}
```

也可以**跨色系**区分功能（绿色=环保、蓝色=健康、橙色=活动等）。

### 白字卡片：渐变要够深

卡片上写**白色标题/说明**时，起始色也不能太浅。实测 `#F4E48A→#E7B93C`、`#A0D8F0→#4BAED0` 在手机上白字几乎看不清。应把**两端都压深**（例如黄 `#D4A017→#A36B08`、蓝 `#2B8BB8→#156A90`、绿 `#18AF38→#0C7A24`）。装饰圆透明度降到 `0.12` 左右，避免浅色块盖住文字。

垃圾分类打卡入口 `pages/flhd/ljfldk` 的场景卡/种类卡配色表见 `mp-home-activity` → [clock-hub-wizard.md](../mp-home-activity/clock-hub-wizard.md)。

## 3. 点击缩放动画（:active 伪类）

点击时轻微缩小 + 透明度变化，给用户"按下去了"的物理反馈：

```scss
.fb-cate-big {
    transition: transform 0.1s ease, opacity 0.1s ease;
    
    &:active {
        transform: scale(0.94);
        opacity: 0.8;
    }
}
```

**要点**：
- 动画时长 `0.1s` 足够快，要跟手；超过 `0.2s` 会感觉"粘滞"
- `scale(0.94)` 不要缩太小，`0.9` 以下会有"塌陷感"
- `opacity: 0.8` 配合缩放，强化按下效果
- `transition` 写在正常态（不是 :active 内），这样松开也会平滑复位

## 4. 卡片阴影与层次

小程序用 `box-shadow` 实现投影，比 H5 要注意**兼容老设备**：

```scss
.fb-cate-big {
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
}
```

**色彩化的阴影**（更高级）：

```scss
// 绿色卡配绿色阴影
box-shadow: 0 8rpx 24px rgba(24, 175, 56, 0.2);

// 蓝色卡配蓝色阴影  
box-shadow: 0 8rpx 24rpx rgba(75, 174, 208, 0.2);
```

不能用 `filter: drop-shadow` —— 小程序低端机性能差。

## 5. 底部横条/角标装饰

在卡片底部或右下角加一抹同色系横条，模拟"彩色下划线"效果：

```scss
.fb-cate-big {
    &::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 8rpx;
        background: rgba(255, 255, 255, 0.35);
        border-radius: 0 0 24rpx 24rpx;  // 跟随底部圆角
    }
}
```

如果是**和标题行同高的左侧色条**（类似 Word 里"引用块"那种）：

```scss
.fb-cate-mini {
    border-left: 8rpx solid rgba(255, 255, 255, 0.6);
    padding-left: 24rpx;
}
```

## 6. 图标圆形背景

图标外面套一个圆形半透明底，高级感瞬间拉满：

```scss
.cate-icon-wrap {
    width: 80rpx;
    height: 80rpx;
    background: rgba(255, 255, 255, 0.3);      // 白色半透明
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16rpx;
    
    .u-icon {
        filter: drop-shadow(0 2rpx 4rpx rgba(0,0,0,0.15));
    }
}
```

## 7. 综合实例：从平淡到精美

**改前**：普通白色背景卡片，左侧图标 + 文字
```scss
.card {
    background: #fff;
    border-radius: 16rpx;
    padding: 24rpx;
    display: flex;
    align-items: center;
}
```

**改后**：彩色渐变 + 装饰圆 + 阴影 + 点击缩放 + 图标圆形底
```scss
.card {
    position: relative;
    overflow: hidden;
    background: linear-gradient(141deg, #A4DB88 0%, #18AF38 100%);
    border-radius: 24rpx;
    padding: 32rpx;
    display: flex;
    align-items: center;
    box-shadow: 0 8rpx 24rpx rgba(24, 175, 56, 0.2);
    transition: transform 0.1s ease, opacity 0.1s ease;
    
    &:active {
        transform: scale(0.94);
        opacity: 0.8;
    }
    
    &::before {
        content: '';
        position: absolute;
        width: 200rpx;
        height: 200rpx;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 50%;
        top: -80rpx;
        left: -50rpx;
        pointer-events: none;
    }
    
    &::after {
        content: '';
        position: absolute;
        width: 160rpx;
        height: 160rpx;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        bottom: -40rpx;
        right: -30rpx;
        pointer-events: none;
    }
    
    .icon-wrap {
        width: 80rpx;
        height: 80rpx;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
```

**改进效果清单**：
- [x] 渐变背景色赋予主题感
- [x] 装饰圆增加层次
- [x] 阴影提升立体感
- [x] 点击缩放增强交互反馈
- [x] 图标圆形背景统一视觉
- [x] pointer-events 解决装饰圆遮挡点击的隐患

## 8. 注意事项

- **scoped 样式里 pseudo-elements 写法**：`&::before` 在 scss 里能编译成 `:scope::before`，小程序支持。如遇不支持可直接写 `::before`（不带 &），uni-app 通常能正确处理。
- **PNG 图片格式**：装饰圆所在区域如果用 `background: linear-gradient`，小程序里边缘会走样（色带）。如果要**无瑕渐变**，切图让设计师出 PNG。
- **rpx vs px**：装饰圆等装饰元素统一用 `rpx`，否则大屏手机（iPad）比例失调。
- **深色模式**：`rgba(255,255,255,alpha)` 只在亮色背景卡片上好看；深色卡片改用 `rgba(0,0,0,alpha)`。
- **装饰圆数量**：单个卡片最多 2 个（左上 + 右下），超过 3 个会显得杂乱。

## 相关 skill

- `mp-uview-components`：uView 组件的视觉配置（按钮、图标、卡片组件）
- `mp-index-layout`：首页各类卡片的实际布局结构和样式参考
- `mp-page-element-migrate`：跨页面搬运卡片组件时，哪些样式需要一并迁移
