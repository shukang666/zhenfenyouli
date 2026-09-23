<template>
	<view class="detail-page">
		<view v-if="detail && detail.id">
			<view class="hero-cover" v-if="detail.cover">
				<u-image width="100%" height="420" :src="detail.cover" mode="aspectFill"></u-image>
			</view>

			<view class="detail-card">
				<view class="detail-title">{{ detail.title }}</view>
				<view class="detail-meta">
					<text>{{ detail.event_date_text }}</text>
					<text class="dot">·</text>
					<text>{{ detail.view_count }}次浏览</text>
					<text class="dot">·</text>
					<text>{{ detail.photo_count }}张照片</text>
				</view>
				<view class="detail-desc" v-if="detail.description && !detail.content">{{ detail.description }}</view>
			</view>

			<view class="rich-section" v-if="detail.content">
				<view class="section-title">活动介绍</view>
				<view class="detail-rich">
					<u-parse :html="detail.content"></u-parse>
				</view>
			</view>

			<view class="photo-section">
				<view class="section-title">现场照片</view>
				<view class="photo-grid">
					<view class="photo-item" v-for="(img, index) in detail.photos" :key="index" @click="previewImage(index)">
						<u-image width="100%" height="220" :src="img" mode="aspectFill" border-radius="16"></u-image>
					</view>
				</view>
			</view>
		</view>

		<view class="loading-state" v-if="loading">
			<u-loading mode="flower" size="60"></u-loading>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			id: 0,
			detail: null,
			loading: false,
		};
	},
	onLoad(options) {
		this.id = options.id || 0;
		if (this.id) {
			this.getDetail();
		}
	},
	methods: {
		getDetail() {
			this.loading = true;
			this.$u.api.getActivityShowcaseDetail({ id: this.id }).then(res => {
				this.loading = false;
				if (res.code == 1) {
					this.detail = res.data || {};
				} else {
					uni.showToast({ title: res.msg || '加载失败', icon: 'none' });
				}
			}).catch(() => {
				this.loading = false;
				uni.showToast({ title: '网络错误', icon: 'none' });
			});
		},
		previewImage(index) {
			if (!this.detail || !this.detail.photos || !this.detail.photos.length) return;
			uni.previewImage({
				current: index,
				urls: this.detail.photos
			});
		}
	}
};
</script>

<style lang="scss" scoped>
.detail-page {
	min-height: 100vh;
	background: #f5f7f6;
}

.detail-card {
	margin: -40rpx 30rpx 0;
	padding: 32rpx;
	background: #fff;
	border-radius: 24rpx;
	position: relative;
	z-index: 2;
	box-shadow: 0 8rpx 24rpx rgba(24, 175, 56, 0.08);
}

.detail-title {
	font-size: 36rpx;
	font-weight: 700;
	color: #1a1a1a;
	line-height: 1.5;
}

.detail-meta {
	margin-top: 16rpx;
	font-size: 24rpx;
	color: #8a938c;

	.dot {
		margin: 0 10rpx;
	}
}

.detail-desc {
	margin-top: 20rpx;
	font-size: 28rpx;
	color: #555;
	line-height: 1.7;
}

.rich-section {
	margin: 0 30rpx 20rpx;
	padding: 28rpx;
	background: #fff;
	border-radius: 24rpx;
	box-shadow: 0 8rpx 24rpx rgba(24, 175, 56, 0.06);
}

.detail-rich {
	font-size: 28rpx;
	color: #444;
	line-height: 1.75;
	word-break: break-word;
}

.photo-section {
	padding: 30rpx;
}

.section-title {
	margin-bottom: 20rpx;
	font-size: 30rpx;
	font-weight: 600;
	color: #222;
}

.photo-grid {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
}

.photo-item {
	width: 48.5%;
	margin-bottom: 20rpx;
}

.loading-state {
	padding: 120rpx 0;
	display: flex;
	justify-content: center;
}
</style>
