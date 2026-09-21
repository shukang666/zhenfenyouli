<template>
	<view class="detail-page">
		<u-navbar title-color="#fff" title="知识详情" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552 green-bg"></view>

		<view class="detail-container" v-if="detail && detail.id">
			<!-- 封面 -->
			<view class="detail-cover" v-if="detail.image">
				<u-image width="100%" height="360" :src="'https://admin.circular-economy.cn' + detail.image" mode="aspectFill" border-radius="0"></u-image>
			</view>

			<!-- 内容 -->
			<view class="detail-content">
				<view class="detail-header">
					<view class="detail-title u-font-bold">{{detail.title}}</view>
					<view class="detail-meta u-font-24 u-type-info u-m-t-16">
						<text v-if="detail.category" class="detail-category">{{detail.category}}</text>
						<text class="u-m-l-20">{{detail.create_time_friendly}}</text>
						<text class="u-m-l-20">{{detail.view_count}}次浏览</text>
					</view>
				</view>

				<u-line color="#eee"></u-line>

				<!-- 富文本内容 -->
				<view class="rich-content u-m-t-30">
					<rich-text :nodes="detail.content"></rich-text>
				</view>
			</view>
		</view>

		<!-- 加载中 -->
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
			customBack() {
				uni.navigateBack({
					fail() {
						uni.switchTab({ url: '/pages/index/index' });
					}
				});
			},
			getDetail() {
				this.loading = true;
				this.$u.api.getKnowledgeDetail({ id: this.id }).then(res => {
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
			}
		}
	}
</script>

<style lang="scss" scoped>
.detail-page {
	min-height: 100vh;
	background: #f8f8f8;
}

.green-bg {
	z-index: -1;
	top: 0; left: 0; right: 0;
	background: linear-gradient(to bottom, #2fb74c, #EFF9F1);
}

.detail-container {
	position: relative;
}

.detail-cover {
	width: 100%;
}

.detail-content {
	margin: -40rpx 30rpx 40rpx;
	padding: 40rpx 30rpx;
	background: #fff;
	border-radius: 24rpx;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);
}

.detail-title {
	font-size: 36rpx;
	color: #333;
	line-height: 1.5;
}

.detail-category {
	display: inline-block;
	background: #E8F7EB;
	color: #18AF38;
	padding: 4rpx 20rpx;
	border-radius: 8rpx;
	font-size: 24rpx;
}

.rich-content {
	font-size: 30rpx;
	color: #444;
	line-height: 1.8;
	white-space: pre-wrap;
	word-break: break-all;
}

.loading-state {
	text-align: center;
	padding: 200rpx 0;
}
</style>
