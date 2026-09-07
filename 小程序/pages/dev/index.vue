<template>
	<view class="dev-container">
		<!-- 开发者模式标识 -->
		<view class="dev-header">
			<view class="dev-title">🛠️ 开发者模式</view>
			<view class="dev-subtitle">测试专用 / 不影响主程序</view>
			<view class="dev-version">v1.0.0</view>
		</view>

		<!-- 九宫格功能入口 -->
		<view class="dev-grid">
			<view class="grid-row">
				<view class="grid-item" @click="goToActivity">
					<view class="grid-icon">
						<u-icon name="/static/tabbar/tab2.png" size="60"></u-icon>
					</view>
					<view class="grid-label">活动页面</view>
				</view>
				<view class="grid-item" @click="goToActivityDetail">
					<view class="grid-icon">
						<u-icon name="file-text" size="60" color="#FF843D"></u-icon>
					</view>
					<view class="grid-label">活动详情</view>
				</view>
				<view class="grid-item disabled">
					<view class="grid-icon">
						<u-icon name="more-circle" size="60" color="#ccc"></u-icon>
					</view>
					<view class="grid-label">敬请期待</view>
				</view>
			</view>
			<view class="grid-row">
				<view class="grid-item disabled">
					<view class="grid-icon">
						<u-icon name="more-circle" size="60" color="#ccc"></u-icon>
					</view>
					<view class="grid-label">敬请期待</view>
				</view>
				<view class="grid-item disabled">
					<view class="grid-icon">
						<u-icon name="more-circle" size="60" color="#ccc"></u-icon>
					</view>
					<view class="grid-label">敬请期待</view>
				</view>
				<view class="grid-item disabled">
					<view class="grid-icon">
						<u-icon name="more-circle" size="60" color="#ccc"></u-icon>
					</view>
					<view class="grid-label">敬请期待</view>
				</view>
			</view>
			<view class="grid-row">
				<view class="grid-item disabled">
					<view class="grid-icon">
						<u-icon name="more-circle" size="60" color="#ccc"></u-icon>
					</view>
					<view class="grid-label">敬请期待</view>
				</view>
				<view class="grid-item disabled">
					<view class="grid-icon">
						<u-icon name="more-circle" size="60" color="#ccc"></u-icon>
					</view>
					<view class="grid-label">敬请期待</view>
				</view>
				<view class="grid-item disabled">
					<view class="grid-icon">
						<u-icon name="more-circle" size="60" color="#ccc"></u-icon>
					</view>
					<view class="grid-label">敬请期待</view>
				</view>
			</view>
		</view>

		<!-- 退出按钮 -->
		<view class="dev-footer">
			<u-button type="error" @click="exitDevMode">退出开发者模式</u-button>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {}
		},
		methods: {
			// 跳转活动页面
			goToActivity() {
				uni.navigateTo({
					url: '/pages/dev/activity'
				})
			},
			// 跳转活动详情页
			goToActivityDetail() {
				uni.navigateTo({
					url: '/pages/dev/activity-detail'
				})
			},
			// 退出开发者模式
			exitDevMode() {
				uni.showModal({
					title: '提示',
					content: '确定要退出开发者模式吗？',
					success: function(res) {
						if (res.confirm) {
							// 返回我的页面
							uni.switchTab({
								url: '/pages/user/index'
							})
						}
					}
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.dev-container {
		min-height: 100vh;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		padding: 40rpx 30rpx;
	}

	.dev-header {
		text-align: center;
		padding: 60rpx 0 40rpx;

		.dev-title {
			font-size: 48rpx;
			font-weight: bold;
			color: #fff;
			margin-bottom: 20rpx;
		}

		.dev-subtitle {
			font-size: 26rpx;
			color: rgba(255, 255, 255, 0.8);
			margin-bottom: 10rpx;
		}

		.dev-version {
			font-size: 22rpx;
			color: rgba(255, 255, 255, 0.6);
		}
	}

	.dev-grid {
		background: #fff;
		border-radius: 20rpx;
		padding: 40rpx 30rpx;
		margin-bottom: 40rpx;

		.grid-row {
			display: flex;
			justify-content: space-between;
			margin-bottom: 30rpx;

			&:last-child {
				margin-bottom: 0;
			}
		}

		.grid-item {
			flex: 1;
			display: flex;
			flex-direction: column;
			align-items: center;
			padding: 30rpx 0;
			border-radius: 16rpx;
			transition: all 0.3s;

			&:active:not(.disabled) {
				transform: scale(0.95);
				background: rgba(0, 0, 0, 0.05);
			}

			&.disabled {
				opacity: 0.5;

				.grid-icon {
					background: #f5f5f5;
				}

				.grid-label {
					color: #ccc;
				}
			}

			.grid-icon {
				width: 100rpx;
				height: 100rpx;
				border-radius: 20rpx;
				display: flex;
				align-items: center;
				justify-content: center;
				background: #f8f9ff;
				margin-bottom: 15rpx;
			}

			.grid-label {
				font-size: 24rpx;
				color: #333;
				font-weight: 500;
			}
		}
	}

	.dev-footer {
		padding: 30rpx;
	}
</style>
