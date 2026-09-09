<template>
	<view class="growth-card glass-card">
		<view class="growth-bg"></view>
		<view class="growth-content">
			<view class="growth-header">
				<text class="growth-title">{{title}}</text>
				<text class="growth-more" v-if="showMore" @click="handleMore">查看详情 ›</text>
			</view>
			
			<!-- 等级进度 -->
			<view class="level-section">
				<view class="level-progress">
					<view 
						class="level-item" 
						:class="{current: growth.currentLevel === index}"
						v-for="(level, index) in levels" 
						:key="index"
					>
						<view class="level-icon-wrap" :class="{current: growth.currentLevel === index}">
							<text class="level-emoji">{{level.emoji}}</text>
						</view>
						<text class="level-name">{{level.name}}</text>
					</view>
				</view>
				<!-- 进度线 -->
				<view class="progress-line">
					<view class="progress-done" :style="{width: progressPercent + '%'}"></view>
				</view>
			</view>
			
			<!-- 数据统计 -->
			<view class="stats-row">
				<view class="stat-item" v-for="(stat, index) in stats" :key="index">
					<text class="stat-value">{{stat.value}}</text>
					<text class="stat-label">{{stat.label}}</text>
				</view>
			</view>
			
			<!-- 徽章展示 -->
			<view class="badge-section" v-if="badges && badges.length > 0">
				<text class="badge-title">我的徽章</text>
				<scroll-view class="badge-scroll" scroll-x>
					<view class="badge-list">
						<view class="badge-item" v-for="(badge, index) in badges" :key="index">
							<image :src="badge.image" class="badge-img" mode="aspectFill" v-if="badge.image"></image>
							<view class="badge-placeholder" v-else>
								<text>🏅</text>
							</view>
							<text class="badge-name">{{badge.name}}</text>
						</view>
					</view>
				</scroll-view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		name: 'GrowthCard',
		props: {
			title: {
				type: String,
				default: '我的成长'
			},
			showMore: {
				type: Boolean,
				default: true
			},
			growth: {
				type: Object,
				default: () => ({
					currentLevel: 0,
					points: 0,
					streak: 0,
					activities: 0,
					badges: 0
				})
			},
			badges: {
				type: Array,
				default: () => []
			}
		},
		data() {
			return {
				levels: [
					{ emoji: '🌱', name: '环保新人', minPoints: 0 },
					{ emoji: '🌿', name: '绿色达人', minPoints: 300 },
					{ emoji: '🌳', name: '社区先锋', minPoints: 1000 }
				]
			}
		},
		computed: {
			stats() {
				return [
					{ value: this.growth.points, label: '积分' },
					{ value: this.growth.streak, label: '连续打卡' },
					{ value: this.growth.activities, label: '完成活动' },
					{ value: this.growth.badges || (this.badges ? this.badges.length : 0), label: '徽章' }
				]
			},
			progressPercent() {
				const points = this.growth.points || 0
				if (points >= 1000) return 100
				if (points >= 300) return 50 + (points - 300) / 700 * 50
				return points / 300 * 50
			}
		},
		methods: {
			handleMore() {
				this.$emit('more')
			}
		}
	}
</script>

<style lang="scss" scoped>
	$primary-green: #34C759;
	$text-primary: #1C1C1E;
	$text-secondary: #8E8E93;
	$card-bg: rgba(255, 255, 255, 0.72);
	
	.growth-card {
		position: relative;
		border-radius: 28rpx;
		overflow: hidden;
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
		
		.growth-bg {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: $card-bg;
			backdrop-filter: blur(20rpx);
			z-index: 0;
		}
		
		.growth-content {
			position: relative;
			z-index: 1;
			padding: 30rpx;
			
			.growth-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 24rpx;
				
				.growth-title {
					font-size: 30rpx;
					font-weight: 700;
					color: $text-primary;
				}
				
				.growth-more {
					font-size: 24rpx;
					color: $primary-green;
				}
			}
			
			.level-section {
				padding: 10rpx 0;
				
				.level-progress {
					display: flex;
					justify-content: space-around;
					align-items: center;
					
					.level-item {
						display: flex;
						flex-direction: column;
						align-items: center;
						gap: 8rpx;
						flex: 1;
						opacity: 0.4;
						transition: opacity 0.3s;
						
						&.current {
							opacity: 1;
						}
						
						.level-icon-wrap {
							width: 80rpx;
							height: 80rpx;
							border-radius: 50%;
							background: #F2F2F7;
							display: flex;
							align-items: center;
							justify-content: center;
							transition: all 0.3s;
							
							&.current {
								background: linear-gradient(135deg, rgba(52, 199, 89, 0.15) 0%, rgba(52, 199, 89, 0.08) 100%);
								box-shadow: 0 4rpx 12rpx rgba(52, 199, 89, 0.2);
							}
							
							.level-emoji {
								font-size: 40rpx;
							}
						}
						
						.level-name {
							font-size: 22rpx;
							color: $text-secondary;
							white-space: nowrap;
						}
					}
				}
				
				.progress-line {
					height: 6rpx;
					background: #E5E5EA;
					border-radius: 3rpx;
					margin-top: 20rpx;
					overflow: hidden;
					
					.progress-done {
						height: 100%;
						background: linear-gradient(90deg, $primary-green 0%, #30B350 100%);
						border-radius: 3rpx;
						transition: width 0.5s ease;
					}
				}
			}
			
			.stats-row {
				display: flex;
				justify-content: space-around;
				align-items: center;
				padding: 20rpx 0 10rpx;
				border-top: 1rpx solid rgba(0, 0, 0, 0.06);
				margin-top: 20rpx;
				
				.stat-item {
					display: flex;
					flex-direction: column;
					align-items: center;
					flex: 1;
					
					.stat-value {
						font-size: 32rpx;
						font-weight: 700;
						color: $text-primary;
					}
					
					.stat-label {
						font-size: 22rpx;
						color: $text-secondary;
						margin-top: 4rpx;
					}
				}
			}
			
			.badge-section {
				margin-top: 20rpx;
				padding-top: 20rpx;
				border-top: 1rpx solid rgba(0, 0, 0, 0.06);
				
				.badge-title {
					font-size: 24rpx;
					color: $text-secondary;
					display: block;
					margin-bottom: 16rpx;
				}
				
				.badge-scroll {
					white-space: nowrap;
					
					.badge-list {
						display: inline-flex;
						gap: 20rpx;
					}
					
					.badge-item {
						display: flex;
						flex-direction: column;
						align-items: center;
						gap: 8rpx;
						
						.badge-img {
							width: 72rpx;
							height: 72rpx;
							border-radius: 50%;
						}
						
						.badge-placeholder {
							width: 72rpx;
							height: 72rpx;
							border-radius: 50%;
							background: #F2F2F7;
							display: flex;
							align-items: center;
							justify-content: center;
							font-size: 32rpx;
						}
						
						.badge-name {
							font-size: 20rpx;
							color: $text-secondary;
							max-width: 80rpx;
							overflow: hidden;
							text-overflow: ellipsis;
							white-space: nowrap;
						}
					}
				}
			}
		}
	}
</style>
