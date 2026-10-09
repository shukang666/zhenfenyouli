<template>
	<view class="hub-page">
		<view class="hub-hero">
			<view class="hub-hero-deco"></view>
			<text class="hub-kicker">认识七贤岭</text>
			<text class="hub-title">分模块 · 边读边测</text>
			<text class="hub-desc">按主题自由选学，读完本篇立刻做测验，不用按顺序解锁</text>
			<view class="hub-progress">
				<view class="hub-progress-row">
					<text class="hub-progress-label">累计通关</text>
					<text class="hub-progress-num">{{ passedCount }} / {{ articleCount }}</text>
				</view>
				<view class="hub-progress-track">
					<view class="hub-progress-fill" :style="{ width: progressWidth }"></view>
				</view>
			</view>
		</view>

		<view class="hub-body">
			<view class="hub-loading" v-if="loading">
				<u-loading mode="circle" color="#0F6E56" size="48"></u-loading>
				<text class="hub-loading-text">加载模块</text>
			</view>

			<view class="hub-bento" v-else>
				<view
					class="hub-tile"
					v-for="mod in displayModules"
					:key="mod.category"
					:class="'hub-tile--' + mod.bento"
					:style="{ background: mod.gradient }"
					@tap="openModule(mod)"
				>
					<view class="hub-tile-top">
						<text class="hub-tile-emoji">{{ mod.emoji }}</text>
						<text class="hub-tile-badge" v-if="mod.all_passed">已通关</text>
						<text class="hub-tile-badge hub-tile-badge--go" v-else-if="mod.passed_count > 0">进行中</text>
					</view>
					<text class="hub-tile-name">{{ mod.category }}</text>
					<text class="hub-tile-sub">{{ mod.tagline }}</text>
					<view class="hub-tile-foot">
						<text class="hub-tile-stat">{{ mod.article_count }} 篇 · 通关 {{ mod.passed_count }}</text>
						<text class="hub-tile-arrow">进入 ›</text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import { getModuleUi } from '@/common/qxlLearnModules.js'

export default {
	data() {
		return {
			loading: true,
			modules: [],
			passedCount: 0,
			articleCount: 0,
		}
	},
	computed: {
		progressWidth() {
			if (!this.articleCount) return '0%'
			return Math.min(100, Math.round((this.passedCount / this.articleCount) * 100)) + '%'
		},
		displayModules() {
			return (this.modules || []).map(m => {
				const ui = getModuleUi(m.category)
				return {
					...m,
					emoji: ui.emoji,
					tagline: ui.tagline,
					bento: ui.bento,
					gradient: ui.gradient,
				}
			})
		},
	},
	onShow() {
		this.loadModules()
	},
	methods: {
		loadModules() {
			this.loading = true
			this.$u.api.getQxlLearnModules().then(res => {
				if (res.code == 1) {
					const data = res.data || {}
					this.modules = data.modules || []
					this.passedCount = data.passed_count || 0
					this.articleCount = data.article_count || 0
				}
			}).finally(() => {
				this.loading = false
			})
		},
		openModule(mod) {
			if (!mod || !mod.category) return
			const cat = encodeURIComponent(mod.category)
			this.$u.route('/pages/activity/qxl-learn-module?category=' + cat)
		},
	},
}
</script>

<style lang="scss" scoped>
.hub-page {
	min-height: 100vh;
	background: #eef3f0;
}
.hub-hero {
	position: relative;
	overflow: hidden;
	padding: 40rpx 32rpx 72rpx;
	background: linear-gradient(160deg, #0b4f3f 0%, #0f6e56 50%, #1fa971 100%);
}
.hub-hero-deco {
	position: absolute;
	width: 360rpx;
	height: 360rpx;
	border-radius: 50%;
	right: -100rpx;
	top: -120rpx;
	background: rgba(255, 255, 255, 0.1);
	pointer-events: none;
}
.hub-kicker {
	position: relative;
	font-size: 22rpx;
	color: rgba(255, 255, 255, 0.78);
	letter-spacing: 4rpx;
}
.hub-title {
	position: relative;
	display: block;
	margin-top: 12rpx;
	font-size: 42rpx;
	font-weight: 800;
	color: #fff;
}
.hub-desc {
	position: relative;
	display: block;
	margin-top: 12rpx;
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.85);
	line-height: 1.55;
}
.hub-progress {
	position: relative;
	margin-top: 28rpx;
	padding: 22rpx 24rpx;
	border-radius: 20rpx;
	background: rgba(255, 255, 255, 0.14);
}
.hub-progress-row {
	display: flex;
	justify-content: space-between;
	align-items: baseline;
}
.hub-progress-label {
	font-size: 22rpx;
	color: rgba(255, 255, 255, 0.8);
}
.hub-progress-num {
	font-size: 32rpx;
	font-weight: 800;
	color: #fff;
}
.hub-progress-track {
	height: 10rpx;
	margin-top: 14rpx;
	border-radius: 10rpx;
	background: rgba(255, 255, 255, 0.22);
	overflow: hidden;
}
.hub-progress-fill {
	height: 100%;
	border-radius: 10rpx;
	background: linear-gradient(90deg, #d7f5c8, #fff);
}
.hub-body {
	margin-top: -32rpx;
	padding: 0 20rpx 80rpx;
	position: relative;
	z-index: 2;
}
.hub-loading {
	padding: 80rpx 0;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16rpx;
}
.hub-loading-text {
	font-size: 24rpx;
	color: #7a8b84;
}
.hub-bento {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 16rpx;
	grid-auto-rows: minmax(168rpx, auto);
}
.hub-tile {
	border-radius: 24rpx;
	padding: 22rpx 20rpx 18rpx;
	color: #fff;
	box-shadow: 0 12rpx 28rpx rgba(15, 110, 86, 0.15);
	display: flex;
	flex-direction: column;
	min-height: 168rpx;
}
.hub-tile:active {
	opacity: 0.92;
}
.hub-tile--hero-left {
	grid-row: span 2;
	min-height: 352rpx;
}
.hub-tile--tall-right {
	grid-row: span 2;
	min-height: 352rpx;
}
.hub-tile--wide {
	grid-column: span 2;
	min-height: 160rpx;
}
.hub-tile-top {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
}
.hub-tile-emoji {
	font-size: 44rpx;
	line-height: 1;
}
.hub-tile-badge {
	font-size: 20rpx;
	padding: 4rpx 12rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.22);
	font-weight: 700;
}
.hub-tile-badge--go {
	background: rgba(255, 244, 214, 0.35);
}
.hub-tile-name {
	margin-top: 16rpx;
	font-size: 32rpx;
	font-weight: 800;
	letter-spacing: 2rpx;
}
.hub-tile-sub {
	margin-top: 8rpx;
	font-size: 22rpx;
	opacity: 0.9;
	line-height: 1.45;
	flex: 1;
}
.hub-tile-foot {
	margin-top: 12rpx;
	display: flex;
	justify-content: space-between;
	align-items: center;
	font-size: 20rpx;
	opacity: 0.88;
}
.hub-tile-arrow {
	font-weight: 700;
}
</style>
