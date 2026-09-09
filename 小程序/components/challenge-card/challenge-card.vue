<template>
	<view class="challenge-card glass-card" @click="handleClick">
		<!-- 背景渐变装饰 -->
		<view class="challenge-bg" :class="`bg-${challenge.type || 'green'}`"></view>
		
		<!-- 内容区域 -->
		<view class="challenge-content">
			<!-- 头部信息 -->
			<view class="challenge-header">
				<view class="challenge-badge">
					<text class="badge-icon">{{challenge.icon || '🎯'}}</text>
					<text class="badge-text">挑战</text>
				</view>
				<view class="challenge-duration">
					<text class="duration-text">{{challenge.days || 7}}天</text>
				</view>
			</view>
			
			<!-- 标题区域 -->
			<view class="challenge-title-area">
				<text class="challenge-title u-line-1">{{challenge.title}}</text>
				<text class="challenge-subtitle u-line-2">{{challenge.subtitle}}</text>
			</view>
			
			<!-- 进度区域 -->
			<view class="progress-area" v-if="challenge.showProgress !== false">
				<view class="progress-header">
					<text class="progress-label">挑战进度</text>
					<text class="progress-value">{{challenge.progress || 0}}/{{challenge.days || 7}}天</text>
				</view>
				<view class="progress-bar">
					<view class="progress-fill" :style="{width: progressPercent + '%'}"></view>
				</view>
			</view>
			
			<!-- 奖励区域 -->
			<view class="reward-area">
				<view class="reward-item">
					<text class="reward-icon">🏆</text>
					<text class="reward-text">+{{challenge.points || 0}}积分</text>
				</view>
				<view class="reward-item" v-if="challenge.badge">
					<text class="reward-icon">🎖️</text>
					<text class="reward-text">{{challenge.badge}}</text>
				</view>
			</view>
			
			<!-- 底部信息 -->
			<view class="challenge-footer">
				<view class="participants">
					<text class="participants-text">{{challenge.participants || 0}}人参与中</text>
				</view>
				<view class="challenge-action">
					<text class="action-text">{{challenge.isJoined ? '继续挑战' : '加入挑战'}}</text>
					<text class="action-arrow">→</text>
				</view>
			</view>
		</view>
		
		<!-- 装饰元素 -->
		<view class="decoration-circle circle-1"></view>
		<view class="decoration-circle circle-2"></view>
	</view>
</template>

<script>
	export default {
		name: 'ChallengeCard',
		props: {
			// 挑战数据
			challenge: {
				type: Object,
				default: () => ({
					title: '7天垃圾分类挑战',
					subtitle: '每天30秒，养成分类好习惯',
					days: 7,
					progress: 0,
					points: 100,
					badge: '分类达人',
					participants: 0,
					type: 'green',
					icon: '🎯',
					isJoined: false,
					showProgress: true
				})
			}
		},
		computed: {
			// 进度百分比
			progressPercent() {
				const total = this.challenge.days || 7;
				const current = this.challenge.progress || 0;
				return Math.min((current / total) * 100, 100);
			}
		},
		methods: {
			handleClick() {
				this.$emit('click', this.challenge);
			}
		}
	};
</script>

<style lang="scss" scoped>
	.challenge-card {
		position: relative;
		border-radius: 24rpx;
		overflow: hidden;
		margin-bottom: 24rpx;
		background: rgba(255, 255, 255, 0.7);
		backdrop-filter: blur(20rpx);
		-webkit-backdrop-filter: blur(20rpx);
		box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.06);
	}
	
	.challenge-bg {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		height: 200rpx;
		opacity: 0.15;
		border-radius: 24rpx 24rpx 0 0;
		
		&.bg-green {
			background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
		}
		
		&.bg-blue {
			background: linear-gradient(135deg, #007AFF 0%, #5856D6 100%);
		}
		
		&.bg-orange {
			background: linear-gradient(135deg, #FF9500 0%, #FF6B00 100%);
		}
		
		&.bg-purple {
			background: linear-gradient(135deg, #AF52DE 0%, #BF5AF2 100%);
		}
	}
	
	.challenge-content {
		position: relative;
		z-index: 2;
		padding: 30rpx;
	}
	
	.challenge-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20rpx;
	}
	
	.challenge-badge {
		display: flex;
		align-items: center;
		background: rgba(52, 199, 89, 0.1);
		padding: 8rpx 16rpx;
		border-radius: 20rpx;
		
		.badge-icon {
			font-size: 24rpx;
			margin-right: 6rpx;
		}
		
		.badge-text {
			font-size: 22rpx;
			color: #34C759;
			font-weight: 500;
		}
	}
	
	.challenge-duration {
		background: rgba(0, 0, 0, 0.05);
		padding: 8rpx 20rpx;
		border-radius: 20rpx;
		
		.duration-text {
			font-size: 24rpx;
			color: #333;
			font-weight: 600;
		}
	}
	
	.challenge-title-area {
		margin-bottom: 24rpx;
		
		.challenge-title {
			font-size: 34rpx;
			font-weight: 600;
			color: #1D1D1F;
			margin-bottom: 8rpx;
			line-height: 1.4;
		}
		
		.challenge-subtitle {
			font-size: 26rpx;
			color: #86868B;
			line-height: 1.5;
		}
	}
	
	.progress-area {
		margin-bottom: 24rpx;
		
		.progress-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 12rpx;
			
			.progress-label {
				font-size: 24rpx;
				color: #86868B;
			}
			
			.progress-value {
				font-size: 24rpx;
				color: #34C759;
				font-weight: 600;
			}
		}
		
		.progress-bar {
			height: 12rpx;
			background: rgba(0, 0, 0, 0.06);
			border-radius: 6rpx;
			overflow: hidden;
			
			.progress-fill {
				height: 100%;
				background: linear-gradient(90deg, #34C759 0%, #30D158 100%);
				border-radius: 6rpx;
				transition: width 0.5s ease;
			}
		}
	}
	
	.reward-area {
		display: flex;
		gap: 24rpx;
		margin-bottom: 24rpx;
		
		.reward-item {
			display: flex;
			align-items: center;
			background: rgba(255, 149, 0, 0.08);
			padding: 10rpx 18rpx;
			border-radius: 16rpx;
			
			.reward-icon {
				font-size: 24rpx;
				margin-right: 8rpx;
			}
			
			.reward-text {
				font-size: 24rpx;
				color: #FF9500;
				font-weight: 500;
			}
		}
	}
	
	.challenge-footer {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-top: 20rpx;
		border-top: 1rpx solid rgba(0, 0, 0, 0.05);
		
		.participants-text {
			font-size: 24rpx;
			color: #86868B;
		}
		
		.challenge-action {
			display: flex;
			align-items: center;
			
			.action-text {
				font-size: 26rpx;
				color: #34C759;
				font-weight: 500;
				margin-right: 8rpx;
			}
			
			.action-arrow {
				font-size: 26rpx;
				color: #34C759;
			}
		}
	}
	
	.decoration-circle {
		position: absolute;
		border-radius: 50%;
		background: rgba(52, 199, 89, 0.05);
		
		&.circle-1 {
			width: 120rpx;
			height: 120rpx;
			top: -40rpx;
			right: -20rpx;
		}
		
		&.circle-2 {
			width: 80rpx;
			height: 80rpx;
			bottom: 60rpx;
			right: 40rpx;
		}
	}
</style>
