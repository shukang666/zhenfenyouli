<template>
	<view class="sh-my">
		<view class="sh-my__item" v-for="item in list" :key="item.id" @tap="goDetail(item.id)">
			<image class="sh-my__img" :src="cover(item)" mode="aspectFill"></image>
			<view class="sh-my__main">
				<text class="sh-my__title u-line-2">{{ item.title }}</text>
				<text class="sh-my__point">{{ item.point_price }} 积分</text>
				<text class="sh-my__status">{{ statusText(item) }}</text>
			</view>
			<view class="sh-my__off" v-if="canOff(item)" @tap.stop="offShelf(item.id)">下架</view>
		</view>
		<view class="sh-my__empty" v-if="!loading && !list.length">
			<text class="sh-my__empty-t">还没有发布过闲置</text>
			<view class="sh-my__go" @tap="goPublish">去发布</view>
		</view>
	</view>
</template>

<script>
import { statusDisplayText } from '@/common/secondhandStore.js'

export default {
	data() {
		return {
			list: [],
			loading: false,
		}
	},
	onShow() {
		if (!this.$u.func.checkLogin()) return
		this.reload()
	},
	methods: {
		reload() {
			this.loading = true
			this.$u.api
				.secondhandMyList({ page: 1 })
				.then(res => {
					this.list = (res.data && res.data.list) || []
				})
				.finally(() => {
					this.loading = false
				})
		},
		cover(item) {
			return (item.images && item.images[0]) || ''
		},
		statusText(item) {
			return item.status_label || statusDisplayText(item)
		},
		canOff(item) {
			const k = item.status_key
			return k === 'on_sale' || k === 'reserved' || item.status === 1 || item.status === 2
		},
		goDetail(id) {
			this.$u.route('/pages/points/secondhand-detail?id=' + id)
		},
		goPublish() {
			this.$u.route('/pages/points/secondhand-publish')
		},
		offShelf(id) {
			uni.showModal({
				title: '下架闲置',
				content: '下架后其他用户将无法看到该物品；进行中的交换意向将一并取消。',
				success: res => {
					if (!res.confirm) return
					this.$u.api.secondhandOffShelf({ id }).then(() => {
						uni.showToast({ title: '已下架', icon: 'success' })
						this.reload()
					}).catch(err => {
						uni.showToast({ title: (err && err.msg) || '操作失败', icon: 'none' })
					})
				},
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.sh-my {
	min-height: 100vh;
	background: #e4e8e5;
	padding: 24rpx 30rpx;
	box-sizing: border-box;
}

.sh-my__item {
	display: flex;
	align-items: center;
	gap: 20rpx;
	padding: 20rpx;
	margin-bottom: 20rpx;
	background: #fff;
	border-radius: 20rpx;
}

.sh-my__img {
	width: 140rpx;
	height: 140rpx;
	border-radius: 12rpx;
	background: #eef1f0;
	flex-shrink: 0;
}

.sh-my__main {
	flex: 1;
	min-width: 0;
}

.sh-my__title {
	font-size: 28rpx;
	font-weight: 600;
	color: #1a1a1a;
}

.sh-my__point {
	display: block;
	margin-top: 8rpx;
	font-size: 26rpx;
	color: #076d3d;
	font-weight: 600;
}

.sh-my__status {
	display: block;
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #8e8e93;
}

.sh-my__off {
	flex-shrink: 0;
	padding: 12rpx 24rpx;
	font-size: 24rpx;
	color: #666;
	background: #f2f4f3;
	border-radius: 999rpx;
}

.sh-my__empty {
	padding: 120rpx 0;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.sh-my__empty-t {
	font-size: 28rpx;
	color: #999;
	line-height: 1.5;
}

.sh-my__go {
	margin-top: 24rpx;
	padding: 16rpx 48rpx;
	background: #18af38;
	color: #fff;
	border-radius: 999rpx;
	font-size: 28rpx;
}
</style>
