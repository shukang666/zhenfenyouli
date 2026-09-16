<template>
	<view class="qxl-kd-page">
		<!-- 自定义导航栏 -->
		<view class="kd-navbar">
			<view class="kd-nav-back" @click="navigateBack">
				<u-icon name="arrow-left" size="44" color="#fff"></u-icon>
			</view>
			<text class="kd-nav-title">学习详情</text>
			<view class="kd-nav-placeholder"></view>
		</view>

		<!-- 加载中 -->
		<view class="kd-loading" v-if="loading">
			<u-loading mode="circle" color="#18AF38" size="60"></u-loading>
		</view>

		<!-- 内容区 -->
		<view class="kd-content" v-else-if="detail && detail.id">
			<view class="kd-title">{{detail.title}}</view>
			<view class="kd-meta">
				<text>{{detail.create_time_friendly}}</text>
				<text class="kd-dot">·</text>
				<text>{{detail.view_count}} 次浏览</text>
			</view>
			<view class="kd-cover" v-if="detail.image">
				<u-image width="100%" :src="detail.image" mode="widthFix"></u-image>
			</view>
			<view class="kd-body">
				<rich-text :nodes="detail.content"></rich-text>
			</view>
		</view>

		<!-- 空态 -->
		<view class="kd-empty" v-else>
			<u-icon name="file-text" size="80" color="#ddd"></u-icon>
			<text class="kd-empty-text">内容不存在或已删除</text>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				detail: null,
				loading: false,
			};
		},
		onLoad(options) {
			if (options.id) {
				this.loadDetail(options.id);
			}
		},
		methods: {
			loadDetail(id) {
				this.loading = true;
				this.$u.api.getQxlKnowledgeDetail({ id }).then(res => {
					this.loading = false;
					if (res.code == 1) {
						this.detail = res.data;
					}
				}).catch(() => {
					this.loading = false;
				});
			},
			navigateBack() {
				uni.navigateBack();
			},
		}
	}
</script>

<style lang="scss" scoped>
.qxl-kd-page {
	min-height: 100vh;
	background: #fff;
}

.kd-navbar {
	position: sticky;
	top: 0;
	z-index: 30;
	display: flex;
	align-items: center;
	justify-content: space-between;
	background: linear-gradient(135deg, #1877F2 0%, #34C759 100%);
	padding: 0 16rpx;
	height: 88rpx;
	padding-top: env(safe-area-inset-top);
}
.kd-nav-back { width: 80rpx; display: flex; align-items: center; justify-content: center; }
.kd-nav-title {
	font-size: 34rpx; font-weight: 700; color: #fff;
	flex: 1; text-align: center;
}
.kd-nav-placeholder { width: 80rpx; }

.kd-loading {
	padding: 100rpx 0;
	text-align: center;
}

.kd-content {
	padding: 30rpx;
}
.kd-title {
	font-size: 36rpx;
	font-weight: 800;
	color: #050505;
	line-height: 1.5;
	margin-bottom: 16rpx;
}
.kd-meta {
	display: flex;
	align-items: center;
	gap: 8rpx;
	font-size: 24rpx;
	color: #8A8A8A;
	margin-bottom: 24rpx;
}
.kd-dot {
	margin: 0 4rpx;
}
.kd-cover {
	margin-bottom: 24rpx;
	border-radius: 12rpx;
	overflow: hidden;
}
.kd-body {
	font-size: 30rpx;
	color: #333;
	line-height: 1.8;
}

.kd-empty {
	padding: 100rpx 0;
	text-align: center;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16rpx;
}
.kd-empty-text {
	font-size: 26rpx;
	color: #8A8A8A;
}
</style>
