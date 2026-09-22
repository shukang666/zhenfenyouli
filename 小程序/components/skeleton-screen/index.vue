<template>
	<view class="skeleton-screen" :class="'skeleton-bg--' + type" v-show="show">
		<!-- 首页：搜索 + banner + 问候打卡卡 + tab + 横向活动卡 -->
		<view v-if="type === 'index'" class="skeleton-index">
			<view class="sk-nav"></view>
			<view class="sk-search">
				<view class="sk-search-ico"></view>
				<view class="sk-search-line"></view>
				<view class="sk-search-scan"></view>
			</view>
			<view class="sk-banner sk-banner--index"></view>
			<view class="sk-notice"></view>
			<view class="sk-hello">
				<view class="sk-hello-avatar"></view>
				<view class="sk-hello-text">
					<view class="sk-hello-row">
						<view class="sk-line sk-line--w220"></view>
						<view class="sk-hello-sign"></view>
					</view>
					<view class="sk-line sk-line--w280 sk-line--sm"></view>
				</view>
				<view class="sk-hello-btn"></view>
			</view>
			<view class="sk-tabs">
				<view class="sk-tab sk-tab--on"></view>
				<view class="sk-tab"></view>
				<view class="sk-tab-more"></view>
			</view>
			<view class="sk-h-scroll">
				<view class="sk-active-card" v-for="i in 2" :key="i">
					<view class="sk-active-cover"></view>
					<view class="sk-active-body">
						<view class="sk-line sk-line--w80"></view>
						<view class="sk-line sk-line--w60 sk-line--sm"></view>
					</view>
				</view>
			</view>
		</view>

		<!-- 活动页：搜索 + banner + 主题五圆钮 + 两个入口 + 海报横滑 -->
		<view v-else-if="type === 'low_carbon'" class="skeleton-low-carbon">
			<view class="sk-fb-nav">
				<view class="sk-search sk-search--fb">
					<view class="sk-search-ico"></view>
					<view class="sk-search-line"></view>
				</view>
			</view>
			<view class="sk-banner sk-banner--fb"></view>
			<view class="sk-entry-row sk-entry-row--hero">
				<view class="sk-entry sk-entry--lg"></view>
				<view class="sk-entry sk-entry--sm"></view>
			</view>
			<view class="sk-sec-title"></view>
			<view class="sk-theme-row">
				<view class="sk-theme-item" v-for="i in 5" :key="i">
					<view class="sk-theme-circle"></view>
					<view class="sk-theme-label"></view>
				</view>
			</view>
			<view class="sk-sec-title"></view>
			<view class="sk-h-scroll">
				<view class="sk-weekly" v-for="i in 2" :key="'w'+i"></view>
			</view>
		</view>

		<!-- 健康页：蓝顶 + 诊断两行入口 + 社区帖 -->
		<view v-else-if="type === 'health'" class="skeleton-health">
			<view class="sk-nav sk-nav--trans"></view>
			<view class="sk-health-block">
				<view class="sk-health-hd"></view>
				<view class="sk-health-body">
					<view class="sk-health-row">
						<view class="sk-health-tile"></view>
						<view class="sk-health-tile"></view>
					</view>
					<view class="sk-health-row">
						<view class="sk-health-tile"></view>
						<view class="sk-health-tile"></view>
					</view>
				</view>
			</view>
			<view class="sk-health-block">
				<view class="sk-health-hd"></view>
				<view class="sk-health-body">
					<view class="sk-forum" v-for="i in 2" :key="i">
						<view class="sk-forum-top">
							<view class="sk-forum-av"></view>
							<view class="sk-line sk-line--w180"></view>
						</view>
						<view class="sk-line sk-line--w90"></view>
						<view class="sk-line sk-line--w70 sk-line--sm"></view>
					</view>
				</view>
			</view>
		</view>

		<!-- 积分权益：绿顶 + 搜索 + 券 + 产区网格 + 两列商品 -->
		<view v-else-if="type === 'mall'" class="skeleton-mall">
			<view class="sk-nav sk-nav--trans"></view>
			<view class="sk-search sk-search--mall">
				<view class="sk-search-line"></view>
			</view>
			<view class="sk-coupon"></view>
			<view class="sk-zone-panel">
				<view class="sk-line sk-line--w40"></view>
				<view class="sk-zone-grid">
					<view class="sk-zone-item" v-for="i in 8" :key="i"></view>
				</view>
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

		<!-- 我的：绿头像区 + 积分条 + 功能宫格 -->
		<view v-else-if="type === 'user'" class="skeleton-user">
			<view class="sk-me-hero">
				<view class="sk-me-user">
					<view class="sk-me-avatar"></view>
					<view class="sk-me-info">
						<view class="sk-line sk-line--light sk-line--w200"></view>
						<view class="sk-line sk-line--light sk-line--w160 sk-line--sm"></view>
					</view>
				</view>
				<view class="sk-me-stat"></view>
			</view>
			<view class="sk-me-sheet">
				<view class="sk-me-grow"></view>
				<view class="sk-me-card">
					<view class="sk-line sk-line--w140"></view>
					<view class="sk-me-grid">
						<view class="sk-me-item" v-for="i in 4" :key="'a'+i">
							<view class="sk-me-ico"></view>
							<view class="sk-line sk-line--w70 sk-line--sm"></view>
						</view>
					</view>
				</view>
				<view class="sk-me-card">
					<view class="sk-line sk-line--w140"></view>
					<view class="sk-me-grid">
						<view class="sk-me-item" v-for="i in 8" :key="'b'+i">
							<view class="sk-me-ico"></view>
							<view class="sk-line sk-line--w70 sk-line--sm"></view>
						</view>
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
.skeleton-bg--low_carbon,
.skeleton-bg--user {
	background: #eef4f0;
}
.skeleton-bg--health {
	background: #eff4ff;
}
.skeleton-bg--mall {
	background: #eff9f1;
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
.skeleton-health .sk-health-tile,
.skeleton-health .sk-forum,
.skeleton-health .sk-health-hd,
.skeleton-health .sk-forum-av,
.skeleton-health .sk-line {
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
.sk-line--w50 { width: 50%; }
.sk-line--w60 { width: 60%; }
.sk-line--w70 { width: 70%; }
.sk-line--w80 { width: 80%; }
.sk-line--w90 { width: 90%; }
.sk-line--w140 { width: 140rpx; height: 32rpx; }
.sk-line--w160 { width: 160rpx; }
.sk-line--w180 { width: 180rpx; }
.sk-line--w200 { width: 200rpx; }
.sk-line--w220 { width: 220rpx; }
.sk-line--w280 { width: 280rpx; }
.sk-line--light {
	background: linear-gradient(90deg, rgba(255,255,255,0.28) 25%, rgba(255,255,255,0.5) 50%, rgba(255,255,255,0.28) 75%);
	background-size: 400rpx 100%;
}

.sk-nav {
	@extend .sk-base;
	height: 176rpx;
	border-radius: 0;
	background: #fff;
	animation: none;
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
.sk-search--fb {
	margin: 0;
	height: 76rpx;
	background: #f3f8f4;
	border-radius: 38rpx;
}
.sk-search--mall {
	height: 70rpx;
	margin: 0 0 24rpx;
	border: 2rpx solid #cdeece;
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

.sk-banner {
	@extend .sk-base;
	margin: 24rpx 30rpx 0;
	border-radius: 28rpx;
}
.sk-banner--index { height: 340rpx; }
.sk-banner--fb {
	height: 300rpx;
	margin: 16rpx 24rpx 0;
	border-radius: 24rpx;
}
.sk-banner--mall {
	height: 350rpx;
	margin: 0 0 24rpx;
	border-radius: 20rpx;
}

.sk-notice {
	@extend .sk-base;
	height: 64rpx;
	margin: 20rpx 30rpx 0;
	border-radius: 20rpx;
}

.sk-hello {
	display: flex;
	align-items: center;
	margin: 24rpx 30rpx 0;
	padding: 24rpx 20rpx 24rpx 24rpx;
	background: linear-gradient(135deg, #2bc24e 0%, #12962e 100%);
	border-radius: 28rpx;
	box-sizing: border-box;
}
.sk-hello-avatar {
	width: 80rpx;
	height: 80rpx;
	border-radius: 50%;
	background: rgba(255,255,255,0.35);
	flex-shrink: 0;
	margin-right: 18rpx;
}
.sk-hello-text { flex: 1; min-width: 0; }
.sk-hello-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.sk-hello-sign {
	width: 110rpx;
	height: 48rpx;
	border-radius: 32rpx;
	background: rgba(255,255,255,0.22);
	flex-shrink: 0;
	margin-left: 12rpx;
}
.sk-hello-btn {
	width: 140rpx;
	height: 88rpx;
	margin-left: 16rpx;
	background: #fff;
	border-radius: 20rpx;
	flex-shrink: 0;
}

.sk-tabs {
	display: flex;
	align-items: center;
	margin: 28rpx 30rpx 8rpx;
	padding: 8rpx 12rpx 8rpx 8rpx;
	background: #fff;
	border-radius: 20rpx;
}
.sk-tab {
	@extend .sk-base;
	width: 140rpx;
	height: 56rpx;
	border-radius: 14rpx;
	margin-right: 8rpx;
}
.sk-tab--on { width: 160rpx; }
.sk-tab-more {
	@extend .sk-base;
	width: 80rpx;
	height: 28rpx;
	margin-left: auto;
}

.sk-h-scroll {
	display: flex;
	padding: 16rpx 0 0 30rpx;
	overflow: hidden;
}
.sk-active-card {
	width: 540rpx;
	margin-right: 24rpx;
	background: #fff;
	border-radius: 28rpx;
	overflow: hidden;
	flex-shrink: 0;
}
.sk-active-cover {
	@extend .sk-base;
	width: 540rpx;
	height: 270rpx;
	border-radius: 0;
}
.sk-active-body { padding: 24rpx; }

.sk-fb-nav {
	background: #fff;
	padding: 16rpx 24rpx;
	padding-top: calc(env(safe-area-inset-top) + 16rpx);
}

.sk-sec-title {
	@extend .sk-base;
	width: 160rpx;
	height: 36rpx;
	margin: 28rpx 24rpx 16rpx;
}

.sk-theme-row {
	display: flex;
	justify-content: space-between;
	padding: 8rpx 20rpx 24rpx;
}
.sk-theme-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.sk-theme-circle {
	@extend .sk-base;
	width: 104rpx;
	height: 104rpx;
	border-radius: 50%;
}
.sk-theme-label {
	@extend .sk-base;
	width: 72rpx;
	height: 22rpx;
	margin-top: 14rpx;
	border-radius: 8rpx;
}

/* 备用：旧版主题分类骨架 sk-cate-* 已弃用 */

.sk-entry-row {
	display: flex;
	align-items: stretch;
	gap: 16rpx;
	padding: 8rpx 24rpx 16rpx;
}
.sk-entry-row--hero {
	padding-top: 16rpx;
}
.sk-entry {
	@extend .sk-base;
	flex: 1;
	height: 160rpx;
	border-radius: 20rpx;
}
.sk-entry--lg {
	clip-path: polygon(0 0, 88% 0, 100% 50%, 88% 100%, 0 100%);
}
.sk-entry--sm {
	clip-path: polygon(12% 0, 100% 0, 100% 100%, 12% 100%, 0 50%);
}

.sk-weekly {
	@extend .sk-base;
	width: 520rpx;
	height: 320rpx;
	border-radius: 24rpx;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.skeleton-health { padding: 0 30rpx 30rpx; }
.sk-health-block {
	border-radius: 20rpx;
	overflow: hidden;
	margin-bottom: 30rpx;
	background: linear-gradient(90deg, #3974F2 0%, rgba(57,116,242,0.5) 100%);
}
.sk-health-hd {
	width: 180rpx;
	height: 36rpx;
	margin: 20rpx;
	border-radius: 8rpx;
	background: rgba(255,255,255,0.35);
	animation: none;
}
.sk-health-body {
	background: #fff;
	border-radius: 20rpx;
	padding: 20rpx;
}
.sk-health-row {
	display: flex;
	justify-content: space-between;
	margin-bottom: 20rpx;
	&:last-child { margin-bottom: 0; }
}
.sk-health-tile {
	width: 314rpx;
	height: 123rpx;
	border-radius: 10rpx;
}
.sk-forum {
	padding: 20rpx;
	border-radius: 20rpx;
	margin-bottom: 20rpx;
	background: #f4f8ff;
	animation: none;
	&:last-child { margin-bottom: 0; }
}
.sk-forum-top {
	display: flex;
	align-items: center;
	margin-bottom: 20rpx;
}
.sk-forum-av {
	width: 40rpx;
	height: 40rpx;
	border-radius: 50%;
	margin-right: 10rpx;
	flex-shrink: 0;
}

.skeleton-mall { padding: 0 30rpx 30rpx; }
.sk-coupon {
	@extend .sk-base;
	height: 210rpx;
	border-radius: 20rpx;
	margin-bottom: 30rpx;
}
.sk-zone-panel {
	@extend .sk-base;
	padding: 28rpx 24rpx;
	border-radius: 24rpx;
	margin-bottom: 30rpx;
}
.sk-zone-grid {
	display: flex;
	flex-wrap: wrap;
	margin: 20rpx -8rpx -8rpx;
}
.sk-zone-item {
	width: 25%;
	padding: 8rpx;
	box-sizing: border-box;
	height: 88rpx;
	background: rgba(255,255,255,0.5);
	border-radius: 16rpx;
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

.sk-me-hero {
	background: linear-gradient(165deg, #18af38 0%, #12962e 58%, #0e7f26 100%);
	padding: 120rpx 32rpx 88rpx;
}
.sk-me-user { display: flex; align-items: center; }
.sk-me-avatar {
	width: 130rpx;
	height: 130rpx;
	border-radius: 50%;
	background: rgba(255,255,255,0.35);
	flex-shrink: 0;
}
.sk-me-info { margin-left: 24rpx; flex: 1; }
.sk-me-stat {
	height: 120rpx;
	margin-top: 32rpx;
	border-radius: 24rpx;
	background: rgba(255,255,255,0.22);
}
.sk-me-sheet {
	margin-top: -40rpx;
	padding: 0 24rpx 40rpx;
	position: relative;
}
.sk-me-grow {
	@extend .sk-base;
	height: 160rpx;
	border-radius: 24rpx;
	margin-bottom: 24rpx;
	background: #fff;
	animation: none;
	box-shadow: 0 8rpx 24rpx rgba(16, 48, 24, 0.06);
}
.sk-me-card {
	background: #fff;
	border-radius: 24rpx;
	padding: 28rpx 20rpx;
	margin-bottom: 24rpx;
}
.sk-me-grid {
	display: flex;
	flex-wrap: wrap;
	margin-top: 24rpx;
}
.sk-me-item {
	width: 25%;
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 28rpx;
}
.sk-me-ico {
	@extend .sk-base;
	width: 72rpx;
	height: 72rpx;
	border-radius: 20rpx;
	margin-bottom: 12rpx;
}
</style>
