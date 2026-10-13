<template>
	<view class="sh-detail" v-if="item">
		<swiper v-if="item.images && item.images.length" class="sh-detail__swiper" circular indicator-dots indicator-color="rgba(255,255,255,0.5)" indicator-active-color="#fff">
			<swiper-item v-for="(src, idx) in item.images" :key="idx">
				<image class="sh-detail__img" :src="src" mode="aspectFill"></image>
			</swiper-item>
		</swiper>
		<view v-else class="sh-detail__swiper sh-detail__swiper--ph">暂无图片</view>
		<view class="sh-detail__panel">
			<view class="sh-detail__price">
				<text class="sh-detail__num">{{ item.point_price }}</text>
				<text class="sh-detail__unit">积分</text>
			</view>
			<text class="sh-detail__title">{{ item.title }}</text>
			<view class="sh-detail__tags">
				<text class="sh-detail__tag">{{ categoryLabel(item.category) }}</text>
				<text class="sh-detail__tag">{{ conditionLabel(item.condition) }}</text>
			</view>
			<text class="sh-detail__desc">{{ item.description || '暂无详细描述' }}</text>
			<view class="sh-detail__row">
				<u-icon name="map" size="28" color="#18AF38"></u-icon>
				<text class="sh-detail__row-t">{{ item.area_text || '交接范围待沟通' }}</text>
			</view>
		</view>
		<view class="sh-detail__seller">
			<u-avatar :src="item.seller_avatar" size="80"></u-avatar>
			<view class="sh-detail__seller-main">
				<text class="sh-detail__seller-name">{{ item.seller_nickname }}</text>
				<text class="sh-detail__seller-sub">邻里闲置 · 线下交接</text>
			</view>
		</view>
		<view class="sh-detail__foot">
			<view class="sh-detail__btn" hover-class="sh-detail__btn--press" @tap="onWant">我想要</view>
		</view>
	</view>
</template>

<script>
import { getSecondhandById, categoryLabel, conditionLabel } from '@/common/secondhandStore.js'

export default {
	data() {
		return {
			id: '',
			item: null,
		}
	},
	onLoad(q) {
		this.id = q.id || ''
		this.item = getSecondhandById(this.id)
		if (!this.item) {
			uni.showToast({ title: '物品不存在', icon: 'none' })
			setTimeout(() => uni.navigateBack(), 800)
		}
	},
	methods: {
		categoryLabel,
		conditionLabel,
		onWant() {
			if (!this.$u.func.checkLogin()) return
			uni.showModal({
				title: '演示说明',
				content: '正式版将冻结您的积分并与卖家确认交接。当前为前端演示，请线下自行联系邻居完成交换。',
				showCancel: false,
				confirmText: '知道了',
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.sh-detail {
	min-height: 100vh;
	background: #e4e8e5;
	padding-bottom: calc(140rpx + env(safe-area-inset-bottom));
}

.sh-detail__swiper {
	height: 560rpx;
	background: #eef1f0;
}

.sh-detail__swiper--ph {
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 28rpx;
	color: #b0b8bc;
}

.sh-detail__img {
	width: 100%;
	height: 100%;
}

.sh-detail__panel {
	margin: 24rpx 30rpx 0;
	padding: 28rpx;
	background: #fff;
	border-radius: 24rpx;
}

.sh-detail__price {
	display: flex;
	align-items: baseline;
	gap: 8rpx;
}

.sh-detail__num {
	font-size: 56rpx;
	font-weight: 700;
	color: #076d3d;
}

.sh-detail__unit {
	font-size: 28rpx;
	color: #076d3d;
	font-weight: 600;
}

.sh-detail__title {
	display: block;
	margin-top: 16rpx;
	font-size: 34rpx;
	font-weight: 700;
	color: #1a1a1a;
	line-height: 1.4;
}

.sh-detail__tags {
	display: flex;
	flex-wrap: wrap;
	gap: 12rpx;
	margin-top: 16rpx;
}

.sh-detail__tag {
	padding: 6rpx 16rpx;
	background: #f3faf6;
	border-radius: 8rpx;
	font-size: 22rpx;
	color: #076d3d;
}

.sh-detail__desc {
	display: block;
	margin-top: 20rpx;
	font-size: 28rpx;
	color: #666;
	line-height: 1.55;
}

.sh-detail__row {
	display: flex;
	align-items: flex-start;
	gap: 12rpx;
	margin-top: 24rpx;
	padding-top: 20rpx;
	border-top: 1rpx solid #f0f2f1;
}

.sh-detail__row-t {
	flex: 1;
	font-size: 26rpx;
	color: #444;
	line-height: 1.45;
}

.sh-detail__seller {
	margin: 24rpx 30rpx 0;
	padding: 24rpx 28rpx;
	background: #fff;
	border-radius: 24rpx;
	display: flex;
	align-items: center;
	gap: 20rpx;
}

.sh-detail__seller-name {
	font-size: 30rpx;
	font-weight: 600;
	color: #1a1a1a;
}

.sh-detail__seller-sub {
	display: block;
	margin-top: 8rpx;
	font-size: 24rpx;
	color: #8e8e93;
}

.sh-detail__foot {
	position: fixed;
	left: 0;
	right: 0;
	bottom: 0;
	padding: 16rpx 30rpx calc(16rpx + env(safe-area-inset-bottom));
	background: rgba(255, 255, 255, 0.96);
}

.sh-detail__btn {
	height: 88rpx;
	line-height: 88rpx;
	text-align: center;
	background: #18af38;
	color: #fff;
	font-size: 32rpx;
	font-weight: 600;
	border-radius: 999rpx;
}

.sh-detail__btn--press {
	opacity: 0.88;
}
</style>
