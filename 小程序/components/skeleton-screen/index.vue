<template>
	<view class="skeleton-screen" :class="'skeleton-bg--' + type" v-show="show">
		<!-- 首页：搜索 + 推广 Banner + 通知 + 四快捷 + 本周精选 -->
		<view v-if="type === 'index'" class="skeleton-index">
			<view class="sk-nav sk-nav--glass"></view>
			<view class="sk-search">
				<view class="sk-search-ico"></view>
				<view class="sk-search-line"></view>
				<view class="sk-search-scan"></view>
			</view>
			<view class="sk-promo">
				<view class="sk-promo__copy">
					<view class="sk-line sk-line--w240"></view>
					<view class="sk-line sk-line--w220"></view>
					<view class="sk-line sk-line--w300 sk-line--sm"></view>
					<view class="sk-promo__btns">
						<view class="sk-promo__btn"></view>
						<view class="sk-promo__btn sk-promo__btn--ghost"></view>
					</view>
				</view>
				<view class="sk-promo__art"></view>
			</view>
			<view class="sk-notice"></view>
			<view class="sk-quick-grid">
				<view class="sk-quick-item" v-for="i in 4" :key="'q' + i">
					<view class="sk-quick-ico"></view>
					<view class="sk-line sk-line--w70 sk-line--sm"></view>
				</view>
			</view>
			<view class="sk-sec-head-row">
				<view class="sk-line sk-line--w160 sk-line--title"></view>
				<view class="sk-line sk-line--w80 sk-line--sm"></view>
			</view>
			<view class="sk-weekly-pair">
				<view class="sk-weekly-card" v-for="i in 2" :key="'w' + i">
					<view class="sk-weekly-card__hero"></view>
					<view class="sk-weekly-card__body">
						<view class="sk-line sk-line--w90"></view>
						<view class="sk-line sk-line--w60 sk-line--sm"></view>
					</view>
				</view>
			</view>
			<view class="sk-guide-cta"></view>
		</view>

		<!-- 活动 Tab · 发现页：标题搜索 + 筛选 chip + 主题分类 + 本月专题 + 活动列表 -->
		<view v-else-if="type === 'low_carbon'" class="skeleton-low-carbon">
			<view class="sk-discover-nav">
				<view class="sk-line sk-line--w180 sk-line--title"></view>
				<view class="sk-search sk-search--discover">
					<view class="sk-search-ico"></view>
					<view class="sk-search-line"></view>
				</view>
			</view>
			<view class="sk-chip-row">
				<view class="sk-chip sk-chip--on"></view>
				<view class="sk-chip" v-for="i in 4" :key="'c' + i"></view>
			</view>
			<view class="sk-cates-panel">
				<view class="sk-line sk-line--w120 sk-line--sm"></view>
				<view class="sk-cates-row">
					<view class="sk-cate-item" v-for="i in 5" :key="'ca' + i">
						<view class="sk-cate-tile"></view>
						<view class="sk-line sk-line--w60 sk-line--xs"></view>
					</view>
				</view>
			</view>
			<view class="sk-month-banner"></view>
			<view class="sk-sec-head-row sk-sec-head-row--pad">
				<view class="sk-line sk-line--w160 sk-line--title"></view>
				<view class="sk-line sk-line--w60 sk-line--sm"></view>
			</view>
			<view class="sk-discover-card" v-for="i in 3" :key="'d' + i">
				<view class="sk-discover-thumb"></view>
				<view class="sk-discover-body">
					<view class="sk-line sk-line--w90"></view>
					<view class="sk-line sk-line--w70 sk-line--sm"></view>
					<view class="sk-discover-foot">
						<view class="sk-line sk-line--w40 sk-line--xs"></view>
						<view class="sk-discover-btn"></view>
					</view>
				</view>
			</view>
		</view>

		<!-- 健康 Tab：沉浸式顶区 + 数字人 + 问答 CTA -->
		<view v-else-if="type === 'health'" class="skeleton-health">
			<view class="sk-hp-scene">
				<view class="sk-hp-nav">
					<view class="sk-hp-nav__copy">
						<view class="sk-line sk-line--w160 sk-line--title"></view>
						<view class="sk-hp-nav__tag"></view>
					</view>
					<view class="sk-hp-nav__logo"></view>
				</view>
				<view class="sk-hp-persona">
					<view class="sk-hp-persona__ring"></view>
					<view class="sk-hp-persona__fig"></view>
				</view>
				<view class="sk-hp-panel">
					<view class="sk-hp-bubble"></view>
					<view class="sk-hp-cta">
						<view class="sk-line sk-line--w120 sk-line--sm"></view>
						<view class="sk-line sk-line--w200 sk-line--title"></view>
						<view class="sk-line sk-line--w280 sk-line--sm"></view>
						<view class="sk-hp-cta__go"></view>
					</view>
					<view class="sk-hp-link">
						<view class="sk-hp-link__ico"></view>
						<view class="sk-hp-link__copy">
							<view class="sk-line sk-line--w180"></view>
							<view class="sk-line sk-line--w240 sk-line--sm"></view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 积分权益：积分卡片 + 闲置入口 + 搜索 + 分类 + 商品 -->
		<view v-else-if="type === 'mall'" class="skeleton-mall">
			<view class="sk-mall-top">
				<view class="sk-nav sk-nav--mall"></view>
				<view class="sk-points-hub">
					<view class="sk-line sk-line--w200 sk-line--sm"></view>
					<view class="sk-points-hub__val"></view>
					<view class="sk-points-hub__actions">
						<view class="sk-points-hub__btn"></view>
						<view class="sk-points-hub__btn"></view>
					</view>
				</view>
			</view>
			<view class="sk-mall-body">
				<view class="sk-secondhand"></view>
				<view class="sk-search sk-search--mall">
					<view class="sk-search-line"></view>
				</view>
				<view class="sk-mall-cate">
					<view class="sk-mall-pill" v-for="i in 4" :key="'p' + i"></view>
				</view>
				<view class="sk-goods-grid">
					<view class="sk-goods-card" v-for="i in 4" :key="i">
						<view class="sk-goods-img"></view>
						<view class="sk-goods-info">
							<view class="sk-line sk-line--w80"></view>
							<view class="sk-line sk-line--w50 sk-line--sm"></view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 我的：扁平顶栏 + 常用 + 服务/更多列表 -->
		<view v-else-if="type === 'user'" class="skeleton-user">
			<view class="sk-me-head">
				<view class="sk-me-profile">
					<view class="sk-me-avatar"></view>
					<view class="sk-me-info">
						<view class="sk-line sk-line--w200 sk-line--title"></view>
						<view class="sk-line sk-line--w260 sk-line--sm"></view>
					</view>
					<view class="sk-me-arrow"></view>
				</view>
				<view class="sk-me-metrics">
					<view class="sk-me-metric" v-for="i in 3" :key="'m' + i">
						<view class="sk-line sk-line--w80 sk-line--title"></view>
						<view class="sk-line sk-line--w60 sk-line--xs"></view>
					</view>
				</view>
			</view>
			<view class="sk-me-body">
				<view class="sk-me-card">
					<view class="sk-line sk-line--w80 sk-line--title"></view>
					<view class="sk-me-quick">
						<view class="sk-me-quick-item" v-for="i in 4" :key="'q' + i">
							<view class="sk-me-ico"></view>
							<view class="sk-line sk-line--w70 sk-line--xs"></view>
						</view>
					</view>
				</view>
				<view class="sk-me-card">
					<view class="sk-line sk-line--w80 sk-line--title"></view>
					<view class="sk-me-row" v-for="i in 3" :key="'s' + i">
						<view class="sk-me-row-ico"></view>
						<view class="sk-line sk-line--w180"></view>
					</view>
				</view>
				<view class="sk-me-card">
					<view class="sk-line sk-line--w80 sk-line--title"></view>
					<view class="sk-me-row" v-for="i in 3" :key="'o' + i">
						<view class="sk-me-row-ico"></view>
						<view class="sk-line sk-line--w160"></view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'SkeletonScreen',
	props: {
		show: {
			type: Boolean,
			default: true
		},
		type: {
			type: String,
			default: 'index'
		}
	}
}
</script>

<style lang="scss" scoped>
.skeleton-screen {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 9999;
	overflow: hidden;
}
.skeleton-bg--index,
.skeleton-bg--low_carbon {
	background: #eef4f0;
}
.skeleton-bg--user {
	background: #f2f3f5;
}
.skeleton-bg--health {
	background: linear-gradient(180deg, #e8f0ff 0%, #eff4ff 42%, #f5f8fc 100%);
}
.skeleton-bg--mall {
	background: #e4e8e5;
}

@keyframes skeleton-loading {
	0% { background-position: -200rpx 0; }
	100% { background-position: calc(200rpx + 100%) 0; }
}

.sk-base {
	background: linear-gradient(90deg, #dde6e0 25%, #eef3ef 50%, #dde6e0 75%);
	background-size: 400rpx 100%;
	animation: skeleton-loading 1.5s ease-in-out infinite;
}
.skeleton-bg--health .sk-base,
.skeleton-health .sk-line,
.skeleton-health .sk-hp-nav__tag,
.skeleton-health .sk-hp-nav__logo,
.skeleton-health .sk-hp-persona__ring,
.skeleton-health .sk-hp-persona__fig,
.skeleton-health .sk-hp-bubble,
.skeleton-health .sk-hp-cta,
.skeleton-health .sk-hp-cta__go,
.skeleton-health .sk-hp-link,
.skeleton-health .sk-hp-link__ico {
	background: linear-gradient(90deg, #d5e2f6 25%, #eaf1fb 50%, #d5e2f6 75%);
	background-size: 400rpx 100%;
	animation: skeleton-loading 1.5s ease-in-out infinite;
}

.sk-line {
	@extend .sk-base;
	height: 28rpx;
	border-radius: 8rpx;
}
.sk-line--sm { height: 22rpx; margin-top: 16rpx; }
.sk-line--xs { height: 18rpx; margin-top: 10rpx; }
.sk-line--title { height: 32rpx; border-radius: 10rpx; }
.sk-line--w40 { width: 40%; }
.sk-line--w50 { width: 50%; }
.sk-line--w60 { width: 60%; }
.sk-line--w70 { width: 70%; }
.sk-line--w80 { width: 80%; }
.sk-line--w90 { width: 90%; }
.sk-line--w120 { width: 120rpx; }
.sk-line--w140 { width: 140rpx; height: 32rpx; }
.sk-line--w160 { width: 160rpx; }
.sk-line--w180 { width: 180rpx; }
.sk-line--w200 { width: 200rpx; }
.sk-line--w220 { width: 220rpx; }
.sk-line--w240 { width: 240rpx; }
.sk-line--w260 { width: 260rpx; }
.sk-line--w280 { width: 280rpx; }
.sk-line--w300 { width: 300rpx; }

.sk-nav {
	@extend .sk-base;
	height: 176rpx;
	border-radius: 0;
	background: #fff;
	animation: none;
}
.sk-nav--glass {
	height: 168rpx;
	background: transparent;
}
.sk-nav--mall {
	height: 88rpx;
	margin: 0 30rpx;
	border-radius: 16rpx;
	background: rgba(255, 255, 255, 0.35);
	animation: skeleton-loading 1.5s ease-in-out infinite;
}
.sk-nav--trans {
	height: 176rpx;
	background: transparent;
	animation: none;
}

.sk-search {
	display: flex;
	align-items: center;
	height: 80rpx;
	margin: 20rpx 30rpx 0;
	padding: 0 12rpx 0 16rpx;
	background: #fff;
	border-radius: 40rpx;
	box-sizing: border-box;
}
.sk-search--discover {
	margin: 16rpx 0 0;
	height: 76rpx;
	background: rgba(255, 255, 255, 0.72);
	border-radius: 38rpx;
}
.sk-search--mall {
	height: 68rpx;
	margin: 0 0 24rpx;
	padding: 0 20rpx;
	background: #f5f7f6;
	border-radius: 34rpx;
}
.sk-search-ico {
	@extend .sk-base;
	width: 56rpx;
	height: 56rpx;
	border-radius: 50%;
	flex-shrink: 0;
}
.sk-search-line {
	@extend .sk-base;
	flex: 1;
	height: 24rpx;
	margin: 0 16rpx;
	border-radius: 8rpx;
}
.sk-search-scan {
	@extend .sk-base;
	width: 64rpx;
	height: 64rpx;
	border-radius: 50%;
	flex-shrink: 0;
}

.sk-promo {
	display: flex;
	align-items: stretch;
	margin: 24rpx 32rpx 0;
	padding: 28rpx 24rpx;
	min-height: 280rpx;
	border-radius: 28rpx;
	background: rgba(255, 255, 255, 0.55);
	box-sizing: border-box;
}
.sk-promo__copy {
	flex: 1;
	min-width: 0;
	padding-right: 12rpx;
}
.sk-promo__btns {
	display: flex;
	flex-wrap: wrap;
	gap: 12rpx;
	margin-top: 24rpx;
}
.sk-promo__btn {
	@extend .sk-base;
	width: 168rpx;
	height: 56rpx;
	border-radius: 999rpx;
}
.sk-promo__btn--ghost {
	width: 148rpx;
	opacity: 0.75;
}
.sk-promo__art {
	@extend .sk-base;
	width: 188rpx;
	height: 188rpx;
	border-radius: 50%;
	flex-shrink: 0;
	align-self: center;
}

.sk-notice {
	@extend .sk-base;
	height: 64rpx;
	margin: 20rpx 32rpx 0;
	border-radius: 20rpx;
}

.sk-quick-grid {
	display: flex;
	justify-content: space-between;
	margin: 24rpx 32rpx 0;
	padding: 22rpx 12rpx;
	border-radius: 24rpx;
	background: rgba(255, 255, 255, 0.58);
}
.sk-quick-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.sk-quick-ico {
	@extend .sk-base;
	width: 88rpx;
	height: 88rpx;
	border-radius: 22rpx;
}

.sk-sec-head-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin: 28rpx 32rpx 16rpx;
}
.sk-sec-head-row--pad {
	margin-left: 24rpx;
	margin-right: 24rpx;
}

.sk-weekly-pair {
	display: flex;
	gap: 16rpx;
	padding: 0 32rpx;
}
.sk-weekly-card {
	flex: 1;
	min-width: 0;
	background: #fff;
	border-radius: 24rpx;
	overflow: hidden;
}
.sk-weekly-card__hero {
	@extend .sk-base;
	height: 160rpx;
	border-radius: 0;
}
.sk-weekly-card__body {
	padding: 16rpx 14rpx 20rpx;
}

.sk-guide-cta {
	@extend .sk-base;
	height: 120rpx;
	margin: 32rpx 32rpx 40rpx;
	border-radius: 24rpx;
}

/* —— 活动 Tab · 发现 —— */
.skeleton-low-carbon {
	padding-bottom: 40rpx;
}
.sk-discover-nav {
	padding: calc(env(safe-area-inset-top) + 24rpx) 24rpx 0;
}
.sk-chip-row {
	display: flex;
	padding: 20rpx 24rpx 8rpx;
	overflow: hidden;
}
.sk-chip {
	@extend .sk-base;
	width: 120rpx;
	height: 56rpx;
	border-radius: 999rpx;
	margin-right: 12rpx;
	flex-shrink: 0;
}
.sk-chip--on {
	width: 136rpx;
}
.sk-cates-panel {
	margin: 16rpx 24rpx 0;
	padding: 24rpx 20rpx;
	border-radius: 24rpx;
	background: rgba(255, 255, 255, 0.72);
}
.sk-cates-row {
	display: flex;
	justify-content: space-between;
	margin-top: 20rpx;
}
.sk-cate-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.sk-cate-tile {
	@extend .sk-base;
	width: 96rpx;
	height: 96rpx;
	border-radius: 22rpx;
}
.sk-month-banner {
	@extend .sk-base;
	height: 240rpx;
	margin: 20rpx 24rpx 0;
	border-radius: 28rpx;
}
.sk-discover-card {
	display: flex;
	margin: 16rpx 24rpx 0;
	padding: 20rpx;
	border-radius: 24rpx;
	background: rgba(255, 255, 255, 0.85);
}
.sk-discover-thumb {
	@extend .sk-base;
	width: 128rpx;
	height: 128rpx;
	border-radius: 16rpx;
	flex-shrink: 0;
}
.sk-discover-body {
	flex: 1;
	min-width: 0;
	margin-left: 20rpx;
}
.sk-discover-foot {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: 20rpx;
}
.sk-discover-btn {
	@extend .sk-base;
	width: 120rpx;
	height: 52rpx;
	border-radius: 999rpx;
}

/* —— 健康 Tab —— */
.skeleton-health {
	padding: 0 0 40rpx;
}
.sk-hp-scene {
	padding: calc(env(safe-area-inset-top) + 12rpx) 24rpx 0;
}
.sk-hp-nav {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 8rpx;
}
.sk-hp-nav__copy {
	display: flex;
	align-items: center;
}
.sk-hp-nav__tag {
	width: 120rpx;
	height: 36rpx;
	margin-left: 12rpx;
	border-radius: 999rpx;
}
.sk-hp-nav__logo {
	width: 200rpx;
	height: 56rpx;
	border-radius: 12rpx;
}
.sk-hp-persona {
	position: relative;
	height: 420rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 8rpx 0 0;
}
.sk-hp-persona__ring {
	position: absolute;
	width: 360rpx;
	height: 360rpx;
	border-radius: 50%;
	opacity: 0.55;
}
.sk-hp-persona__fig {
	width: 280rpx;
	height: 320rpx;
	border-radius: 140rpx 140rpx 100rpx 100rpx;
}
.sk-hp-panel {
	margin-top: 8rpx;
}
.sk-hp-bubble {
	height: 72rpx;
	border-radius: 20rpx;
	margin-bottom: 20rpx;
}
.sk-hp-cta {
	padding: 28rpx 24rpx;
	border-radius: 28rpx;
	margin-bottom: 16rpx;
}
.sk-hp-cta__go {
	@extend .sk-base;
	height: 64rpx;
	margin-top: 24rpx;
	border-radius: 16rpx;
}
.sk-hp-link {
	display: flex;
	align-items: center;
	padding: 24rpx 20rpx;
	border-radius: 24rpx;
}
.sk-hp-link__ico {
	width: 72rpx;
	height: 72rpx;
	border-radius: 20rpx;
	flex-shrink: 0;
}
.sk-hp-link__copy {
	flex: 1;
	margin-left: 16rpx;
}

/* —— 积分权益 —— */
.sk-mall-top {
	padding-top: calc(env(safe-area-inset-top) + 8rpx);
}
.sk-points-hub {
	@extend .sk-base;
	margin: 16rpx 24rpx 0;
	padding: 28rpx 24rpx;
	border-radius: 28rpx;
	animation: none;
	background: rgba(255, 255, 255, 0.72);
}
.sk-points-hub__val {
	@extend .sk-base;
	height: 72rpx;
	margin-top: 20rpx;
	border-radius: 16rpx;
}
.sk-points-hub__actions {
	display: flex;
	gap: 16rpx;
	margin-top: 24rpx;
}
.sk-points-hub__btn {
	@extend .sk-base;
	flex: 1;
	height: 96rpx;
	border-radius: 20rpx;
}
.sk-mall-body {
	padding: 24rpx 30rpx 40rpx;
}
.sk-secondhand {
	@extend .sk-base;
	height: 120rpx;
	border-radius: 20rpx;
	margin-bottom: 24rpx;
}
.sk-mall-cate {
	display: flex;
	gap: 12rpx;
	margin-bottom: 28rpx;
	overflow: hidden;
}
.sk-mall-pill {
	@extend .sk-base;
	width: 140rpx;
	height: 56rpx;
	border-radius: 999rpx;
	flex-shrink: 0;
}
.sk-goods-grid {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
}
.sk-goods-card {
	width: 334rpx;
	margin-bottom: 30rpx;
	background: #fff;
	border-radius: 20rpx;
	overflow: hidden;
}
.sk-goods-img {
	@extend .sk-base;
	width: 334rpx;
	height: 334rpx;
	border-radius: 0;
}
.sk-goods-info { padding: 16rpx; }

/* —— 我的 —— */
.sk-me-head {
	background: #fff;
	padding: calc(env(safe-area-inset-top) + 88rpx) 32rpx 0;
	border-bottom: 1rpx solid rgba(0, 0, 0, 0.06);
}
.sk-me-profile {
	display: flex;
	align-items: center;
	padding-bottom: 28rpx;
}
.sk-me-avatar {
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	background: #e8e8ed;
	flex-shrink: 0;
}
.sk-me-info {
	flex: 1;
	margin: 0 20rpx;
}
.sk-me-arrow {
	@extend .sk-base;
	width: 28rpx;
	height: 28rpx;
	border-radius: 50%;
}
.sk-me-metrics {
	display: flex;
	padding: 28rpx 0 32rpx;
	border-top: 1rpx solid rgba(0, 0, 0, 0.06);
}
.sk-me-metric {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.sk-me-body {
	padding: 24rpx;
}
.sk-me-card {
	background: #fff;
	border-radius: 24rpx;
	padding: 28rpx 20rpx;
	margin-bottom: 24rpx;
}
.sk-me-quick {
	display: flex;
	flex-wrap: wrap;
	margin-top: 24rpx;
}
.sk-me-quick-item {
	width: 25%;
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 8rpx;
}
.sk-me-ico {
	@extend .sk-base;
	width: 72rpx;
	height: 72rpx;
	border-radius: 20rpx;
	margin-bottom: 12rpx;
}
.sk-me-row {
	display: flex;
	align-items: center;
	padding: 22rpx 0;
	border-bottom: 1rpx solid rgba(0, 0, 0, 0.05);
	&:last-child {
		border-bottom: none;
		padding-bottom: 0;
	}
}
.sk-me-row-ico {
	@extend .sk-base;
	width: 64rpx;
	height: 64rpx;
	border-radius: 16rpx;
	margin-right: 20rpx;
	flex-shrink: 0;
}
</style>
