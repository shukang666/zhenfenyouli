<template>
	<view class="detail-page">
		<view v-if="detail && detail.id">
			<view class="hero-cover" v-if="detail.cover">
				<u-image width="100%" height="420" :src="detail.cover" mode="aspectFill"></u-image>
				<view class="hero-cover__shade"></view>
			</view>

			<view class="detail-card">
				<view class="detail-title">{{ detail.title }}</view>
				<view class="detail-meta">
					<text>{{ detail.event_date_text }}</text>
					<text class="dot">·</text>
					<text>{{ detail.view_count }}次浏览</text>
					<text v-if="galleryPhotos.length">
						<text class="dot">·</text>
						<text>{{ galleryPhotos.length }}张照片</text>
					</text>
				</view>
				<view class="detail-desc" v-if="detail.description && !hasIntro">{{ detail.description }}</view>
			</view>

			<!-- 活动介绍：仅文字，图片统一在下方相册 -->
			<view class="block-card" v-if="hasIntro">
				<view class="block-card__head">
					<text class="block-card__title">活动介绍</text>
				</view>
				<view class="detail-rich">
					<u-parse :html="introHtml"></u-parse>
				</view>
			</view>

			<!-- 现场相册：可预览，不与正文重复 -->
			<view class="block-card block-card--gallery" v-if="galleryPhotos.length">
				<view class="block-card__head">
					<text class="block-card__title">现场相册</text>
					<text class="block-card__hint">点击查看大图</text>
				</view>
				<view class="photo-grid">
					<view
						class="photo-item"
						v-for="(img, index) in galleryPhotos"
						:key="index"
						hover-class="photo-item--press"
						@click="previewImage(index)"
					>
						<u-image width="100%" height="220" :src="img" mode="aspectFill" border-radius="16"></u-image>
						<view class="photo-item__idx" v-if="galleryPhotos.length > 1">{{ index + 1 }}/{{ galleryPhotos.length }}</view>
					</view>
				</view>
			</view>

			<view class="page-foot"></view>
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
	computed: {
		galleryPhotos() {
			const list = (this.detail && this.detail.photos) || [];
			return Array.isArray(list) ? list : [];
		},
		/** 去掉富文本里的图片，避免与下方相册重复 */
		introHtml() {
			const html = (this.detail && this.detail.content) || '';
			if (!html) return '';
			return html
				.replace(/<img\b[^>]*>/gi, '')
				.replace(/<p>\s*(&nbsp;|\u00a0|\s)*<\/p>/gi, '')
				.trim();
		},
		hasIntro() {
			const plain = this.introHtml.replace(/<[^>]+>/g, '').replace(/\s+/g, '').trim();
			return plain.length > 0;
		},
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
			if (!this.galleryPhotos.length) return;
			uni.previewImage({
				current: index,
				urls: this.galleryPhotos
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

.hero-cover {
	position: relative;
}

.hero-cover__shade {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 0;
	height: 120rpx;
	background: linear-gradient(180deg, transparent 0%, rgba(245, 247, 246, 0.95) 100%);
	pointer-events: none;
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

.block-card {
	margin: 20rpx 30rpx 0;
	padding: 28rpx;
	background: #fff;
	border-radius: 24rpx;
	box-shadow: 0 8rpx 24rpx rgba(24, 175, 56, 0.06);
}

.block-card--gallery {
	padding-bottom: 12rpx;
}

.block-card__head {
	display: flex;
	align-items: baseline;
	justify-content: space-between;
	margin-bottom: 20rpx;
}

.block-card__title {
	font-size: 30rpx;
	font-weight: 600;
	color: #1c2b1e;
}

.block-card__hint {
	font-size: 22rpx;
	color: #8a938c;
}

.detail-rich {
	font-size: 28rpx;
	color: #444;
	line-height: 1.75;
	word-break: break-word;
}

.photo-grid {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
}

.photo-item {
	position: relative;
	width: 48.5%;
	margin-bottom: 20rpx;
	border-radius: 16rpx;
	overflow: hidden;
	transition: transform 0.15s ease;
}

.photo-item--press {
	transform: scale(0.98);
	opacity: 0.92;
}

.photo-item__idx {
	position: absolute;
	right: 12rpx;
	bottom: 12rpx;
	padding: 4rpx 12rpx;
	font-size: 20rpx;
	color: #fff;
	background: rgba(0, 0, 0, 0.45);
	border-radius: 999rpx;
}

.page-foot {
	height: calc(40rpx + env(safe-area-inset-bottom));
}

.loading-state {
	padding: 120rpx 0;
	display: flex;
	justify-content: center;
}
</style>
