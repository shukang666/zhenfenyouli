# 活动 Tab · 精选区已下线模板备份

从 `low_carbon.vue` 拆组件时移出的内联模板（约 540 行，含注释掉的「本周推荐 / 现在就能参加 / 近期活动 / 全部活动列表」等）。

若需恢复某区块：

1. 从 Git 历史中查看拆分前 `小程序/pages/low_carbon/low_carbon.vue` 的 `v-show="activityPageTab === 'featured'"` 段落；或
2. 将对应 HTML 加回 `components/activity-tab/activity-tab-featured-panel.vue` 底部，样式仍可在 `pages/low_carbon/low_carbon.vue` 的 legacy SCSS 中查找（`.fb-weekly-*`、`.fb-recent-*` 等）。

当前 **精选专区** 线上仅保留：顶部 Banner、轻任务、主题活动 Bento、公益行动入口整图（视口裁切见 `banner-viewport-crop` skill）。
