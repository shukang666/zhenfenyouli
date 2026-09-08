<template>
	<view class="skeleton-screen" v-show="show">
		<!-- 首页骨架屏 -->
		<view v-if="type === 'index'" class="skeleton-index">
			<!-- 顶部导航栏 -->
			<view class="skeleton-navbar"></view>
			<!-- Banner轮播图 -->
			<view class="skeleton-banner"></view>
			<!-- 走马灯通知栏 -->
			<view class="skeleton-notice"></view>
			<!-- 核心功能入口 -->
			<view class="skeleton-entry-grid">
				<view class="skeleton-entry-main"></view>
				<view class="skeleton-entry-side">
					<view class="skeleton-entry-mini"></view>
					<view class="skeleton-entry-mini"></view>
					<view class="skeleton-entry-wide"></view>
				</view>
			</view>
			<!-- 热门活动标题 -->
			<view class="skeleton-section-header"></view>
			<!-- 热门活动横向滚动卡片 -->
			<view class="skeleton-active-scroll">
				<view class="skeleton-active-card" v-for="i in 3" :key="i"></view>
			</view>
			<!-- 商品标题 -->
			<view class="skeleton-section-header"></view>
			<!-- 商品列表 -->
			<view class="skeleton-goods-grid">
				<view class="skeleton-goods-card" v-for="i in 4" :key="i"></view>
			</view>
		</view>

		<!-- 活动页骨架屏 -->
		<view v-else-if="type === 'low_carbon'" class="skeleton-low-carbon">
			<!-- 侧边标签栏 -->
			<view class="skeleton-side-bar">
				<view class="skeleton-tab-item" v-for="i in 4" :key="i"></view>
			</view>
			<!-- 右侧内容区：本月推荐展示海报，其他标签显示卡片列表 -->
			<view class="skeleton-content-area">
				<!-- 骨架占位：根据默认选中标签（本月推荐=海报，其他=卡片）展示对应骨架 -->
				<view class="skeleton-rec-banner"></view>
			</view>
		</view>

		<!-- 健康页骨架屏 -->
		<view v-else-if="type === 'health'" class="skeleton-health">
			<!-- 顶部导航 -->
			<view class="skeleton-navbar"></view>
			<!-- 健康诊断卡片 -->
			<view class="skeleton-health-card">
				<view class="skeleton-health-row">
					<view class="skeleton-health-item"></view>
					<view class="skeleton-health-item"></view>
				</view>
				<view class="skeleton-health-row">
					<view class="skeleton-health-item"></view>
					<view class="skeleton-health-item"></view>
				</view>
				<view class="skeleton-health-row">
					<view class="skeleton-health-item"></view>
					<view class="skeleton-health-item"></view>
				</view>
			</view>
			<!-- 健康生活社区 -->
			<view class="skeleton-health-card">
				<view class="skeleton-forum-item" v-for="i in 3" :key="i"></view>
			</view>
		</view>

		<!-- 积分权益页骨架屏 -->
		<view v-else-if="type === 'mall'" class="skeleton-mall">
			<!-- 顶部导航 -->
			<view class="skeleton-navbar"></view>
			<!-- 搜索框 -->
			<view class="skeleton-search"></view>
			<!-- 轮播图 -->
			<view class="skeleton-banner"></view>
			<!-- 优惠券 -->
			<view class="skeleton-coupon"></view>
			<!-- 分类标签 -->
			<view class="skeleton-cate-tags">
				<view class="skeleton-cate-tag" v-for="i in 5" :key="i"></view>
			</view>
			<!-- 商品列表 -->
			<view class="skeleton-goods-grid">
				<view class="skeleton-goods-card" v-for="i in 4" :key="i"></view>
			</view>
		</view>

		<!-- 我的页骨架屏 -->
		<view v-else-if="type === 'user'" class="skeleton-user">
			<!-- 用户信息区 -->
			<view class="skeleton-user-header">
				<view class="skeleton-avatar"></view>
				<view class="skeleton-user-info">
					<view class="skeleton-user-name"></view>
					<view class="skeleton-user-phone"></view>
					<view class="skeleton-user-point"></view>
				</view>
			</view>
			<!-- 环保达人进度 -->
			<view class="skeleton-progress-card"></view>
			<!-- 常用功能 -->
			<view class="skeleton-functions">
				<view class="skeleton-function-item" v-for="i in 8" :key="i"></view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'SkeletonScreen',
	props: {
		show: {
			type: Boolean,
			default: true
		},
		type: {
			type: String,
			default: 'index' // index, low_carbon, health, mall, user
		}
	}
}
</script>

<style lang="scss" scoped>
/* 骨架屏基础样式 */
.skeleton-screen {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: #f5f5f5;
	z-index: 9999;
	overflow: hidden;
}

/* 骨架屏闪烁动画 */
@keyframes skeleton-loading {
	0% {
		background-position: -200rpx 0;
	}
	100% {
		background-position: calc(200rpx + 100%) 0;
	}
}

.skeleton-base {
	background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
	background-size: 400rpx 100%;
	animation: skeleton-loading 1.5s ease-in-out infinite;
	border-radius: 8rpx;
}

/* 首页骨架屏 */
.skeleton-index {
	padding: 30rpx;
	
	.skeleton-navbar {
		@extend .skeleton-base;
		height: 88rpx;
		margin-bottom: 20rpx;
	}
	
	.skeleton-banner {
		@extend .skeleton-base;
		height: 340rpx;
		border-radius: 24rpx;
		margin-bottom: 20rpx;
	}
	
	.skeleton-notice {
		@extend .skeleton-base;
		height: 60rpx;
		margin-bottom: 30rpx;
	}
	
	.skeleton-entry-grid {
		display: flex;
		gap: 20rpx;
		margin-bottom: 30rpx;
		
		.skeleton-entry-main {
			@extend .skeleton-base;
			flex: 1.2;
			height: 360rpx;
			border-radius: 20rpx;
		}
		
		.skeleton-entry-side {
			flex: 1;
			display: flex;
			flex-direction: column;
			gap: 20rpx;
			
			.skeleton-entry-mini {
				@extend .skeleton-base;
				flex: 1;
				border-radius: 20rpx;
			}
			
			.skeleton-entry-wide {
				@extend .skeleton-base;
				height: 140rpx;
				border-radius: 20rpx;
			}
		}
	}
	
	.skeleton-section-header {
		@extend .skeleton-base;
		height: 60rpx;
		margin-bottom: 20rpx;
	}
	
	.skeleton-active-scroll {
		display: flex;
		gap: 20rpx;
		margin-bottom: 30rpx;
		
		.skeleton-active-card {
			@extend .skeleton-base;
			width: 540rpx;
			height: 400rpx;
			border-radius: 20rpx;
			flex-shrink: 0;
		}
	}
	
	.skeleton-goods-grid {
		display: flex;
		flex-wrap: wrap;
		gap: 20rpx;
		
		.skeleton-goods-card {
			@extend .skeleton-base;
			width: calc(50% - 10rpx);
			height: 400rpx;
			border-radius: 20rpx;
		}
	}
}

/* 活动页骨架屏 */
.skeleton-low-carbon {
	display: flex;
	height: 100%;
	
	.skeleton-side-bar {
		width: 160rpx;
		background-color: #f9f9f9;
		padding: 20rpx 0;
		
		.skeleton-tab-item {
			@extend .skeleton-base;
			height: 80rpx;
			margin: 20rpx 15rpx;
			border-radius: 10rpx;
		}
	}
	
	.skeleton-content-area {
		flex: 1;
		padding: 30rpx;
		
		/* 本月推荐海报骨架 */
		.skeleton-rec-banner {
			@extend .skeleton-base;
			width: 100%;
			height: 100%;
			min-height: 600rpx;
			border-radius: 20rpx;
		}
	}
}

/* 健康页骨架屏 */
.skeleton-health {
	padding: 30rpx;
	
	.skeleton-navbar {
		@extend .skeleton-base;
		height: 88rpx;
		margin-bottom: 20rpx;
	}
	
	.skeleton-health-card {
		@extend .skeleton-base;
		padding: 20rpx;
		border-radius: 20rpx;
		margin-bottom: 30rpx;
		
		.skeleton-health-row {
			display: flex;
			gap: 20rpx;
			margin-bottom: 20rpx;
			
			.skeleton-health-item {
				@extend .skeleton-base;
				flex: 1;
				height: 123rpx;
				border-radius: 10rpx;
			}
		}
		
		.skeleton-forum-item {
			@extend .skeleton-base;
			height: 200rpx;
			margin-bottom: 20rpx;
			border-radius: 10rpx;
		}
	}
}

/* 积分权益页骨架屏 */
.skeleton-mall {
	padding: 30rpx;
	
	.skeleton-navbar {
		@extend .skeleton-base;
		height: 88rpx;
		margin-bottom: 20rpx;
	}
	
	.skeleton-search {
		@extend .skeleton-base;
		height: 70rpx;
		border-radius: 35rpx;
		margin-bottom: 30rpx;
	}
	
	.skeleton-banner {
		@extend .skeleton-base;
		height: 350rpx;
		border-radius: 20rpx;
		margin-bottom: 30rpx;
	}
	
	.skeleton-coupon {
		@extend .skeleton-base;
		height: 210rpx;
		border-radius: 20rpx;
		margin-bottom: 30rpx;
	}
	
	.skeleton-cate-tags {
		display: flex;
		gap: 20rpx;
		margin-bottom: 30rpx;
		
		.skeleton-cate-tag {
			@extend .skeleton-base;
			width: 120rpx;
			height: 60rpx;
			border-radius: 30rpx;
		}
	}
	
	.skeleton-goods-grid {
		display: flex;
		flex-wrap: wrap;
		gap: 20rpx;
		
		.skeleton-goods-card {
			@extend .skeleton-base;
			width: calc(50% - 10rpx);
			height: 450rpx;
			border-radius: 20rpx;
		}
	}
}

/* 我的页骨架屏 */
.skeleton-user {
	padding: 30rpx;
	
	.skeleton-user-header {
		display: flex;
		align-items: center;
		padding: 150rpx 0 30rpx;
		
		.skeleton-avatar {
			@extend .skeleton-base;
			width: 130rpx;
			height: 130rpx;
			border-radius: 50%;
		}
		
		.skeleton-user-info {
			margin-left: 30rpx;
			flex: 1;
			
			.skeleton-user-name {
				@extend .skeleton-base;
				height: 40rpx;
				width: 200rpx;
				margin-bottom: 20rpx;
			}
			
			.skeleton-user-phone {
				@extend .skeleton-base;
				height: 30rpx;
				width: 250rpx;
				margin-bottom: 20rpx;
			}
			
			.skeleton-user-point {
				@extend .skeleton-base;
				height: 30rpx;
				width: 180rpx;
			}
		}
	}
	
	.skeleton-progress-card {
		@extend .skeleton-base;
		height: 200rpx;
		border-radius: 20rpx;
		margin-bottom: 30rpx;
	}
	
	.skeleton-functions {
		display: flex;
		flex-wrap: wrap;
		gap: 30rpx;
		padding: 30rpx;
		
		.skeleton-function-item {
			@extend .skeleton-base;
			width: calc(25% - 23rpx);
			height: 120rpx;
			border-radius: 10rpx;
		}
	}
}
</style>
