<template>
	<view class="ai-root" v-show="visible">
		<view class="ai-mask" v-if="open" @click="closePanel"></view>
		<view class="ai-dock" :class="{ 'ai-dock--open': open }">
			<view class="ai-panel" v-if="open">
				<text class="ai-panel__title">猜你想问</text>
				<view class="ai-panel__item" hover-class="ai-panel__item--on" :hover-stay-time="80" @click="askGuide">
					<view class="ai-panel__dot"></view>
					<text class="ai-panel__text">新手引导</text>
					<u-icon name="arrow-right" size="22" color="#8a9b8d"></u-icon>
				</view>
			</view>
			<view class="ai-bot" @click="togglePanel">
				<view class="ai-bot__leaf"></view>
				<view class="ai-bot__face">
					<view class="ai-bot__eye"></view>
					<view class="ai-bot__eye"></view>
					<view class="ai-bot__mouth"></view>
				</view>
			</view>
			<text class="ai-bot__label" v-if="open">问问</text>
		</view>
	</view>
</template>

<script>
export default {
	name: 'ai-ask-fab',
	options: {
		virtualHost: true
	},
	props: {
		visible: {
			type: Boolean,
			default: true
		}
	},
	data() {
		return {
			open: false
		}
	},
	watch: {
		visible(val) {
			if (!val) this.open = false
		}
	},
	methods: {
		togglePanel() {
			this.open = !this.open
		},
		closePanel() {
			this.open = false
		},
		askGuide() {
			this.open = false
			uni.setStorageSync('start_guide_now', '1')
			const pages = getCurrentPages()
			const route = pages.length ? (pages[pages.length - 1].route || '') : ''
			if (route === 'pages/index/index') {
				this.$emit('start-guide')
				return
			}
			uni.switchTab({
				url: '/pages/index/index'
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.ai-root {
	pointer-events: none;
}
.ai-mask {
	position: fixed;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	z-index: 9970;
	background: rgba(0, 0, 0, 0.12);
	pointer-events: auto;
}
.ai-dock {
	position: fixed;
	left: 0;
	bottom: calc(148rpx + constant(safe-area-inset-bottom));
	bottom: calc(148rpx + env(safe-area-inset-bottom));
	z-index: 9971;
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	pointer-events: auto;
	transform: translateX(-40rpx);
	transition: transform 0.28s ease;
}
.ai-dock--open {
	transform: translateX(20rpx);
}
.ai-panel {
	width: 320rpx;
	margin-bottom: 16rpx;
	background: #fff;
	border-radius: 24rpx;
	padding: 22rpx 20rpx 16rpx;
	box-shadow: 0 12rpx 32rpx rgba(16, 48, 24, 0.14);
}
.ai-panel__title {
	display: block;
	font-size: 22rpx;
	color: #8a9b8d;
	margin-bottom: 12rpx;
	padding-left: 6rpx;
}
.ai-panel__item {
	display: flex;
	flex-direction: row;
	align-items: center;
	padding: 18rpx 12rpx;
	background: #f3f8f4;
	border-radius: 16rpx;
}
.ai-panel__item--on {
	opacity: 0.86;
}
.ai-panel__dot {
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
	background: #18af38;
	margin-right: 12rpx;
	flex-shrink: 0;
}
.ai-panel__text {
	flex: 1;
	font-size: 28rpx;
	font-weight: 700;
	color: #1c2b1e;
}
.ai-bot {
	width: 72rpx;
	height: 72rpx;
	border-radius: 50%;
	background: linear-gradient(160deg, #3dd45f 0%, #18af38 100%);
	box-shadow: 4rpx 6rpx 16rpx rgba(24, 175, 56, 0.28);
	position: relative;
	overflow: hidden;
}
.ai-dock--open .ai-bot {
	width: 88rpx;
	height: 88rpx;
	overflow: visible;
	box-shadow: 0 10rpx 22rpx rgba(24, 175, 56, 0.32);
}
.ai-bot__leaf {
	position: absolute;
	top: -8rpx;
	left: 28rpx;
	width: 16rpx;
	height: 22rpx;
	background: #0e7f26;
	border-radius: 0 16rpx 4rpx 16rpx;
	transform: rotate(-18deg);
	opacity: 0;
}
.ai-dock--open .ai-bot__leaf {
	opacity: 1;
	left: 34rpx;
}
.ai-bot__face {
	position: absolute;
	left: 18rpx;
	right: 0;
	top: 20rpx;
	display: flex;
	flex-direction: row;
	justify-content: flex-start;
}
.ai-dock--open .ai-bot__face {
	left: 0;
	justify-content: center;
	top: 24rpx;
}
.ai-bot__eye {
	width: 10rpx;
	height: 13rpx;
	border-radius: 50%;
	background: #fff;
	margin: 0 6rpx;
}
.ai-bot__mouth {
	position: absolute;
	top: 22rpx;
	left: 22rpx;
	width: 14rpx;
	height: 8rpx;
	border-bottom: 3rpx solid rgba(255, 255, 255, 0.9);
	border-radius: 0 0 10rpx 10rpx;
}
.ai-dock--open .ai-bot__mouth {
	left: 50%;
	margin-left: -10rpx;
	width: 20rpx;
	top: 26rpx;
}
.ai-bot__label {
	margin-top: 6rpx;
	font-size: 20rpx;
	color: #18af38;
	font-weight: 700;
	padding: 2rpx 10rpx;
	background: rgba(255, 255, 255, 0.92);
	border-radius: 999rpx;
}
</style>
