<template>
	<view class="container">
		<view class="fb-hero" v-if="imgUrl">
			<u-image :src="`${imgUrl}rank-bg.png`" width="750" height="504" mode="aspectFill"></u-image>
			<view class="fb-hero-mask"></view>
			<view class="fb-hero-title">
				<text class="fb-hero-title-main">签到排行榜</text>
				<text class="fb-hero-title-sub">坚持打卡，绿色积累</text>
			</view>
		</view>
		<view class="fb-rank-card">
			<view class="fb-rank-header">
				<view class="fb-rank-col fb-rank-col-rank">排名</view>
				<view class="fb-rank-col fb-rank-col-user">用户</view>
				<view class="fb-rank-col fb-rank-col-count">打卡次数</view>
			</view>
			<view class="fb-rank-item" v-for="item,i in dataList" :key="i" :style="{background:returnColor(i)}">
				<view class="fb-rank-col fb-rank-col-rank fb-rank-num-box">
					<image src="/static/images/rank1.png" class="fb-rank-medal" mode="aspectFill" v-if="i == 0"></image>
					<image src="/static/images/rank2.png" class="fb-rank-medal" mode="aspectFill" v-else-if="i == 1"></image>
					<image src="/static/images/rank3.png" class="fb-rank-medal" mode="aspectFill" v-else-if="i == 2"></image>
					<text class="fb-rank-num" v-else>{{i+1}}</text>
				</view>
				<view class="fb-rank-col fb-rank-col-user">
					<u-avatar size="56" :src="item.member_info.avatar"></u-avatar>
					<text class="fb-rank-name">{{item.member_info.nickname}}</text>
				</view>
				<view class="fb-rank-col fb-rank-col-count">
					<text class="fb-rank-count">{{item.clock_count}}</text>
					<text class="fb-rank-unit">次</text>
				</view>
			</view>
			<view class="fb-empty" v-if="!dataList.length">
				<text>暂无排行数据</text>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				id: '',
				dataList: [],
			};
		},
		onLoad(op) {
			this.id = op.id
			this.get_new_active_ranking()
		},
		methods: {
			async get_new_active_ranking() {
				try {
					let params = {
						active_id: this.id
					}
					let res = await this.$u.api.get_new_active_ranking(params)
					this.dataList = res.data
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
			returnColor(i) {
				if (i == 0) return '#FFF8DC'
				if (i == 1) return '#F0F5FE'
				if (i == 2) return '#FEF5EB'
				return '#FFFFFF'
			},
		}
	}
</script>

<style lang="scss" scoped>
.container {
	min-height: 100vh;
	background: #F0F2F5;
	padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
}

/* Hero 顶部图 */
.fb-hero {
	position: relative;
	width: 750rpx;
	height: 504rpx;
	overflow: hidden;
}
.fb-hero-mask {
	position: absolute;
	top: 0; left: 0; right: 0; bottom: 0;
	background: linear-gradient(180deg, rgba(24,119,242,0.2) 0%, rgba(24,119,242,0.5) 100%);
}
.fb-hero-title {
	position: absolute;
	left: 30rpx;
	bottom: 40rpx;
	display: flex;
	flex-direction: column;
}
.fb-hero-title-main {
	font-size: 44rpx;
	font-weight: 700;
	color: #fff;
	line-height: 1.3;
	text-shadow: 0 2rpx 8rpx rgba(0,0,0,0.3);
}
.fb-hero-title-sub {
	font-size: 26rpx;
	color: rgba(255,255,255,0.9);
	margin-top: 8rpx;
	text-shadow: 0 1rpx 4rpx rgba(0,0,0,0.3);
}

/* 排行卡片 */
.fb-rank-card {
	margin: -100rpx 24rpx 0;
	background: #fff;
	border-radius: 16rpx;
	overflow: hidden;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
	border: 1rpx solid #E4E6EB;
}

.fb-rank-header {
	display: flex;
	align-items: center;
	padding: 24rpx 30rpx;
	background: #F0F2F5;
	border-bottom: 1rpx solid #E4E6EB;
}
.fb-rank-item {
	display: flex;
	align-items: center;
	padding: 24rpx 30rpx;
	border-bottom: 1rpx solid #E4E6EB;
}
.fb-rank-item:last-child {
	border-bottom: none;
}

.fb-rank-col {
	display: flex;
	align-items: center;
}
.fb-rank-col-rank {
	width: 100rpx;
	justify-content: center;
}
.fb-rank-col-user {
	flex: 1;
	min-width: 0;
	gap: 16rpx;
}
.fb-rank-col-count {
	width: 160rpx;
	justify-content: flex-end;
	gap: 4rpx;
}

.fb-rank-num-box {
	height: 80rpx;
}
.fb-rank-medal {
	width: 80rpx;
	height: 50rpx;
}
.fb-rank-num {
	font-size: 32rpx;
	font-weight: 700;
	color: #65676B;
}

.fb-rank-name {
	font-size: 28rpx;
	color: #050505;
	font-weight: 500;
	flex: 1;
	min-width: 0;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}

.fb-rank-count {
	font-size: 32rpx;
	font-weight: 700;
	color: #1877F2;
}
.fb-rank-unit {
	font-size: 22rpx;
	color: #65676B;
}

.fb-empty {
	padding: 60rpx 0;
	text-align: center;
	color: #65676B;
	font-size: 26rpx;
}
</style>
