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

				<text class="sh-detail__tag">{{ item.category_label || categoryLabel(item.category) }}</text>

				<text class="sh-detail__tag">{{ item.condition_label || conditionLabel(item.condition) }}</text>

			</view>

			<text class="sh-detail__desc">{{ item.description || '暂无详细描述' }}</text>

			<view class="sh-detail__row">

				<u-icon name="map" size="28" color="#18AF38"></u-icon>

				<text class="sh-detail__row-t">{{ item.area_text || '交接说明见绿色二手市集规则' }}</text>

			</view>

		</view>

		<view class="sh-detail__seller">

			<u-avatar :src="item.seller_avatar" size="80"></u-avatar>

			<view class="sh-detail__seller-main">

				<text class="sh-detail__seller-name">{{ item.seller_nickname }}</text>

				<text class="sh-detail__seller-sub">邻里闲置 · 市集现场交接</text>

			</view>

		</view>

		<view class="sh-detail__foot" v-if="!item.is_owner && item.status_key === 'on_sale'">

			<view class="sh-detail__btn" hover-class="sh-detail__btn--press" @tap="onWant">我想要</view>

		</view>

		<view class="sh-detail__foot sh-detail__foot--hint" v-else-if="item.my_intent">

			<text class="sh-detail__hint">您已登记交换意向，请前往绿色二手市集现场办理。</text>

		</view>

	</view>

</template>



<script>

import { categoryLabel, conditionLabel } from '@/common/secondhandStore.js'

import { fleaMarketHubUrl } from '@/common/secondhandNav.js'



export default {

	data() {

		return {

			id: '',

			item: null,

		}

	},

	onLoad(q) {

		this.id = q.id || ''

		this.loadDetail()

	},

	methods: {

		categoryLabel,

		conditionLabel,

		loadDetail() {

			if (!this.id) return

			this.$u.api.secondhandDetail({ id: this.id }).then(res => {

				this.item = res.data || null

			}).catch(err => {

				uni.showToast({ title: (err && err.msg) || '加载失败', icon: 'none' })

				setTimeout(() => uni.navigateBack(), 800)

			})

		},

		onWant() {

			if (!this.$u.func.checkLogin()) return

			this.$u.api.secondhandCreateIntent({ item_id: this.id }).then(() => {

				uni.showModal({

					title: '登记成功',

					content: '请按「绿色二手市集」公布的场次，携带物品到现场交接。积分将在工作人员确认交割后划转。',

					confirmText: '查看场次',

					cancelText: '知道了',

					success: (res) => {

						if (res.confirm) {

							this.$u.route(fleaMarketHubUrl())

						} else {

							this.loadDetail()

						}

					},

				})

			}).catch(err => {

				uni.showToast({ title: (err && err.msg) || '登记失败', icon: 'none' })

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

	color: #999;

	font-size: 28rpx;

}



.sh-detail__img {

	width: 100%;

	height: 100%;

}



.sh-detail__panel {

	margin: 24rpx;

	padding: 28rpx;

	background: #fff;

	border-radius: 20rpx;

}



.sh-detail__price {

	display: flex;

	align-items: baseline;

}



.sh-detail__num {

	font-size: 48rpx;

	font-weight: 700;

	color: #076d3d;

}



.sh-detail__unit {

	margin-left: 8rpx;

	font-size: 28rpx;

	color: #076d3d;

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

	margin-top: 16rpx;

	display: flex;

	flex-wrap: wrap;

	gap: 12rpx;

}



.sh-detail__tag {

	padding: 8rpx 16rpx;

	background: #f2f4f3;

	border-radius: 8rpx;

	font-size: 24rpx;

	color: #666;

}



.sh-detail__desc {

	display: block;

	margin-top: 20rpx;

	font-size: 28rpx;

	color: #444;

	line-height: 1.55;

}



.sh-detail__row {

	margin-top: 20rpx;

	display: flex;

	align-items: flex-start;

	gap: 8rpx;

}



.sh-detail__row-t {

	flex: 1;

	font-size: 26rpx;

	color: #666;

	line-height: 1.45;

}



.sh-detail__seller {

	margin: 0 24rpx;

	padding: 24rpx;

	background: #fff;

	border-radius: 20rpx;

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

	margin-top: 6rpx;

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



.sh-detail__foot--hint {

	text-align: center;

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



.sh-detail__hint {

	font-size: 28rpx;

	color: #076d3d;

	line-height: 1.5;

}

</style>

