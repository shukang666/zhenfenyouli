<template>
	<view class="detail-page" v-if="item">
		<swiper v-if="item.images && item.images.length" class="gallery" circular indicator-dots indicator-color="rgba(255,255,255,.5)" indicator-active-color="#fff"><swiper-item v-for="(src, idx) in item.images" :key="idx"><image class="gallery-image" :src="src" mode="aspectFill"></image></swiper-item></swiper>
		<view v-else class="gallery gallery-empty">暂无图片</view>
		<view class="detail-card">
			<text class="price">¥{{ Number(item.price || 0).toFixed(2) }}</text>
			<text class="title">{{ item.title }}</text>
			<view class="tags"><text class="tag">{{ item.category_label || categoryLabel(item.category) }}</text><text class="tag">{{ item.condition_label || conditionLabel(item.condition) }}</text></view>
			<text class="description">{{ item.description || '暂无详细介绍' }}</text>
			<view class="scope"><u-icon name="map" size="28" color="#16864b"></u-icon><text>{{ item.scope_name || item.area_text }}</text></view>
		</view>
		<view class="seller-card"><u-avatar :src="item.seller_avatar" size="78"></u-avatar><view class="seller-info"><text class="seller-name">{{ item.seller_nickname }}</text><text class="seller-contact">联系方式：{{ item.contact }}</text></view></view>
		<view class="disclaimer">请与发布者自行联系，协商价格及交接方式。平台不代收款、不参与交易。</view>
		<view class="bottom-action" v-if="!item.is_owner && item.status_key === 'on_sale'" @tap="showContact">联系卖家</view>
	</view>
</template>

<script>
import { categoryLabel, conditionLabel } from '@/common/secondhandStore.js'

export default {
	data() { return { id: '', item: null } },
	onLoad(query) { this.id = query.id || ''; this.loadDetail() },
	methods: {
		categoryLabel,
		conditionLabel,
		loadDetail() {
			if (!this.id) return
			this.$u.api.secondhandDetail({ id: this.id }).then(res => { this.item = res.data || null }).catch(err => {
				uni.showToast({ title: (err && err.msg) || '加载失败', icon: 'none' })
				setTimeout(() => uni.navigateBack(), 800)
			})
		},
		showContact() { uni.showModal({ title: '联系卖家', content: '联系方式：' + (this.item.contact || '未提供'), showCancel: false }) },
	},
}
</script>

<style lang="scss" scoped>
.detail-page { min-height: 100vh; padding-bottom: calc(130rpx + env(safe-area-inset-bottom)); background: #f4f7f5; }
.gallery { width: 100%; height: 620rpx; background: #edf2ee; }
.gallery-image { width: 100%; height: 100%; }
.gallery-empty { display: flex; align-items: center; justify-content: center; color: #9aa59e; font-size: 27rpx; }
.detail-card, .seller-card { margin: 22rpx 24rpx 0; padding: 28rpx; border-radius: 22rpx; background: #fff; }
.price { display: block; color: #16864b; font-size: 42rpx; font-weight: 700; }
.title { display: block; margin-top: 12rpx; color: #26372d; font-size: 34rpx; font-weight: 600; }
.tags { display: flex; gap: 12rpx; margin-top: 16rpx; }
.tag { padding: 8rpx 16rpx; border-radius: 999rpx; background: #eff5f1; color: #567361; font-size: 22rpx; }
.description { display: block; margin-top: 24rpx; color: #536158; font-size: 27rpx; line-height: 1.7; white-space: pre-wrap; }
.scope { display: flex; align-items: center; gap: 12rpx; margin-top: 24rpx; color: #66756b; font-size: 25rpx; }
.seller-card { display: flex; align-items: center; gap: 18rpx; }
.seller-info { display: flex; flex: 1; flex-direction: column; gap: 8rpx; }
.seller-name { color: #26372d; font-size: 27rpx; font-weight: 600; }
.seller-contact { color: #16864b; font-size: 25rpx; }
.disclaimer { margin: 20rpx 34rpx; color: #87928a; font-size: 23rpx; line-height: 1.55; }
.bottom-action { position: fixed; right: 30rpx; bottom: calc(24rpx + env(safe-area-inset-bottom)); left: 30rpx; height: 92rpx; border-radius: 999rpx; background: #16864b; color: #fff; text-align: center; line-height: 92rpx; font-size: 30rpx; font-weight: 600; }
</style>
