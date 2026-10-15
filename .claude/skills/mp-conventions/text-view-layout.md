# text 与 view 排版（微信小程序）

## 行为

- 编译到 WXML 时，`<text>` 对应 **inline** 文本节点，语义接近 HTML 的 `<span>`，**不会**单独占一行。
- 紧跟其后的 `<view>` 若未强制块级排版，容易与 `<text>` **排在同一行**，出现「文案 + 按钮横排、按钮换行失效」等问题。
- `text-align: center` 加在父级 `<view>` 上，**不能**让 inline 的 `<text>` 与后面的 `<view>` 自动上下堆叠。

## 正确写法

**空状态、标题 + 按钮、多行文案 + 操作区** 等需要纵向排列时，任选其一：

1. **父级用 flex 纵向（推荐）**

```vue
<view class="empty-wrap">
  <text class="empty-wrap__t">还没有发布过闲置</text>
  <view class="empty-wrap__btn" @tap="goPublish">去发布</view>
</view>
```

```scss
.empty-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.empty-wrap__t {
  font-size: 28rpx;
  color: #999;
}
.empty-wrap__btn {
  margin-top: 24rpx;
  /* 不要用 inline-block 指望换行 */
}
```

2. **文案用 `<view>` 包一层**（块级容器，自然换行）

3. **必须单独一行展示的 `<text>`**，加 `display: block`（或包在 block 容器里）

## 反例

```vue
<!-- 错误：text 与 view 会挤在一行 -->
<view class="empty" style="text-align: center">
  <text>提示文案</text>
  <view class="btn">按钮</view>
</view>
```

```scss
/* 错误：inline-block 按钮仍可能跟在 text 同一行 */
.btn { display: inline-block; margin-top: 24rpx; }
```

## 自检

- 空状态、弹窗说明 + 主按钮：预览里是否 **上下居中、按钮在第二行**。
- 列表项内多行标题仍可用 `<text>` + `display: block` 或外层 flex column（与「空状态横排 bug」不矛盾）。
