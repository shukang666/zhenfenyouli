<template>
	<view class="prd-header" :style="headerWrapStyle">
		<view class="prd-header__inner">
			<view class="prd-brand">
				<view class="prd-brand-mark" aria-hidden="true">
					<view class="prd-clover">
						<view class="prd-clover__petal prd-clover__petal--tl"></view>
						<view class="prd-clover__petal prd-clover__petal--tr"></view>
						<view class="prd-clover__petal prd-clover__petal--br"></view>
						<view class="prd-clover__petal prd-clover__petal--bl"></view>
						<view class="prd-clover__core"></view>
					</view>
				</view>
				<view class="prd-brand-type">
					<text class="prd-brand-name">甄分友礼</text>
					<text class="prd-brand-sub">LIFE, NEARBY.</text>
				</view>
			</view>
			<view class="prd-header__loc" :style="locStyle">
				<u-icon name="map" size="26" color="#738264"></u-icon>
				<text class="prd-header__loc-text">{{ locationLabel }}</text>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'HomePrdHeader',
	props: {
		locationLabel: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			statusBarHeight: 20,
			locRightPad: 12,
		}
	},
	created() {
		const sys = uni.getSystemInfoSync()
		this.statusBarHeight = sys.statusBarHeight || 20
		// #ifdef MP-WEIXIN
		try {
			const menu = uni.getMenuButtonBoundingClientRect()
			if (menu && menu.left > 0) {
				this.locRightPad = Math.max(12, sys.windowWidth - menu.left + 6)
			}
		} catch (e) {}
		// #endif
	},
	computed: {
		headerWrapStyle() {
			return {
				paddingTop: this.statusBarHeight + 'px',
			}
		},
		locStyle() {
			return {
				paddingRight: this.locRightPad + 'px',
			}
		},
	},
}
</script>

<style lang="scss" scoped>
$ink: #26382d;
$green-dark: #345640;
$green-light: #b7cf70;
$bg: #f7f8f3;

.prd-header {
	background: rgba(247, 248, 243, 0.96);
}
.prd-header__inner {
	display: flex;
	align-items: center;
	justify-content: space-between;
	min-height: 88rpx;
	padding: 8rpx 0 12rpx 32rpx;
	gap: 16rpx;
}
.prd-brand {
	display: flex;
	align-items: center;
	gap: 18rpx;
	flex: 1;
	min-width: 0;
}
.prd-brand-mark {
	flex-shrink: 0;
	width: 72rpx;
	height: 72rpx;
	border-radius: 22rpx;
	background: $bg;
	box-shadow: 0 6rpx 18rpx rgba(52, 86, 64, 0.08);
	display: flex;
	align-items: center;
	justify-content: center;
}
.prd-clover {
	position: relative;
	width: 44rpx;
	height: 44rpx;
}
.prd-clover__petal {
	position: absolute;
	width: 22rpx;
	height: 28rpx;
	border-radius: 50% 50% 45% 45%;
}
.prd-clover__petal--tl {
	left: 2rpx;
	top: 6rpx;
	background: $green-dark;
	transform: rotate(-35deg);
}
.prd-clover__petal--tr {
	right: 2rpx;
	top: 6rpx;
	background: $green-light;
	transform: rotate(35deg);
}
.prd-clover__petal--br {
	right: 4rpx;
	bottom: 4rpx;
	background: $green-dark;
	transform: rotate(145deg);
}
.prd-clover__petal--bl {
	left: 4rpx;
	bottom: 4rpx;
	background: $green-light;
	transform: rotate(-145deg);
}
.prd-clover__core {
	position: absolute;
	left: 50%;
	top: 50%;
	width: 8rpx;
	height: 8rpx;
	margin: -4rpx 0 0 -4rpx;
	border-radius: 50%;
	background: #8fa86a;
	opacity: 0.85;
}
.prd-brand-type {
	display: flex;
	flex-direction: column;
	gap: 2rpx;
	min-width: 0;
}
.prd-brand-name {
	font-size: 34rpx;
	font-weight: 600;
	letter-spacing: 6rpx;
	color: $ink;
	line-height: 1.25;
}
.prd-brand-sub {
	font-size: 14rpx;
	letter-spacing: 2.4rpx;
	color: #8a957b;
	line-height: 1.2;
}
.prd-header__loc {
	flex-shrink: 0;
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 8rpx;
	max-width: 320rpx;
	pointer-events: none;
}
.prd-header__loc-text {
	font-size: 22rpx;
	line-height: 1.3;
	color: #738264;
	letter-spacing: 0.5rpx;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	max-width: 240rpx;
}
</style>
