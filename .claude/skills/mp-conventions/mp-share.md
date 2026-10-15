---
name: mp-share
description: 小程序微信分享卡片默认封面。改转发图、onShareAppMessage 截图不对、朋友圈封面时用。
---

# 微信分享封面

默认封面 URL 写在 `小程序/common/settings.js` 的 `shareCoverUrl`，现为 `https://admin.circular-economy.cn/static/images/share.png`（5:4）。全局 mixin `小程序/uview-ui/libs/mixin/mpShare.js` 在 `onLoad` 把 `this.$u.mpShare.imageUrl` 设成这个地址。

线上文件在服务器 `public/static/images/share.png`。`public/` 被 git 忽略，换图是换服务器文件，不是改仓库。

页面如果**自己写了** `onShareAppMessage` / `onShareTimeline` 且没带 `imageUrl`，微信会改用当前页截图，默认封面失效。首页、分类打卡页已经补了 `imageUrl: this.$u.mpShare.imageUrl`。活动详情、商品详情等继续用内容缩略图，不要改成这张默认图。

换同名 png 后微信可能仍显示旧图，属缓存；确认线上 URL 能打开后再测转发。
