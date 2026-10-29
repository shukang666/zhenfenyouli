<template>
	<view class="home-ongoing" v-if="visible">
		<view class="home-ongoing__head">
			<text class="home-ongoing__title">我的进行中</text>
			<view class="home-ongoing__more" v-if="showMore" @click="$emit('more')">
				<text>查看全部</text>
				<u-icon name="arrow-right" size="20" color="#18AF38"></u-icon>
			</view>
		</view>
		<view
			class="home-ongoing-card"
			v-for="item in list"
			:key="item.id + '-' + item.role"
			hover-class="home-ongoing-card--on"
			:hover-stay-time="80"
			@click="$emit('open', item)"
		>
			<view class="home-ongoing-card__cal">
				<text class="home-ongoing-card__day">{{ item.cal_day }}</text>
				<text class="home-ongoing-card__sub">{{ item.cal_sub }}</text>
			</view>
			<view class="home-ongoing-card__body">
				<text class="home-ongoing-card__name u-line-1">{{ item.name }}</text>
				<text class="home-ongoing-card__loc u-line-1">{{ item.location_line }}</text>
				<view class="home-ongoing-card__tag">
					<text>{{ item.status_hint }}</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'HomeOngoingSection',
	props: {
		list: {
			type: Array,
			default: () => [],
		},
		total: {
			type: Number,
			default: 0,
		},
		visible: {
			type: Boolean,
			default: false,
		},
		previewLimit: {
			type: Number,
			default: 5,
		},
	},
	computed: {
		showMore() {
			return this.total > this.list.length || this.total > this.previewLimit
		},
	},
}
</script>

<style lang="scss" scoped>
.home-ongoing {
	position: relative;
	z-index: 1;
	margin: 24rpx 32rpx 0;
}

.home-ongoing__head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 16rpx;
}

.home-ongoing__title {
	font-size: 32rpx;
	font-weight: 800;
	color: #1a2e22;
}

.home-ongoing__more {
	display: flex;
	align-items: center;
	gap: 4rpx;
	font-size: 24rpx;
	font-weight: 600;
	color: #18af38;
}

.home-ongoing-card {
	display: flex;
	flex-direction: row;
	align-items: stretch;
	padding: 22rpx 20rpx;
	margin-bottom: 14rpx;
	border-radius: 20rpx;
	background: #ffffff;
	box-shadow: 0 8rpx 28rpx rgba(22, 53, 36, 0.06);
}
.home-ongoing-card:last-child {
	margin-bottom: 0;
}
.home-ongoing-card--on {
	opacity: 0.92;
	transform: scale(0.995);
}

.home-ongoing-card__cal {
	flex: 0 0 112rpx;
	width: 112rpx;
	margin-right: 18rpx;
	padding: 14rpx 8rpx;
	border-radius: 16rpx;
	background: #e8f7ec;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	box-sizing: border-box;
}

.home-ongoing-card__day {
	font-size: 40rpx;
	font-weight: 800;
	color: #12962e;
	line-height: 1.1;
}

.home-ongoing-card__sub {
	margin-top: 6rpx;
	font-size: 20rpx;
	font-weight: 600;
	color: #5f7166;
	text-align: center;
	line-height: 1.25;
}

.home-ongoing-card__body {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	justify-content: center;
}

.home-ongoing-card__name {
	font-size: 30rpx;
	font-weight: 700;
	color: #1a2e22;
	line-height: 1.35;
}

.home-ongoing-card__loc {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #7a8a80;
	line-height: 1.4;
}

.home-ongoing-card__tag {
	display: inline-flex;
	align-self: flex-start;
	margin-top: 12rpx;
	padding: 6rpx 14rpx;
	border-radius: 999rpx;
	background: #edf9f0;
}
.home-ongoing-card__tag text {
	font-size: 20rpx;
	font-weight: 600;
	color: #12962e;
	line-height: 1.3;
}
</style>
