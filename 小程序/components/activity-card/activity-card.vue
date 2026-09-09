<template>
	<view class="activity-card glass-card" @click="handleClick">
		<view class="activity-image-wrap">
			<u-image 
				:src="item.thumb || defaultImage" 
				height="320" 
				mode="aspectFill" 
				border-radius="20 20 0 0"
			></u-image>
			<!-- 参与人数浮层 -->
			<view class="activity-overlay" v-if="item.active_count">
				<view class="avatar-stack">
					<image 
						:src="img" 
						class="stack-avatar" 
						mode="aspectFill" 
						v-for="(img,i) in (item.avatar_array || []).slice(0,3)" 
						:key="i"
						:style="{left: i * 16 + 'rpx'}"
					></image>
				</view>
				<text class="participants-count">{{item.active_count}}人参与</text>
			</view>
			<!-- 积分徽章 -->
			<view class="point-badge" v-if="item.send_point">
				<text class="point-text">+{{item.send_point}}</text>
			</view>
			<!-- 挑战/打卡标签 -->
			<view class="challenge-badge" v-if="item.is_clock == 1">
				<text class="challenge-text">打卡挑战</text>
			</view>
		</view>
		<view class="activity-info">
			<text class="activity-name u-line-1">{{item.name}}</text>
			<view class="activity-meta">
				<view class="meta-item">
					<u-icon name="clock" size="22"></u-icon>
					<text class="meta-text">{{formatDate(item.date)}}</text>
				</view>
				<view class="meta-item" v-if="item.address && item.address.trim()">
					<u-icon name="map" size="22"></u-icon>
					<text class="meta-text u-line-1">{{item.address}}</text>
				</view>
			</view>
			<view class="activity-footer">
				<view v-if="item.badge_image" class="badge-hint">
					<image :src="item.badge_image" class="badge-icon" mode="aspectFill"></image>
					<text class="badge-tip">完成可获得徽章</text>
				</view>
				<view class="participants-avatar" v-if="item.avatar_array && item.avatar_array.length > 3">
					<text class="more-count">+{{item.avatar_array.length}}</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		name: 'ActivityCard',
		props: {
			item: {
				type: Object,
				default: () => ({})
			},
			defaultImage: {
				type: String,
				default: ''
			}
		},
		methods: {
			handleClick() {
				this.$emit('click', this.item)
			},
			formatDate(timestamp) {
				if (!timestamp) return ''
				const date = new Date(timestamp * 1000)
				const year = date.getFullYear().toString().slice(-2)
				const month = (date.getMonth() + 1).toString().padStart(2, '0')
				const day = date.getDate().toString().padStart(2, '0')
				return `${year}-${month}-${day}`
			}
		}
	}
</script>

<style lang="scss" scoped>
	// 颜色变量
	$primary-green: #34C759;
	$text-primary: #1C1C1E;
	$text-secondary: #8E8E93;
	$card-bg: rgba(255, 255, 255, 0.72);
	
	.activity-card {
		border-radius: 24rpx;
		overflow: hidden;
		background: $card-bg;
		backdrop-filter: blur(20rpx);
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
		transition: transform 0.2s;
		
		&:active {
			transform: scale(0.98);
		}
		
		.activity-image-wrap {
			position: relative;
			
			.activity-overlay {
				position: absolute;
				left: 24rpx;
				bottom: 24rpx;
				display: flex;
				align-items: center;
				gap: 12rpx;
				background: rgba(0, 0, 0, 0.4);
				backdrop-filter: blur(8rpx);
				padding: 8rpx 16rpx 8rpx 8rpx;
				border-radius: 20rpx;
				
				.avatar-stack {
					position: relative;
					width: 60rpx;
					height: 36rpx;
					
					.stack-avatar {
						position: absolute;
						width: 36rpx;
						height: 36rpx;
						border-radius: 50%;
						border: 2rpx solid #FFFFFF;
					}
				}
				
				.participants-count {
					font-size: 22rpx;
					color: #FFFFFF;
				}
			}
			
			.point-badge {
				position: absolute;
				top: 20rpx;
				right: 20rpx;
				background: linear-gradient(135deg, #FF9500 0%, #FF6B00 100%);
				padding: 6rpx 16rpx;
				border-radius: 12rpx;
				box-shadow: 0 2rpx 8rpx rgba(255, 149, 0, 0.4);
				
				.point-text {
					font-size: 22rpx;
					font-weight: 600;
					color: #FFFFFF;
				}
			}
			
			.challenge-badge {
				position: absolute;
				top: 20rpx;
				left: 20rpx;
				background: rgba(52, 199, 89, 0.9);
				padding: 6rpx 16rpx;
				border-radius: 12rpx;
				
				.challenge-text {
					font-size: 20rpx;
					color: #FFFFFF;
					font-weight: 500;
				}
			}
		}
		
		.activity-info {
			padding: 24rpx;
			
			.activity-name {
				font-size: 30rpx;
				font-weight: 600;
				color: $text-primary;
				display: block;
			}
			
			.activity-meta {
				display: flex;
				gap: 24rpx;
				margin-top: 12rpx;
				
				.meta-item {
					display: flex;
					align-items: center;
					gap: 6rpx;
					
					.meta-text {
						font-size: 22rpx;
						color: $text-secondary;
					}
				}
			}
			
			.activity-footer {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-top: 16rpx;
				
				.badge-hint {
					display: flex;
					align-items: center;
					gap: 8rpx;
					
					.badge-icon {
						width: 36rpx;
						height: 36rpx;
						border-radius: 50%;
					}
					
					.badge-tip {
						font-size: 20rpx;
						color: $text-secondary;
					}
				}
				
				.participants-avatar {
					.more-count {
						font-size: 22rpx;
						color: $text-secondary;
					}
				}
			}
		}
	}
</style>
