<template>
	<view class="task-card glass-task-card" @click="handleClick">
		<view class="task-card-bg"></view>
		<view class="task-badge" v-if="task.isChallenge">
			<text class="badge-text">挑战</text>
		</view>
		<view class="task-content">
			<view class="task-icon-area">
				<text class="task-emoji">{{task.icon || '🌍'}}</text>
			</view>
			<view class="task-main">
				<text class="task-title">{{task.title}}</text>
				<text class="task-subtitle">{{task.subtitle}}</text>
				<!-- 进度条 -->
				<view class="progress-section" v-if="task.showProgress">
					<view class="progress-header">
						<text class="streak">🔥 连续{{task.streak}}天</text>
						<text class="goal" v-if="task.daysToGoal > 0">还差{{task.daysToGoal}}天</text>
						<text class="goal achieved" v-else>目标达成!</text>
					</view>
					<view class="progress-bar">
						<view class="progress-fill" :style="{width: task.progress + '%'}"></view>
					</view>
				</view>
			</view>
			<view class="task-reward">
				<text class="points">+{{task.points}}</text>
				<text points-label">积分</text>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		name: 'TaskCard',
		props: {
			task: {
				type: Object,
				default: () => ({
					title: '今日环保任务',
					subtitle: '完成每日环保行动',
					points: 20,
					streak: 0,
					daysToGoal: 7,
					progress: 0,
					icon: '🌍',
					showProgress: true,
					isChallenge: false
				})
			}
		},
		methods: {
			handleClick() {
				this.$emit('click', this.task)
			}
		}
	}
</script>

<style lang="scss" scoped>
	$primary-green: #34C759;
	
	.task-card {
		position: relative;
		border-radius: 28rpx;
		overflow: hidden;
		box-shadow: 0 8rpx 32rpx rgba(52, 199, 89, 0.12);
		
		&:active {
			transform: scale(0.98);
		}
		
		.task-card-bg {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: linear-gradient(135deg, rgba(52, 199, 89, 0.95) 0%, rgba(48, 179, 80, 0.9) 100%);
			backdrop-filter: blur(20rpx);
			z-index: 0;
		}
		
		.task-badge {
			position: absolute;
			top: 20rpx;
			right: 20rpx;
			z-index: 2;
			background: rgba(255, 255, 255, 0.25);
			padding: 4rpx 14rpx;
			border-radius: 10rpx;
			
			.badge-text {
				font-size: 20rpx;
				color: #FFFFFF;
				font-weight: 500;
			}
		}
		
		.task-content {
			position: relative;
			z-index: 1;
			display: flex;
			align-items: center;
			gap: 20rpx;
			padding: 32rpx;
			
			.task-icon-area {
				width: 80rpx;
				height: 80rpx;
				background: rgba(255, 255, 255, 0.25);
				border-radius: 20rpx;
				display: flex;
				align-items: center;
				justify-content: center;
				flex-shrink: 0;
				
				.task-emoji {
					font-size: 40rpx;
				}
			}
			
			.task-main {
				flex: 1;
				
				.task-title {
					font-size: 30rpx;
					font-weight: 600;
					color: #FFFFFF;
					display: block;
				}
				
				.task-subtitle {
					font-size: 22rpx;
					color: rgba(255, 255, 255, 0.8);
					margin-top: 4rpx;
					display: block;
				}
				
				.progress-section {
					margin-top: 16rpx;
					padding-top: 12rpx;
					border-top: 1rpx solid rgba(255, 255, 255, 0.2);
					
					.progress-header {
						display: flex;
						justify-content: space-between;
						margin-bottom: 8rpx;
						
						.streak {
							font-size: 24rpx;
							color: #FFFFFF;
							font-weight: 500;
						}
						
						.goal {
							font-size: 22rpx;
							color: rgba(255, 255, 255, 0.8);
							
							&.achieved {
								color: #FFEB3B;
							}
						}
					}
					
					.progress-bar {
						height: 8rpx;
						background: rgba(255, 255, 255, 0.3);
						border-radius: 4rpx;
						overflow: hidden;
						
						.progress-fill {
							height: 100%;
							background: #FFFFFF;
							border-radius: 4rpx;
							transition: width 0.3s ease;
						}
					}
				}
			}
			
			.task-reward {
				display: flex;
				flex-direction: column;
				align-items: center;
				background: rgba(255, 255, 255, 0.2);
				padding: 12rpx 20rpx;
				border-radius: 16rpx;
				flex-shrink: 0;
				
				.points {
					font-size: 36rpx;
					font-weight: 700;
					color: #FFFFFF;
				}
				
				.points-label {
					font-size: 20rpx;
					color: rgba(255, 255, 255, 0.8);
				}
			}
		}
	}
</style>
