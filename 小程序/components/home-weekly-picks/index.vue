<template>
	<view class="home-weekly" v-if="list && list.length">
		<view class="home-weekly__head">
			<text class="home-weekly__title">本周精选</text>
			<view class="home-weekly__more" @click="$emit('more')">
				<text>更多</text>
				<u-icon name="arrow-right" size="20" color="#9aa39c"></u-icon>
			</view>
		</view>
		<view class="home-weekly__row">
			<view
				class="home-weekly-card"
				v-for="item in list"
				:key="item.id"
				hover-class="home-weekly-card--on"
				:hover-stay-time="80"
				@click="$emit('open', item)"
			>
				<view class="home-weekly-card__hero" :class="'home-weekly-card__hero--' + (item.theme || 'cool')">
					<view class="home-weekly-card__hero-orb" aria-hidden="true"></view>
					<view class="home-weekly-card__hero-wave" aria-hidden="true"></view>
					<view class="home-weekly-card__hero-copy">
						<text class="home-weekly-card__hero-title u-line-1">{{ item.cover_title || item.name }}</text>
						<text class="home-weekly-card__hero-sub u-line-1">{{ item.cover_sub }}</text>
					</view>
				</view>
				<view class="home-weekly-card__body">
					<text class="home-weekly-card__name u-line-2">{{ item.name }}</text>
					<text class="home-weekly-card__time">{{ item.time_text }}</text>
					<view class="home-weekly-card__chips">
						<view
							class="home-weekly-chip"
							v-for="(chip, idx) in item.chip_list || []"
							:key="idx"
							:class="'home-weekly-chip--' + (chip.tone || 'muted')"
						>
							<text>{{ chip.text }}</text>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'HomeWeeklyPicks',
	props: {
		list: {
			type: Array,
			default: () => [],
		},
	},
}
</script>

<style lang="scss" scoped>
.home-weekly {
	position: relative;
	z-index: 1;
	margin: 28rpx 32rpx 0;
}

.home-weekly__head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 16rpx;
}

.home-weekly__title {
	font-size: 32rpx;
	font-weight: 800;
	color: #1a2e22;
}

.home-weekly__more {
	display: flex;
	align-items: center;
	gap: 4rpx;
	font-size: 24rpx;
	font-weight: 500;
	color: #9aa39c;
}

.home-weekly__row {
	display: flex;
	flex-direction: row;
	align-items: stretch;
	gap: 16rpx;
}

.home-weekly-card {
	flex: 1;
	min-width: 0;
	border-radius: 20rpx;
	overflow: hidden;
	background: #ffffff;
	box-shadow: 0 8rpx 28rpx rgba(22, 53, 36, 0.07);
}
.home-weekly-card--on {
	opacity: 0.94;
	transform: scale(0.99);
}

.home-weekly-card__hero {
	position: relative;
	height: 168rpx;
	padding: 20rpx 18rpx 16rpx;
	box-sizing: border-box;
	overflow: hidden;
}
.home-weekly-card__hero--cool {
	background: linear-gradient(145deg, #2f6fd8 0%, #5a9ae8 48%, #7eb8f5 100%);
}
.home-weekly-card__hero--warm {
	background: linear-gradient(145deg, #e86b4a 0%, #f0937a 45%, #f7b89a 100%);
}

.home-weekly-card__hero-orb {
	position: absolute;
	right: 12rpx;
	top: 10rpx;
	width: 56rpx;
	height: 56rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.22);
}
.home-weekly-card__hero-wave {
	position: absolute;
	left: -20%;
	right: -10%;
	bottom: -36rpx;
	height: 80rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.14);
}

.home-weekly-card__hero-copy {
	position: relative;
	z-index: 1;
}
.home-weekly-card__hero-title {
	display: block;
	font-size: 30rpx;
	font-weight: 800;
	color: #ffffff;
	line-height: 1.25;
}
.home-weekly-card__hero-sub {
	display: block;
	margin-top: 8rpx;
	font-size: 22rpx;
	font-weight: 500;
	color: rgba(255, 255, 255, 0.92);
	line-height: 1.3;
}

.home-weekly-card__body {
	padding: 16rpx 16rpx 18rpx;
}
.home-weekly-card__name {
	display: block;
	font-size: 26rpx;
	font-weight: 700;
	color: #1a2e22;
	line-height: 1.35;
	min-height: 70rpx;
}
.home-weekly-card__time {
	display: block;
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #8a9690;
	line-height: 1.35;
}

.home-weekly-card__chips {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	gap: 8rpx;
	margin-top: 12rpx;
}
.home-weekly-chip {
	display: inline-flex;
	padding: 4rpx 12rpx;
	border-radius: 999rpx;
}
.home-weekly-chip text {
	font-size: 20rpx;
	font-weight: 600;
	line-height: 1.3;
}
.home-weekly-chip--accent {
	background: #fff0e6;
}
.home-weekly-chip--accent text {
	color: #e86b20;
}
.home-weekly-chip--muted {
	background: #f0f2f5;
}
.home-weekly-chip--muted text {
	color: #7a8480;
}
</style>
