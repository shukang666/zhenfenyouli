<template>
	<view class="care-cord" v-show="visible">
		<view class="care-cord__inner">
			<view class="care-cord__rope">
				<view class="care-cord__line"></view>
				<view class="care-cord__knob"></view>
			</view>
			<text class="care-cord__text">下拉进入关怀版</text>
		</view>
	</view>
</template>

<script>
export default {
	name: 'care-pull-hint',
	props: {
		visible: {
			type: Boolean,
			default: true
		}
	}
}
</script>

<style lang="scss" scoped>
.care-cord {
	height: 48rpx;
	display: flex;
	align-items: flex-start;
	justify-content: center;
	pointer-events: none;
	overflow: visible;
}

.care-cord__inner {
	display: flex;
	flex-direction: row;
	align-items: center;
	padding: 0 16rpx 4rpx;
	animation: care-cord-pull 1s ease-in-out infinite;
}

.care-cord__rope {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-right: 10rpx;
}

.care-cord__line {
	width: 3rpx;
	height: 20rpx;
	background: #18af38;
	border-radius: 3rpx;
}

.care-cord__knob {
	width: 14rpx;
	height: 14rpx;
	margin-top: -2rpx;
	border-radius: 50%;
	background: #18af38;
}

.care-cord__text {
	font-size: 20rpx;
	color: #3d7a48;
	line-height: 1.2;
	letter-spacing: 1rpx;
}

@keyframes care-cord-pull {
	0%,
	100% {
		transform: translateY(0);
	}
	16% {
		transform: translateY(10rpx);
	}
	28% {
		transform: translateY(2rpx);
	}
	46% {
		transform: translateY(14rpx);
	}
	60% {
		transform: translateY(3rpx);
	}
	76% {
		transform: translateY(8rpx);
	}
	88% {
		transform: translateY(1rpx);
	}
}
</style>
