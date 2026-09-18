<template>
	<view class="ai-root" v-show="visible">
		<view class="ai-mask" v-if="open" @click="closePanel"></view>
		<view class="ai-dock">
			<view class="ai-panel" v-if="open">
				<text class="ai-panel__title">猜你想问</text>
				<view class="ai-panel__item" hover-class="ai-panel__item--on" :hover-stay-time="80" @click="askGuide">
					<view class="ai-panel__dot"></view>
					<text class="ai-panel__text">新手引导</text>
					<u-icon name="arrow-right" size="22" color="#8a9b8d"></u-icon>
				</view>
			</view>
			<view class="ai-bot" hover-class="ai-bot--on" :hover-stay-time="80" @click="togglePanel">
				<view class="ai-bot__leaf"></view>
				<view class="ai-bot__face">
					<view class="ai-bot__eye"></view>
					<view class="ai-bot__eye"></view>
					<view class="ai-bot__mouth"></view>
				</view>
			</view>
			<text class="ai-bot__label">问问</text>
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
	background: rgba(0, 0, 0, 0.18);
	pointer-events: auto;
}
.ai-dock {
	position: fixed;
	left: 20rpx;
	bottom: calc(132rpx + constant(safe-area-inset-bottom));
	bottom: calc(132rpx + env(safe-area-inset-bottom));
	z-index: 9971;
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	pointer-events: auto;
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
	width: 96rpx;
	height: 96rpx;
	border-radius: 50%;
	background: linear-gradient(160deg, #3dd45f 0%, #18af38 100%);
	box-shadow: 0 10rpx 22rpx rgba(24, 175, 56, 0.32);
	position: relative;
	animation: ai-bob 2s ease-in-out infinite;
}
.ai-bot--on {
	transform: scale(0.94);
	opacity: 0.9;
}
.ai-bot__leaf {
	position: absolute;
	top: -10rpx;
	left: 36rpx;
	width: 22rpx;
	height: 30rpx;
	background: #0e7f26;
	border-radius: 0 20rpx 4rpx 20rpx;
	transform: rotate(-18deg);
}
.ai-bot__face {
	position: absolute;
	left: 0;
	right: 0;
	top: 28rpx;
	display: flex;
	flex-direction: row;
	justify-content: center;
}
.ai-bot__eye {
	width: 12rpx;
	height: 16rpx;
	border-radius: 50%;
	background: #fff;
	margin: 0 8rpx;
}
.ai-bot__mouth {
	position: absolute;
	top: 28rpx;
	left: 50%;
	margin-left: -10rpx;
	width: 20rpx;
	height: 10rpx;
	border-bottom: 4rpx solid rgba(255, 255, 255, 0.9);
	border-radius: 0 0 12rpx 12rpx;
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
@keyframes ai-bob {
	0%,
	100% {
		transform: translateY(0);
	}
	50% {
		transform: translateY(-8rpx);
	}
}
</style>
